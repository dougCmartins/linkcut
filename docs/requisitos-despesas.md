# Requisitos — Despesas, custo por procedimento e margem de lucro

PRD, módulo 5: mostrar ao barbeiro **quanto ele realmente lucra**, não só quanto fatura. A margem só é real se as despesas forem reais. Por isso o barbeiro cadastra os custos dele, e o custo de cada atendimento entra sozinho.

## 1. Como a conta é feita

```
Despesas do mês = fixas já vencidas + avulsas do mês + custos dos atendimentos concluídos
Lucro           = Faturamento − Despesas do mês
Margem (%)      = Lucro ÷ Faturamento × 100
```

- Período: **do dia 1 até hoje** (o mesmo "Mês até hoje" do painel).
- É a **margem líquida**, porque desconta todas as despesas, não só o custo do serviço.
- Sem faturamento no mês, a margem é 0%.
- Abaixo de **25%** o card mostra o alerta "Abaixo de 25%"; acima disso, "Saudável".

## 2. Custo por procedimento (Configurações → Agenda → Serviços)

| Código | Requisito |
|---|---|
| RF-D1 | Cada serviço tem o campo opcional **"Custo por atendimento (R$)"**: valor fixo de material e produtos gastos (ex.: Corte R$ 50,00, custo R$ 6,00). |
| RF-D2 | O custo pode ser zero e precisa ser **menor que o preço**. Se não for, o campo mostra o erro e as configurações não salvam. |
| RF-D3 | Abaixo do campo aparece quanto sobra: "Sobram R$ 44,00 (88%) por atendimento". |
| RF-D4 | Ao **concluir um atendimento**, preço e custo são gravados no histórico naquele momento (*snapshot*). Mudar o custo depois não altera os meses que já passaram. |

## 3. Tela de despesas (`/painel/despesas`, protegida)

| Código | Requisito |
|---|---|
| RF-D5 | Resumo "Despesas do mês até hoje" com o total e a divisão: fixas já vencidas, avulsas do mês e custos dos atendimentos, mais um link para editar os custos dos serviços. |
| RF-D6 | **Fixas mensais** (aluguel da cadeira, internet, DAS…): nome, valor e dia do vencimento (1–31). Cada uma entra **inteira no dia do vencimento** (regime de caixa). Antes disso aparece como "Entra no dia 20" e não é somada. No mês com menos dias, o dia 31 vira o último dia do mês. |
| RF-D7 | **Avulsas:** descrição, valor e data. A data não pode ser futura, porque só entra o que já foi pago. A lista mostra as do mês atual. |
| RF-D8 | Remover uma despesa mostra por 5 s o aviso com **Desfazer**. |
| RF-D9 | No primeiro acesso, a tela já vem com exemplos editáveis (aluguel R$ 1.500 no dia 5, internet, DAS, lâminas e toalhas), para a margem não começar irreal em 100%. |
| RF-D10 | O painel ganha o card **Despesas**, que abre esta tela. |

Validação com Zod (`Expenses/expenses.schemas.ts`):
- nome de 1 a 40 caracteres;
- valor inteiro em centavos, maior que zero e até R$ 100.000,00;
- dia entre 1 e 31;
- data até hoje.

## 4. Tooltip da margem (painel)

O ícone "i" ao lado de **Margem** explica a conta com os números do barbeiro:
- a fórmula "Margem = Lucro ÷ Faturamento × 100";
- Faturamento − despesas fixas − avulsas − custos dos atendimentos = Lucro;
- a conta com os valores reais, ex.: "R$ 3.152,00 ÷ R$ 5.835,00 × 100 = 54,0%";
- "De cada R$ 100 que entram, sobram R$ 54,00 para você" (ou um aviso de prejuízo);
- a referência de 25% para barbearias;
- um aviso quando nenhuma despesa entrou no mês;
- a diferença entre margem (sobre o preço, nunca passa de 100%) e markup (sobre o custo).

**Comportamento** (`components/ui/InfoTooltip.vue`, reutilizável):
- abre ao passar o mouse, com o foco do teclado e com um toque (celular não tem hover);
- fecha com `Esc`, com um toque fora, ou ao tirar o mouse (quando foi aberto por hover);
- `role="tooltip"` ligado ao botão por `aria-describedby`;
- o painel se ajusta para ficar dentro da tela (margem de 16 px).

## 5. Próximas etapas (backend)

- `GET/POST/DELETE /expenses` no lugar do `localStorage`, e `GET /finances/month-to-date` com o resumo pronto.
- O custo do serviço é dado **privado** do barbeiro: a API pública de serviços (página de agendamento) não pode devolvê-lo.
- Taxa da maquininha (%) por forma de pagamento.
