# Decisões para executar

Esta pasta é a passagem entre os dois chats do linkcut.

- **Chat do arquiteto:** conversa com o dono, decide e escreve aqui o que foi decidido e como aplicar. Usa a skill `arquiteto-software`.
- **Chat executor:** lê esta pasta, confere o que já está no código, implementa o que falta e devolve o feedback no próprio arquivo. Usa a skill `executar-decisoes`.

O executor não decide nada que não esteja escrito aqui. Se faltar informação, ele para naquele passo e pergunta.

## Como escrever uma decisão (chat do arquiteto)
1. Copie `_modelo.md` para `NNNN-titulo-em-kebab-case.md`, com o próximo número livre.
2. Preencha as seções 1 a 6. Em "Como aplicar", cada passo precisa de um critério de aceite.
3. Adicione uma linha na tabela abaixo, com status `Decidido`.
4. Faça commit na `main` (ou numa PR) para o executor enxergar.
5. Uma mudança de rumo vira um arquivo novo, que substitui o antigo. Ajuste a decisão antiga só enquanto ela estiver `Decidido`; depois disso, não.

Decisões de arquitetura grandes continuam virando ADR em `docs/adr/`. O arquivo daqui aponta para o ADR e detalha a execução.

## Status
| Status | Significado |
|---|---|
| `Decidido` | Pronto para executar |
| `Em execução` | O executor aplicou parte dos passos |
| `Bloqueado` | O executor parou por uma pergunta aberta (ver a seção "Execução" do arquivo) |
| `Aplicado` | Todos os passos feitos e verificados |
| `Substituído por NNNN` | Uma decisão mais nova tomou o lugar desta |

## Decisões
| # | Decisão | Status | Branch / PR |
|---|---|---|---|
| [0001](0001-filiais-e-equipe.md) | Filiais e equipe: estrutura completa (organização, unidades, equipe, agenda por barbeiro e comissão) | Em execução | `claude/practical-wozniak-auyi2x` (P1–P6 pelo Cursor, não revisado; P4 frontend pendente) |
| [0002](0002-filiais-e-equipe-telas.md) | Filiais e equipe de ponta a ponta: telas que faltam (P4 frontend e "Nova filial") | Aplicado | `claude/decision-0002-branch-screens`, mergeado na `main` em 25/11 (sem PR) |
| [0003](0003-transferencia-e-emprestimo-de-barbeiro.md) | Transferência e empréstimo de barbeiro entre filiais (horário novo no destino, estender, encerrar, tornar definitivo) | Em execução (P1 feito) | `claude/charming-euler-dh3nfa` |
