# Privacidade e LGPD: o que o linkcut trata

Inventário para a revisão jurídica e para manter a Política de Privacidade (`/privacidade`) atualizada.

> Os textos da política (`frontend/src/i18n/privacyPolicy.pt-BR.ts`, em `/privacidade`) e dos Termos de Uso
> (`frontend/src/i18n/termsOfUse.pt-BR.ts`, em `/termos`) são **modelos**. Antes de divulgar,
> peça a um advogado para revisar. Os dados do controlador vêm do `.env.production`:
> `VITE_PRIVACY_CONTROLLER`, `VITE_PRIVACY_DOCUMENT` e `VITE_PRIVACY_EMAIL`.

## Armazenamento no navegador
O app **não grava cookies**: a API é stateless (Bearer token).

| Chave | Onde | Para quê | Categoria |
|---|---|---|---|
| sessão do barbeiro | `sessionStorage` | manter o login até fechar o navegador | necessário |
| `linkcut.client-session` | `localStorage` | manter o cliente conectado (até 30 dias) | necessário |
| tema claro/escuro | `localStorage` | lembrar a escolha do visitante | necessário (preferência) |
| aviso de notificações dispensado | `localStorage` | não perguntar de novo | necessário (preferência) |
| `linkcut.consent` | `localStorage` | guardar a escolha de privacidade, com a versão e a data | necessário |

## Serviços de terceiros
| Serviço | O que recebe | Quando | Base legal |
|---|---|---|---|
| Sentry: erros | erro técnico, ids de barbeiro e barbearia, sem nome nem e-mail | sempre | legítimo interesse |
| Sentry: desempenho e replay | tempos de tela; gravação no erro com textos e imagens ocultos | **só com consentimento** ("Desempenho") | consentimento |
| Provedor de e-mail (ex.: Brevo) | nome e e-mail do destinatário, texto do aviso | ao enviar códigos e avisos | execução de contrato |
| ViaCEP | o CEP digitado e o IP de quem digitou | ao buscar endereço | execução de contrato |
| OpenStreetMap (Nominatim) | endereço da barbearia | ao salvar o endereço | execução de contrato |
| Hospedagem (VPS) | tudo | sempre | execução de contrato |

**Não usamos** publicidade, pixel, Google Analytics nem fontes externas: as fontes são servidas pelo próprio app.

## Consentimento
- O aviso aparece na primeira visita. "Aceitar tudo", "Rejeitar opcionais" e "Personalizar" têm o mesmo peso.
- A escolha vale até mudar a versão (`CONSENT_VERSION` em `frontend/src/Legal/consent.ts`): **aumente a versão sempre que o aviso ou as categorias mudarem**, e todos serão perguntados de novo.
- "Preferências de cookies", no rodapé das páginas e em "Minha conta", reabre o aviso.

## Papéis
- **linkcut é o controlador** dos dados dos barbeiros e da conta de cliente.
- **Cada barbearia é a controladora** dos dados dos clientes dela (agenda, histórico, financeiro); o linkcut é o **operador** (arts. 37 a 40). Isso está nos Termos de Uso.

## Termos de Uso e aceite
- O cadastro do barbeiro só termina com a caixa "Li e aceito os Termos de Uso e a Política de Privacidade" marcada (`accept_terms`).
- A conta grava a data (`users.terms_accepted_at`) e a versão aceita (`users.terms_version`).
- A versão atual fica em `backend/config/legal.php` (`terms_version`). **Ao mudar os termos de forma importante, aumente a versão** e a do texto (`terms.page.updated`).

## Direitos do titular
- **Cliente:**
  - corrige nome e WhatsApp em "Minha conta";
  - baixa os dados ("Baixar meus dados": conta e horários em JSON, `GET /api/client/me/export`);
  - exclui a conta. A exclusão anonimiza os agendamentos e o financeiro das barbearias, **inclusive os agendamentos antigos feitos com o mesmo e-mail antes de existir a conta**.
- **Barbeiro**, em Configurações → Segurança → "Seus dados":
  - baixa tudo da barbearia em JSON (perfil, configurações, serviços, horários, agendamentos e financeiro, `GET /api/account/export`);
  - exclui a barbearia, com a senha e o link digitado de novo (`DELETE /api/account`). Apaga a barbearia, os usuários, os acessos, os agendamentos, o financeiro, as inscrições de push e os arquivos de imagem.
- Outros pedidos (confirmação, informação sobre compartilhamento) pelo e-mail do encarregado (`VITE_PRIVACY_EMAIL`), com resposta em até 15 dias.

## Retenção
| O quê | Por quanto tempo | Como |
|---|---|---|
| Registros de acesso (IP, data, página, resultado) | 6 meses (Marco Civil, art. 15) | log do Caddy em `/data/access.log`, com rotação (`roll_keep_for 4320h`); sem cabeçalhos, tokens nem dados do navegador |
| Logs de segurança e da aplicação | até 90 dias | `storage/logs/security.log` diário com 90 dias; os demais logs saem no Docker, com limite de tamanho |
| Sessões, códigos e desafios vencidos | apagados todo dia | `model:prune`, `passport:purge` |
| Backups cifrados | 14 dias | `deploy/backup.sh` |
| Dados da barbearia | enquanto a conta existir | exclusão pela própria barbearia |

## Incidentes
Veja [incidentes.md](incidentes.md): avaliar, avisar a ANPD e os titulares em até 3 dias úteis e registrar.

## Prévia de links (Open Graph)
- `GET /api/og/{slug}` devolve o nome, a bio e a foto que **o próprio barbeiro publicou** na página pública.
- O Caddy só manda para lá os robôs de prévia (WhatsApp, Facebook, X etc.).
