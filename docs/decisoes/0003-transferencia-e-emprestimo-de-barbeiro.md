# 0003. Transferência e empréstimo de barbeiro entre filiais

- **Status:** Decidido
  <!-- Decidido → Em execução → Aplicado. Bloqueado: o executor parou por uma pergunta aberta (ver "Execução"). -->
- **Data da decisão:** 2026-11-25
- **Origem:** chat do arquiteto (conversa com o dono em 25/11, a partir da pergunta 4 da 0002)
- **Base:** [0001](0001-filiais-e-equipe.md) (um barbeiro, uma filial; comissão com "foto"); [0002](0002-filiais-e-equipe-telas.md) (horários com intervalo, passar atendimento); [ADR 0002](../adr/0002-organizacao-com-filiais.md). Sem ADR novo: nenhuma fronteira muda.
- **Depende de:** 0002 aplicada até o P4 (o editor de horários com intervalo e o "passar atendimento" são reaproveitados aqui).

<!--
Seções 1 a 6: escreve o CHAT DO ARQUITETO. O executor não as altera.
Seção 7: escreve o EXECUTOR (skill executar-decisoes). O arquiteto só lê.
-->

## 1. Contexto
Cada filial tem os próprios barbeiros, e uma filial nova não copia a equipe. Mas a dona precisa mover gente: "em uma barbearia precisa de mais um e lá está fechado a lista, pode ocorrer uma transferência de barbeiro; ele passa a aparecer na outra, mas a que ele foi cadastrado fica desativada; todos os seus ganhos ficam associados à antiga barbearia e os novos ganhos à nova, porque pode ter regras específicas na filial; e na distribuição de lucros isso deve contar."

Sobre o formato, o dono pediu (25/11): "abre o leque e deixa pro usuário decidir; para de definir regras tão fechadas quanto as do AppBarber."

**O modelo atual já sustenta a parte difícil** (conferido no código):
- `staff` é por unidade e aceita uma ficha por unidade para o mesmo login (`unique(tenant_id, user_id)`);
- `commission_entries` é a "foto" de cada ganho, com `tenant_id`, `staff_id`, base, percentual e ajustes. O passado fica na unidade onde foi feito, com as regras dela;
- as regras de comissão são por unidade: o que ele fizer na unidade nova já calcula com as regras dela;
- `users.tenant_id` diz em qual unidade o barbeiro entra (o middleware `tenant` usa esse campo).

**Cuidado:** `commission_entries.staff_id` tem `cascadeOnDelete`. **Nunca apagar** uma ficha de barbeiro; mover é sempre desativar.

## 2. Decisões do dono
**Princípio:** o app oferece as opções e sugere um padrão; **quem decide é a dona**. Nada de regra fixa onde uma escolha simples resolve.

- **Dois tipos de movimento**, escolhidos pela dona na hora:
  - **Transferência definitiva:** o barbeiro passa a trabalhar na filial de destino, sem data de volta.
  - **Empréstimo:** o barbeiro trabalha na filial de destino até uma data de fim e depois volta sozinho para a de origem.
- **Empréstimo é flexível:** a dona pode **estender** (nova data de fim), **encerrar agora** (volta imediata) ou **tornar definitivo** (vira transferência) a qualquer momento.
- **Um barbeiro, uma filial ativa por vez** (regra da 0001 preservada): durante o movimento, a ficha da origem fica **desativada** e a do destino **ativa**. Ao voltar, o inverso.
- **Horário na filial de destino é obrigatório no próprio movimento:** a dona cadastra o horário de trabalho (com o intervalo) dele na filial nova, porque o horário pode não bater entre filiais. O formulário já vem preenchido com uma sugestão (o horário da filial de destino), e a dona edita.
- **Serviços na filial de destino:** a dona escolhe; padrão sugerido, todos os serviços da filial de destino. Os serviços da origem não servem (cada filial tem os seus, com os seus preços).
- **Permissões (as 3 chaves):** a dona escolhe entre "manter as da filial de origem" (padrão sugerido) e "começar com tudo desligado".
- **Ganhos:** o que ele ganhou na origem fica na origem, com as regras dela; o que ganhar no destino é do destino, com as regras dele. No relatório, aparece **por unidade e por pessoa** (seção 4, P4).
- **Só a dona** move barbeiros. Só entre unidades da mesma organização. Barbeiro com convite pendente (sem login) não se move: a dona convida direto na outra filial.
- **Movimentar de novo para uma filial onde ele já trabalhou reativa a ficha antiga** (não cria outra), com o horário e os serviços que a dona definir no movimento.

