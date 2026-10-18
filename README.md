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

O botão no canto superior direito alterna entre as paletas **Escuro** (Dark Premium) e **Claro** (Light Minimal).

```bash
make up         # sobe o frontend em http://localhost:5173 (Docker)
make test       # Vitest
make typecheck  # vue-tsc
make build
```

Sem Docker: `cd frontend && npm install && npm run dev`.
