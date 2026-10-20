# Requisitos: área do cliente e lembretes

> **Por quê:** antes, o cliente agendava e não sabia mais nada: se o barbeiro aceitou, quando era o horário, onde ficava. O barbeiro tinha que avisar todo mundo na mão. Agora o linkcut avisa o cliente sozinho, por e-mail, e o cliente tem um lugar para ver os horários e agendar de novo.

## 1. Regras

### Agendamento
| ID | Regra |
|---|---|
| RF-C1 | O formulário de agendamento pede **nome, WhatsApp e e-mail** (todos obrigatórios). |
| RF-C2 | O e-mail identifica o cliente: o primeiro agendamento cria a conta dele, ainda **sem senha**. Os próximos, com o mesmo e-mail (maiúsculas ou minúsculas), entram na mesma conta. O formulário nunca altera os dados de uma conta que já existe. |
| RF-C3 | Depois de "Solicitação enviada!", aparece o cartão **"Finalize seu cadastro"**, que leva a `/conta/finalizar` com o e-mail preenchido. O cartão não aparece para quem já está logado na área do cliente. |
| RF-C4 | Logado na área do cliente, o formulário já vem com nome, WhatsApp e e-mail preenchidos. |

### E-mails automáticos ao cliente
| ID | E-mail | Quando |
|---|---|---|
| RF-C5 | **Recebemos seu pedido** | Ao agendar. Leva o botão "Finalizar cadastro" enquanto o cliente não tem senha. |
| RF-C6 | **Horário confirmado** | Quando o barbeiro aceita. Leva o endereço, "Como chegar" e o WhatsApp da barbearia. |
| RF-C7 | **Pedido não confirmado** | Quando o barbeiro recusa. Leva o botão "Escolher outro horário" (página da barbearia). |
| RF-C8 | **Lembrete** | Na véspera: horários confirmados que começam **entre 20 e 28 horas** depois, no fuso da barbearia. Vai **uma vez só** por horário. |
| RF-C9 | **Convite para o cadastro** | **Uma vez só**, para quem agendou há mais de 2 horas e não criou senha. |

- Todos os e-mails vão **em fila**, depois de o pedido ser salvo: o cliente nunca espera o envio.
- Pedidos antigos, feitos antes de o e-mail ser pedido, não recebem nada.

### Parar de receber
| ID | Regra |
|---|---|
| RF-C10 | Os e-mails de lembrete e de convite trazem o link **"Não quer mais receber lembretes?"**. É um link assinado: funciona sem login e não pode ser alterado para outra pessoa. |
| RF-C11 | Depois disso, o cliente não recebe mais lembretes nem convites. As respostas aos pedidos (recebido, confirmado, recusado) e os códigos continuam chegando, porque ele pediu por eles. |

### Cadastro e login (`/conta`)
| ID | Regra |
|---|---|
| RF-C12 | **Finalizar cadastro:** o cliente pede um código de 6 números no e-mail, digita o código e cria a senha (mínimo 8 caracteres). O código prova que o e-mail é dele: ninguém cria a senha da conta de outra pessoa. |
| RF-C13 | **Entrar:** e-mail e senha. "Esqueci minha senha" envia um código e o cliente cria uma senha nova. A senha nova encerra as outras sessões. |
| RF-C14 | **Limites do código:** vale 15 minutos, aceita 5 tentativas e só pode ser reenviado depois de 30 segundos. A resposta ao pedir um código é sempre a mesma, exista a conta ou não (ninguém descobre quem é cliente). |
| RF-C15 | A sessão do cliente é **separada** da do barbeiro: outro tipo de token, outra chave no navegador. O token de um nunca abre as rotas do outro. |
| RF-C16 | A sessão dura 30 dias (renovada sozinha), para o cliente voltar semanas depois e agendar de novo. |

### Meus horários
| ID | Regra |
|---|---|
| RF-C17 | `/conta` lista os horários do cliente em **todas as barbearias onde ele agendou**: "Próximos" (aguardando ou confirmados, do mais perto ao mais longe) e "Anteriores" (concluídos, recusados ou passados). |
| RF-C18 | Os próximos mostram o endereço, "Como chegar" e "Falar no WhatsApp". Os anteriores mostram **"Agendar de novo"**, que abre o agendamento daquela barbearia. |
| RF-C19 | **Sem marketplace:** o cliente só vê as barbearias onde já agendou. Se ele agendar em outra barbearia que usa o linkcut, ela passa a aparecer. Cada barbeiro continua vendo só os próprios pedidos. |

