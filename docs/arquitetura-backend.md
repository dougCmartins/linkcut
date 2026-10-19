# Arquitetura do backend

Laravel 11 (PHP 8.3) com **DDD pragmático**, no mesmo padrão do `barber-saas`:
- a regra de negócio fica em domínios (`backend/src/Domain`);
- o Laravel cuida só da infraestrutura;
- as regras completas estão em `.cursor/rules/` (principalmente `laravel-backend.md`).

## Pastas

```
backend/
├── app/                      Infraestrutura (sem regra de negócio)
│   ├── Http/Middleware/      AssignRequestContext (X-Request-Id), AuthorizePulse
│   ├── Observability/        AddTenantToContext (logs + Sentry por tenant)
│   ├── Notifications/Channels/SmsChannel.php
│   ├── Sms/                  SmsSender + LogSmsSender (provedor real numa próxima fase)
│   ├── Pulse/ + Livewire/    Recorder e card "Tenants" do Pulse
│   └── Providers/AppServiceProvider.php (rate limits, Passport, bindings)
├── bootstrap/app.php         Rotas, middlewares e o envelope de erro
├── config/identity.php       Regras do 2FA e da sessão
├── src/Domain/
│   ├── Tenant/               O tenant (barbearia) e o isolamento por tenant_id
│   ├── Identity/             Conta do barbeiro, login, 2FA e sessão
│   └── Orchestrator/SignIn/  Fluxo que cruza Identity + Tenant
└── database/                 migrations, factories, seeders
```

Cada domínio segue a mesma forma:

```
Domain/{X}/
├── Actions/      uma classe final por tarefa, com um único handle()
├── Controllers/  finos: recebem a Data e devolvem $action->handle($data)
├── Data/         spatie/laravel-data, final e readonly, validação por atributos
├── Enums/  Exceptions/  Models/  Routes/api.php
└── Tests/Feature + Tests/Unit
```

## Contrato da API

- **Sucesso:** o corpo é a própria Data (ou `DataCollection`), **sem envelope**. Um POST bem-sucedido responde `201`.
- **Erro:** o envelope é montado só em `bootstrap/app.php`:

  ```json
  { "data": null, "message": "…", "code": "INVALID_CODE", "status_code": 422, "errors": {}, "request_id": "…" }
  ```

  - `errors` lista, por campo, o nome da regra que falhou, em snake_case (`{"email": ["email"], "vacation.ends_on": ["after_or_equal"]}`). Regras próprias usam o código com que falham (`WhatsappNumberRule` → `phone`). O frontend traduz.
  - Alguns erros trazem `data`. Por exemplo, `INVALID_CODE` traz `{ "attempts_left": 3 }`.
  - Erros de domínio são classes `final` que estendem `DomainException`, com `getErrorCode()` e `getHttpStatus()`.
- Qualquer erro inesperado vira `SERVER_ERROR` 500, sem detalhes. Os detalhes vão para o log e o Sentry, ligados pelo `request_id`.

## Multi-tenancy (banco único, isolamento por linha)

Todas as barbearias usam o mesmo banco MySQL. Toda tabela que pertence a uma barbearia tem `tenant_id`.

**Regra de ouro: ninguém filtra por `tenant_id` à mão.**

| Peça | O que faz |
|---|---|
| `Tenant/Context/CurrentTenant` | Tenant da requisição atual (um por requisição, `scoped` no container). |
| `Tenant/Middleware/ResolveTenant` (alias `tenant`) | Depois do `auth:api`, lê o `tenant_id` do usuário, confere se o tenant está ativo (`TENANT_INACTIVE` 403) e preenche o `CurrentTenant`. |
| `Tenant/Models/Concerns/BelongsToTenant` | Trait dos models do tenant. Aplica o `TenantScope` (filtro automático) e preenche `tenant_id` ao criar. Recusa gravar linha de outro tenant (`TENANT_MISMATCH`). |
| Fail-closed | Uma consulta num model do tenant **sem tenant resolvido** lança `TenantNotResolvedException`, em vez de devolver dados de todas as barbearias. |
| Saídas explícitas | `Model::withoutTenantScope()` para a visão da plataforma (admin, métricas). `CurrentTenant::run($id, fn)` para jobs e comandos. |

**Páginas públicas:** o middleware `tenant.slug` (`ResolveTenantFromSlug`) resolve o tenant pelo `{slug}` da URL, sem login. Slug desconhecido ou barbearia inativa respondem `TENANT_NOT_FOUND` 404. As rotas ficam em `/api/public/{slug}`, com o limite `throttle:public` (120/min por IP), e as Datas públicas nunca trazem campos privados, como o custo dos serviços.

O `User` **não** usa o escopo, porque o login acontece antes de o tenant existir na requisição. Só Actions de Identity leem `users`, sempre pelo id ou pelo e-mail.

Os erros de isolamento (`TENANT_NOT_RESOLVED`, `TENANT_MISMATCH`) indicam bug ou ataque. Por isso sempre vão para o Sentry e para o log `security`. Os demais erros de domínio (código errado, sessão expirada…) são respostas esperadas e não poluem o Sentry.

## Orchestrator

É o único lugar que cruza domínios. Não tem model, só chama Actions.

Exemplo: `Orchestrator/SignIn/CompleteSignIn` faz:
1. confere o código (Identity `VerifyTwoFactorCode`);
2. confere se o tenant está ativo (Tenant `EnsureTenantIsActive`);
3. abre a sessão (Identity `IssueSession`).

## Como criar um domínio novo

1. Escreva **uma frase** dizendo o que o contexto faz e por que não cabe num domínio que já existe. Sem a frase, a pasta não se cria.
2. Crie `src/Domain/{X}` com a forma acima e registre `Routes/api.php` em `routes/api.php`.
3. Models do tenant usam `BelongsToTenant`, e a migration tem `tenant_id` com FK e índice.
4. Rotas autenticadas usam `->middleware(['auth:api', 'tenant'])`.
5. Escreva os testes em `Domain/{X}/Tests` (padrão AAA, cobrindo caminho feliz, erro de domínio, borda e validação).

## Como rodar

```bash
make up        # MySQL :3309, API :8087, fila, Pulse, Mailpit :8025, SPA :5173
make seed      # tenants de demonstração; login rafael@linkcut.app / linkcut123
make test      # PHPUnit (SQLite em memória)
make pint      # formatação
```

Sem Docker (PHP 8.3 + Composer):

```bash
cd backend && composer install && cp .env.example .env
php artisan key:generate && php artisan passport:keys
php artisan migrate --seed && php artisan passport:ensure-personal-client
php artisan serve --port=8087     # e, em outro terminal: php artisan queue:work
php artisan test
```

Para usar SQLite fora do Docker, troque no `.env`: `DB_CONNECTION=sqlite`, `MAIL_MAILER=log`, `LOG_STACK=single`.
