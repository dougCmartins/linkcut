# Arquitetura do linkcut

Mapa do sistema para não perder o fio. Vale para a `main` de 29/10/2026.

## Visão geral
O linkcut tem duas partes, no mesmo repositório:
- **`backend/`:** uma API em Laravel 12 (PHP 8.3), em DDD.
- **`frontend/`:** um SPA em Vue 3 + TypeScript, feito com Vite.

Em produção, tudo roda em Docker numa VPS de 2 GB.

```
Celular / navegador
      │  https
      ▼
┌─────────────┐   arquivos do SPA (dist/), CSP, prévia de links para robôs
│ Caddy (web) │───────────────────────────────────────────────┐
└─────┬───────┘                                               │
      │ /api/*                                                │
      ▼                                                       │
┌──────────────────┐   ┌───────────┐   ┌──────────┐           │
│ app (FrankenPHP) │──▶│  MySQL 8  │   │  Redis   │◀── cache e limite de tentativas
│  Laravel 12 API  │──▶│ (dados,   │   │ (64 MB)  │
└──────────────────┘   │  fila)    │   └──────────┘
┌──────────────────┐   └───────────┘
│ queue (worker)   │── e-mails (SMTP/Brevo) e push (Web Push)
│ scheduler        │── lembretes, convites, limpezas
└──────────────────┘
```

## Backend (`backend/`)

### Pilha
| Peça | Uso |
|---|---|
| Laravel 12 / PHP 8.3 | framework |
| `spatie/laravel-data` | DTOs de entrada e saída: validação e resposta JSON (não há FormRequest nem JsonResource) |
| Laravel Passport | tokens do barbeiro (guard `api`) e do cliente (guard `client`) |
| Laravel Socialite | "Continuar com Google" do cliente (desligado até haver as chaves) |
| `minishlink/web-push` | push no celular (barbeiro e cliente) |
| Laravel Pulse | painel de saúde em `/pulse` (desligado em produção por padrão) |
| Sentry | erros, só com o consentimento do usuário no frontend |

### Organização: DDD em `src/Domain/`
Cada domínio é uma pasta com a mesma forma:

```
src/Domain/<Domínio>/
  Actions/       uma regra de negócio por classe, com um único handle()
  Data/          DTOs (Spatie Data): o que entra e o que sai
  Models/        Eloquent
  Controllers/   finos: recebem o Data, chamam a Action, devolvem o Data
  Routes/api.php rotas do domínio (carregadas em routes/api.php)
  Exceptions/    erros com código próprio (ex.: BOOKING_REQUEST_NOT_FOUND)
  Tests/         Feature e Unit, no padrão AAA
```

| Domínio | O que guarda |
|---|---|
| `Tenant` | a barbearia (slug, fuso, ativo) e o **contexto multi-tenant** |
| `Identity` | o barbeiro (usuário), login com código, sessões, segurança |
| `Settings` | perfil (link na bio), portfólio, tema, serviços, horários, imagens, links livres |
| `Schedule` | pedidos de agendamento (`BookingRequest`), disponibilidade, status |
| `Finance` | receitas dos atendimentos concluídos, despesas fixas e avulsas, histórico |
| `Client` | conta do cliente (global, sem tenant), login, Google, push |
| `Notification` | push do barbeiro, `PlainText` (limpeza de texto) |
| `Review` | avaliações dos atendimentos |
| `Orchestrator` | fluxos que juntam domínios: agendamento público, concluir atendimento, página pública, exclusão de conta (LGPD) e outros |

**Regra de ouro:** um domínio não chama outro diretamente. Quem cruza domínios é o `Orchestrator`. Exemplo: `CompleteAppointment` muda o status no `Schedule` e lança a receita no `Finance`.