## 3. Referências
- **AppBarber:** não tem transferência nem empréstimo entre filiais nos artigos salvos pelo dono (Drive, 25/11). O horário por profissional tem "Período 1" e "Período 2" (intervalo), o que já está na 0002.
- **Código a reaproveitar:**
  - `Domain/Team/Support/SeedStaffSchedule.php` (cópia do horário da unidade): serve como sugestão inicial do formulário, não como valor final;
  - `ManageStaffSchedule` (horários, serviços e permissões por barbeiro);
  - `ReassignAppointment` (0001 P4) para os atendimentos que impedem o movimento;
  - `StaffHoursSection` e `StaffServicesSection` (0002 P3) dentro do formulário do movimento;
  - `Orchestrator/BrandReport` (0001 P6) para a soma por pessoa.

## 4. Como aplicar
Ordem: P1 → P2 → P3 → P4. P1 e P2 são backend; P3 é a tela; P4 é o relatório.

- [ ] **P1 — Dados e transferência definitiva (backend).**
  - **Tabela `staff_moves`:** `id`, `organization_id`, `user_id`, `from_staff_id`, `to_staff_id`, `kind` (`transfer` | `loan`), `starts_on`, `ends_on` (nulo na transferência), `status` (`active` | `ended` | `made_permanent`), `created_by`, `ended_at`, `ended_by`, timestamps. Índice por `(organization_id, status)` e por `(user_id, status)`.
  - **Fluxo** em `Domain/Orchestrator/StaffMove` (cruza `Team`, `Identity` e `Schedule`):
    - `POST team/staff/{id}/move` com `{to_unit_id, kind: "transfer", weekly_hours: [...com intervalo], service_ids: [...], permissions: "keep" | "reset"}`;
    - numa transação: valida dona, mesma organização, unidade diferente, ficha com login; cria ou reativa a ficha no destino; grava horários, serviços e permissões escolhidos; desativa a ficha na origem; muda `users.tenant_id`; grava `staff_moves`;
    - **atendimentos futuros** (pendentes ou confirmados, de hoje em diante) do barbeiro na origem **impedem** o movimento: erro `STAFF_HAS_UPCOMING_APPOINTMENTS` com a lista (id, data, hora, cliente), para a dona passar cada um a outro barbeiro antes;
    - avisa o barbeiro por e-mail (e push, se ativo): "A partir de {data} você atende em {filial}".
  - **Aceite** (AAA):
    - depois da transferência, o barbeiro entra na filial de destino e recebe 403 com `X-Unit` da origem;
    - as `commission_entries` antigas continuam com `tenant_id` da origem e o mesmo valor;
    - um atendimento novo concluído no destino gera comissão pela regra do destino;
    - com atendimento futuro na origem, o movimento é recusado e nada muda;
    - barbeiro, outra organização e convite pendente recebem erro;
    - transferir de volta reativa a ficha antiga (nenhuma ficha nova).

- [ ] **P2 — Empréstimo (backend).**
  - `POST team/staff/{id}/move` com `kind: "loan"` e `ends_on` (obrigatório, depois de `starts_on`). Mesmo fluxo do P1.
  - **Durante o empréstimo:** a ficha do destino só é oferecida no agendamento até `ends_on`; a da origem não é oferecida.
  - **Ações sobre um empréstimo ativo** (`team/moves/{id}`):
    - `PATCH .../extend` com novo `ends_on` (no futuro e depois do atual);
    - `POST .../end`: volta agora;
    - `POST .../make-permanent`: vira transferência (`ends_on` nulo, `status = made_permanent`).
  - **Volta** (pelo `end` ou pela data): reativa a ficha da origem, desativa a do destino, `users.tenant_id` volta, `staff_moves.status = ended`. Atendimentos futuros no destino depois da volta **impedem** o `end` e a redução da data, com o mesmo erro e a lista do P1.
  - **Volta automática:** comando agendado diário (no `TenantClock` da unidade de destino) que encerra os empréstimos com `ends_on` vencido. Idempotente.
  - **Aceite:** estender, encerrar e tornar definitivo funcionam e são recusados para quem não é dona; o comando agendado devolve o barbeiro no dia seguinte ao `ends_on` e rodar duas vezes não muda nada; o agendamento público não oferece o barbeiro no destino depois de `ends_on`.

