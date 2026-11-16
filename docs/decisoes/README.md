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
| — | (nenhuma ainda) | — | — |
