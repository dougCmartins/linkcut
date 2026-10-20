# Requisitos — Histórico de atendimentos e painel do mês

> **Escopo:** frontend e API (`/api/finance`). Os dados de demonstração (cerca de 2 anos de atendimentos) vêm do seeder.
> **Rota:** `/painel/historico` (precisa de login). Abre ao tocar no card **Faturamento** do painel.

## 1. Painel principal (dashboard)

- Mostra **sempre o mês atual, do dia 1 até agora**. Não é personalizável.
- Abaixo do título "Resumo de <mês>" aparece um selo pequeno **"Mês até hoje"**. A explicação completa, "Cálculo do mês até o momento", fica no tooltip e no texto para leitores de tela.
- O cabeçalho mostra a **data do dia** (ex.: "Domingo, 18 de outubro").
- O **Faturamento** é a soma dos atendimentos do mês até agora, a mesma conta do histórico. O **Lucro** e a **Margem** usam as despesas do mês até agora: fixas já vencidas, avulsas e o custo de cada atendimento concluído (ver [requisitos de despesas](requisitos-despesas.md)).
- O card de Faturamento é um link para o histórico.

## 2. Histórico

### Requisitos funcionais

| Código | Requisito |
|---|---|
| RF-H1 | Lista dos atendimentos realizados com **data, hora, cliente, serviço e valor**, do mais recente para o mais antigo, agrupada por dia (com o total e a quantidade de cada dia). |
| RF-H2 | **Filtro de período:** Semana (de segunda até hoje), Mês (do dia 1 até hoje), Ano (de 1º de janeiro até hoje) e **Personalizado** (datas "De" e "Até"). O período aberto por padrão é o Mês. |
| RF-H3 | **Período personalizado:** as duas datas são obrigatórias e "Até" precisa ser igual ou posterior a "De" (validação com Zod). Com datas inválidas, a lista e a exportação ficam bloqueadas e o erro aparece no campo. |
| RF-H4 | **Resumo do período:** faturamento, número de atendimentos e ticket médio. |
| RF-H5 | **Itens por página:** 50 (padrão), 100 ou 200, com "Anterior/Próxima" e o indicador "1–50 de 312 · Página 1 de 7". Trocar o filtro ou a quantidade volta para a página 1. |
| RF-H6 | **Exportar CSV:** baixa **todos** os atendimentos do período filtrado, não só a página atual. O arquivo se chama `historico-linkcut_<de>_a_<até>.csv`. |

### Formato do CSV

- Colunas: `Data;Hora;Cliente;Serviço;Valor (R$)`.
- Separador `;` e valor com vírgula decimal (`65,50`), o padrão do Excel em português; a coluna de valor pode ser somada direto.
- Data em `dd/mm/aaaa`, UTF-8 com BOM (acentos corretos no Excel), linhas com CRLF.
- **Proteção contra injeção de fórmula:** células que começam com `=`, `+`, `-` ou `@` recebem um apóstrofo na frente, para a planilha não executá-las.

### Requisitos não funcionais

- **Desempenho:** a lista do período pode ter milhares de linhas. Ela fica em `shallowRef` (sem reatividade profunda), e a tela só desenha a página atual.
- **Consistência:** respostas atrasadas de um filtro antigo são descartadas, então a tela nunca mostra dados de outro período.
- **Mobile-first:** 390 px, cards por dia, valores alinhados com números de largura fixa.
- **Acessibilidade:** filtros como `radiogroup`, datas em `<time datetime>`, paginação com aviso para leitor de tela.
- Textos via i18n e testes AAA para períodos, resumo, paginação, CSV, API simulada, tela do histórico e painel.

## 3. Agenda de hoje e "Concluir atendimento" (PRD, módulo 4)

| Código | Requisito |
|---|---|
| RF-A1 | O painel mostra a seção **"Agenda de hoje"**: os atendimentos de hoje já aceitos (confirmados ou concluídos), por horário, com o contador "X de Y concluídos". Sem nenhum, aparece uma mensagem explicando que é preciso aceitar uma solicitação. |
| RF-A2 | Cada atendimento confirmado tem o botão **"Concluir atendimento"** (um clique). Ele muda o status para *concluído* e envia o atendimento ao histórico com data, hora, cliente, serviço e o **preço do serviço naquele momento**. |
| RF-A3 | Logo depois, Faturamento, Lucro e Margem do painel são recalculados, e o atendimento aparece no histórico. |
| RF-A4 | Um aviso "Atendimento concluído · R$ 50,00 no faturamento" fica 5 s na tela com o botão **Desfazer**, que volta o atendimento para confirmado e o retira do histórico (proteção contra toque por engano). |
| RF-A5 | Só um atendimento **confirmado e de hoje** pode ser concluído; qualquer outro caso é recusado (falha rápida). O mesmo atendimento nunca conta duas vezes no faturamento (o id do registro é o id do agendamento). |

**Situação atual (Etapa 3):** "Concluir atendimento" chama `POST /api/schedule/requests/{id}/complete`, que grava o preço e o custo atuais do serviço no histórico. O "Desfazer" chama `POST …/reopen` e tira o lançamento.

## 4. API

- `GET /api/finance/history?from&to&page&per_page` (50, 100 ou 200): página do período, do mais novo ao mais antigo, mais os totais do período inteiro (faturamento, quantidade e ticket médio).
- `GET /api/finance/history/export?from&to`: CSV do período inteiro, gerado pelo servidor em streaming. Usa `;` como separador, vírgula decimal, BOM UTF-8 e CRLF, e neutraliza células que viram fórmula.
- Concluir e desfazer passam pelo `Orchestrator/CompleteAppointment`, que usa `RecordServiceRevenue` (idempotente pelo pedido) e `RemoveServiceRevenue`.
