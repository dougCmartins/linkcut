# 🤖 Instruções para o Assistente Claude

Este repositório utiliza padrões estritos de engenharia limpa e arquitetura orientada a domínio. Ao ler, gerar ou refatorar código neste projeto, deves seguir obrigatoriamente as diretrizes abaixo:

## ⚡ Regras de Ouro (Core Rules)
1. **Idioma do Código:** Todo o código-fonte (variáveis, funções, classes, métodos, tabelas, colunas, logs e exceções) **deve ser estritamente em Inglês**. Textos de UI devem usar i18n. Documentação e PRs podem ser em Português.
2. **KISS & Simplicidade:** Prefira sempre a solução mais óbvia, limpa e modular. Evite *over-engineering* ou acoplamentos desnecessários.
3. **Separação de Responsabilidades (SoC):** Respeite os limites de domínio, utilize DTOs para transferência de dados entre camadas e o **Envelope Pattern** para respostas de API.
4. **Testes Automatizados:** Todo código crítico deve possuir testes baseados no padrão **AAA (Arrange, Act, Assert)**.
5. **Documentação de Apoio:** Consulte os ficheiros detalhados na pasta `.cursor/rules/` e o `GUIA_ENGENHARIA_LIMPA.md` para orientações específicas da arquitetura.

## 💸 Economia de contexto
- Uma tarefa por sessão (`/clear` entre tarefas). Para continuar depois, escreva um handoff curto e comece a nova sessão lendo só ele.
- Designs do Figma: delegue ao subagente `lendo-figma` e leia apenas `docs/design/<nome>.md`. Nunca traga respostas brutas do MCP para a sessão principal.
- Buscas no código: delegue ao `explorador`. Revisão final: `revisor`.
- Leia as regras de `.cursor/rules/` só quando relevantes à tarefa, não todas.
- Loops de correção: máximo de 3 iterações, com teste ou lint como critério de parada.
