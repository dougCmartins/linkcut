# 0001. Monólito modular, com fronteiras guardadas por teste

- **Status:** Aceito
- **Data:** 2026-11-15
- **Decisores:** dono do produto, arquiteto (Claude)

## Contexto
O linkcut é um único deploy (Laravel 12 + Vue 3) numa VPS de 2 GB, organizado em domínios em `backend/src/Domain`.
A regra "domínio não chama domínio; quem cruza é o `Orchestrator`" já estava em `.cursor/rules/02-backend.md`, mas
nada a verificava.

Em 15/11 a análise encontrou:
- **3 violações:**
  - `Review` usava um utilitário de `Notification`;
  - as rotas de `Catalog` importavam um controller do `Orchestrator`;
  - as rotas de `Settings` importavam outro controller do `Orchestrator`.
  - As duas últimas criavam ciclos Catalog↔Orchestrator e Settings↔Orchestrator.
- **Cerca de 34 importações de `Tenant`:** ele funciona como núcleo compartilhado, sem que isso estivesse escrito.

Os próximos módulos (filiais, equipe, comissão e visagismo) vão aumentar os cruzamentos.

## Decisão
1. **Continuar como monólito modular.** Os ganhos:
   - um deploy só;
   - chamadas em memória entre módulos;
   - transações ACID entre módulos;
   - refatoração barata.
2. **Núcleo compartilhado:** só `Domain\Tenant` (contexto do tenant) e `Domain\Shared` (utilitários sem regra de negócio). O núcleo não importa nenhum domínio de negócio.
3. **Cruzamentos de domínio:** só em `Domain\Orchestrator`. Nada fora dele o importa, nem nas rotas.
4. **Leitura entre módulos, em ordem de preferência:**
   - contrato (Action pública que devolve um Data);
   - composição no `Orchestrator`;
   - réplica por evento, só com justificativa no ATAM.
5. **Serviços externos:** entram por interface + adaptador, como o `PushSender`.
6. **Guarda:** `backend/tests/Feature/ArchitectureTest.php` falha o CI quando alguém quebra 2 ou 3.
7. **Correções feitas junto:**
   - `PlainText` foi para `Domain\Shared\Support`;
   - as rotas de produtos e da página pública foram para `Orchestrator/Catalog/Routes` e `Orchestrator/PublicPage/Routes`, com as mesmas URLs e os mesmos middlewares.

## Alternativas descartadas
| Alternativa | Por que não |
|---|---|
| Microsserviços | Custo de operação e latência sem nenhum gatilho medido; viola o KISS e a VPS de 2 GB |
| deptrac ou Pest arch | Uma dependência nova para o que um teste PHPUnit de 80 linhas já garante |
| Interfaces para toda Action entre domínios | Cerimônia sem ganho enquanto o `Orchestrator` é o único consumidor; reservado para serviços externos |

## Consequências
- **Técnicas:**
  - os ciclos sumiram;
  - os próximos módulos nascem dentro das regras;
  - uma exceção nova exige um ADR.
- **Técnicas, ainda abertas:** os testes ainda compartilham fixtures entre domínios (permitido, o teste não entra na regra). O frontend ainda tem `Settings` no centro das importações; fica para um ADR futuro.
- **Econômicas:** custo zero de infra; meio dia de trabalho.
- **Caminho futuro:** um módulo só sai para um serviço próprio com um gatilho medido, por exemplo escala 10× maior que o resto ou um time separado. Ele sai pela mesma interface.

## Como validar
O `ArchitectureTest` passa no CI, e o `route:list` antes e depois das correções é idêntico (conferido em 15/11).
