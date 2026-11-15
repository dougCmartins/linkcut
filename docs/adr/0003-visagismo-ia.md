# 0003. Visagismo com IA

- **Status:** Proposto (falta o dono escolher o provedor, se houver)
- **Data:** 2026-11-15
- **Decisores:** dono do produto, arquiteto (Claude)

## Contexto
O cliente quer saber qual corte combina com o rosto dele. O `barber-saas` tem as telas e as regras de negócio:
- uma análise por cliente por dia;
- IA ou análise manual;
- ciclo de vida com soft delete.

Mas a "IA" de lá é de mentira (`sleep(2)` com resultados fixos no código), e as sugestões não estão ligadas aos
serviços reais. Uma foto do rosto e o formato do rosto são **dado biométrico**, dado **sensível** pela LGPD
(art. 5º, II, e art. 11): exigem consentimento específico e destacado, e o mínimo de retenção.

## ATAM (resumo)
**Cenários:**
| # | Cenário |
|---|---|
| V1 | A foto nunca é guardada |
| V2 | O resultado sai em menos de 5 s num celular médio |
| V3 | As sugestões são só serviços que a barbearia oferece, com "Agendar" direto |
| V4 | O custo por análise cabe no plano |
| V5 | Trocar de provedor não muda o domínio |

**Abordagens:**
- **A — Questionário manual:** o cliente escolhe o formato do rosto, com desenhos.
- **B — Análise no aparelho:** os pontos do rosto são detectados no navegador (MediaPipe Face Landmarker, WASM) e só as medidas vão para o servidor, que classifica o formato.
- **C — IA de visão externa:** a foto vai para a fila e para um provedor.

**Avaliação:**
| Abordagem | V1 | V2 | V3 | V4 | V5 | Observação |
|---|---|---|---|---|---|---|
| A | ok | ok | ok | ok | ok | Pouco "uau" |
| B | ok (a foto não sai do aparelho) | ok | ok | ok (R$ 0) | ok | Precisa calibrar as regras |
| C | arriscado (a foto sai) | depende do provedor | ok | custo por chamada | ok | Precisa de contrato de tratamento de dados |

## CBAM (resumo)
| Abordagem | Dias | Custo por análise | Benefício (0–100) | ROI |
|---|---|---|---|---|
| A | 4 | R$ 0 | 55 | 13,8 |
| **B + A como plano B** | 9 | R$ 0 | 85 | **9,4** |
| C | 8 | ~R$ 0,05–0,30 + DPA | 80 | 10,0 (sem contar o risco de LGPD) |

- **A tem o maior ROI**, e o risco é baixo: entra primeiro (fase 1), em poucos dias.
- **B dá o maior benefício**, com custo por análise zero e a foto sem sair do aparelho: entra como fase 2, mantendo A como alternativa.
- **C empata com B no ROI bruto**, mas perde no V1 e no risco legal. Fica fora, a menos que um ADR novo o traga.

## Decisão (proposta)
1. **Domínio novo `Visagism`,** com modelos `BelongsToTenant`:
   - `visagism_analyses`: cliente, formato do rosto, origem (`device` | `manual`) e sugestões;
   - **nunca a foto.**
2. **Fases:**
   - **Fase 1 (A, ~4 dias):** questionário manual com desenhos; já entrega as sugestões ligadas aos serviços e o "Agendar".
   - **Fase 2 (B, ~5 dias a mais):** análise no aparelho, que preenche o questionário sozinha. O cliente confirma ou corrige.
3. **Contrato `FaceAnalyzer`** (interface no domínio), que recebe as medidas do rosto e devolve o formato.
   - Adaptador 1: regras próprias sobre as medidas da opção B.
   - Adaptador falso: para os testes.
   - Um provedor externo (C) só entra como outro adaptador, por um ADR novo, se o dono quiser.
4. **Fluxo no `Orchestrator/Visagism`:**
   1. consentimento específico;
   2. medidas vindas do aparelho;
   3. o `FaceAnalyzer` classifica o formato;
   4. as sugestões vêm dos serviços da barbearia (`ShowBookingOptions`);
   5. "Agendar" já abre com o serviço.
5. **Regras herdadas do `barber-saas`:**
   - uma análise por cliente por dia;
   - escolher entre manual e automática;
   - incluir o resultado na exportação e na exclusão de dados (LGPD).
6. **Depois do visagismo:** a sugestão de produtos com IA (`proximos-passos.md`, item 2.3) usa o resultado, pelo `Orchestrator`.

## Consequências
- **Técnicas:**
  - nenhuma foto no servidor;
  - um módulo isolado, atrás de uma interface;
  - o MediaPipe só é carregado na tela do visagismo (chunk próprio).
- **Econômicas:**
  - cerca de 9 dias;
  - custo por análise zero;
  - serve de atrativo do plano Pro ou Equipe.
- **Riscos:**
  - a precisão das regras de formato do rosto, mitigada pela opção manual e pela correção pelo barbeiro;
  - o aviso de consentimento precisa de revisão jurídica.

## O que falta o dono decidir
- Usar só a análise no aparelho, que é a recomendada, ou também um provedor externo.
- Em qual plano entra.
