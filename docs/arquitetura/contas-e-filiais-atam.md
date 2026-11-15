# ATAM: barbeiro individual × barbearia com filiais

Análise feita com a skill `arquiteto-software` (15/11/2026). Decisão registrada em
[ADR 0002](../adr/0002-organizacao-com-filiais.md). Custos e retorno: [CBAM](contas-e-filiais-cbam.md).

## Objetivo de negócio
Atender dois públicos com o mesmo produto:
- o **barbeiro individual**, que continua com tudo simples e sem ver nada de "filiais";
- a **barbearia com filiais**, que administra matriz, filiais e barbeiros num painel só e vende por um link principal.

O objetivo é abrir os planos Equipe e Unidades sem reescrever o que já funciona.

## Como o sistema está hoje
- **Cada barbearia é um `tenant`.** Todas as tabelas de negócio têm `tenant_id`, filtrado pelo `TenantScope`.
  - O `CurrentTenant` falha fechado: sem tenant, nada é lido.
  - O `BelongsToTenant` impede gravar em outro tenant.
  - O cache da página pública é por tenant.
- **O modelo assume "1 tenant = 1 barbeiro = 1 endereço":**
  - configurações únicas por tenant (perfil, tema, horários, endereço, agendamento);
  - o lock e a checagem de horário ocupado valem para o tenant inteiro (um cliente por vez);
  - o push vai para todos os aparelhos do tenant;
  - a receita não tem barbeiro;
  - um fuso e um slug por tenant.
- **Os clientes já são globais** (sem `tenant_id`). Isso já serve para várias unidades.
- **Os papéis** (`UserRole::Owner`, `Barber`) existem, mas nada os verifica.

## Decisões do dono que entram como requisito
- **Um painel só,** com o seletor da unidade no topo.
  - Agenda, barbeiros, histórico, faturamento e configurações seguem a unidade escolhida.
  - O que se muda na matriz vale só para a matriz.
- **Um link principal com um select de filiais.**
  - O select troca o "Quem somos".
  - "Agendar" abre com a filial selecionada e mostra as outras e os endereços.
  - Cada filial também tem o próprio link.
- **Cada barbeiro pertence a uma filial só.**

## Árvore de utilidade
| Atributo | Refinamento | Cenário | Importância | Dificuldade |
|---|---|---|---|---|
| Segurança (isolamento) | Ninguém vê dados de uma unidade a que não tem acesso | S1 | A | M |
| Usabilidade | O barbeiro individual não muda nada | S2 | A | B |
| Modificabilidade | Criar uma filial nova não exige migração de dados nem código novo | S3 | A | M |
| Correção (concorrência) | Dois clientes não pegam o mesmo barbeiro no mesmo horário | S4 | A | M |
| Desempenho | A página principal com o select e a troca de unidade no painel são rápidas | S5 | M | B |
| Custo de operação | Continua numa VPS de 2 GB, um deploy só | S6 | M | B |

## Cenários
| # | Estímulo | Ambiente | Resposta | Medida |
|---|---|---|---|---|
| S1 | Um barbeiro da filial A pede agenda, histórico ou faturamento | Produção, várias filiais | Recebe só a filial A; o pedido de outra unidade dá 403 | 0 registros de outra unidade nos testes de isolamento |
| S2 | Um barbeiro individual usa o app depois da mudança | Conta existente | Telas, links e dados iguais aos de hoje | 0 telas novas; testes atuais passam sem mudança |
| S3 | A dona cria a 3ª filial | Produção | A filial nasce com configurações próprias (cópia opcional da matriz) e um slug próprio | Nenhuma migração ou deploy |
| S4 | Dois clientes pedem o mesmo barbeiro às 10h | Pico | Um entra, o outro recebe "horário ocupado"; outro barbeiro da mesma filial continua livre | 0 sobreposições num teste concorrente |
| S5 | Um visitante abre o link principal e troca a filial no select | Celular 4G | A troca usa o cache por unidade que já existe | Menos de 300 ms por troca (cache quente) |
| S6 | Uma marca com 5 filiais e 20 barbeiros usa o sistema | Produção | Mesma VPS, mesmo deploy | Memória dentro do limite atual; nenhum serviço novo |

