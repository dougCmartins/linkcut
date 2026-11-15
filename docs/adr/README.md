# Decisões de arquitetura (ADR)

Cada decisão estrutural do linkcut fica registrada aqui, com o motivo técnico e econômico. As análises vêm da skill
`arquiteto-software` (`.claude/skills/arquiteto-software/SKILL.md`): primeiro o ATAM (trade-offs entre atributos de
qualidade), depois o CBAM (custo, benefício e ROI) e, por fim, o ADR.

**Como escrever um ADR**
- Copie `.claude/skills/arquiteto-software/templates/adr.md` para `NNNN-titulo-em-kebab-case.md`, com o próximo número.
- Adicione uma linha na tabela abaixo.
- Um ADR aceito não se edita: uma mudança de rumo vira um ADR novo, que substitui o antigo.

| # | Decisão | Status | Análises |
|---|---|---|---|
| [0001](0001-monolito-modular-e-fronteiras.md) | Monólito modular: fronteiras entre domínios guardadas por teste | Aceito | — |
| [0002](0002-organizacao-com-filiais.md) | Barbeiro individual e barbearia com filiais: organização acima do tenant | Aceito (implementação em fases) | [ATAM](../arquitetura/contas-e-filiais-atam.md), [CBAM](../arquitetura/contas-e-filiais-cbam.md) |
| [0003](0003-visagismo-ia.md) | Visagismo com IA: análise no aparelho e contrato `FaceAnalyzer` | Proposto (provedor a decidir) | no próprio ADR |
