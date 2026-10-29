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
| **Equipe: barbeiros funcionários, agenda por barbeiro e comissão** (ver `docs/proximos-passos.md`, seção 5) | barbearias com equipe pagam mais | nenhum | grande |
| **Modo secretária e fidelidade** | recepção e retenção de clientes em barbearias maiores | nenhum | médio cada |
| **Cartão fidelidade** ("10 cortes = 1 grátis") | o cliente volta | nenhum | médio |
| **Pacotes ou clube de assinatura** ("4 cortes por mês por R$ 120") | receita recorrente para o barbeiro | gateway | médio/grande |
| **Relatórios do financeiro**: lucro por serviço, clientes que sumiram, exportação | decisão de negócio | nenhum | médio |
| **Página sem a marca "linkcut"** e **domínio próprio** | profissionalismo | baixo | pequeno/médio |
| **Vitrine sem limite** (o grátis tem até 50 produtos) | quem vende muito produto na cadeira | baixo (fotos no disco) | pequeno: o limite já existe (`PRODUCTS_LIMIT`) |
| **Categorias na vitrine** (cabelo, barba, acessórios) | organizar uma vitrine grande | nenhum | médio: telas de categoria, ordem e agrupamento na página |
| Lembrete por WhatsApp automático | o cliente lê mais o WhatsApp | API oficial é cara: **fora por enquanto** | — |

## Proposta de planos (a validar com barbeiros)
| | **Grátis** | **Pro** (R$ 29–39/mês) | **Equipe** (R$ 59–79/mês) |
|---|---|---|---|
| Página, agenda e financeiro básico | ✅ | ✅ | ✅ |
| Lembrete por e-mail e área do cliente | ✅ | ✅ | ✅ |
| Agendamentos por mês | até 60 | ilimitado | ilimitado |
| Produtos na vitrine | até 50 | ilimitado | ilimitado |
| Categorias na vitrine | — | ✅ | ✅ |
| Links livres na página | sem limite por enquanto (decidir: limitar no grátis?) | ilimitado | ilimitado |
| Sinal via Pix, relatórios, sem a marca linkcut | — | ✅ | ✅ |
| Barbeiros da equipe, agenda por barbeiro e comissão | — | — | ✅ |
| Modo secretária e fidelidade | — | — | plano acima do Equipe (futuro, mais caro) |

**Cobrança:** assinatura mensal via **Asaas** (Pix, boleto e cartão, com recorrência) ou Mercado Pago. O mesmo gateway serve para a assinatura do barbeiro e para o sinal Pix dos clientes.

## Ordem sugerida
1. **Planos e limites:** tabela de planos, limite de 60 por mês no grátis, tela "Meu plano" e período de teste do Pro.
2. **Assinatura via Asaas:** o barbeiro paga o Pro.
3. **Sinal via Pix:** o recurso Pro com mais apelo.
4. **Equipe:** barbeiros funcionários, agenda por barbeiro e comissão.
5. Depois, num plano mais caro: modo secretária, fidelidade, várias unidades e pacotes ou assinatura de clientes.

**Antes de começar, conversar com 3 a 5 barbeiros:**
- o preço;
- o limite de 60 agendamentos por mês;
- se o sinal via Pix é o que mais os faz pagar.

### Vitrine: decisões já tomadas
- **Hoje:** limite total de 50 produtos por barbearia, numa lista única, sem paginação e sem categorias. Uma barbearia típica tem de 5 a 20 produtos.
- **Por que não limite por categoria:** criando várias categorias, o barbeiro contorna o limite. Só valeria limitando também as categorias: duas regras para explicar.
- **Quando houver planos:** "50 no grátis, ilimitado no pago" é só trocar a regra do `PRODUCTS_LIMIT`.
- **Se o limite sumir:**
  - no painel, paginação por cursor de 50 em 50, com "Carregar mais" e busca por nome;
  - na página pública, os primeiros 6 a 8 produtos e um "Ver todos", sem rolagem infinita, para o rodapé continuar acessível.
- **Categorias:** entram quando houver barbearias com vitrines grandes, como recurso do plano pago.

## Outras ideias para a área do cliente (ainda não feitas)
- **Remarcar:** escolher outro horário em vez de cancelar.
- ~~**Avaliação após o atendimento**~~: feita (#48). O barbeiro escolhe se mostra a média e os destaques.
- ~~**Push no celular do cliente**~~: feito (#43).

## Fora de cogitação (decisões já tomadas)
- SMS: sem verba.
- WhatsApp pela API oficial: cara.
- Chat dentro do app.
- Marketplace de barbearias, isto é, o cliente procurando barbearias perto dele dentro do app. O linkcut é gestão: o cliente chega pelo Instagram ou pela internet, e as barbearias que ele usa aparecem em "Minhas barbearias".
