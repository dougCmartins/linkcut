# Requisitos: onboarding (cadastro aberto do barbeiro)

> **Por quê:** até aqui, um barbeiro só existia se fosse criado à mão (seeder). Agora qualquer barbeiro cria a conta sozinho e sai com a página de agendamentos no ar em poucos minutos.

## 1. Regras

### Entrada (`/`, `/cadastrar`, `/entrar`)
- As três rotas abrem **a mesma tela**: o formulário à esquerda e uma foto à direita (só em telas largas; o celular não baixa a foto).
- A `/` e a `/cadastrar` abrem em "Criar minha página grátis", com "Já tem conta? Entrar" embaixo.
- A `/entrar` é **"Encontrar minha página"**: o barbeiro digita o e-mail e recebe por e-mail o link de entrada da página dele. A resposta é a mesma para qualquer e-mail. Se o aparelho já entrou antes, aparece o atalho "Continuar em linkcut.app/<link>".
- Trocar de modo muda só o formulário e a foto, sem recarregar a página. O endereço acompanha o modo e o "Voltar" do navegador funciona.
- Barbeiro já conectado que abre uma dessas rotas vai direto para o `/painel`.

### Login do barbeiro (`/<link>/entrar`)
- O barbeiro entra **pela página da barbearia**, com o tema, a foto e o nome dela.
- O backend só aceita barbeiros daquela barbearia. Página errada, inexistente ou desativada dá **o mesmo erro** de senha errada (não revela quais e-mails existem).
- O aparelho guarda a última página: "Sair", sessão expirada e o app instalado voltam para ela.
- O rodapé da página pública ("feito com linkcut") leva a esse login.

### Cadastro
| ID | Regra |
|---|---|
| RF-O1 | **Passo 1, a conta:** nome, e-mail e senha (mínimo 8 caracteres). "Continuar" envia um código de 6 números para o e-mail. |
| RF-O2 | **Passo 2, o código:** confirma que o e-mail é do barbeiro. Vale 15 minutos e aceita 5 tentativas. Pode ser reenviado depois de 30 segundos. |
| RF-O3 | **Passo 3, a barbearia:** o nome e o link da página (`linkcut.app/<link>`). O link nasce do nome ("Barbearia do João" → `barbearia-do-joao`) e pode ser editado. |
| RF-O4 | O link é conferido enquanto o barbeiro digita: livre, em uso ou inválido. Quando está em uso, a tela sugere um livre (`navalha-2`). |
| RF-O5 | O link aceita de 3 a 40 letras minúsculas, números e hífens. **Links reservados** (páginas do app e nomes guardados) nunca podem ser usados: `admin`, `ajuda`, `api`, `app`, `assets`, `cadastrar`, `conta`, `entrar`, `icons`, `login`, `painel`, `privacidade`, `public`, `pulse`, `suporte`, `termos`, `www`. |
| RF-O6 | Ao concluir, a API confere o código e cria tudo de uma vez: a barbearia, o dono e as configurações padrão (com o nome da barbearia). O barbeiro **já entra logado**: o e-mail acabou de ser confirmado, então não pede o código de novo. |
| RF-O7 | Código errado: a tela volta para o código e diz quantas tentativas restam. Código vencido ou tentativas esgotadas: volta para o início, para pedir outro. |
| RF-O8 | **Sem descobrir contas:** pedir o código dá sempre a mesma resposta. Se o e-mail já tem conta, chega um e-mail "Você já tem uma conta no linkcut" com o botão para entrar, em vez do código. |

### Passo a passo (`/painel/boas-vindas`)
| ID | Regra |
|---|---|
| RF-O9 | Enquanto o passo a passo não termina, o painel sempre leva o barbeiro para ele. Depois de terminado, ele não aparece mais. |
| RF-O10 | **Onde te encontrar:** o WhatsApp da barbearia e o endereço (CEP preenche a rua; "Estou na barbearia" marca o ponto no mapa). Os dois são obrigatórios, porque sem endereço a agenda não recebe pedidos. Os erros aparecem só depois de o barbeiro tentar continuar. |
| RF-O11 | **Serviços e horários:** já vêm com um exemplo (Corte 45 min R$ 50, Barba 30 min R$ 40, seg–sex 9h–19h, sáb 9h–14h) para o barbeiro ajustar. |
| RF-O12 | **Página no ar:** mostra o link com "Copiar link" e "Ver minha página". "Ir para o painel" encerra o passo a passo. |
| RF-O13 | Cada passo salva na hora, pelas mesmas regras das Configurações. Tudo pode ser mudado depois em Configurações. |

### Login só com e-mail (SMS desligado)
| ID | Regra |
|---|---|
| RF-O14 | Não há verba para SMS. O SMS fica **desligado** (`SMS_ENABLED=false`): o login, o envio do código e a preferência do canal só oferecem o **e-mail**, mesmo para quem tem telefone. Uma preferência antiga por SMS é ignorada. |
| RF-O15 | Em Configurações → Segurança, a opção SMS só aparece quando o SMS estiver ligado. |
| RF-O16 | O código de SMS continua no projeto. Para ligar: integrar um provedor (ex.: Zenvia), configurar `SMS_DRIVER` e mudar `SMS_ENABLED=true`. |

## 2. API

| Método e rota | Faz |
|---|---|
| `POST /api/sign-up/code` `{email}` | Envia o código (ou o aviso de conta existente). Resposta sempre igual: `{expires_in, resend_after}` |
| `GET /api/sign-up/slug?slug=` | `{slug, available, suggestion}` |
| `POST /api/sign-up` `{name, email, password, code, barbershop_name, slug}` | Cria a barbearia, o dono e as configurações, e devolve a sessão (mesmo formato do login) |
| `GET /api/tenant` | Agora com `onboarded` (passo a passo terminado) |
| `POST /api/tenant/onboarding/complete` | Encerra o passo a passo |
| `GET /api/me` | Agora com `available_channels` (canais do código) |

Erros novos: `SLUG_UNAVAILABLE` (409, link tomado por outra barbearia no mesmo instante) e `EMAIL_ALREADY_REGISTERED` (409).

## 3. Como funciona

- **Orquestrador `Onboarding`** (`SignUpBarber`): o código prova o e-mail (Identity). Depois, numa transação, cria a barbearia (Tenant), o dono (Identity) e as configurações padrão (Settings, dentro de `CurrentTenant::run`). Por fim, abre a sessão (Identity).
- **Código do cadastro:** tabela `sign_up_codes`, uma linha por e-mail, só com o HMAC do código. A tentativa errada é contada mesmo quando a requisição falha.
- **Limites (throttle):**
  - `sign-up-codes`: 3 por minuto por e-mail, 20 por hora por visitante;
  - `sign-up`: 10 por minuto e 30 por hora por visitante.
- **Colunas novas:**
  - `tenants.onboarded_at`: as barbearias que já existiam ficam como concluídas;
  - `users.email_verified_at`.
- **SPA:**
  - `src/Domain/Onboarding`: `SignUpView`, `OnboardingView`, api, lista de links reservados (igual à da API; um teste garante que cobre todas as rotas fixas);
  - `src/Domain/Tenant/tenant.store.ts`: o guard usa para mandar ao passo a passo.

## 4. Próximos

- Teste grátis e assinatura (Etapa 8, billing): o cadastro já cria a barbearia ativa e pronta para receber um plano.
- Termos de uso e política de privacidade com aceite no cadastro.
