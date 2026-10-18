# linkcut
Interface do meu SaaS para barbeiros 

## Frontend (Vue 3 + Tailwind)

Telas mobile-first com dados mockados (`frontend/src/mocks/barber.ts`):

| Rota | Tela |
|------|------|
| `/` | Link da Bio (agregador) |
| `/portfolio` | Portfólio / Sobre Mim |
| `/agendar` | Modal de agendamento (também abre pelo botão "Agendar") |
| `/painel` | Painel do Barbeiro (métricas + solicitações pendentes) |
| `/painel/configuracoes` | Configurações: abas Link, Portfólio, Agenda e Aparência (`?aba=`) |

Tudo o que as telas públicas mostram vem das **Configurações** (requisitos em [`docs/requisitos-configuracoes.md`](docs/requisitos-configuracoes.md)). Enquanto não há backend, os dados ficam no navegador (localStorage + IndexedDB para imagens), atrás de `frontend/src/Settings/settings.api.ts`.

As cores ficam em um único arquivo, `frontend/src/styles/core.css`: 6 paletas, cada uma com versão clara e escura, mais uma cor personalizada opcional. O visitante pode alternar claro/escuro nas páginas públicas pelo botão no canto superior direito.

```bash
make up         # sobe o frontend em http://localhost:5173 (Docker)
make test       # Vitest
make typecheck  # vue-tsc
make build
```

Sem Docker: `cd frontend && npm install && npm run dev`.
