# Filiais e equipe: tudo o que foi decidido

**Status:** decidido, **ainda não implementado**. Este documento é o roteiro para quando a etapa começar.
**Data:** 16/11/2026. Feito com a skill `arquiteto-software` (ATAM → CBAM → ADR).

## 1. Resumo
- **Dois tipos de conta:**
  - o **barbeiro individual**, que continua exatamente como hoje;
  - a **barbearia com filiais**, administrada num painel só, com uma equipe de barbeiros.
- **Como funciona por dentro:** cada filial é uma barbearia completa no sistema (um *tenant*), e uma **organização** (a marca) agrupa a matriz e as filiais. O barbeiro individual é uma organização com uma unidade só.
- **Por que assim:** reaproveita o isolamento, o cache e as configurações que já existem por tenant. Teve o melhor custo-benefício: ROI 3,52 contra 1,67 e 0,88 das alternativas.
- **Custo e entrega:** cerca de 26 dias, em 5 fases (uma PR cada), sem custo novo de servidor.
- **Antes da fase 3 (equipe), faltam 4 respostas do dono** (seção 12).

## 2. Fontes e status
| Documento | O que tem | Status |
|---|---|---|
| [ADR 0002](../adr/0002-organizacao-com-filiais.md) | A decisão de arquitetura | Aceito |
| [ATAM](../arquitetura/contas-e-filiais-atam.md) | Cenários de qualidade e trade-offs | Concluído |
| [CBAM](../arquitetura/contas-e-filiais-cbam.md) | Custo, benefício, ROI e fases | Concluído |
| [ADR 0001](../adr/0001-monolito-modular-e-fronteiras.md) | Regras de fronteira entre módulos | Aceito |
| [`proximos-passos.md`](../proximos-passos.md), seção 5 | O mínimo da Equipe e as perguntas | Referência |
| [`ideias-cobranca.md`](../ideias-cobranca.md) | Planos e preços | Hipóteses |

Este documento junta tudo. Ele não substitui o ADR: se um dia algo mudar, a mudança nasce num ADR novo, e este documento é atualizado.

## 3. Os dois tipos de conta
| | Barbeiro individual | Barbearia com filiais |
|---|---|---|
| Organização | `type = individual` | `type = multi_unit` |
| Unidades | 1 (a própria barbearia) | matriz + filiais, cada uma um tenant |
| Seletor de unidade no painel | não aparece | aparece no topo |
| Link público | `/rafael`, como hoje | link da marca com select + um link por filial |
| Equipe | ele mesmo; "Minha equipe" só aparece ao adicionar o 2º barbeiro | barbeiros por filial |
| O que muda para quem já usa | **nada** | — |

## 4. Decisões do dono
| Data | Decisão |
|---|---|
| 15/11 | **Um painel só.** Um seletor no topo do app escolhe a unidade (matriz ou filial). Agenda, barbeiros, histórico, faturamento e configurações seguem a unidade escolhida. |
| 15/11 | **Configurações por unidade.** O que se muda com a matriz selecionada vale só para a matriz. Para mudar uma filial, troca-se a unidade no topo e altera-se lá. |
| 15/11 | **Link principal da marca com um select de filiais.** Trocar no select muda o "Quem somos" (bio, fotos, endereço, horários) para a filial escolhida. |
| 15/11 | **Agendar pelo link principal:** abre já com a filial selecionada, mostrando as outras filiais e os endereços, para o cliente trocar se quiser. |
| 15/11 | **Cada filial tem o próprio link** (ex.: para outro Instagram), além do link da marca. |
| 15/11 | **Cada barbeiro pertence a uma filial só.** Mudar de filial é editar o cadastro. |
| 15/11 | **Referência visual da página pública:** Figma "Linktree UI Kit" (enviado pelo dono), usado na fase 2. |
| 29/10 | **Gestão, não marketplace:** o cliente chega pelo Instagram ou pela internet; não há busca de barbearias dentro do app. |
| 29/10 | **Equipe:** a dona cadastra os barbeiros, que recebem um convite por e-mail e entram com o próprio login. O barbeiro vê a própria agenda e os próprios atendimentos; a dona vê e gerencia tudo. |
| 29/10 | **Agenda por barbeiro:** cada um tem horários e serviços próprios. O cliente escolhe o barbeiro ou "qualquer um disponível". A dona pode passar um atendimento de um barbeiro para outro. |
| 29/10 | **Comissão:** percentual por serviço, com a opção de um valor diferente por barbeiro. É registrada ao concluir o atendimento e aparece num relatório do mês por barbeiro. |
| 29/10 | **Não quebrar quem trabalha sozinho:** toda barbearia ganha um "barbeiro" que é o próprio dono, criado por migração. |

