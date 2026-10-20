# Requisitos: avisos (push) de novos pedidos

> **Por quê:** sem aviso, o barbeiro só descobre um pedido quando abre o painel. Com o push, ele recebe uma notificação no celular ou no computador assim que o cliente agenda, mesmo com o linkcut fechado.

## 1. Regras

| ID | Regra |
|---|---|
| RF-N1 | A cada novo pedido de agendamento, todos os aparelhos da barbearia que ativaram os avisos recebem a notificação "Novo pedido de agendamento · Ana Souza · Corte · 20/10 às 10:00". Tocar nela abre o painel. |
| RF-N2 | Os avisos são **por aparelho**. O barbeiro ativa em cada celular ou computador, pelo convite no painel ("Ativar avisos") ou em Configurações → Segurança → Avisos (liga/desliga). |
| RF-N3 | "Agora não" esconde o convite neste aparelho. O botão em Configurações continua disponível. |
| RF-N4 | O navegador mostra o próprio pedido de permissão. Se a pessoa bloquear, a tela explica como liberar nas configurações do navegador. |
| RF-N5 | **iPhone/iPad:** só funciona pelo app na Tela de Início (Compartilhar → "Adicionar à Tela de Início", iOS 16.4+). Fora dele, a tela explica isso em vez de mostrar o botão. |
| RF-N6 | Sair da conta desliga os avisos deste navegador, para que outra pessoa no mesmo aparelho não receba os pedidos da barbearia. |
| RF-N7 | O envio é em fila: o pedido do cliente nunca espera o serviço de push. Um aparelho que não existe mais (o serviço responde 404/410) é esquecido. |

## 2. Como funciona

- **Web Push padrão (VAPID):** sem conta em serviço externo (Firebase etc.) e sem custo. O navegador usa o serviço de push dele (Google no Chrome, Mozilla no Firefox, Apple no Safari), e a API envia a mensagem cifrada (aes128gcm) direto para ele.
- **Chaves:** `php artisan notifications:vapid-keys` grava `VAPID_PUBLIC_KEY` e `VAPID_PRIVATE_KEY` no `.env` uma vez (o entrypoint do Docker já roda). Trocar as chaves invalida os aparelhos já cadastrados.
- **Extensão PHP:** a biblioteca `minishlink/web-push` precisa de **GMP** (ou BCMath) para a criptografia. A imagem Docker instala GMP.
- **SPA:**
  - `public/sw.js` (service worker) mostra a notificação e abre a página;
  - `public/manifest.webmanifest` e os ícones permitem instalar o app (necessário no iPhone);
  - o store `Domain/Notification/push.store.ts` liga e desliga os avisos.

## 3. API

| Método e rota | Faz |
|---|---|
| `GET /api/notifications/push/public-key` | Chave pública VAPID (o navegador precisa dela para se inscrever) |
| `POST /api/notifications/push/subscriptions` | Cadastra este aparelho (`endpoint` + `keys.p256dh` + `keys.auth`). Se o navegador já era de outro barbeiro, passa para o atual. |
| `DELETE /api/notifications/push/subscriptions` | Remove este aparelho (só um aparelho do próprio barbeiro) |

## 4. Próximos

- Aviso por e-mail como alternativa para quem não ativar o push.
- Outros avisos: pedido cancelado, lembrete do primeiro horário do dia.
