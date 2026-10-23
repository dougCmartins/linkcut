# Privacidade e LGPD: o que o linkcut trata

Inventário para a revisão jurídica e para manter a Política de Privacidade (`/privacidade`) atualizada.

> O texto da política no app (`frontend/src/i18n/pt-BR.ts` → `privacy.page`) é um **modelo**. Antes de divulgar,
> peça a um advogado para revisar. Os dados do controlador vêm do `.env.production`:
> `VITE_PRIVACY_CONTROLLER`, `VITE_PRIVACY_DOCUMENT` e `VITE_PRIVACY_EMAIL`.

## Armazenamento no navegador
O app **não grava cookies**: a API é stateless (Bearer token).

| Chave | Onde | Para quê | Categoria |
|---|---|---|---|
| sessão do barbeiro | `sessionStorage` | manter o login até fechar o navegador | necessário |
| `linkcut.client-session` | `localStorage` | manter o cliente conectado (até 30 dias) | necessário |
| tema claro/escuro | `localStorage` | lembrar a escolha do visitante | necessário (preferência) |
| aviso de notificações dispensado | `localStorage` | não perguntar de novo | necessário (preferência) |
| `linkcut.consent` | `localStorage` | guardar a escolha de privacidade, com a versão e a data | necessário |

## Serviços de terceiros
| Serviço | O que recebe | Quando | Base legal |
|---|---|---|---|
| Sentry: erros | erro técnico, ids de barbeiro e barbearia, sem nome nem e-mail | sempre | legítimo interesse |
| Sentry: desempenho e replay | tempos de tela; gravação no erro com textos e imagens ocultos | **só com consentimento** ("Desempenho") | consentimento |
| Provedor de e-mail (ex.: Brevo) | nome e e-mail do destinatário, texto do aviso | ao enviar códigos e avisos | execução de contrato |
| ViaCEP | o CEP digitado e o IP de quem digitou | ao buscar endereço | execução de contrato |
| OpenStreetMap (Nominatim) | endereço da barbearia | ao salvar o endereço | execução de contrato |
| Hospedagem (VPS) | tudo | sempre | execução de contrato |

**Não usamos** publicidade, pixel, Google Analytics nem fontes externas: as fontes são servidas pelo próprio app.

## Consentimento
- O aviso aparece na primeira visita. "Aceitar tudo", "Rejeitar opcionais" e "Personalizar" têm o mesmo peso.
- A escolha vale até mudar a versão (`CONSENT_VERSION` em `frontend/src/Legal/consent.ts`): **aumente a versão sempre que o aviso ou as categorias mudarem**, e todos serão perguntados de novo.
- "Preferências de cookies", no rodapé das páginas e em "Minha conta", reabre o aviso.

## Direitos do titular
- **Cliente:** corrige nome e WhatsApp e exclui a conta em "Minha conta". A exclusão anonimiza os agendamentos e o financeiro das barbearias.
- **Barbeiro:** pedidos de exclusão e portabilidade pelo e-mail do encarregado (`VITE_PRIVACY_EMAIL`), por enquanto manuais.

## Prévia de links (Open Graph)
- `GET /api/og/{slug}` devolve o nome, a bio e a foto que **o próprio barbeiro publicou** na página pública.
- O Caddy só manda para lá os robôs de prévia (WhatsApp, Facebook, X etc.).
