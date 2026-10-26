# Performance

Como medimos, o que mudou e como repetir a medição quando o banco crescer. Os números de carga e o que fazer quando o movimento crescer estão também em [escala.md](escala.md).

## Como medir
1. Suba um MySQL 8 só para isso (nunca use o banco de produção):
   ```bash
   docker run -d --name perfdb -p 3307:3306 -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE=linkcut_perf mysql:8.0
   ```
2. Crie as tabelas e o volume de dados. São 200 barbearias, 100 mil clientes, 500 mil agendamentos e 700 mil lançamentos, e leva cerca de 30 segundos:
   ```bash
   cd backend
   export DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3307 DB_DATABASE=linkcut_perf DB_USERNAME=root DB_PASSWORD=root
   php artisan migrate:fresh --force
   php artisan db:seed --class=PerformanceSeeder --force
   ```
3. Rode o `EXPLAIN` da consulta que quer olhar. O que observar:
   - `type`: `range`, `ref` ou `const` é bom; `ALL` lê a tabela inteira;
   - `rows`: quantas linhas o MySQL lê;
   - `Using index`: lê só o índice, sem ir à tabela;
   - `Using filesort`: precisa ordenar em memória.

## Antes → depois
Medição feita no MySQL 8 com o volume acima. Os tempos são a mediana de 5 execuções de cada ação, sem a rede.

| Ação | Consultas | Tempo | Leitura (EXPLAIN) |
|---|---|---|---|
| Lembretes da véspera (200 barbearias) | 601 → **2** | 351 ms → **17 ms** | uma consulta por fuso horário, no índice novo |
| Meus horários (cliente com horários em 5 barbearias) | 16 → **3** | 9,0 ms → **2,3 ms** | contatos das barbearias em lote |
| Criar agendamento | 20 → **13** | 20,6 ms → **10,0 ms** | confere só o dia pedido, lendo só o índice |
| Horários livres (página pública) | 6 → **5** | 6,9 ms → **3,5 ms** | `Using index` (antes ia à tabela) |
| Financeiro: histórico do ano | 3 → **2** | 11,7 ms → **4,1 ms** | `range` + `Using index` (antes: 3.500 linhas lidas da tabela) |
| Financeiro: mês até hoje | 5 → **3** | 3,5 ms → **1,8 ms** | as duas somas numa consulta, só no índice |
| Pedidos pendentes (painel) | 2 → **1** | 5,1 ms → **4,1 ms** | sem `filesort` (já sai na ordem do índice) e 60 linhas lidas em vez de 180 |
| Agenda do dia (painel) | 2 → **1** | 1,8 ms → **1,1 ms** | — |

Os lembretes foram medidos num horário sem nenhum agendamento na janela. Com agendamentos, a versão antiga ainda fazia de 5 a 7 consultas por e-mail; a nova continua com as mesmas consultas, seja 1 ou 1.000 lembretes.

## O que mudou
- **Fim do N+1:**
  - "Meus horários", lembretes e convites fazem o mesmo número de consultas, não importa a quantidade de dados;
  - testes em `tests/Feature/QueryBudgetTest.php` garantem isso e falham se alguém reintroduzir uma consulta dentro de um laço.
- **Leituras repetidas:**
  - a hora local da barbearia (`TenantClock`) usa o fuso que já veio na identificação da barbearia;
  - o agendamento lê as opções uma vez e confere só o dia pedido.
- **Lotes:**
  - lembretes: uma consulta por fuso horário e um único `UPDATE` no fim;
  - convites: rodadas de 500, cada uma com um `UPDATE`;
  - configurações: redes sociais, destaques e fotos com um `INSERT` só; horários da semana com um `upsert`;
  - imagens sem uso: apagadas por lote.
- **Uma consulta no lugar de várias:**
  - link livre no cadastro: antes eram até 98 consultas;
  - somas do financeiro.
- **`NOT EXISTS`:** a limpeza diária de imagens não carrega mais a lista de todos os ids em uso.
- **Só as colunas necessárias e limites:**
  - "Meus horários" mostra os 100 mais recentes;
  - pedidos pendentes têm limite de 200;
  - as listas de barbearias, contatos e clientes leem só as colunas usadas.
- **Índices** (`2026_10_28_100000_add_performance_indexes.php`):
  - `booking_requests`:
    - `(tenant_id, date, status, starts_at, ends_at)`, que substitui o antigo `(tenant_id, date, status)`;
    - `(tenant_id, status, date, starts_at)`;
    - `(client_id, date, starts_at)`;
  - `revenue_entries`: `(tenant_id, date, price_in_cents, cost_in_cents)`;
  - `clients`: `(registration_invited_at, emails_opted_out_at, created_at)`;
  - `tenants.active`;
  - `images.created_at`.
- **Produção:**
  - cache e limite de tentativas no Redis (`docker-compose.prod.yml`), sem consultar o banco;
  - `config:cache`, `route:cache` e `event:cache` ao subir;
  - `make prod-up` recria os containers do app.

