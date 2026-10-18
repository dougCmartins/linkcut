---
globs: *.php
alwaysApply: false
---

# ⚡ Performance & Optimization Rules

## 1. The N+1 Query Problem (Critical)
- Nunca execute consultas ao banco de dados dentro de loops (`foreach`, `while`)[cite: 27].
- Utilize sempre **Eager Loading** (`with()`, `load()`) ao lidar com relacionamentos que serão iterados[cite: 27].
- Monitore a contagem de queries durante o desenvolvimento utilizando ferramentas de profiling (Laravel Debugbar, Telescope ou logs de query)[cite: 27].

## 2. Memory Management & Large Datasets
- Nunca carregue tabelas massivas inteiras para a memória utilizando `Model::all()`[cite: 27].
- Utilize paginação (`paginate()`), fragmentação (`Model::chunk()`), coleções lazy (`LazyCollection`) ou cursores (`Model::cursor()`) ao processar grandes conjuntos de dados ou streams de arquivos (CSV, logs)[cite: 27].

## 3. Caching Strategies
- Utilize cache (`Cache facade`, tags e Redis) para dados estáticos ou altamente custosos de processar, definindo TTLs (Time-To-Live) adequados[cite: 27].
- Invalide o cache de forma reativa sempre que houver mutações em dados críticos[cite: 27].

## 4. Premature Optimization
- *"Premature optimization is the root of all evil."* — Donald Knuth[cite: 27].
- Escreva código limpo e expressivo primeiro[cite: 27]. Mensure gargalhos de desempenho com dados reais e perfis de carga antes de aplicar cache complexo ou micro-otimizações[cite: 27].

## 5. Race Conditions e Concorrência
- **Transações e Lock Pessimista:** Ao atualizar saldos, stocks ou dados financeiros críticos suscetíveis a acessos simultâneos, evite condições de corrida (race conditions) envolvendo a operação sempre dentro de um `DB::transaction()`.
- **Bloqueio de Registos (`lockForUpdate`):** Dentro da transação, encadeie o método `lockForUpdate()` na consulta do Eloquent. Isto tranca a linha na base de dados, obrigando qualquer outro processo concorrente a aguardar que a transação atual seja finalizada (via commit ou rollback) antes de conseguir atualizar o mesmo registo.
- **Atomic Locks:** Para processos de negócio concorrentes que não dependem estritamente de tabelas relacionais (ex: evitar que um *Job* ou chamada a uma API externa corra duas vezes em simultâneo), utilize os *Atomic Locks* do Laravel (`Cache::lock()`) para garantir a execução exclusiva.

## 6. Model rica (query e relação)
- **Query no Model:** Filtro, ordenação e restrição de eager load vivem no Model, em scope ou método de relação. A Action chama o método. Não reescreva a mesma query dentro da Action.
- **Relação do mesmo domínio:** `Sale` e o item ou produto que lhe pertence usam-se pela relação: `$sale->items()`, ou o inverso no Model de produto. Não abra Orchestrator só para navegar essa relação.
- **Onde declarar:** A relação fica no Model de origem ou no de destino, conforme a frase do domínio. Os dois Models têm de estar no mesmo domínio.
- **Não abusar:** Caso de uso (criar venda, debitar stock, notificar) não entra no Model. Relação não escreve regra de outro domínio. Cruzar domínios continua a ser Orchestrator.