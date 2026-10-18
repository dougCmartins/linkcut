---
alwaysApply: false
---

# ⚠️ Armadilhas Comuns (Pitfalls)

## 1. A Teoria da Janela Partida
- **Tolerância Zero:** Nunca deixe "janelas partidas" (código mal formatado, decisões temporárias perigosas, *gambiarras*) sem correção. Corrija o código deteriorado assim que o encontrar para não criar um ambiente permissivo à má qualidade técnica.

## 2. Padrões a Evitar (Anti-Patterns)
- **God Classes / Código Esparguete:** Classes gigantes que assumem responsabilidades sobre dezenas de tabelas e fluxos. Divida o código em serviços mais pequenos e coesos.
- **Copy-Paste Impaciente:** A pressa gera dívida técnica. Se um bloco de código precisa de ser copiado mais de duas vezes, transforme-o numa função reutilizável ou componente.
- **Over-Engineering:** Não crie abstrações complexas (camadas excessivas, interfaces não utilizadas) para problemas que ainda não existem.
- **Domínio por tabela e camada morta:** Não abra um domínio porque a tabela existe, nem deixe `Repositories`, Form Request ou model em `app/Models` ao lado do domínio novo. O que não tem `use` vivo apaga-se.