### Multi-tenant (uma barbearia = um tenant)
- **Tabelas da barbearia:** têm `tenant_id`. O trait `BelongsToTenant` aplica o `TenantScope`, um filtro global por `tenant_id`, e preenche o `tenant_id` ao criar.
- **De onde vem o tenant:**
  - nas rotas do painel, do usuário logado (middleware `tenant`);
  - nas rotas públicas, do `{slug}` da URL (middleware `tenant.slug`).

  Os dois guardam o tenant em `CurrentTenant`.
- **Cliente:** é global, porque pode agendar em várias barbearias. Quando ele grava algo de uma barbearia (ex.: uma avaliação), o código usa `CurrentTenant::run($tenantId, ...)`.
- **Hoje cada tenant tem um barbeiro só.** Não existem "profissional" nem "unidade" no modelo; ver a seção de escala.

### Autenticação
- **Barbeiro:**
  1. e-mail e senha em `/:slug/panel/login`;
  2. código de 6 dígitos por e-mail (2FA);
  3. o token de acesso fica na memória e o refresh token no `localStorage`, com rotação.
- **Cliente:**
  - e-mail e senha em `/:slug/login`, ou Google;
  - a conta nasce no primeiro agendamento e é ativada com um código por e-mail.
- **Proteções:**
  - limite de tentativas (`throttle:*`, no Redis);
  - tempo de resposta igual para e-mail certo e errado;
  - "sair de todos os aparelhos" ao trocar a senha.

### Respostas e erros
- **Sucesso:** o JSON do Data, sem envelope.
- **Erro:** envelope `{ code, message, errors? }`, montado em `bootstrap/app.php`. O frontend traduz o `code` pelo i18n.

### Performance
- **Cache por barbearia:** página pública, opções de agendamento e avaliações ficam no Redis (`PublicSettingsCache`). Salvar qualquer configuração apaga o cache daquela barbearia.
- **Índices:** pensados para cada tela (ver `docs/performance.md`).
- **Consultas:** testes de orçamento de consultas (`QueryBudgetTest`) impedem N+1.

### Rotinas em segundo plano
| Rotina | Quando |
|---|---|
| Fila (`queue:work`, tabela `jobs` no MySQL) | e-mails e push, sempre |
| `schedule:send-reminders` | a cada hora: lembrete da véspera |
| `client:invite-registration` | a cada hora: convite para o cliente criar a senha |
| `settings:prune-images` | todo dia às 03:30: apaga imagens sem uso |
| Limpezas de tokens e códigos, heartbeat | diário / a cada 5 min |

## Frontend (`frontend/`)

### Pilha
| Peça | Uso |
|---|---|
| Vue 3 (`<script setup>`) + TypeScript | telas |
| Vite 8 | servidor de desenvolvimento e build (chunks por rota) |
| Vue Router 5 | rotas; `meta.requiresAuth` (barbeiro) e `meta.requiresClient` (cliente) |
| Pinia | estado: sessão, configurações, agenda, página pública |
| vue-i18n | todos os textos em `src/i18n/pt-BR.ts` |
| Tailwind CSS 4 | estilo, com tokens de tema (cores da barbearia) em `src/styles/` |
| zod | validação de formulários |
| Vitest + Testing Library | testes de componentes e lógica |

### Organização
```
src/
  main.ts            inicia Vue, Pinia, router, i18n, guards e Sentry
  router.ts          todas as rotas (lazy: cada tela é um chunk)
  http/client.ts     fetch com token, refresh automático e erros (ApiError)
  Domain/<Domínio>/  telas, store, *.api.ts e testes, espelhando o backend
  components/ui/     kit visual (BaseButton, TextField, BaseTabs, ImageUploader…)
  composables/       lógica reutilizável (useApiErrors, useQueryOverlay, usePageMeta…)
  BioLink/, Portfolio/, Dashboard/, Home/   telas de topo
  Legal/             privacidade, termos, consentimento de cookies
  i18n/              textos (pt-BR) e política de privacidade
```