## Cache da página pública
- A página pública (`GET /api/public/{slug}`) e as opções de agendamento (`GET /api/public/{slug}/booking-options`) ficam no cache **por barbearia** (`PublicSettingsCache`).
- Salvar qualquer aba das configurações apaga o cache daquela barbearia: o visitante vê a mudança na hora.
- Validade de 10 minutos, só para o caso de alguém mudar o banco direto.
- A cada deploy, o app limpa o cache depois das migrações (`docker/entrypoint.sh`): nada da versão anterior fica para trás.
- Com o cache, essas duas rotas fazem **uma consulta** (a barbearia pelo slug), em vez de 5 a 12.
- As duas respostas saem com `ETag` e `Cache-Control: no-cache`: o navegador sempre pergunta, mas, se nada mudou, recebe um `304` vazio.
- O `CreateDefaultSettings` continua como garantia nessas rotas, mas só roda quando o cache está vazio. As configurações já nascem no cadastro (`SignUpBarber`).
- O "mês até hoje" do financeiro soma as despesas fixas no banco, sem carregar todas.
- O histórico abre com um esqueleto do tamanho final e só desenha os dias que estão na tela (`content-visibility`).

## Teste de carga (k6)
Os roteiros estão em `loadtest/`:
- `public.js`: visitantes chegando do Instagram. Cada um abre a página (perfil e opções juntos), vê os horários livres, e 1 em cada 10 pede um horário. Sobe de 50 para 200 visitantes ao mesmo tempo.
- `stress.js`: aumenta as aberturas de página por segundo (de 20 para 300) até o servidor não dar conta.

**Como rodar** (com o banco de teste do começo desta página, nunca em produção):
```bash
docker run --rm --network host -v $PWD/loadtest:/k6 grafana/k6 run /k6/public.js   # BASE=http://127.0.0.1:8081 por padrão
docker run --rm --network host -v $PWD/loadtest:/k6 grafana/k6 run /k6/stress.js
```
Cada visitante manda um `X-Forwarded-For` diferente, como celulares diferentes, para não cair no limite de 120 por minuto por IP. Isso só funciona falando direto com o app. Atrás do Caddy, o IP real prevalece e o limite vale.

**Ambiente da medição:**
- 4 núcleos e o k6 rodando na mesma máquina;
- PHP 8.3 com OPcache, 8 processos, no modo clássico (um boot do Laravel por requisição, como o FrankenPHP de produção hoje);
- MySQL 8 com 200 barbearias e 500 mil agendamentos.

| Teste | Antes (`main`) | Depois | Depois, com a configuração de produção |
|---|---|---|---|
| `public.js`: tempo de resposta p95 | 42 ms | 32 ms | **21 ms** (mediana 11 ms) |
| `public.js`: horários livres p95 | 33 ms | 25 ms | **15 ms** |
| `stress.js`: máximo de requisições por segundo | 170 | 193 | **240** |
| `stress.js`: aberturas de página descartadas | 2.968 | 2.091 | **324** |
| `stress.js`: tempo de resposta mediano no pico | 1,8 s | 0,8 s | **19 ms** |

- **"Configuração de produção"** é o Laravel Pulse desligado (`PULSE_ENABLED=false`, como no `.env.production.example`). Ligado, o Pulse grava duas linhas no banco a cada requisição, cerca de 4 ms cada vez.
- **Falhas:** nenhuma, fora alguns `409` de horário já ocupado, que é a resposta certa quando dois visitantes pedem o mesmo horário.
- **O limite agora é a CPU do PHP:** cerca de 6 ms por requisição só para subir o Laravel. Numa VPS de 2 núcleos, espere metade: perto de 120 requisições por segundo, ou 60 aberturas de página por segundo. Isso dá mais de **200 mil visitas por hora**.

## Lighthouse (celular)
Lighthouse no perfil de celular do PageSpeed (Moto G Power, 4G lento), com o build de produção atrás do Caddy:

| Página | Performance | CLS | LCP |
|---|---|---|---|
| `/` | 97 | 0 | 2,4 s |
| `/:slug` | 95 | 0 | 2,6 s |
| `/:slug/agendar` | 95 | 0,006 | 2,6 s |
| `/:slug/panel/login` (antes `/:slug/entrar`) | 93 | 0 | 3,0 s |
| `/entrar` | 96 | 0 | 2,4 s |
| `/conta/entrar` | 94 | 0 | 2,8 s |

- Acessibilidade 100 em todas.
- SEO 100 nas páginas públicas; as de login ficam fora do Google (`noindex`) de propósito.

As telas com login foram medidas no Playwright, com CPU 4x mais lenta e 4G lento:

| Página | CLS | LCP |
|---|---|---|
| `/painel` | 0,027 | 0,6 s |
| `/painel/historico` | 0,016 | 0,9 s |
| `/painel/configuracoes` | 0 | 1,2 s |
