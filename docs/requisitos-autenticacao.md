# Requisitos — Login do barbeiro com 2FA

> **Escopo:** frontend. Ainda não há backend: o login usa uma **API simulada** (`frontend/src/Auth/auth.api.ts`) que aplica as mesmas regras que o backend vai ter. Para ligar o backend, só esse arquivo muda.
> **Rota:** `/entrar`.

## 1. Acesso protegido

| Rota | Quem acessa |
|---|---|
| `/`, `/portfolio`, `/agendar` | Qualquer pessoa (páginas públicas) |
| `/entrar` | Visitante sem sessão (quem já está logado vai direto para o painel) |
| `/painel`, `/painel/configuracoes` | Somente o barbeiro com sessão válida |

A regra fica num único lugar: rotas com `meta.requiresAuth` passam pelo guard `Auth/auth.guard.ts`. Sem sessão, o barbeiro vai para `/entrar?redirect=<página pedida>` e, depois de entrar, volta para a página que tinha pedido. Só são aceitos endereços internos do app como destino, o que evita redirecionamento aberto para outro site.

## 2. Fluxo de login

1. **E-mail e senha.** Validados com Zod antes de chamar o servidor: e-mail válido e senha com pelo menos 8 caracteres. Se o e-mail ou a senha estiverem errados, a mensagem é sempre a mesma ("E-mail ou senha incorretos"), para não revelar quais e-mails têm conta.
2. **Escolha do canal (2FA).** WhatsApp ou e-mail, com o destino mascarado (ex.: `(11) •••••-8888`, `ra•••@linkcut.app`).
3. **Código de 6 dígitos.**
   - Campo em 6 caixas: aceita colar o código inteiro e o preenchimento automático do celular (`autocomplete="one-time-code"`), e confere sozinho quando o 6º dígito é digitado.
   - O código vale **5 minutos**.
   - O reenvio fica liberado depois de **30 segundos**, com contagem regressiva na tela.
   - São **5 tentativas**. Cada erro mostra quantas tentativas restam, e na 5ª o processo recomeça do e-mail e senha.
   - Dá para trocar de canal sem recomeçar.

### Canal padrão (Configurações → Segurança)

- Opções: **Perguntar sempre** (padrão), **WhatsApp** ou **E-mail**, com o destino mascarado.
- Com um canal padrão, depois do e-mail e da senha o código já é enviado por esse canal e a tela pula a escolha.
- Na tela do código continua o botão **Usar outro canal**.
- A preferência pertence à conta, porque precisa ser conhecida antes do login: o backend vai devolvê-la na resposta do login (`preferredChannel`). Na API simulada, ela é lida das configurações salvas.

## 3. Sessão

- **Access token** curto (15 min) e **refresh token** (7 dias) com **rotação**: a cada renovação o refresh antigo deixa de valer.
- A sessão fica no `sessionStorage`: fechar o navegador encerra a sessão. Ao recarregar a página, a sessão continua.
- Botão **Sair** no painel: invalida o refresh token e volta para `/entrar`.

## 4. Requisitos não funcionais

| Código | Requisito |
|---|---|
| RNF-A1 | Validação estrita dos formulários com Zod (`Auth/auth.schemas.ts`). |
| RNF-A2 | Logs estruturados (JSON) de falhas de login, código errado e acesso negado (`utils/logger.ts`), **sem** senha, código ou token. |
| RNF-A3 | Acessibilidade: labels nos campos, mensagens de erro com `role="alert"`, caixas do código com rótulo "dígito X de 6". |
| RNF-A4 | Mobile-first: teclado numérico no código e teclado de e-mail no campo de e-mail. |
| RNF-A5 | Textos via i18n; código em inglês; testes AAA (schemas, API simulada, guard, componente de código e tela de login). |

## 5. Ambiente de testes (sem backend)

Em desenvolvimento (`npm run dev`), a tela mostra a conta de teste e o código "enviado":

- **E-mail:** `rafael@linkcut.app`
- **Senha:** `linkcut123`

Esses avisos **não aparecem no build de produção**. Sem backend, o login de produção só vai funcionar quando a API real substituir `auth.api.ts`.

## 6. Próximas etapas (backend)

- Endpoints reais de login, envio de código (WhatsApp API e e-mail) e verificação.
- Refresh token em cookie `HttpOnly` + `Secure`, em vez do `sessionStorage`.
- Limite de tentativas também por IP e por conta, no servidor.
- Lista de dispositivos conectados, com opção de encerrar sessões.
