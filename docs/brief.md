# Brief do linkcut

> Ponto de partida para quem chega ao projeto: uma pessoa, o chat do arquiteto ou o chat executor.
>
> - **Atualizado em:** 18/11/2026, até a PR #70.
> - **Como usar:** cada seção é curta e aponta para o documento com os detalhes. Na dúvida, o documento detalhado vale mais que este resumo.

## 1. O que é
O linkcut é um **SaaS de gestão para barbeiros**. Não é um marketplace: cada barbeiro divulga o próprio link.

**As três partes:**
- **Link da bio** (`/:slug`). A página pública do barbeiro para o Instagram, com:
  - serviços, portfólio, avaliações e endereço;
  - o botão de agendar.
- **Painel do barbeiro** (`/painel`):
  - agenda do dia, com pedidos para aceitar ou recusar;
  - concluir atendimento;
  - finanças, configurações e avaliações.
- **Área do cliente** (`/:slug/account`):
  - agendamentos;
  - agendar de novo;
  - perfil;
  - vitrine de produtos;
  - avaliações.

**Público:**
- **Hoje:** o barbeiro que trabalha sozinho.
- **Próximo passo:** a barbearia com equipe e filiais (seção 8).

**O que não fazemos (decisões do dono):**
- sem SMS: o código existe, mas está desligado (`SMS_ENABLED=false`);
- sem app autenticador;
- sem a API oficial do WhatsApp: só o botão `wa.me` do barbeiro;
- o linkcut não recebe pagamentos.

