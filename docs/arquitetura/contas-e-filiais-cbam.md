# CBAM: barbeiro individual × barbearia com filiais

Continuação do [ATAM](contas-e-filiais-atam.md). Decisão no [ADR 0002](../adr/0002-organizacao-com-filiais.md).

- **Benefício** = soma de (utilidade × peso), com utilidade de 0 a 100.
- **ROI** = benefício ÷ dias de desenvolvimento.
- **Dias:** estimativa com testes AAA, migração e docs, para 1 pessoa com o Claude.
- Os valores são hipóteses para comparar as abordagens, não promessas.

## Pesos dos cenários
| Cenário | Peso | Por quê |
|---|---|---|
| S1 isolamento | 0,30 | Um vazamento entre unidades acaba com a confiança e é um incidente de LGPD |
| S2 barbeiro individual intacto | 0,20 | É a base de clientes de hoje |
| S3 filial nova sem código | 0,15 | Venda do plano Unidades |
| S4 sem horário duplicado | 0,15 | O núcleo do produto |
| S5 desempenho | 0,10 | Hoje já é bom; só não pode piorar |
| S6 custo de operação | 0,10 | VPS de 2 GB, um deploy |

## Utilidade por abordagem
| Cenário | Peso | A (organização acima do tenant) | B (`branch_id` em tudo) | C (serviços separados) |
|---|---|---|---|---|
| S1 | 0,30 | 90 | 65 | 90 |
| S2 | 0,20 | 95 | 70 | 85 |
| S3 | 0,15 | 90 | 75 | 50 |
| S4 | 0,15 | 90 | 90 | 90 |
| S5 | 0,10 | 90 | 80 | 75 |
| S6 | 0,10 | 95 | 90 | 20 |
| **Benefício** | | **91,5** | **75,3** | **74,5** |

## Custo
| Abordagem | Dias | Operação por mês | Risco residual |
|---|---|---|---|
| A | ~26 | igual a hoje | Relatório consolidado da marca precisa de permissão explícita |
| B | ~45 | igual a hoje | Vazamento entre filiais em qualquer consulta esquecida |
| C | ~85 | +1 VPS e +1 banco por marca (≥ R$ 150 por marca por mês) | Operação multiplicada; dados de cliente espalhados |

## ROI
| Abordagem | Benefício | Dias | ROI |
|---|---|---|---|
| **A** | 91,5 | 26 | **3,52** |
| B | 75,3 | 45 | 1,67 |
| C | 74,5 | 85 | 0,88 |

**A tem o dobro do ROI de B.** C só faria sentido com um gatilho medido (ADR 0001), como uma marca que precise de isolamento físico por contrato.

## Retorno financeiro (hipóteses)
- **Planos:** Equipe a R$ 69 por mês; Unidades (proposta) a R$ 129 por mês mais R$ 39 por filial extra. Ver `ideias-cobranca.md`.
- **Hipótese de vendas:** 15 marcas com 2 filiais em média, no plano Unidades, dão 15 × (129 + 39) = **R$ 2.520 por mês**.
- **Custo de A:** com 26 dias a uma referência de R$ 500 por dia, **R$ 13.000**. O retorno vem em cerca de **5 meses**.
- **Custo de B:** com 45 dias, R$ 22.500. O retorno levaria cerca de 9 meses, e o risco seria maior.

## Fases de A, em ordem de ROI (uma PR cada)
| Fase | Entrega | Dias | Benefício principal | ROI |
|---|---|---|---|---|
| 1 | **Organização e unidades.** Tabela `organizations` com tipo e slug da marca; `tenants.organization_id` e `is_main`; migração com uma organização por tenant existente; seletor de unidade no topo do painel (só para `multi_unit`); criar filial (copiando as configurações da matriz, se a dona quiser); middleware `tenant` validando a unidade ativa | 6 | S1, S2, S3 | alto |
| 2 | **Link principal com select.** `GET /api/public/brand/{slug}` lista as filiais; a página principal troca o "Quem somos" pela filial do select; o agendamento abre com a filial escolhida e mostra as outras e os endereços. Tela pelo Figma "Linktree UI Kit" | 5 | S3, S5 | alto |
| 3 | **Equipe.** Domínio `Team`: barbeiro de uma filial (`staff`), convite por e-mail, papéis verificados (dona, barbeiro); `booking_requests.staff_id`; horários e lock por barbeiro; push por usuário; "qualquer um disponível" | 9 | S1, S4 | médio |
| 4 | **Comissão.** Percentual por serviço, com a opção de um valor por barbeiro, gravado ao concluir; relatório mensal por barbeiro | 3 | venda do plano Equipe | médio |
| 5 | **Relatórios consolidados da marca.** Faturamento e histórico somando as unidades, numa Action do `Orchestrator` restrita à dona; CSV por e-mail, como o do histórico | 3 | uso pela dona | médio |

**Decisões que ficam com o dono antes da fase 3:**
- sobre o que a comissão é calculada (preço cheio ou preço menos custo);
- o limite de barbeiros por plano;
- se o barbeiro vê o próprio faturamento.
