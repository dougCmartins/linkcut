# Plano — migração das telas para a API (um módulo por vez)

Hoje só o **login** e **Configurações → Segurança** falam com a API. Todas as outras telas usam dados simulados no navegador (`localStorage`, IndexedDB e `src/mocks/`). Este plano leva cada módulo para o backend Laravel (`backend/src/Domain`), seguindo `.cursor/rules/laravel-backend.md`. Ao fim de cada etapa, a tela correspondente já está ligada à API.

## 1. Levantamento das telas

| Rota atual | Tela | O que faz | Dados hoje | Domínio no backend |
|---|---|---|---|---|
| `/` | Link da Bio | Perfil, redes sociais, botão do WhatsApp, abre o agendamento | `localStorage` (configurações) + IndexedDB (imagens) | **Configurações** (leitura pública) |
| `/portfolio` | Portfólio | Capa, frase, história, destaques, até 4 fotos | idem | **Configurações** (leitura pública) |
| `/agendar` | Agendamento (modal) | Serviços, dias e horários livres, pedido do cliente | Serviços e horários nas configurações; pedidos **só em memória** | **Agenda** (+ regras de Configurações) |
| `/entrar` | Login 2FA | E-mail, senha e código por e-mail ou SMS | **API real** | Identity ✅ |
| `/painel` | Painel | Faturamento, despesas, lucro, margem, agenda de hoje, concluir atendimento, pedidos pendentes | Histórico e despesas simulados; pedidos em memória | **Financeiro** + **Agenda** |
| `/painel/historico` | Histórico | Filtro semana/mês/ano/personalizado, totais, paginação 50/100/200, CSV | `mocks/history.ts` + `localStorage` | **Financeiro** |
| `/painel/despesas` | Despesas | Despesas fixas e avulsas: criar, editar, remover/desfazer; total do mês | `mocks/expenses.ts` + `localStorage` | **Financeiro** |
| `/painel/configuracoes` | Configurações | Abas Link, Portfólio, Agenda (serviços, custo, horários, férias), Aparência | `localStorage` + IndexedDB | **Configurações** |
| — aba Segurança | Canal padrão do 2FA | | **API real** | Identity ✅ |

### Problemas que a API resolve

- **O custo dos serviços vaza para o público.** As páginas públicas recebem o objeto `Service` inteiro, com `costInCents`. A API pública não vai devolver o custo.
- **Os pedidos de horário se perdem.** O pedido do cliente fica só na memória do navegador dele e nunca chega ao painel do barbeiro.
- **O horário não é conferido de novo ao gravar.** Hoje dois clientes podem marcar o mesmo horário. O servidor vai conferir de novo, com trava no banco.
- **Qualquer mudança de status é aceita.** A API vai impor as transições: pendente → confirmado/recusado, confirmado → concluído, concluído → confirmado (desfazer).
- **Um barbeiro só.** As páginas públicas passam a ser por barbearia: `/:slug`.

## 2. Domínios (DDD pragmático)

Cada domínio tem linguagem, regras e ciclo próprios. Os que cruzam domínios passam por um Orchestrator.

| Domínio | Frase do contexto | Contém |
|---|---|---|
| **Tenant** (existe) | A barbearia dona dos dados e o isolamento por `tenant_id`. | `tenants` (+ `timezone`), resolução do tenant pelo login e **pelo slug** nas páginas públicas. |
| **Identity** (existe) | Conta do barbeiro, login, 2FA e sessão. | Sem mudanças. |
| **Settings** (Configurações) | Como a barbearia se apresenta e as regras que ela define para atender. | Perfil e link, portfólio e fotos, aparência, catálogo de serviços (preço e **custo privado**), horário de funcionamento, intervalo e janela de agendamento, férias, imagens. |
| **Finance** (Financeiro) | Dinheiro que entrou e saiu, e o resultado do mês. | Lançamentos de receita (o histórico, com preço e custo congelados), despesas fixas e avulsas, resumo do mês. |
| **Schedule** (Agenda) | Pedidos de horário e seu ciclo: pendente → confirmado → concluído. | Pedidos de agendamento, horários ocupados, agenda do dia, aceitar, recusar, concluir, desfazer. |
| **Orchestrator** | Fluxos que cruzam domínios; só chama Actions. | `PublicBooking`: horários livres e pedido público (Settings + Schedule). `CompleteAppointment`: concluir e desfazer (Schedule + Settings + Finance). |

