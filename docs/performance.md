# Performance do backend

Como medimos, o que mudou e como repetir a medição quando o banco crescer.

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
  - `CACHE_STORE=file`: o limite de tentativas deixa de consultar o banco;
  - `config:cache`, `route:cache` e `event:cache` ao subir;
  - `make prod-up` recria os containers do app.