### Barbeiro
| ID | Regra |
|---|---|
| RF-C20 | Na "Agenda de hoje", cada horário ainda não concluído tem o botão **"Lembrar no WhatsApp"**. Ele abre o WhatsApp do barbeiro com a mensagem pronta: "Oi, Ana! Passando para lembrar do seu horário hoje às 18:30 na Rafael Costa. Endereço: …". O barbeiro só toca em enviar. |

## 2. Decisões

- **Automático só por e-mail.** A API oficial do WhatsApp é paga por conversa e **não será usada por enquanto**. Bots não oficiais do WhatsApp são bloqueados pelo próprio WhatsApp, então também não são opção.
- **O botão `wa.me`** não tem custo: abre o WhatsApp do próprio barbeiro com o texto preenchido.
- **O código de login do barbeiro não mudou:** continua por e-mail por padrão. O SMS fica para a Etapa 6.

## 3. Como funciona

- **Backend:**
  - domínio novo `Client` (global, sem `tenant_id`, como os usuários): contas, códigos, sessões;
  - guard Passport `client` com o próprio client de acesso pessoal (`passport:ensure-personal-client` cria os dois);
  - os e-mails ficam no domínio `Notification` (Laravel Notifications, em fila e cifradas). Os textos estão em `lang/pt_BR/client_mail.php`.
- **Orquestradores:**
  - `PublicBooking` liga o pedido ao cliente;
  - `AnswerBookingRequest` responde ao pedido e envia o e-mail;
  - `ClientEmails` cuida dos lembretes e dos convites;
  - `ClientAppointments` monta a lista de "Meus horários";
  - `Barbershop` monta o nome, o endereço, o mapa, o WhatsApp e a página da barbearia.
- **Tarefas agendadas** (serviço `scheduler` do Docker, de hora em hora):
  - `php artisan schedule:send-reminders`
  - `php artisan client:invite-registration`
- **Links nos e-mails:** apontam para a SPA (`FRONTEND_URL`). O link para parar de receber aponta para a API, que redireciona para `/conta/lembretes-desativados`.
- **SPA:**
  - `src/Domain/Client`: api, store e guard, com a sessão em `localStorage`;
  - o cliente HTTP aceita `auth: 'client'` para usar o token do cliente.

## 4. API

| Método e rota | Faz |
|---|---|
| `POST /api/public/{slug}/booking-requests` | Agora exige `client_email` |
| `POST /api/client/codes` | Envia o código (`purpose`: `registration` ou `password`) |
| `POST /api/client/registration` | Código + senha: finaliza o cadastro e abre a sessão |
| `POST /api/client/password` | Código + senha nova: troca a senha e abre a sessão |
| `POST /api/client/login` | Entrar |
| `POST /api/client/token/refresh` | Renova a sessão (troca o refresh token; reuso derruba a família) |
| `POST /api/client/logout` | Sair |
| `GET /api/client/me` | Dados do cliente |
| `GET /api/client/appointments` | Horários do cliente em todas as barbearias, com a barbearia |
| `GET /api/client/emails/opt-out/{client}` | Link assinado do e-mail: para os lembretes e convites |

## 5. Produção

- **Enviar e-mail exige um provedor SMTP.** Exemplos com plano gratuito: Amazon SES, Brevo e Resend. Configure `MAIL_*` no `.env`. Use um domínio de remetente verificado (SPF/DKIM), senão os lembretes caem no spam.
- Defina `FRONTEND_URL` com o endereço público da SPA, porque os links dos e-mails usam esse endereço.
- O serviço `scheduler` precisa estar rodando para os lembretes e os convites saírem.

## 6. LGPD

- Só guardamos o que o cliente digitou: nome, WhatsApp e e-mail. A localização, quando compartilhada, continua virando só a distância.
- Os e-mails de lembrete e de convite podem ser desligados com um toque (RF-C10).
- Os e-mails ficam cifrados na fila (`ShouldBeEncrypted`).

## 7. Próximos

- Avaliação e depoimentos depois do atendimento (módulo pago, ver o [roadmap](roadmap.md)).
- Cancelamento pelo cliente na área do cliente.