Cada pasta do frontend leva o nome do domínio do backend (`src/Domain/Settings`, `src/Domain/Finance`, `src/Domain/Schedule`), como já foi feito com `src/Domain/Identity`.

## 3. Convenções que valem para todas as etapas

As regras não cobrem estes pontos, então ficam decididos aqui:

- **Dinheiro:** inteiro em centavos, com colunas e campos `*_in_cents` (como o frontend já usa). Nunca float.
- **Datas e horas:**
  - `date` em `Y-m-d` e `time` em `H:i`, no fuso da barbearia (`tenants.timezone`, padrão `America/Sao_Paulo`);
  - "hoje" e "agora" sempre no fuso do tenant;
  - instantes em ISO 8601.
- **Rotas:**
  - privadas em `/api/{domínio}/...`, com `auth:api` + `tenant`;
  - públicas em `/api/public/{slug}/...`, com o middleware `tenant.slug` e rate limit;
  - URLs em kebab-case e no plural.
- **Contrato:**
  - sucesso devolve a Data ou a `DataCollection`, sem envelope; erro usa o envelope do handler;
  - `POST` responde 201, `DELETE` responde 204;
  - controllers seguem a forma única da rule: id de rota via `Data::validate`, sem `Request`.
- **Listas grandes:** só o histórico é paginado. A Data recebe `page` e `per_page` com default no construtor, e a resposta é `{ items, page, per_page, total, last_page }` numa Data própria (`HistoryPageData`), com os totais do período.
- **Imagens:**
  - `POST /api/settings/images` em multipart;
  - jpeg, png ou webp, até 5 MB;
  - o servidor redimensiona para no máximo 1600 px e grava em WebP no disco `public`, em `tenants/{id}/...`;
  - a resposta traz `{ id, url }`;
  - imagens que nenhuma configuração usa são apagadas por um comando agendado.
- **Isolamento:**
  - todo model de negócio usa `BelongsToTenant`;
  - toda etapa tem um teste "o tenant A não vê nem altera dados do tenant B".
- **Seeders:** os dados de demonstração saem dos mocks e vão para seeders do tenant `rafael`. Assim o painel continua com números para testar.
- **Por etapa:**
  - migrations reversíveis com FK e índice, factories, seeders;
  - testes de backend (caminho feliz, erro de domínio, borda, validação, isolamento);
  - `*.api.ts` real com testes sobre a API falsa (`utils/testFetch.ts`);
  - remoção do mock substituído;
  - e2e no navegador, documentação, **um PR e merge**.

## 4. Etapas

### Etapa 1 — Configurações + páginas públicas por slug ✅

> Feita. Além do previsto: `GET /api/tenant` (slug para o "Ver minha página"), comando `settings:prune-images` (diário, apaga imagens enviadas e não usadas há mais de 24 h) e o serviço `scheduler` no Docker.

**Backend**
- **Tenant:**
  - coluna `timezone`;
  - middleware `ResolveTenantFromSlug` (`tenant.slug`): resolve o tenant pelo slug, recusa tenant inativo com 404 e preenche o `CurrentTenant`.
- **Settings — tabelas** (todas com `tenant_id`):

  | Tabela | Campos |
  |---|---|
  | `profile_settings` | name, handle, bio, avatar_image_id, background_image_id, whatsapp_number, whatsapp_message |
  | `social_links` | network, url, position |
  | `portfolio_settings` | cover_image_id, quote, story |
  | `portfolio_highlights` | label, value, position |
  | `portfolio_photos` | image_id, alt, position |
  | `theme_settings` | palette, mode, custom_accent, button_shape, button_style |
  | `services` | name, duration_minutes, price_in_cents, cost_in_cents, position, soft delete |
  | `business_hours` | weekday, is_open, opens_at, closes_at |
  | `booking_settings` | slot_interval_minutes, booking_window_days, vacation_enabled, vacation_starts_on, vacation_ends_on, vacation_message |
  | `images` | path, mime, size, width, height |

