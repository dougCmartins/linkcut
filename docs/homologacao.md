# Homologação final (antes do lançamento)

Roteiro para testar o linkcut como um barbeiro e um cliente reais, **num celular**, antes de abrir para o público.

**Como usar**
- Siga na ordem. Cada passo tem o que fazer e o que deve acontecer.
- Se algo sair diferente, anote na tabela do fim: o passo, o que aconteceu, o celular e o navegador, e uma captura.
- Os achados viram correções, uma PR por bloco.

**Antes de começar**
- Use dois aparelhos: o do **barbeiro** (de preferência com o app instalado na tela inicial) e o do **cliente** (de preferência abrindo o link pelo Instagram).
- Se der, rode com o domínio e https de produção. Em local, use `make up` e o IP mostrado por `make ip`.
- Tenha à mão um e-mail real para o barbeiro e outro para o cliente.

## 1. Barbeiro: cadastro e página
| # | Fazer | Esperado |
|---|---|---|
| 1.1 | Abrir `/` e criar a conta | O código chega por e-mail; ao confirmar, abre o assistente de boas-vindas |
| 1.2 | Preencher WhatsApp, endereço (pelo CEP) e serviços | O assistente termina e abre o `/painel` |
| 1.3 | Em Configurações, enviar foto, capa, bio, redes e um link livre | "Alterações salvas" e a prévia ao lado já mostra tudo |
| 1.4 | Escolher outro tema e salvar | A prévia e a página pública mudam de cor |
| 1.5 | Abrir "Ver minha página" | A página abre na mesma aba, com o tema escolhido e o link livre |
| 1.6 | Ativar o aviso de push no painel | O navegador pede permissão e o painel mostra que está ativo |
| 1.7 | Mandar o link da página para alguém pelo WhatsApp | A prévia mostra a foto e o nome da barbearia |

## 2. Cliente: agendamento
| # | Fazer | Esperado |
|---|---|---|
| 2.1 | Abrir o link da barbearia pelo Instagram (navegador interno) | A página abre com o tema da barbearia |
| 2.2 | Tocar em "Agendar", escolher 2 serviços, o dia e o horário | O tempo e o valor somam; só aparecem horários livres |
| 2.3 | Preencher nome, WhatsApp e e-mail; apertar "Ir" no teclado | O teclado passa para o próximo campo e **não** envia |
| 2.4 | Escrever uma observação e tocar em "Solicitar Agendamento" | Pergunta sobre a localização; depois, "Solicitação enviada!" |
| 2.5 | Tocar no "voltar" do celular | Fecha o agendamento sem sair da página (nem do Instagram) |
| 2.6 | Conferir o e-mail do cliente | Chegou "Recebemos seu pedido", com a marca da barbearia |

## 3. Barbeiro: responder
| # | Fazer | Esperado |
|---|---|---|
| 3.1 | Esperar no aparelho do barbeiro | Chega o push "Novo pedido de agendamento" |
| 3.2 | Com o `/painel` aberto | O pedido aparece em "Solicitações Pendentes" sozinho, em até 30 s, sem F5 |
| 3.3 | Tocar em "Aceitar" | O pedido vai para "Agenda de hoje" ou "Próximos atendimentos" |
| 3.4 | Conferir o e-mail e o push do cliente | Chegou "Seu horário foi confirmado" |
| 3.5 | Fazer outro agendamento e "Recusar" | O cliente recebe o aviso de recusa |

## 4. Cliente: conta
| # | Fazer | Esperado |
|---|---|---|
| 4.1 | Abrir o link "Criar minha senha" do e-mail | Pede o código do e-mail e a senha; entra na área do cliente |
| 4.2 | Ver "Meus horários" | O horário aparece como confirmado, com "Como chegar" e "Adicionar à agenda" |
| 4.3 | Sair e entrar de novo em `/{barbearia}/login` | O login usa o tema da barbearia |
| 4.4 | Cancelar um horário futuro | Some dos próximos; o barbeiro recebe o aviso e o horário volta a ficar livre |

## 5. Atendimento, avaliação e financeiro
| # | Fazer | Esperado |
|---|---|---|
| 5.1 | Na véspera do horário (ou mudando a data no teste) | Chega o lembrete por e-mail e push |
| 5.2 | Barbeiro conclui o atendimento em "Agenda de hoje" | O faturamento do mês sobe; "Desfazer" funciona por alguns segundos |
| 5.3 | Cliente avalia em "Meus horários" (nota e comentário) | A avaliação aparece no painel do barbeiro |
| 5.4 | Barbeiro liga "mostrar média" e destaca o comentário | A média aparece no link e o comentário no "Sobre" |
| 5.5 | Barbeiro cadastra uma despesa fixa e uma avulsa | O lucro e a margem do mês mudam |
| 5.6 | Abrir o Histórico e exportar o CSV | O arquivo abre no Excel ou no Google Planilhas com acentos certos |

## 6. Privacidade e conta
| # | Fazer | Esperado |
|---|---|---|
| 6.1 | Abrir a página pela primeira vez num navegador limpo | Aparece o aviso de privacidade; "Rejeitar opcionais" funciona |
| 6.2 | Cliente: "Baixar meus dados" | Baixa um arquivo com os dados e os horários |
| 6.3 | Cliente: excluir a conta | Pede a senha (ou nada, se for só Google) e sai; o horário fica anônimo para o barbeiro |
| 6.4 | Barbeiro: trocar a senha | As outras sessões saem |

## 7. Celular e app instalado
| # | Fazer | Esperado |
|---|---|---|
| 7.1 | Instalar o app do painel na tela inicial (Android e iPhone) | Abre em tela cheia e entra direto no painel |
| 7.2 | iPhone: ativar o push pelo app instalado | O push chega com a tela bloqueada |
| 7.3 | Girar o celular e usar o modo escuro do aparelho | Nada fica cortado ou ilegível |

## Achados
| Passo | O que aconteceu | Celular / navegador | Captura |
|---|---|---|---|
| | | | |
