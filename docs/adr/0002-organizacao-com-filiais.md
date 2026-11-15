# 0002. Barbeiro individual e barbearia com filiais: organização acima do tenant

- **Status:** Aceito (implementação em 5 fases)
- **Data:** 2026-11-15
- **Decisores:** dono do produto, arquiteto (Claude)
- **Análises:** [ATAM](../arquitetura/contas-e-filiais-atam.md) e [CBAM](../arquitetura/contas-e-filiais-cbam.md)

## Contexto
Hoje "1 tenant = 1 barbeiro = 1 endereço". O produto passa a ter dois tipos de conta:
- **barbeiro individual**, que não pode notar mudança nenhuma;
- **barbearia com filiais.**

**Decisões do dono:**
- **Um painel só,** com o seletor da unidade no topo. Tudo segue a unidade escolhida, e o que muda na matriz vale só para a matriz.
- **Um link principal com select de filiais.** O select troca o "Quem somos"; "Agendar" já vem com a filial e mostra as outras e os endereços. Cada filial tem também o próprio link.
- **Cada barbeiro pertence a uma filial só.**

A ideia veio do projeto `barber-saas`. Lá não há filiais de verdade (uma `Barbershop` por usuário), então o modelo é novo.

## Decisão
1. **Unidade = tenant.**
   - A matriz e cada filial são tenants, com tudo o que um tenant já tem: configurações, slug, tema, horários, fuso, cache e isolamento pelo `TenantScope`.
   - `tenants` ganha `organization_id` e `is_main`.
2. **Organização = marca.**
   - Nova tabela `organizations`: dona, `type` (`individual` | `multi_unit`) e o slug da marca, que é o link principal.
   - O barbeiro individual é uma organização com um tenant.
   - Uma migração cria uma organização por tenant existente.
3. **Unidade ativa no painel:**
   - o frontend manda a unidade escolhida no topo;
   - o middleware `tenant` só aceita uma unidade da organização do usuário, e o barbeiro só a dele;
   - nada muda no `CurrentTenant`.
4. **Equipe** (domínio novo `Team`):
   - `staff` com `tenant_id` (uma filial);
   - papéis verificados;
   - `booking_requests.staff_id`;
   - lock e horário ocupado por barbeiro;
   - push por usuário.
5. **Visão da marca** (faturamento e histórico somando as filiais): só por uma Action do `Orchestrator` restrita à dona, que cruza tenants de propósito. Nunca desligando o escopo em outro lugar.
6. **Ordem das fases** (CBAM): organização e unidades → link principal com select → equipe → comissão → relatórios consolidados.

## Alternativas descartadas
| Alternativa | Por que não |
|---|---|
| O tenant é a marca, e `branch_id` vai para todas as tabelas | ROI 1,67 contra 3,52. Um filtro novo em toda consulta, com risco de vazamento entre filiais da mesma marca; reescreve configurações e cache |
| Um deploy ou banco por unidade | ROI 0,88. Custo de operação multiplicado (≥ R$ 150 por marca por mês); fora da VPS de 2 GB |
| Barbeiro em várias filiais | Recusado pelo dono. Exigiria checar conflito de horário entre tenants |

## Consequências
- **Técnicas:**
  - reaproveita o isolamento, o cache e as configurações por tenant;
  - o trabalho novo fica concentrado na organização, no seletor e na equipe;
  - relatórios da marca são o único ponto que cruza tenants, e por isso ficam explícitos e testados.
- **Econômicas:**
  - cerca de 26 dias;
  - nenhum custo de infra novo;
  - hipótese de retorno em cerca de 5 meses com 15 marcas no plano Unidades (ver o CBAM).
- **Riscos aceitos:**
  - "qualquer barbeiro disponível" escolhe o barbeiro dentro da transação, o que exige um teste concorrente;
  - cópia das configurações da matriz para a filial: opcional e feita uma vez só, sem sincronização automática.

## Como validar
- Teste de isolamento: um barbeiro da filial A não lê nada da filial B, e uma unidade de outra organização dá 403.
- Todos os testes atuais passam sem mudança para o barbeiro individual.
- Teste concorrente: o mesmo barbeiro no mesmo horário não aceita dois agendamentos.
- O `QueryBudgetTest` da página principal com o select.