## 2. Stack
| Parte | Peças |
|---|---|
| **Backend** (`backend/`) | Laravel 12, PHP 8.3, Passport (guards `api` do barbeiro e `client` do cliente), `spatie/laravel-data`, Socialite (Google do cliente), `minishlink/web-push`, Pulse, Sentry opcional |
| **Frontend** (`frontend/`) | Vue 3.5, Vite 8, Pinia, Vue Router, TypeScript, Tailwind 4, vue-i18n (pt-BR), Vitest, PWA com Web Push (VAPID) |
| **Dados** | MySQL 8 (dados e fila `jobs`) e Redis (cache e limite de tentativas) |
| **Produção** | Docker numa VPS de 2 GB: Caddy (https, SPA, CSP) → FrankenPHP (API), worker da fila, scheduler, Redis e MySQL. Ver [arquitetura](arquitetura.md#produção-docker-composeprodyml) e [deploy](deploy-vps.md) |
| **Local** | `make up` sobe tudo: SPA :5173, API :8087, Mailpit :8025, MySQL :3309. `make seed` cria a demo `rafael@linkcut.app` / `linkcut123` |

## 3. Arquitetura em uma página
**Monólito modular em DDD** ([ADR 0001](adr/0001-monolito-modular-e-fronteiras.md)). Cada domínio fica em `backend/src/Domain/<Domínio>/`, com as pastas `Actions/`, `Data/`, `Models/`, `Controllers/`, `Routes/` e `Tests/`.

| Domínio | Responsabilidade |
|---|---|
| `Tenant` | a barbearia (slug, fuso, ativa) e o contexto multi-tenant (`CurrentTenant`, `TenantScope`, `BelongsToTenant`, `TenantClock`) |
| `Identity` | o barbeiro: cadastro, login com código por e-mail, sessões |
| `Settings` | perfil, tema, serviços (com custo), horários, endereço, portfólio, imagens, links |
| `Schedule` | pedidos de agendamento, disponibilidade, status |
| `Finance` | receitas, despesas e histórico (paginado por cursor) |
| `Client` | conta global do cliente, login e Google, push |
| `Notification` | push do barbeiro |
| `Review` | avaliações dos atendimentos |
| `Catalog` | vitrine de produtos (até 50 por barbearia) |
| `Shared` | utilitários sem regra de negócio (ex.: `PlainText`) |
| `Orchestrator` | fluxos que cruzam domínios: agendamento público, concluir atendimento, página pública, login, relatório do histórico, LGPD |

**Regras que o CI garante** (`backend/tests/Feature/ArchitectureTest.php`):
- um domínio não importa outro; quem cruza domínios é o `Orchestrator`;
- só `Tenant` e `Shared` são o núcleo compartilhado, e não conhecem nenhum domínio de negócio;
- nada fora do `Orchestrator` importa o `Orchestrator`.

**Padrões:**
- **Multi-tenant:** um tenant é uma barbearia.
  - As tabelas da barbearia têm `tenant_id` e o `TenantScope`, que falha fechado: sem tenant, nada é lido.
  - O tenant vem do usuário logado (`tenant`) ou do `{slug}` da URL (`tenant.slug`).
  - O cliente é global.
- **Entrada e saída:** DTOs do Spatie Data. Não há FormRequest nem JsonResource.
- **Respostas:** sucesso é o JSON do Data; erro é o envelope `{ code, message, errors? }`. O frontend traduz o `code` pelo i18n.
- **Desempenho:**
  - cache da página pública por barbearia (`PublicSettingsCache`, no Redis);
  - índices por tela;
  - `QueryBudgetTest` contra N+1;
  - paginação por cursor no histórico.
- **Segundo plano:** e-mails, push e relatórios CSV vão pela fila. Os lembretes, os convites e as limpezas rodam no scheduler.

Detalhes: [arquitetura](arquitetura.md), [padrões do backend](arquitetura-backend.md), [performance](performance.md), [escala](escala.md).

## 4. O que já está pronto
| Módulo | O que faz | Requisitos |
|---|---|---|
| **Entrar e criar conta (barbeiro)** | Tela única no estilo do Linktree (`/`, `/cadastrar`, `/entrar`). O e-mail identifica a barbearia, também em `/:slug/panel/login`. Código de 6 dígitos por e-mail (2FA), refresh token com rotação, "sair de todos os aparelhos" | [autenticação](requisitos-autenticacao.md) |
| **Onboarding** | Cadastro aberto com o e-mail confirmado. Escolha do nome e do link. Passo a passo para WhatsApp, endereço, serviços e horários | [onboarding](requisitos-onboarding.md) |
| **Agenda e painel** | Pedidos do dia, aceitar ou recusar, concluir atendimento (lança a receita), "Lembrar no WhatsApp". A tela se atualiza sozinha (`useLiveRefresh`) | [área do cliente](requisitos-area-do-cliente.md) |
| **Finanças** | Receita e margem por serviço (custo do serviço) e despesas fixas e avulsas | [despesas](requisitos-despesas.md) |
| **Histórico** | Período de até 1 ano, paginado por cursor com rolagem infinita e resumo na 1ª página. O CSV é gerado em segundo plano e chega por e-mail, como num banco (limite de 10 por hora) | [histórico](requisitos-historico.md) |
| **Configurações** | Perfil, tema claro ou escuro, serviços, horários, endereço e distância, portfólio, imagens, links livres | [configurações](requisitos-configuracoes.md), [localização](requisitos-localizacao.md) |
| **Página pública** | Link da bio, portfólio e agendamento (escolha de dia e horário), compartilhamento com prévia nos apps | — |
| **Avaliações** | O cliente avalia o atendimento concluído; a página pública mostra o carrossel "Nossos clientes", com setas | — |
| **Vitrine de produtos** | O barbeiro cadastra até 50 produtos, que só o cliente logado vê, na aba "Produtos" da área do cliente | [ideias de cobrança](ideias-cobranca.md#vitrine-decisões-já-tomadas) |
| **Área do cliente** | `/:slug/account`: agendamentos, agendar de novo, perfil, produtos, avaliações, cancelamento, "Adicionar à agenda", "Minhas barbearias". A conta nasce no 1º agendamento; login por e-mail e senha ou Google | [área do cliente](requisitos-area-do-cliente.md) |
| **Avisos** | Push do novo pedido para o barbeiro e push para o cliente. E-mails do pedido recebido, aceito ou recusado, lembrete na véspera e um convite para criar a senha | [notificações](requisitos-notificacoes.md) |
| **LGPD** | Termos com cláusula de operador, aceite no cadastro, exportação e exclusão da conta (barbeiro e cliente), registros de acesso, aviso de cookies, Sentry só com consentimento | [privacidade](privacidade.md), [incidentes](incidentes.md) |
| **Segurança e operação** | Limites de tentativas (Redis), CSP e cabeçalhos, uploads validados, backup cifrado com restauração testada, heartbeat, logs em JSON, `composer audit` e `npm audit` no CI | [observabilidade](observabilidade.md), [deploy](deploy-vps.md) |

As telas estão no [README](../README.md#telas). O histórico de cada etapa está no [roadmap](roadmap.md).

## 5. Rotas principais
| Quem | Rotas (SPA) |
|---|---|
| **Barbeiro** | `/` e `/cadastrar` (criar conta), `/entrar`, `/:slug/panel/login`, `/painel/boas-vindas`, `/painel`, `/painel/historico`, `/painel/despesas`, `/painel/configuracoes`, `/painel/reviews` |
| **Cliente** | `/:slug/login`, `/conta/entrar`, `/conta/finalizar`, `/:slug/account`, `/:slug/account/book`, `/:slug/account/profile`, `/:slug/account/products`, `/:slug/account/review/:appointment`, `/account` (minhas barbearias) |
| **Público** | `/:slug`, `/:slug/portfolio`, `/:slug/agendar`, `/privacidade`, `/termos` |

As rotas da API ficam em `backend/routes/api.php`, que carrega o `Routes/api.php` de cada domínio. Para ver todas: `php artisan route:list`.

## 6. Decisões e análises
| Documento | O quê |
|---|---|
| [ADR 0001](adr/0001-monolito-modular-e-fronteiras.md) (Aceito) | Monólito modular, fronteiras entre domínios e o `ArchitectureTest` |
| [ADR 0002](adr/0002-organizacao-com-filiais.md) (Aceito) | Filiais: uma **organização acima do tenant**, e cada filial é um tenant |
| [ATAM](arquitetura/contas-e-filiais-atam.md) e [CBAM](arquitetura/contas-e-filiais-cbam.md) | Comparação de 3 abordagens. A venceu, com benefício 91,5, ~26 dias e ROI 3,52 (o dobro de B) |
| [ADR 0003](adr/0003-visagismo-ia.md) (Proposto) | Visagismo: fase 1 com questionário e fase 2 com análise no aparelho (MediaPipe), atrás de um `FaceAnalyzer` |
| [Concorrência](arquitetura/concorrencia-equipe-e-comissao.md) | AppBarber, Trinks, Booksy e Codental: comissão, agenda por profissional e fechamento |
| [Decisão 0001](decisoes/0001-filiais-e-equipe.md) (Decidido, não aplicado) | O plano de filiais e equipe em 6 passos (P1 a P6), pronto para o chat executor |
| [Ideias de cobrança](ideias-cobranca.md) | Planos e preços: só ideias, nada implementado |

**Skills do projeto** (`.claude/skills/`):
- `arquiteto-software`: ATAM, CBAM e ADR; grava as decisões em `docs/decisoes/`.
- `executar-decisoes`: lê `docs/decisoes/`, aplica os passos e devolve o feedback no próprio arquivo.

## 7. Como trabalhamos
**Regras** ([CLAUDE.md](../CLAUDE.md), [guia](../GUIA_ENGENHARIA_LIMPA.md), `.cursor/rules/`):
- código, tabelas, logs e exceções em inglês;
- textos da tela pelo i18n (`frontend/src/i18n/pt-BR.ts`);
- docs e PRs em português;
- KISS;
- testes AAA em todo código crítico.

**Fluxo de cada mudança:**
1. Plano aprovado pelo dono.
2. Código com testes.
3. Teste no navegador.
4. PR em português.
5. CI verde.
6. Squash merge.

**CI** (`.github/workflows/ci.yml`):
- **backend:** Pint, testes e `composer audit`;
- **frontend:** typecheck, Vitest, build e `npm audit`.

**Testes:** cerca de 260 no backend (`make test`) e 376 no frontend (`make test-web`).

**Nunca vão para o git:** `.env.production` e certificados locais.

## 8. Pendências conhecidas
- **Push do cliente:** "Avisar neste aparelho" dá erro em alguns navegadores. Falta a linha `client_push_failed` do console para achar a causa.
- **Homologação no celular de verdade:** roteiro em [homologacao.md](homologacao.md).
- **Lançamento:** domínio, VPS, e-mail de envio (SMTP/Brevo), dados do controlador para a LGPD e backups agendados ([deploy](deploy-vps.md)).
- **PRs do Dependabot** abertas, para revisar.

## 9. Próximos passos
1. **Filiais e equipe** ([decisão 0001](decisoes/0001-filiais-e-equipe.md)), em ordem:
   - P1: organização e unidades;
   - P2: link da marca com select das filiais;
   - P3: equipe, com papéis e 3 permissões;
   - P4: agenda por barbeiro, com bloqueios;
   - P5: comissão configurável (ajustes antes/depois, percentual ou fixo, foto do cálculo, fechamento com trava);
   - P6: relatórios da marca.

   As 3 perguntas abertas (forma de pagamento, periodicidade do fechamento, padrão das permissões) **não bloqueiam** o início.
2. **Visagismo com IA:** falta o dono escolher o provedor e o plano ([ADR 0003](adr/0003-visagismo-ia.md)).
3. **Planos e cobrança:** módulos pagos bloqueados, assinatura e painel admin ([roadmap](roadmap.md#fase-b-negócio-depois-da-fundação)).

Lista detalhada: [próximos passos](proximos-passos.md).
