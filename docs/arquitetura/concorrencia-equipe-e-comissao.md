# Concorrência: equipe, comissão e agenda por profissional

Análise feita com a skill `arquiteto-software` em 17/11/2026, para responder às perguntas do dono sobre comissão,
limite de barbeiros e o que o barbeiro vê. Decisão resultante: [`0001-filiais-e-equipe`](../decisoes/0001-filiais-e-equipe.md).

## Fontes
| Concorrente | Fonte | Como foi lida |
|---|---|---|
| **AppBarber / AppBeleza** | 5 artigos da central de ajuda, salvos em PDF pelo dono (16/11/2026): [valores bruto, líquido e descontado](https://appbarber-appbeleza.zendesk.com/hc/pt-br/articles/360001554872), [deduções](https://appbarber-appbeleza.zendesk.com/hc/pt-br/articles/360028310472), [reabrir comanda de caixa fechado](https://appbarber-appbeleza.zendesk.com/hc/pt-br/articles/360001571051), [bloquear horários](https://appbarber-appbeleza.zendesk.com/hc/pt-br/articles/360001560072), [agenda cinza](https://appbarber-appbeleza.zendesk.com/hc/pt-br/articles/360002649931) | Lido na íntegra |
| **AppBarber** (resumo do dono) | [como funcionam as comissões](https://appbarber-appbeleza.zendesk.com/hc/pt-br/articles/360021152412-Como-funcionam-as-comiss%C3%B5es), [appbarber.com.br](https://appbarber.com.br/) | Texto colado pelo dono |
| **Trinks** | [configurar desconto da operadora](https://ajuda.trinks.com/configurar-desconto-da-operadora), [comissão](https://ajuda.trinks.com/comissao), [taxa de comanda](https://ajuda.trinks.com/como-funciona-a-taxa-de-comanda), [desconto ao cliente e o profissional](https://ajuda.trinks.com/como-alterar-a-configuracao-de-desconto-do-profissional), [pagamento de profissionais](https://ajuda.trinks.com/financeiro/pagamento-de-profissionais) | Pelo resumo da busca; o site não abre neste ambiente, então os detalhes devem ser conferidos |
| **Booksy** | [taxas de comissão padrão e por profissional](https://support.booksy.com/hc/en-gb/articles/28900308157970-How-do-I-set-default-and-staff-commission-rates), [gestão de equipe](https://biz.booksy.com/features/team-management) | Pelo resumo da busca |
| **Codental** (clínicas, outro setor; serve de referência de regra) | [descontar custos e taxas da comissão](https://intercom.help/codental/pt-BR/articles/11888041-como-descontar-custos-e-taxas-do-cartao-da-comissao-por-porcentagem-no-codental) | Pelo resumo da busca |

O que não aparece nas fontes está marcado como **não confirmado**.

## Comparação
| Tema | AppBarber | Trinks | Booksy | Codental |
|---|---|---|---|---|
| **Comissão por serviço ou por profissional** | Por serviço e por profissional | Sim | Padrão, por serviço/produto e por profissional | % ou valor fixo |
| **Base da comissão** | Preço do serviço | Preço; a **taxa administrativa** é tirada antes e reduz a base | Não confirmado | Preço menos a taxa da maquininha e menos o **custo do procedimento** (opcional) |
| **Taxa do cartão** | 3 valores à escolha na hora de pagar: **bruto**, **líquido** e **descontado** | O dono escolhe: proporcional à comissão (padrão), tudo do profissional, metade ou percentual próprio | Não confirmado | Opção "descontar a taxa da maquininha" |
| **Deduções do profissional** | Tipos criados pelo dono, com um % por profissional (ex.: "Taxa de operação 5%"), escolhidos na hora de pagar | **Taxa de comanda**: valor fixo por atendimento fechado | Não confirmado | Não confirmado |
| **Desconto dado ao cliente** | Não confirmado | Configurável: sai ou não da comissão | Não confirmado | Não confirmado |
| **Mudança de regra** | Vale só para pagamentos futuros | Vale só para fechamentos novos, sem efeito retroativo | Não confirmado | Não confirmado |
| **Pagamento ao profissional** | Fora do app (Pix, dinheiro); o app calcula, gera o recibo e desconta vales | Fora do app, ou repasse automático com a maquininha própria (split) | Relatórios para pagar | Paga depois do pagamento do paciente ou depois do procedimento |
| **Quem fecha o atendimento** | A recepção ou o caixa fecha a comanda | A recepção fecha a comanda | Não confirmado | — |
| **Trava depois do fechamento** | Comanda de caixa fechado só reabre reabrindo o caixa | Não confirmado | Não confirmado | Não confirmado |
| **O que o profissional vê** | App do profissional com agenda, atendimentos e comissão acumulada | Não confirmado | Não confirmado | Consulta das próprias comissões |
| **Horário por profissional** | Fixo por dia (2 períodos), por período e por serviço; sem horário, a agenda fica cinza | Não confirmado | Sim | — |
| **Bloqueio de horário** | Horário único (horas, dia, férias) ou recorrente (ex.: toda quarta à tarde), por profissional; desbloqueio por período | Não confirmado | Não confirmado | — |
| **Vale, adiantamento e gorjeta** | Sim | Não confirmado | Não confirmado | Adiantamento |

## O que aprendemos
1. **Ninguém decide a regra pelo dono.** Os líderes deixam a dona escolher quem paga a taxa do cartão e que custos saem da comissão. O pedido do dono ("configurável, porque o funcionário usa a estrutura") é o padrão do mercado.
2. **Há dois momentos de desconto:**
   - **antes da comissão:** reduz a base (taxa administrativa, custo do serviço);
   - **depois da comissão:** sai do valor do profissional (taxa do cartão no "líquido", taxa de comanda, deduções).

   Misturar os dois é a maior fonte de dúvida dos usuários; o AppBarber dedica um artigo inteiro só a isso.
3. **Regra nova nunca muda o passado.** Os dois líderes aplicam a mudança só daqui para frente. Isso exige guardar a "foto" do cálculo em cada atendimento.
4. **O app calcula; o pagamento é por fora.** O repasse automático só existe com a maquininha do próprio app (Trinks), que fica fora do nosso escopo.
5. **Fechamento com trava.** Depois de fechado o período, um atendimento não muda sem reabrir o fechamento. Isso protege o que já foi pago.
6. **A agenda é de cada profissional.** Cada um tem horário próprio e bloqueios, únicos e recorrentes. Sem horário cadastrado, a agenda fica fechada; por isso o horário do barbeiro deve nascer copiado do horário da unidade.
7. **Diferença do linkcut:** nos concorrentes quem fecha é a recepção ou o caixa. No linkcut, **o barbeiro conclui o próprio atendimento** (decisão do dono), e a dona confere e fecha o período.

## Como fazer melhor que a concorrência
- **A mesma estrutura serve às duas regras.** Em vez de "bruto, líquido e descontado" fixos, uma **política de comissão** por unidade, com uma lista de **ajustes**. Cada ajuste diz:
  - **quando:** `before` (reduz a base) ou `after` (sai do valor do barbeiro);
  - **como:** percentual ou valor fixo;
  - **sobre o quê:** o preço, a comissão ou cada atendimento.

  Os casos de todos os concorrentes cabem nisso:
  - custo do serviço (já existe `cost_in_cents`);
  - taxa administrativa;
  - taxa de comanda;
  - deduções do AppBarber;
  - divisão da taxa do cartão (quando houver forma de pagamento).

  Funções novas viram tipos novos de ajuste, sem migração.
- **"Simulador" na tela da regra.** A dona vê "Serviço de R$ 100 → barbeiro recebe R$ 46" antes de salvar. Isso evita a dúvida que o AppBarber precisa explicar num artigo de ajuda.
- **Foto do cálculo em cada atendimento.** Ao concluir, grava a base, o percentual, cada ajuste aplicado e o valor final. O relatório é sempre explicável ("por que recebi isso?") e a regra nova não muda o passado.
- **O barbeiro vê o que a dona liberar,** com 3 chaves:
  - "ver os valores dos serviços";
  - "ver a própria comissão";
  - "ver a agenda da unidade inteira".

  Por padrão, ele vê só a própria agenda e os atendimentos que fez, sem valores.
- **Conclusão pelo barbeiro, com a conferência da dona.** O barbeiro conclui. A dona pode desfazer até o fechamento do período; depois disso, só reabrindo o fechamento.

## CBAM resumido (o que entra agora)
- **Benefício:** 0 a 100, pelo pedido do dono e pelo padrão do mercado.
- **Dias:** com testes, para quem já conhece o código.
- **ROI** = benefício ÷ dias.

| Item | Benefício | Dias | ROI | Entra? |
|---|---|---|---|---|
| Comissão % por serviço, com exceção por barbeiro | 90 | 1,5 | 60 | Sim |
| Ajustes configuráveis (antes/depois, % ou fixo) + foto do cálculo | 85 | 2 | 42 | Sim (é a estrutura base) |
| Simulador na tela da regra | 60 | 0,5 | 120 | Sim |
| Fechamento do período com trava e reabertura pela dona | 75 | 1,5 | 50 | Sim |
| Visibilidade do barbeiro com 3 chaves | 80 | 1 | 80 | Sim |
| Horário de trabalho por barbeiro (cópia da unidade) | 90 | 1,5 | 60 | Sim |
| Bloqueios único e recorrente por barbeiro | 80 | 2 | 40 | Sim |
| Forma de pagamento ao concluir + taxa do cartão por forma | 55 | 2,5 | 22 | **Depois** (pergunta aberta) |
| Comanda e caixa (abertura e fechamento diários) | 35 | 5 | 7 | Não (o linkcut não recebe pagamento) |
| Vale, adiantamento e gorjeta | 40 | 2 | 20 | Depois |
| Comissão de produto e de assinatura | 30 | 3 | 10 | Depois (a vitrine não vende; não há assinatura) |
| Limite de barbeiros por plano | — | — | — | Não agora (decisão do dono: planos em etapa própria) |

## Riscos
- **Cálculo errado de dinheiro.** Mitigação:
  - valores em centavos e inteiros;
  - arredondamento único e documentado (meio para cima, no fim);
  - testes AAA com os exemplos do AppBarber (R$ 100, 50%, 4%: R$ 50, R$ 48 e R$ 46) e da Trinks (R$ 100, 60%, 10%).
- **A dona mudar a regra no meio do mês.** Mitigação: a foto do cálculo e a regra com data de início.
- **O barbeiro concluir um atendimento que não fez.** Mitigação:
  - ele só conclui atendimentos dele, e do dia;
  - a dona vê quem concluiu e quando;
  - a dona pode desfazer até o fechamento do período.
