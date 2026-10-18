# linkcut
Interface do meu SaaS para barbeiros 

## Frontend (Vue 3 + Tailwind)

Telas mobile-first com dados mockados (`frontend/src/mocks/barber.ts`):

| Rota | Tela |
|------|------|
| `/` | Link da Bio (agregador) |
| `/portfolio` | Portfólio / Sobre Mim |
| `/agendar` | Modal de agendamento (também abre pelo botão "Agendar") |
| `/entrar` | Login do barbeiro (e-mail + senha + código 2FA por WhatsApp ou e-mail) |
| `/painel` 🔒 | Painel do Barbeiro (métricas + solicitações pendentes) |
| `/painel/configuracoes` 🔒 | Configurações: abas Link, Portfólio, Agenda, Aparência e Segurança (`?aba=`) |

🔒 = só com login. Em desenvolvimento, use a conta de teste `rafael@linkcut.app` / `linkcut123`; o código 2FA aparece na própria tela (detalhes em [`docs/requisitos-autenticacao.md`](docs/requisitos-autenticacao.md)).

Tudo o que as telas públicas mostram vem das **Configurações** (requisitos em [`docs/requisitos-configuracoes.md`](docs/requisitos-configuracoes.md)). Enquanto não há backend, os dados ficam no navegador (localStorage + IndexedDB para imagens), atrás de `frontend/src/Settings/settings.api.ts`.

As cores ficam em um único arquivo, `frontend/src/styles/core.css`: 6 paletas, cada uma com versão clara e escura, mais uma cor personalizada opcional. O visitante pode alternar claro/escuro nas páginas públicas pelo botão no canto superior direito.

```bash
make up         # sobe o frontend em http://localhost:5173 (Docker)
make test       # Vitest
make typecheck  # vue-tsc
make build
```

Sem Docker: `cd frontend && npm install && npm run dev`.