## 5. Modelo de dados proposto
Apenas o desenho, sem nenhuma migração agora. Os nomes de código ficam em inglês, como manda o `CLAUDE.md`.

| Tabela | Campos novos | Para quê |
|---|---|---|
| `organizations` (nova) | `id`, `owner_user_id`, `type` (`individual` \| `multi_unit`), `name`, `slug` (o link da marca) | A marca |
| `tenants` | `organization_id`, `is_main` | Cada unidade (matriz ou filial) |
| `users` | `organization_id`; `role` passa a ser verificado (`owner`, `barber`) | Quem pode o quê |
| `staff` (nova) | `id`, `tenant_id`, `user_id` (nullable até aceitar o convite), `name`, `avatar`, `active` | O barbeiro, numa filial só |
| `staff_services` (nova) | `staff_id`, `service_id` | Que serviços cada barbeiro faz |
| `staff_business_hours` (nova) | `staff_id`, `weekday`, horários | Horário de cada barbeiro |
| `booking_requests` | `staff_id` (nullable) | Quem atende |
| `revenue_entries` | `staff_id`, `commission_in_cents` | Receita e comissão por barbeiro |
| `commission_rules` (nova) | `service_id`, `staff_id` (nullable = regra geral), `percent` | Percentual por serviço, com exceção por barbeiro |

**Migração dos dados atuais:**
- uma organização `individual` para cada tenant existente;
- o tenant vira `is_main = true`;
- um `staff` que é o próprio dono, com todos os serviços e os horários atuais;
- os agendamentos e receitas antigos recebem esse `staff_id`.

## 6. Como funciona, por área
- **Painel:**
  - o seletor manda a unidade escolhida em cada pedido;
  - o middleware `tenant` só aceita uma unidade da organização do usuário, e o barbeiro só a dele (outra unidade dá 403);
  - o `CurrentTenant` não muda.
- **Criar filial:** a dona cria uma unidade nova com nome, slug e endereço. Pode copiar as configurações da matriz uma vez; depois cada uma segue a própria, sem sincronização.
- **Página pública:**
  - endpoint novo da marca (`/api/public/brand/{slug}`) com a lista das filiais;
  - o select troca para a página da filial, que já tem cache por unidade;
  - o link de cada filial continua sendo `/{slug-da-filial}`.
- **Agendamento:**
  - o lock e a checagem de horário ocupado passam a ser por barbeiro;
  - outro barbeiro da mesma filial continua livre no mesmo horário;
  - "qualquer um disponível" escolhe um barbeiro livre dentro da mesma transação.
- **Push:** vai para o barbeiro do atendimento e para a dona, não para todos os aparelhos da unidade.
- **Financeiro:** ao concluir o atendimento, grava a receita com o barbeiro e a comissão calculada naquele momento.
- **Relatórios da marca:** faturamento e histórico somando as unidades, numa Action do `Orchestrator` liberada só para a dona. É o único ponto que cruza unidades, de propósito e testado. O CSV vai por e-mail, como o do histórico.

## 7. Fronteiras entre módulos ([ADR 0001](../adr/0001-monolito-modular-e-fronteiras.md))
- **Domínio novo `Team`:** barbeiros, serviços e horários de cada um, e convites.
- **`Organization` fica no núcleo `Tenant`:** é contexto de tenant, usado por todos.
- **Fluxos que cruzam domínios ficam no `Orchestrator`:**
  - convidar barbeiro (Team + Identity + e-mail);
  - agendar com barbeiro (Schedule + Team);
  - concluir com comissão (Schedule + Finance + Team);
  - página da marca (Tenant + Settings);
  - relatório da marca.
- **O `ArchitectureTest` continua valendo:** nenhuma exceção nova é necessária.

