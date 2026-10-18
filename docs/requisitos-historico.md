# Requisitos — Histórico de atendimentos e painel do mês

> **Escopo:** frontend. Sem backend ainda: os dados vêm de uma **API simulada** (`frontend/src/History/history.api.ts`), que gera um histórico fixo de cerca de 2 anos (o mesmo dia mostra sempre os mesmos atendimentos). Para ligar o backend, só esse arquivo muda.
> **Rota:** `/painel/historico` (precisa de login). Abre ao tocar no card **Faturamento** do painel.

## 1. Painel principal (dashboard)

- Mostra **sempre o mês atual, do dia 1 até agora**. Não é personalizável.
- Abaixo do título "Resumo de <mês>" aparece um selo pequeno **"Mês até hoje"**. A explicação completa, "Cálculo do mês até o momento", fica no tooltip e no texto para leitores de tela.
- O cabeçalho mostra a **data do dia** (ex.: "Domingo, 18 de outubro").
- O **Faturamento** é a soma dos atendimentos do mês até agora, a mesma conta do histórico. O **Lucro** e a **Margem** usam as despesas do mês até agora (simuladas: aluguel no dia 5 e produtos às segundas).
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

## 3. Próximas etapas (backend)

- `GET /history?from=&to=&page=&perPage=` paginado no servidor, com exportação gerada pelo servidor para períodos muito grandes.
- Atendimentos concluídos pelo botão "Concluir atendimento" (PRD, módulo 4) alimentando o histórico.
- Despesas reais cadastradas pelo barbeiro (PRD, módulo 5) no lugar das simuladas.