- **Settings — endpoints privados:**

  | Método e rota | Faz |
  |---|---|
  | `GET /api/settings` | As quatro seções para o editor, com o custo |
  | `PUT /api/settings/profile` | Salva a seção inteira, como o botão "Salvar" da tela |
  | `PUT /api/settings/portfolio` | idem |
  | `PUT /api/settings/schedule` | idem (serviços, horários, regras e férias) |
  | `PUT /api/settings/theme` | idem |
  | `POST /api/settings/images` | Envia uma imagem |

- **Settings — endpoints públicos:**

  | Método e rota | Faz |
  |---|---|
  | `GET /api/public/{slug}` | Perfil, portfólio e aparência |
  | `GET /api/public/{slug}/booking-options` | Serviços **sem custo**, horários, regras e férias |

- **Regras:** todas as validações de `settings.validation.ts` passam para atributos das Datas, mais:
  - preço inteiro;
  - custo menor que o preço;
  - 7 dias da semana únicos;
  - intervalo em {15, 30, 45, 60} e janela em {7, 14, 21, 30};
  - no máximo 4 fotos.
- **Padrões:** uma barbearia nova recebe as configurações padrão, via Action `CreateDefaultSettings` chamada pelo seeder. O onboarding vem depois.

**Frontend**
- `src/Settings` → `src/Domain/Settings`:
  - `settings.api.ts` real (carregar, salvar seção, enviar imagem);
  - remove o IndexedDB, `utils/imageStore.ts` e o prefixo `stored:`.
- Rotas públicas:
  - `/:slug`, `/:slug/portfolio` e `/:slug/agendar`, com um `publicProfile.store` lendo a API pública;
  - `/` vira uma página simples do linkcut.
- O link "Ver minha página" do painel usa o slug do tenant.
- O modal de agendamento passa a ler `booking-options`. Os pedidos continuam simulados até a Etapa 3.

**Pronto quando:**
- as configurações salvas aparecem em `/:slug` em outro navegador;
- o custo não aparece em nenhuma resposta pública;
- as imagens sobrevivem a um reload;
- o tenant B não lê nem grava as configurações do A.

### Etapa 2 — Financeiro ✅

> Feita. Além do previsto: `TenantClock` (hoje no fuso da barbearia) e o erro `EXPENSE_NOT_FOUND` 404. O restore responde com a despesa de volta.

**Backend (Finance)**
- **Tabelas:**
  - `revenue_entries` (o histórico): `booking_request_id` único e nulo, `date`, `time`, `client_name`, `service_name`, `price_in_cents`, `cost_in_cents`;
  - `fixed_expenses`: `name`, `amount_in_cents`, `due_day`, soft delete;
  - `one_off_expenses`: `description`, `amount_in_cents`, `date`, soft delete.
- **Endpoints:**

  | Método e rota | Faz |
  |---|---|
  | `GET /api/finance/history?from&to&page&per_page` | Página do período + totais (faturamento, quantidade, ticket médio) |
  | `GET /api/finance/history/export?from&to` | CSV do período inteiro: `;`, BOM, CRLF, proteção contra fórmula |
  | `GET /api/finance/expenses` | Despesas fixas + avulsas do mês |
  | `POST /api/finance/fixed-expenses` | Cria despesa fixa |
  | `PUT /api/finance/fixed-expenses/{id}` | Edita |
  | `DELETE /api/finance/fixed-expenses/{id}` | Remove |
  | `POST /api/finance/fixed-expenses/{id}/restore` | Desfaz a remoção |
  | idem para `one-off-expenses` | |
  | `GET /api/finance/month-to-date` | Faturamento + quebra das despesas (fixas vencidas, avulsas, custos dos atendimentos) |

- **Actions internas** para a Etapa 3: `RecordServiceRevenue` (idempotente pelo pedido) e `RemoveServiceRevenue`.
- **Seeder:** o gerador de `mocks/history.ts` vira seeder de demonstração do tenant `rafael`.