## 8. Segurança e LGPD
- Um barbeiro da filial A nunca vê agenda, clientes, histórico ou faturamento da filial B.
- Um barbeiro não vê os dados de outro barbeiro da mesma filial. O faturamento próprio depende da resposta do dono (seção 12).
- Um pedido para uma unidade fora da organização dá 403; um id de outra unidade dá 404.
- "Baixar meus dados" e "excluir conta" passam a cobrir barbeiros, comissões e a organização.
- O convite por e-mail é de uso único e expira.

## 9. Fases (uma PR cada)
| # | Entrega | Dias | Pronto quando |
|---|---|---|---|
| 1 | **Organização e unidades:** tabela `organizations`, migração, seletor no topo (só `multi_unit`), criar filial, middleware validando a unidade | 6 | O barbeiro individual não vê mudança; a dona troca de unidade e cada uma mostra só os próprios dados |
| 2 | **Link principal com select:** endpoint da marca, página com select, agendamento com a filial já escolhida e as outras visíveis (Figma "Linktree UI Kit") | 5 | O select troca o "Quem somos" sem recarregar a página; o link de cada filial continua funcionando |
| 3 | **Equipe:** `staff`, convite, papéis verificados, horários e serviços por barbeiro, lock por barbeiro, "qualquer um disponível", reatribuir, push por usuário | 9 | Dois clientes não pegam o mesmo barbeiro no mesmo horário; o barbeiro vê só a própria agenda |
| 4 | **Comissão:** regras por serviço e por barbeiro, gravação ao concluir, relatório do mês | 3 | O relatório bate com os atendimentos concluídos |
| 5 | **Relatórios da marca:** faturamento e histórico consolidados, só para a dona, com CSV por e-mail | 3 | A soma das unidades bate com cada unidade separada |
| | **Total** | **~26** | |

## 10. Planos e retorno (hipóteses)
- **Equipe:** R$ 59–79 por mês (barbeiros, agenda por barbeiro e comissão).
- **Unidades (proposta):** R$ 129 por mês mais R$ 39 por filial extra.
- **Exemplo:** 15 marcas com 2 filiais em média dão cerca de R$ 2.520 por mês. Contra cerca de R$ 13.000 de desenvolvimento, o retorno vem em cerca de 5 meses.
- Os números servem para comparar caminhos, não são previsão de venda.

## 11. Testes de aceitação
- **Isolamento:** a filial A não lê nada da B, e outra organização dá 403 (em cada fase).
- **Barbeiro individual:** todos os testes atuais passam sem mudança.
- **Concorrência:** o mesmo barbeiro no mesmo horário não aceita dois agendamentos; outro barbeiro da filial aceita.
- **Desempenho:** `QueryBudgetTest` da página da marca e da troca de unidade.
- **LGPD:** a exportação e a exclusão incluem os dados novos.
- **Arquitetura:** o `ArchitectureTest` passa.

## 12. Perguntas em aberto para o dono (antes da fase 3)
1. **Base da comissão:** sobre o preço cheio ou sobre o preço menos o custo do serviço?
2. **Limite de barbeiros:** o plano Equipe tem limite (ex.: até 5)?
3. **Barbeiro e financeiro:** o barbeiro vê o próprio faturamento e a comissão, ou só a agenda?
4. **Plano Unidades:** o preço e o limite de filiais.

## 13. Fora do escopo
- **Modo secretária:** recepção que agenda para todos os barbeiros. Fica para um plano mais caro.
- **Barbeiro em várias filiais:** recusado pelo dono.
- **Programa de fidelidade e assinatura de clientes:** depois, num plano mais caro.
- **Um sistema separado por unidade:** recusado (ROI 0,88).

## 14. Alternativas descartadas
| Caminho | Benefício | Dias | ROI | Por que não |
|---|---|---|---|---|
| **A — Organização acima do tenant (escolhido)** | 91,5 | 26 | **3,52** | — |
| B — O tenant é a marca e todas as tabelas ganham `branch_id` | 75,3 | 45 | 1,67 | Um filtro novo em toda consulta, com risco de mostrar dados de uma filial em outra; reescreve configurações e cache |
| C — Um sistema separado por unidade | 74,5 | 85 | 0,88 | Custo de servidor multiplicado (≥ R$ 150 por marca por mês); foge da VPS de 2 GB |
