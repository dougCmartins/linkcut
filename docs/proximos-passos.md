# linkcut: próximos passos

**Situação (29/10):**
- A Etapa 11 está na `main` (#40–#46), com o login com Google desligado até haver as chaves.
- Os módulos 2.1 (links livres, #47) e 2.2 (avaliações, #48) estão feitos.
- O 2.3 (vitrine) está com o Cursor.
- Depois vem a **Equipe** (seção 5), a próxima etapa grande.
- O mapa do sistema está em `docs/arquitetura.md`.

**Para quem é:** este documento é o roteiro para continuar o trabalho, inclusive no Cursor. Cada item vira uma PR. Depois, cada PR passa pela revisão do Claude.

---

## 0. Regras do projeto (o Cursor deve seguir)
Leia `CLAUDE.md`, `.cursor/rules/` e `GUIA_ENGENHARIA_LIMPA.md`. Na prática:

**Geral**
- **Idioma:** o código é todo em inglês. Os textos da tela vão em `frontend/src/i18n/pt-BR.ts`. Docs e PRs em português.

**Backend** (`backend/src/Domain/<Domínio>/`)
- **Estrutura:** `Actions/` (uma ação, um `handle()`), `Data/` (Spatie Data, nada de FormRequest/JsonResource), `Models/`, `Controllers/` finos, `Routes/api.php`, `Tests/Feature` e `Tests/Unit`.
- **Orquestração:** o que junta domínios vai em `Domain/Orchestrator/<Fluxo>/Actions`.
- **Multi-tenant:**
  - models com tenant usam o trait de tenant (`TenantScope`) e `CurrentTenant`;
  - a hora local vem do `TenantClock`;
  - rotas públicas usam o middleware `tenant.slug`.
- **Erros:** no formato envelope que já existe (`code` + `message`). As exceções seguem o padrão das que já estão em `Exceptions/`.
- **Rotas:**
  - rotas públicas levam `throttle:public`;
  - leituras públicas das configurações passam pelo `PublicSettingsCache` (`Domain/Settings/Support`);
  - quem grava configurações chama `forget()`.
- **Testes:** em padrão AAA (`// Arrange`, `// Act`, `// Assert`).
  - `php artisan test` e `vendor/bin/pint --test`;
  - o `tests/Feature/QueryBudgetTest.php` falha se aparecer N+1.

**Frontend** (`frontend/src/Domain/<Domínio>/`)
- **Estrutura:**
  - `*.api.ts`: snake_case no fio, camelCase no app;
  - stores Pinia;
  - componentes de `@/components/ui`: `BaseButton`, `BaseCard`, `TextField`, `ToggleSwitch` e outros.
- **Overlays:** fecham com o "voltar" do celular, via `useQueryOverlay` (`src/composables/useQueryOverlay.ts`). Nada de `target="_blank"` para páginas internas.
- **Carregamento:** esqueletos com a altura final, para evitar CLS. Painéis pesados com `defineAsyncComponent`.
- **Testes:**
  - vitest + Testing Library, com `renderWithPlugins` (`src/utils/testRender.ts`) e `installFakeApi` (`src/utils/testFetch.ts`);
  - `npm run typecheck`, `npx vitest run`, `npm run build`.
- **Build de produção:** com `VITE_API_URL=/api`, como no `deploy/web.Dockerfile`.

**Segurança**
- **CSP estrita** no `deploy/Caddyfile` (linha `Content-Security-Policy`). Não liberar `script-src` de terceiros sem necessidade.

**O que não fazer**
- Sem app autenticador.
- Sem API oficial do WhatsApp.
- SMS desligado.
- Cobrança e planos ficam só em `docs/ideias-cobranca.md`, sem código.

**Fluxo de cada entrega**
- Uma PR por item, em português.
- CI verde: `.github/workflows/ci.yml`.
- Squash merge.

---

## 1. PR 5: login com Google para clientes (prioridade)
**Decisão do dono:** é só para clientes. O barbeiro continua com e-mail, senha e código.

### Precisa do dono antes
- **Criar o cliente OAuth no Google Cloud**, do tipo "Aplicativo da Web":
  - redirect: `https://SEU-DOMINIO/api/client/auth/google/callback`;
  - para desenvolvimento, `http://localhost:8087/api/client/auth/google/callback`.
- **Preencher `GOOGLE_CLIENT_ID` e `GOOGLE_CLIENT_SECRET`** no `.env.production`, e documentar no `.env.production.example`.

### Backend
- **Instalar** `laravel/socialite`, com o provider `google` em `config/services.php`.
- **Migração:** `clients.google_id` (string, nulo, único).
- **Rotas** em `Domain/Client/Routes/api.php`, com `throttle:client-login`:

  | Rota | O que faz |
  |---|---|
  | `GET client/auth/google/redirect?volta=/conta` | Redireciona para o Google. Guarda o `volta` (só caminhos internos começando com `/`) e um `state` |
  | `GET client/auth/google/callback` | Recebe o usuário do Google |
  | `POST client/auth/google/exchange` (`{code}`) | Troca o código de uso único pela sessão |

- **Ação `SignInClientWithGoogle`** (`Domain/Client/Actions`):
  - recusa e-mail não verificado (`email_verified`);
  - procura por `google_id` e depois por e-mail. Achou pelo e-mail: grava o `google_id`. Não achou: cria o cliente com nome e e-mail do Google, sem senha. Reaproveite o `FindOrCreateClient`;
  - emite a sessão com o `IssueClientSession`, o mesmo do login normal.
- **Sem token na URL:**
  - o callback gera um **código de uso único**, aleatório, guardado no cache (Redis) por 60 s;
  - depois redireciona para `/conta/entrar/google?codigo=...&volta=...`;
  - o `exchange` apaga o código e devolve o `ClientSessionData`.
- **Erros:**
  - Google cancelado ou e-mail não verificado: redireciona para `/conta/entrar?erro=google`;
  - código inválido ou vencido: erro envelope `GOOGLE_CODE_INVALID`.
- **LGPD:** a exclusão de conta (`DeleteClient`) também zera o `google_id`; a exportação mostra que a conta está ligada ao Google.

### Frontend
- **Botão "Continuar com Google"** em três lugares:
  - `ClientAuthView.vue` (`/conta/entrar`);
  - o "Criar minha senha";
  - a tela de sucesso do agendamento, junto do `ClientPushOffer`.
- **O botão é um link normal** (`<a href="/api/client/auth/google/redirect?volta=...">`). Assim não é preciso mudar a CSP, nem carregar script do Google.
- **Nova rota `/conta/entrar/google`:**
  - lê `codigo` e `volta`;
  - chama o `exchange` pelo `client.store` (reaproveitar o que o login já faz para salvar a sessão);
  - faz `router.replace(volta)`;
  - mostra o erro se falhar.
- **Textos em `pt-BR.ts`:** botão, "Entrando com Google…" e o erro.

### Testes
- **Backend** (Socialite simulado com `Socialite::shouldReceive`):
  - cria o cliente novo;
  - liga a conta existente pelo e-mail;
  - recusa e-mail não verificado;
  - o código só vale uma vez e vence em 60 s;
  - `volta` externo (`https://...`, `//...`) vira `/conta`.
- **Frontend:** a tela `/conta/entrar/google` troca o código e navega; mostra o erro com código inválido.

---

## 2. Módulos novos (para acelerar a interface)
Cada módulo é uma PR. A ordem sugerida vai do mais simples ao mais complexo.

### 2.1 Mais links e redes (sem limite por enquanto)
- **Hoje:** as redes são fixas (`SettingsDefaults::socialNetworks()`, model `SocialLink`), e a aba Perfil das configurações edita só essas.
- **Fazer:** links livres na página pública, com título, URL e ícone opcional, ordenáveis.
  - Tabela `profile_links`, com tenant, `title`, `url`, `position`.
  - Entram no `UpdateProfileSettings`, que **precisa chamar `forget()` do cache** (já chama).
  - Aparecem no `ShowPublicPage` / `PublicProfileData`.
- **Validação:** só `https://`, até 300 caracteres. **Sem limite de quantidade** (o dono vai decidir os planos depois).
- **Frontend:**
  - editor na aba Perfil, reaproveitando o padrão das redes;
  - botões na página pública (`BioLinkView`), com o estilo de botão do tema (`useButtonStyle`).

### 2.2 Avaliações e depoimentos
- **Quem avalia:** o cliente, só depois de um atendimento **concluído**. O link vai no e-mail pós-atendimento ou em "Meus horários".
- **O que avalia:** nota de 1 a 5 e um texto opcional (até 500 caracteres, `PlainText`).
- **Backend:**
  - tabela `reviews` (tenant, `booking_request_id` único, `client_id`, `rating`, `comment`, `published_at`, `hidden_at`);
  - o barbeiro pode ocultar uma avaliação (moderação), mas não editar.
- **Página pública:**
  - média e as últimas 6 publicadas, numa seção nova;
  - entram no cache público, e qualquer avaliação nova ou ocultada chama `forget()`.
- **Painel:** lista com a opção de ocultar.

### 2.3 Produtos (vitrine)
- **O que é:** só vitrine, **sem venda nem pagamento**.
- **Cada produto tem:** nome, preço, foto (reaproveitar o `UploadImage` e as imagens do Settings), descrição curta, ativo/inativo e ordem.
- **Backend:** domínio novo `Catalog` (ver `backend/src/Domain/Catalog` do repositório `barber-saas` como referência).
- **Painel:** CRUD.
- **Página pública:**
  - seção "Produtos";
  - botão "Tenho interesse", que abre o WhatsApp da barbearia com uma mensagem pronta (o mesmo padrão do `whatsapp_message`).
- **Cache:** os produtos entram no cache público e invalidam ao salvar.

### 2.4 Visagismo com IA (portar do `barber-saas`)
- **De onde vem:** o repositório `barber-saas`.
  - backend `backend/src/Domain/Visagism` (`AnalyzeFaceProfile`, `VisagismResultBuilder`, limites e exclusão/restauração);
  - frontend `frontend/src/Domain/Visagism` (`VisagismFlow`, `VisagismCapture`, `VisagismResults` e modais).
- **Adaptar ao linkcut:**
  - **Tenant:** o tenant é pelo slug com o middleware `tenant.slug`, e não `barbershops/{slug}`;
  - **Cliente:** o fluxo é do **cliente** (guard `auth:client`); hoje está em `auth:api`;
  - **Sugestões de corte:** usam os **serviços** da barbearia (`ShowBookingOptions`), e o resultado leva ao agendamento com os serviços já marcados;
  - **Produtos:** só depois do item 2.3.
- **LGPD:**
  - a foto do rosto é dado sensível: pedir consentimento explícito antes da câmera;
  - não guardar a foto, só o resultado;
  - incluir o resultado na exportação e na exclusão do cliente;
  - atualizar `docs/privacidade.md` e a política.
- **Antes de começar:** confirmar com o dono se usa IA externa (custo por análise). Se usar, a chamada vai para a fila, com limite por cliente e por dia.

---

## 3. Pendências pequenas
1. **Imagem Docker de produção com Redis:** rodar `make prod-up` numa VPS ou numa máquina com acesso à internet. Conferir que o `redis` fica saudável e que o `docker compose logs app` não mostra erro de cache. No sandbox não deu para testar.
2. **PRs do Dependabot (#30 a #37):** revisar uma por uma e fazer o merge das que passarem no CI. Atualizações grandes (major) só com o changelog lido.
3. **Teste do esqueleto do histórico:** um teste vitest que verifica que `HistoryView` mostra `role="status"` com o esqueleto enquanto carrega (`frontend/src/Domain/Finance/History/__tests__`).
4. **Depois, quando crescer:** FrankenPHP em modo worker. Ver os cuidados em `docs/escala.md`, passo 2; não é para agora.

---

## 5. Próxima etapa grande: Equipe (de barbeiro sozinho a barbearia completa)
**Visão do dono:** o linkcut continua sendo **gestão**, não marketplace.
- O cliente não procura barbearias perto dele dentro do app: chega pelo Instagram ou pela internet.
- As barbearias que ele usa aparecem em "Minhas barbearias", como hoje.
- O que muda é o lado do barbeiro: ele pode virar uma barbearia com funcionários.

**Ordem:** terminar a vitrine (2.3) e lançar a versão para barbeiro sozinho. Só depois começar a Equipe.

### O mínimo da Equipe
1. **Barbeiros da equipe (subusuários):**
   - o dono cadastra os funcionários, que recebem um convite por e-mail e entram com o próprio login;
   - o funcionário vê a própria agenda e os próprios atendimentos;
   - o dono vê e gerencia tudo.
2. **Agenda por barbeiro:**
   - cada um tem os próprios horários e os serviços que faz;
   - no agendamento público, o cliente escolhe o barbeiro ou "qualquer um disponível";
   - o dono pode passar um atendimento de um barbeiro para outro.
3. **Comissão:**
   - percentual por serviço, com a opção de um valor diferente por barbeiro;
   - ao concluir o atendimento, a comissão fica registrada para o barbeiro;
   - relatório do mês com o faturamento e o valor a receber de cada um.

**Sem quebrar quem trabalha sozinho:**
- toda barbearia passa a ter um "barbeiro", que é o próprio dono, criado por migração para as barbearias que já existem;
- nada muda na tela de quem trabalha sozinho;
- "Minha equipe" só aparece ao adicionar o segundo barbeiro.

**Referência:** o `barber-saas` já tem profissionais e agendamento com serviços. Ele serve de modelo, mas o código precisa ser adaptado ao tenant do linkcut, não copiado.

### Perguntas para o dono (responder quando a etapa começar)
- **Base da comissão:** sobre o preço cheio ou sobre o preço menos o custo do serviço?
- **Limite da equipe:** o plano Equipe tem limite de barbeiros (ex.: até 5)?
- **Funcionário e financeiro:** o funcionário vê o próprio faturamento ou só a agenda?

### Fica para depois (plano pago mais caro)
- Modo secretária: papel de recepção que agenda pelo balcão para todos os barbeiros.
- Programa de fidelidade.
- Várias unidades.
- Assinatura de clientes (clube mensal; depende do gateway de pagamento).

**Quando a Equipe existir:** rever a paginação do histórico (cursor com "Carregar mais") e a exportação em fila. Uma barbearia com equipe pode passar de 50 mil atendimentos por ano. Para barbeiro sozinho (uns 5 mil por ano), a paginação atual basta.

---

## 4. Como o Claude vai revisar (o que o Cursor deve entregar)
**Para cada PR:**
- **CI verde e descrição em português**, com "O que muda", "Como testei" e capturas de tela no celular (390 px).
- **Regras do projeto respeitadas:**
  - código em inglês;
  - i18n;
  - Actions e Data;
  - testes AAA;
  - multi-tenant (um tenant nunca vê dado de outro: ter teste disso);
  - throttle nas rotas públicas;
  - cache invalidado.

**Na revisão, o Claude confere:**
- **Segurança:** redirect aberto no `volta`; código de uso único; CSP; IDOR entre tenants e entre clientes.
- **Desempenho:**
  - consultas por requisição (o `QueryBudgetTest`);
  - Lighthouse no celular de `/:slug` e `/:slug/agendar` (meta: performance ≥ 90, CLS < 0,1).
- **Navegação:** o "voltar" do celular fecha os overlays novos.
- **LGPD:** exportação e exclusão cobrem os dados novos.

**Para pedir a revisão:** mande o número da PR (ou PRs) ao Claude.