- **Contrato com a API:** cada `*.api.ts` traduz o snake_case da API para camelCase no app.
- **Overlays** (agendar, compartilhar, documentos) entram no histórico do navegador, então o "voltar" do celular os fecha.
- **PWA:**
  - `public/sw.js` recebe os push;
  - o app pode ser instalado na tela inicial (é obrigatório no iPhone para receber push).

### Rotas principais
| Rota | Quem | Tela |
|---|---|---|
| `/`, `/cadastrar`, `/entrar` | barbeiro | cadastro e "Encontrar minha página" |
| `/:slug` | público | link na bio |
| `/:slug/portfolio` | público | "Sobre", com o carrossel de avaliações |
| `/:slug/agendar` | público | agendamento |
| `/:slug/login` | cliente | login com o tema da barbearia |
| `/:slug/panel/login` | barbeiro | login com o tema da barbearia |
| `/painel`, `/painel/historico`, `/painel/despesas`, `/painel/configuracoes` | barbeiro | painel |
| `/conta`, `/conta/perfil`, `/conta/barbearias`, `/conta/review/:id` | cliente | área do cliente |

## Ambiente de desenvolvimento
- **Subir:** `make up` (ver `Makefile`) sobe o `docker-compose.yml`:
  - `mysql`;
  - `backend` (API em `:8087`);
  - `queue`, `scheduler` e `pulse`;
  - `mailpit` (e-mails em `:8025`);
  - `frontend` (Vite em `:5173`).
- **Dados de demonstração:** `make seed` cria o login `rafael@linkcut.app` / `linkcut123`.
- **Testes:**
  - backend: `make test` (ou `php artisan test`) e `vendor/bin/pint`;
  - frontend: `npm run typecheck`, `npx vitest run`, `npm run build`.

## Produção (`docker-compose.prod.yml`)
| Serviço | Imagem | Memória | Papel |
|---|---|---|---|
| `web` | Caddy (`deploy/web.Dockerfile`, com o build do SPA) | 128 MB | https automático (Let's Encrypt), arquivos do SPA, `/api` → app, CSP, prévias para robôs |
| `app` | FrankenPHP (`backend/Dockerfile.prod`) | 512 MB | API Laravel (modo clássico) |
| `queue` | a mesma do app | 256 MB | fila |
| `scheduler` | a mesma do app | 192 MB | rotinas |
| `redis` | `redis:7-alpine` | 64 MB | cache e limite de tentativas |
| `mysql` | `mysql:8.0` | 768 MB | banco |

- **Configuração:** `.env.production` (modelo em `.env.production.example`).
- **Deploy:** `git pull && make prod-up`. As migrações rodam sozinhas e o cache é limpo a cada deploy.
- **Backup:** `make prod-backup` gera um arquivo cifrado com banco, imagens e chaves; `deploy/restore.sh` restaura.
- **Monitoramento:** logs em JSON, heartbeat para healthchecks.io, Sentry opcional.
- **Passo a passo:** `docs/deploy-vps.md`. Escala: `docs/escala.md`.

## CI (`.github/workflows/ci.yml`)
Duas tarefas em cada PR:
- **Backend:** Pint, testes e `composer audit`.
- **Frontend:** typecheck, Vitest, build e `npm audit`.

Toda mudança entra por PR com squash merge.

## Onde ler mais
| Assunto | Documento |
|---|---|
| Regras de código | `CLAUDE.md`, `GUIA_ENGENHARIA_LIMPA.md`, `.cursor/rules/` |
| Padrões do backend | `docs/arquitetura-backend.md` |
| Performance e carga | `docs/performance.md` |
| Escala | `docs/escala.md` |
| LGPD | `docs/privacidade.md`, `docs/incidentes.md` |
| Requisitos de cada tela | `docs/requisitos-*.md` |
| Próximas tarefas | `docs/proximos-passos.md` |
| Ideias de cobrança | `docs/ideias-cobranca.md` |
