# linkcut
Interface do meu SaaS para barbeiros 

## Telas

Painel do barbeiro no celular (390 px), com dados de exemplo.

<table>
  <tr>
    <td align="center"><img src="docs/screenshots/painel-agenda.png" width="240" alt="Painel e agenda de hoje"><br><sub>Painel e agenda de hoje</sub></td>
    <td align="center"><img src="docs/screenshots/concluir-atendimento.png" width="240" alt="Concluir atendimento"><br><sub>Concluir atendimento</sub></td>
    <td align="center"><img src="docs/screenshots/tooltip-margem.png" width="240" alt="Tooltip da margem"><br><sub>Tooltip da margem</sub></td>
  </tr>
  <tr>
    <td align="center"><img src="docs/screenshots/despesas.png" width="240" alt="Despesas"><br><sub>Despesas</sub></td>
    <td align="center"><img src="docs/screenshots/editar-despesa.png" width="240" alt="Editar despesa"><br><sub>Editar despesa</sub></td>
    <td align="center"><img src="docs/screenshots/custo-servico.png" width="240" alt="Custo por serviço"><br><sub>Custo por serviço</sub></td>
  </tr>
</table>

## Frontend (Vue 3 + Tailwind)

Telas mobile-first com dados mockados (`frontend/src/mocks/barber.ts`):

| Rota | Tela |
|------|------|
| `/` | Link da Bio (agregador) |
| `/portfolio` | Portfólio / Sobre Mim |
| `/agendar` | Modal de agendamento (também abre pelo botão "Agendar") |
| `/entrar` | Login do barbeiro (e-mail + senha + código 2FA por WhatsApp ou e-mail) |
| `/painel` 🔒 | Painel do Barbeiro (métricas com tooltip da margem, agenda de hoje e solicitações pendentes) |
| `/painel/historico` 🔒 | Histórico de atendimentos: filtros semana/mês/ano/personalizado, 50/100/200 por página e exportação CSV ([requisitos](docs/requisitos-historico.md)) |
| `/painel/despesas` 🔒 | Despesas fixas e avulsas do mês, mais o custo dos atendimentos ([requisitos](docs/requisitos-despesas.md)) |
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
