# Observabilidade (consciente do tenant)

Num SaaS multi-tenant, todo erro precisa responder **qual barbearia foi afetada**. Por isso toda requisição carrega três ids:

| Id | De onde vem |
|---|---|
| `request_id` | Header `X-Request-Id`. O SPA gera um por chamada, e a API aceita esse valor (ou cria um novo) e o devolve na resposta e no envelope de erro. |
| `tenant_id` | Resolvido pelo middleware `tenant` depois do login. |
| `user_id` | O barbeiro autenticado. |

## Logs estruturados

- O canal **`json`** escreve um objeto JSON por linha no `stderr`. É o padrão no Docker e em produção (`LOG_STACK=json`).
- Todo log da requisição recebe `request_id`, `tenant_id` e `user_id` automaticamente (`Log::shareContext`).
- Os eventos têm nomes em snake_case com contexto, por exemplo `Log::info('user_authenticated', ['user_id' => 1, 'tenant_id' => 3])`.
- O canal **`security`** (`storage/logs/security-*.log`, JSON, 90 dias) registra:
  - `login_failed`
  - `two_factor_code_invalid`
  - `two_factor_challenge_locked`
  - `refresh_token_reuse_detected`
  - `tenant_isolation_error`
- **Nunca** vão para o log: senha, código 2FA, token, e-mail ou telefone completos (aparecem mascarados: `ra****@linkcut.app`).
- Exceção de desenvolvimento: o driver `log` de SMS grava a mensagem (com o código) em `storage/logs/sms.log`. Esse driver é **proibido em produção**: a aplicação não sobe com `SMS_DRIVER=log` em produção.

## Sentry

| | Backend (`sentry/sentry-laravel`) | Frontend (`@sentry/vue`) |
|---|---|---|
| Ativação | `SENTRY_LARAVEL_DSN` (vazio = desligado) | `VITE_SENTRY_DSN` (vazio = desligado) |
| Tags | `tenant_id`, `request_id`; usuário = `user_id` | `tenant_id`; usuário = `user_id` (definidos no login, limpos no logout) |
| Performance | `SENTRY_TRACES_SAMPLE_RATE` (0.2) | `VITE_SENTRY_TRACES_SAMPLE_RATE` (0.2), com o trace ligado ao da API |
| Privacidade | `send_default_pii=false` | Session Replay só quando há erro, com `maskAllText` e `blockAllMedia` |

Erros de domínio esperados (código errado, sessão expirada…) **não** vão para o Sentry. Os erros de isolamento de tenant **sempre** vão.

## Laravel Pulse

- Dashboard em `http://localhost:8087/pulse`.
  - **Aberto** em `APP_ENV=local`.
  - Fora disso, pede HTTP Basic Auth (`PULSE_USER` e `PULSE_PASSWORD_HASH`, gerado com `bcrypt()`).
- Cards: servidores, filas, cache, queries e requisições lentas, exceções, jobs lentos.
- Card **Tenants**: requisições e erros 5xx por barbearia (recorder `App\Pulse\TenantRequests`).
- No Docker, o serviço `pulse` roda `pulse:check` (servidores) e o serviço `queue` processa os envios de código, que aparecem no card de filas.