- [ ] **P3 — Tela do movimento (frontend).**
  - Em "Minha equipe", cada barbeiro (com login, unidade com 2+ filiais na organização) ganha "Mover para outra filial".
  - **Formulário em passos** (overlay com `useQueryOverlay`):
    1. filial de destino e tipo: "Transferência definitiva" ou "Empréstimo até [data]";
    2. horário na filial de destino (o `StaffHoursSection` da 0002, já preenchido com o horário da filial; editável, com intervalo);
    3. serviços na filial de destino (todos marcados por padrão);
    4. permissões: "Manter as atuais" (padrão) ou "Começar sem permissões";
    5. resumo e confirmar.
  - Se a API responder `STAFF_HAS_UPCOMING_APPOINTMENTS`, a tela mostra a lista com o botão "Passar para outro barbeiro" (0002 P4) em cada item e deixa tentar de novo.
  - **Empréstimos ativos:** um cartão em "Minha equipe" com "Emprestado para {filial} até {data}" e os botões "Estender", "Encerrar agora" e "Tornar definitivo".
  - **Aceite** (AAA): o corpo enviado em cada passo; o erro de atendimentos futuros mostra a lista; as três ações do empréstimo chamam as rotas certas; barbeiro não vê nada disso; conta individual não vê "Mover".

- [ ] **P4 — Ganhos por pessoa e por unidade.**
  - O relatório de comissão da marca (P6 da 0001) ganha a visão **por pessoa**: junta as fichas do mesmo `user_id` e mostra o total e a quebra por unidade ("Filial A: R$ X · Filial B: R$ Y").
  - Cada unidade continua com o próprio fechamento de período; no mês do movimento, a dona fecha cada uma.
  - **Aceite:** com um barbeiro que trabalhou em A e B no mesmo mês, a soma por pessoa = a soma das duas unidades; a soma da marca não muda; `QueryBudgetTest` do relatório sem N+1.

**Notas para todos os passos**
- **LGPD:** a exportação e a exclusão do barbeiro incluem todas as fichas e os `staff_moves`.
- **Desempenho:** a disponibilidade pública não pode ganhar consulta por barbeiro; o limite de `ends_on` entra na mesma consulta que já lista os barbeiros ativos.
- Textos em `pt-BR.ts`, código em inglês, testes AAA, `ArchitectureTest` passando.

## 5. Fora do escopo
- Barbeiro ativo em duas filiais ao mesmo tempo (recusado na 0001).
- Mover barbeiro entre organizações (marcas) diferentes.
- Copiar a equipe ao criar uma filial (0002, pergunta 4: não copia).
- Regras de comissão próprias para o período de empréstimo: valem as da filial de destino.
- Rodízio / fila por ordem de chegada.

## 6. Perguntas abertas
1. **O barbeiro vê os ganhos da filial antiga?** Depois do movimento ele entra no destino. Padrão sugerido: com `see_own_commission`, ele vê só para leitura o que ganhou nas outras fichas dele. Não bloqueia P1 a P3; afeta o P4.
2. **Agendar na origem para depois da volta?** Durante um empréstimo, a origem não oferece o barbeiro, nem para datas depois de `ends_on`. Padrão: não oferece (mais simples). Se a dona quiser, a origem passa a oferecer só datas depois da volta. Não bloqueia.

## 7. Execução
<!-- Preenchido pelo executor. Uma entrada por execução, a mais nova em cima. -->