**Frontend**
- `src/History`, `src/Expenses` e a parte financeira do Dashboard vão para `src/Domain/Finance`.
- `history.api.ts`, `expenses.api.ts` e `dashboard.api.ts` passam a ser reais. A paginação e o CSV passam para o servidor.
- Saem `mocks/history.ts`, `mocks/expenses.ts` e as chaves `linkcut.mockExpenses` e `linkcut.mockHistory.completed`.

**Atenção:** entre a Etapa 2 e a 3, "Concluir atendimento" continua simulado e **não entra no faturamento do servidor**. A Etapa 3 resolve isso.

**Pronto quando:**
- o painel, o histórico, as despesas e o CSV mostram os mesmos números da API;
- editar ou remover uma despesa reflete no resumo do mês.

### Etapa 3 — Agenda ✅

> Feita, com uma diferença: `GET /api/public/{slug}/availability?service_id` devolve **todos os dias da janela** com os horários livres numa chamada só, em vez de um dia por vez. O modal precisa disso para marcar os dias sem horário, e o "hoje" passa a ser o da barbearia, não o do aparelho. Sem `service_id`, usa o serviço mais curto.

**Backend (Schedule + Orchestrator)**
- **Tabela `booking_requests`:**
  - `client_name`, `client_whatsapp` (só dígitos), `service_id`;
  - cópias no pedido: `service_name` e `duration_minutes`;
  - `date`, `starts_at`, `ends_at`, `status` (enum), `completed_at`;
  - índice `(tenant_id, date, status)`.
- **Endpoints públicos** (`Orchestrator/PublicBooking`):
  - `GET /api/public/{slug}/availability?date&service_id`: horários livres calculados no servidor. Usa as regras de Settings e os horários ocupados de Schedule, e devolve só os horários, nunca dados de clientes.
  - `POST /api/public/{slug}/booking-requests`:
    - confere de novo o horário dentro de `DB::transaction` com `lockForUpdate`;
    - status inicial pendente;
    - rate limit por IP.
- **Endpoints privados:**

  | Método e rota | Faz |
  |---|---|
  | `GET /api/schedule/requests?status=pending` | Pedidos pendentes |
  | `GET /api/schedule/today` | Agenda de hoje |
  | `PATCH /api/schedule/requests/{id}/accept` | Aceita |
  | `PATCH /api/schedule/requests/{id}/reject` | Recusa |
  | `POST /api/schedule/requests/{id}/complete` | Conclui (`Orchestrator/CompleteAppointment`) |
  | `POST /api/schedule/requests/{id}/reopen` | Desfaz a conclusão |

- **Concluir** (`Orchestrator/CompleteAppointment`), numa transação:
  1. Schedule confere que o pedido está confirmado e é de hoje, e marca como concluído;
  2. Settings fornece o preço e o custo atuais do serviço;
  3. Finance grava o lançamento com esses valores congelados.
- **Desfazer:** o mesmo fluxo ao contrário.
- **Transições:** inválidas dão `INVALID_STATUS_TRANSITION` 409; horário tomado dá `SLOT_UNAVAILABLE` 409.

**Frontend**
- `src/Booking` → `src/Domain/Schedule`, com `booking.api.ts` real.
- O modal busca os horários livres na API.
- O painel lê os pedidos pendentes e a agenda de hoje.
- Concluir e desfazer chamam a API e recarregam o resumo do mês.
- Sai `mocks/barber.ts`. **Não sobra nenhum mock.**

**Pronto quando, ponta a ponta:**
1. o cliente pede um horário em `/:slug/agendar`;
2. o pedido aparece no painel do barbeiro;
3. o barbeiro aceita e o pedido entra na agenda de hoje;
4. concluir soma no faturamento e o custo entra nas despesas;
5. desfazer volta tudo;
6. dois pedidos para o mesmo horário: o segundo recebe `SLOT_UNAVAILABLE`.

## 5. Fora deste plano (próximos)

- Onboarding: criar a barbearia e o dono pela tela.
- Avisar o barbeiro de novo pedido (e-mail, push ou WhatsApp).
- Provedor real de SMS.
- Mini-CRM de clientes.
- Painel admin da plataforma: métricas globais, MRR.
