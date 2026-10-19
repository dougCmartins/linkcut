# Requisitos — Login do barbeiro com 2FA

> **Backend:** `backend/src/Domain/Identity` + `backend/src/Domain/Orchestrator/SignIn`.
> **Frontend:** `frontend/src/Domain/Identity`. A pasta tem o nome do domínio do backend; as chamadas ficam em `auth.api.ts`, sobre `src/http/client.ts`.
> **Rota:** `/entrar`.

## 1. Acesso protegido

| Rota | Quem acessa |
|---|---|
| `/`, `/portfolio`, `/agendar` | Qualquer pessoa (páginas públicas) |
| `/entrar` | Visitante sem sessão (quem já está logado vai direto para o painel) |
| `/painel/*` | Somente o barbeiro com sessão válida |

- **Frontend:** rotas com `meta.requiresAuth` passam pelo guard `Domain/Identity/auth.guard.ts`.
  - Sem sessão, o barbeiro vai para `/entrar?redirect=<página pedida>` e, depois de entrar, volta para essa página.
  - Só são aceitos endereços internos do app como destino.
- **Backend:** rotas protegidas usam `auth:api` (Passport) + `tenant`. O middleware `tenant` resolve a barbearia do usuário e recusa uma barbearia desativada.

## 2. Fluxo de login (API)

| Passo | Endpoint | Resposta |
|---|---|---|
| 1. E-mail e senha | `POST /api/login` `{email, password}` | `{challenge_id, preferred_channel, destinations: [{channel, masked}]}` |
| 2. Envio do código | `POST /api/two-factor/send` `{challenge_id, channel}` (`email` ou `sms`) | `{channel, masked_destination, expires_at, resend_available_at}` |
| 3. Verificação | `POST /api/two-factor/verify` `{challenge_id, code}` | `{access_token, token_type, expires_in, refresh_token, refresh_expires_in, user}` |
| Renovação | `POST /api/token/refresh` `{refresh_token}` | Nova sessão (o refresh antigo deixa de valer) |
| Sair | `POST /api/logout` `{refresh_token}` (autenticado) | `204` |
| Perfil | `GET /api/me` | `{id, tenant_id, name, email, phone_masked, role, two_factor_channel}` |
| Canal padrão | `PATCH /api/me/two-factor-channel` `{channel \| null}` | Usuário atualizado |

**Regras**

- E-mail ou senha errados dão sempre a mesma resposta (`INVALID_CREDENTIALS`). Um e-mail inexistente leva o mesmo tempo que uma senha errada.
- **Código de 6 dígitos**, gerado com `random_int`:
  - vale **5 minutos**;
  - o reenvio fica liberado depois de **30 s**, com no máximo **5 envios** por tentativa;
  - são **5 tentativas**: cada erro devolve `attempts_left` e, na 5ª, a tentativa é bloqueada (`TOO_MANY_ATTEMPTS`);
  - a tentativa inteira expira em **10 minutos**.
- O código **nunca é salvo em texto**: fica só o HMAC-SHA256, ligado à tentativa. A fila guarda a notificação criptografada.
- SMS só aparece como opção quando a conta tem telefone (`CHANNEL_UNAVAILABLE`).
- **Limite de requisições:**
  - login: 5/min por e-mail+IP e 20/min por IP;
  - código: 10/min por tentativa+IP;
  - refresh: 30/min por IP.
  - Acima disso, a resposta é `TOO_MANY_REQUESTS` 429.

### Canal padrão (Configurações → Segurança)

- Opções: **Perguntar sempre**, **SMS** (desativado sem telefone) ou **E-mail**.
- É salvo **no servidor** ao clicar, porque pertence à conta e precisa ser conhecido antes do login. O login devolve esse canal em `preferred_channel`, e a tela pula a escolha.
- Na tela do código continua o botão **Usar outro canal**.

## 3. Sessão

- **Access token** do Passport, válido por **15 min**, enviado como `Authorization: Bearer`.
- **Refresh token** próprio, opaco (só o SHA-256 é salvo), válido por **7 dias**.
  - É **trocado a cada uso**.
  - Se um refresh token já usado aparecer de novo, ele foi copiado: **toda a família de tokens é revogada** e o barbeiro precisa entrar de novo.
- No SPA, a sessão fica no `sessionStorage`: fechar o navegador encerra a sessão.
- Quando a API responde 401, o cliente HTTP tenta **um** refresh silencioso e repete a chamada. Duas renovações nunca correm ao mesmo tempo.
- **Sair** revoga o access token e a família do refresh token no servidor e limpa a sessão local, mesmo sem internet.

## 4. Requisitos não funcionais

| Código | Requisito |
|---|---|
| RNF-A1 | Validação dupla: Zod no formulário e spatie/laravel-data na API (erros por campo com o nome da regra). |
| RNF-A2 | Logs estruturados com `request_id`/`tenant_id`/`user_id`; eventos de segurança no canal `security`; nunca senha, código ou token (ver `observabilidade.md`). |
| RNF-A3 | Acessibilidade: labels nos campos, erros com `role="alert"`, caixas do código com rótulo "dígito X de 6". |
| RNF-A4 | Mobile-first: teclado numérico no código e teclado de e-mail no campo de e-mail. |
| RNF-A5 | Textos via i18n (`lang/pt_BR` na API, `i18n/pt-BR.ts` no SPA); código em inglês; testes AAA nos dois lados. |

## 5. Desenvolvimento

- **Conta de demonstração** (criada pelo seeder): `rafael@linkcut.app` / `linkcut123`, com telefone. Há um segundo tenant, `bruno@navalhanorte.app`, sem telefone.
- **Onde ver o código:**
  - **E-mail:** Mailpit, em `http://localhost:8025`.
  - **SMS:** `backend/storage/logs/sms.log` (driver `log`, só local).
- O botão **Preencher** e a dica de onde ver o código **não aparecem no build de produção**.

## 6. Próximas etapas

- Provedor real de SMS (Twilio, Zenvia ou SNS) atrás de `App\Sms\SmsSender`.
- Cadastro e troca de telefone com verificação, e recuperação de senha.
- Lista de dispositivos conectados, com opção de encerrar sessões.