## Abordagens candidatas
- **A — Organização acima do tenant (tenant = unidade).**
  - Nova tabela `organizations`: dona, tipo `individual | multi_unit` e slug da marca. Também `tenants.organization_id` e `tenants.is_main` (matriz).
  - Cada filial é um tenant, com tudo o que um tenant já tem (configurações, slug, tema, horários, cache).
  - O barbeiro individual é uma organização com um tenant só.
  - A dona escolhe a unidade ativa no topo. O middleware `tenant` valida a unidade pedida contra o acesso do usuário.
- **B — O tenant é a marca, e as tabelas ganham `branch_id`.**
  - Um tenant por marca; as filiais são linhas dentro dele.
  - Agenda, serviços, horários, receita, despesas, configurações, avaliações e produtos ganham `branch_id`, e um segundo escopo filtra por ele.
- **C — Serviços separados por unidade** (um deploy, ou um banco, por marca ou por filial).

## Análise
| Cenário | Abordagem | Sensibilidade | Trade-off | Risco | Não-risco |
|---|---|---|---|---|---|
| S1 | A | Validar a unidade pedida no middleware `tenant` | Reaproveita o isolamento já testado | Um relatório consolidado da marca cruza tenants de propósito: precisa de uma Action do `Orchestrator` com permissão da dona | O `TenantScope` continua sendo a única barreira |
| S1 | B | Dois escopos (tenant + branch) em todas as consultas | Um filtro novo em todo lugar | Esquecer o `branch_id` vaza dados entre filiais **da mesma marca** sem nenhum teste atual pegar | |
| S2 | A | `organizations.type = individual` esconde o seletor | Nada muda para quem já usa | | Migração cria uma organização por tenant existente |
| S2 | B | Telas mostram a filial "padrão" | | Telas e consultas mudam para todos | |
| S3 | A | Criar um tenant novo (já existe) com `organization_id` | Filial = tenant: tudo pronto | Fuso e endereço por filial já vêm de graça | |
| S3 | B | Tabelas de configuração deixam de ser únicas por tenant | | Reescrever as configurações e o cache | |
| S4 | A e B | Lock e checagem por barbeiro (`staff_id`) | Mais vagas simultâneas por filial | Agendamento sem barbeiro ("qualquer um") precisa escolher um livre dentro da transação | Mesmo `lockForUpdate` de hoje, só que por barbeiro |
| S5 | A | Cache por tenant já existe | O select troca de tenant = outra chave de cache | | A página principal só lista as filiais (1 query a mais, cacheada) |
| S6 | C | Um deploy por unidade | Isolamento físico | Custo e operação multiplicados; viola o KISS e a VPS de 2 GB | |

## Temas de risco
1. **Vazamento entre unidades.**
   - A evita criando um filtro novo só onde precisa: relatórios consolidados.
   - B espalha o risco por todas as consultas.
2. **Regressão para o barbeiro individual.**
   - A o mantém como caso trivial (1 organização, 1 tenant).
3. **Concorrência na agenda com vários barbeiros.**
   - Vale para qualquer abordagem. Exige lock por barbeiro e um teste concorrente.
4. **Fronteiras entre módulos.**
   - Equipe, comissão e visagismo aumentam os cruzamentos.
   - A guarda é o `ArchitectureTest` (ADR 0001): os cruzamentos ficam no `Orchestrator`.

## Recomendação
**Abordagem A.**
- Ela transforma "filial" em algo que o sistema já sabe fazer bem (um tenant).
- Ela concentra o trabalho novo em três peças:
  - a organização (a marca);
  - o seletor de unidade;
  - a equipe por filial.

Ver o CBAM para custo e fases.
