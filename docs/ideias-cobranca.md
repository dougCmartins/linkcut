# Ideias guardadas: cobrança e próximos recursos

> **Status:** guardado para depois. Nada disto está implementado.
>
> Primeiro veio a área do cliente (Etapa 7). Voltamos a este documento para decidir como cobrar.

## Princípio
- **O grátis continua bom** para atrair barbeiros: página, agenda, financeiro básico, lembrete por e-mail e área do cliente.
- **O pago** é o que faz o barbeiro ganhar dinheiro ou deixar de perder.

## Recursos que podem ser pagos
| Recurso | Por que alguém pagaria | Custo para nós | Esforço |
|---|---|---|---|
| **Sinal via Pix no agendamento** (ex.: R$ 10, abatido no corte) | acaba com a falta sem aviso, a maior dor | gateway (Asaas ou Mercado Pago, ~1% por Pix) | grande |
| **Mais de um barbeiro na barbearia** (agenda por profissional) | barbearias com equipe pagam mais | nenhum | grande |
| **Cartão fidelidade** ("10 cortes = 1 grátis") | o cliente volta | nenhum | médio |
| **Pacotes ou clube de assinatura** ("4 cortes por mês por R$ 120") | receita recorrente para o barbeiro | gateway | médio/grande |
| **Relatórios do financeiro**: lucro por serviço, clientes que sumiram, exportação | decisão de negócio | nenhum | médio |
| **Página sem a marca "linkcut"** e **domínio próprio** | profissionalismo | baixo | pequeno/médio |
| Lembrete por WhatsApp automático | o cliente lê mais o WhatsApp | API oficial é cara: **fora por enquanto** | — |

## Proposta de planos (a validar com barbeiros)
| | **Grátis** | **Pro** (R$ 29–39/mês) | **Equipe** (R$ 59–79/mês) |
|---|---|---|---|
| Página, agenda e financeiro básico | ✅ | ✅ | ✅ |
| Lembrete por e-mail e área do cliente | ✅ | ✅ | ✅ |
| Agendamentos por mês | até 60 | ilimitado | ilimitado |
| Sinal via Pix, fidelidade, relatórios, sem a marca linkcut | — | ✅ | ✅ |
| Vários barbeiros | — | — | ✅ |

**Cobrança:** assinatura mensal via **Asaas** (Pix, boleto e cartão, com recorrência) ou Mercado Pago. O mesmo gateway serve para a assinatura do barbeiro e para o sinal Pix dos clientes.

## Ordem sugerida
1. **Planos e limites:** tabela de planos, limite de 60 por mês no grátis, tela "Meu plano" e período de teste do Pro.
2. **Assinatura via Asaas:** o barbeiro paga o Pro.
3. **Sinal via Pix:** o recurso Pro com mais apelo.
4. Depois: fidelidade e pacotes, vários barbeiros.

**Antes de começar, conversar com 3 a 5 barbeiros:**
- o preço;
- o limite de 60 agendamentos por mês;
- se o sinal via Pix é o que mais os faz pagar.

## Outras ideias para a área do cliente (ainda não feitas)
- **Remarcar:** escolher outro horário em vez de cancelar.
- **Avaliação após o atendimento:** nota e comentário na página da barbearia; precisa de moderação.
- **Push no celular do cliente:** lembrete sem e-mail. O push já existe para o barbeiro.

## Fora de cogitação (decisões já tomadas)
- SMS: sem verba.
- WhatsApp pela API oficial: cara.
- Chat dentro do app.
- Marketplace de barbearias.
