# 0002. Filiais e equipe de ponta a ponta: telas que faltam (P4 frontend e "Nova filial")

- **Status:** Aplicado
  <!-- Decidido → Em execução → Aplicado. Bloqueado: o executor parou por uma pergunta aberta (ver "Execução"). -->
- **Data da decisão:** 2026-11-25
- **Origem:** chat do arquiteto (pedido do dono em 25/11: deixar filiais e equipe usáveis de ponta a ponta)
- **Base:** [0001](0001-filiais-e-equipe.md) (esta decisão **fecha** o P4 frontend e a lacuna do P1; não muda nenhuma regra da 0001); [revisão de 24/11](../delegacao/revisao-2026-11-24.md); [ADR 0002](../adr/0002-organizacao-com-filiais.md). Não precisa de ADR novo: nenhuma fronteira muda; a única mudança de dados é o intervalo opcional em `staff_business_hours` (P3).

<!--
Seções 1 a 6: escreve o CHAT DO ARQUITETO. O executor não as altera.
Seção 7: escreve o EXECUTOR (skill executar-decisoes). O arquiteto só lê.
-->

## 1. Contexto
O backend de filiais e equipe está pronto (P1 a P6 da 0001), mas **duas pontas não existem na tela**:
1. **P4 frontend.** O cliente não escolhe barbeiro; o dono não edita horários, serviços e bloqueios de um barbeiro; não há como passar um atendimento a outro barbeiro. Tudo isso já tem rota (ver seção 3).
2. **Nova filial.** `POST /api/organization/units` existe, mas só quem chama a API cria filial. O `UnitSelector` só aparece com 2 ou mais unidades, então o dono individual nem tem por onde começar.

Pedido do dono (25/11): "Para filiais e equipe serem usáveis de ponta a ponta: P4 no frontend (cliente escolhe o barbeiro ou 'qualquer um', telas de horários e bloqueios do barbeiro, passar atendimento a outro barbeiro) e a tela de Nova filial."

**Achado da avaliação (backend, falta antes das telas):** `POST /api/organization/units` não confere o papel. Um barbeiro convidado tem `organization_id`, então hoje ele consegue criar filial da marca pela API. A rota só exige `auth:api` + `tenant`; o `CreateUnitTest` não tem caso de barbeiro. Vira o passo P0.

## 2. Decisões do dono
Valem as da 0001. Nesta decisão, só o que é novo:
- **Quem cria filial:** só a dona (`role = owner`). O barbeiro nunca vê o botão e a API recusa com 403.
- **Entrada da tela "Nova filial":** em Configurações, num cartão "Filiais" (visível para toda dona, inclusive a individual), e como última opção do seletor de unidade quando ele existir. Criar a 2ª filial transforma a conta em `multi_unit`, e só aí aparece o seletor, como já decidido (0001, P1).
- **O cliente só vê a escolha de barbeiro quando a unidade tem 2 ou mais barbeiros ativos.** Com 1, a tela do agendamento fica como hoje (o barbeiro individual não vê nada de equipe).
- **Padrão da escolha:** "Qualquer um disponível".
- **Quem o "qualquer um" escolhe (25/11):** "o que tem menos horários e o que tem liberação mais próxima do que o cliente selecionou", no espírito do rodízio do AppBarber (dividir o trabalho, não sobrecarregar um). Entre os barbeiros livres no horário escolhido, nesta ordem:
  1. o que tem **menos atendimentos no dia** (pendentes e confirmados);
  2. empate: o que fica com **menos buraco na agenda**, ou seja, cujo atendimento anterior (ou o início do expediente/fim do intervalo) termina mais perto do horário escolhido;
  3. empate: o menor `staff.id`.
- **O cliente vê o nome do barbeiro (25/11):** sempre, inclusive quando escolheu "qualquer um": na confirmação, nos e-mails e na área do cliente.
- **A agenda do barbeiro é controle de acesso (ACL), decidido pelo dono em 25/11:** "o barbeiro não trava a agenda dele; o segredo está no horário de trabalho, o adm define o intervalo". A dona define horários, **intervalo**, serviços e bloqueios de cada barbeiro; o barbeiro só usa a agenda que recebeu. É o que o backend já impõe (`ManageStaffSchedule` só para `owner`).
- **Intervalo no horário de trabalho:** cada dia tem o período de trabalho e um intervalo opcional (almoço), como os "Período 1" e "Período 2" do AppBarber. O intervalo **não** é um bloqueio: faz parte do horário.
- **Mais autonomia para o barbeiro** (ex.: bloquear o próprio horário) só entra como uma chave de permissão nova, desligada por padrão e ligada pela dona. Não entra nesta decisão (pergunta 1 da seção 6).
- **Passar atendimento a outro barbeiro:** só a dona; o outro barbeiro precisa fazer o serviço e estar livre no horário. Se não estiver livre, a tela mostra o erro `SLOT_UNAVAILABLE` e não muda nada.

## 3. Referências

### Rotas que já existem (não criar de novo)
| Uso | Rota | Quem |
|---|---|---|
| Lista pública de barbeiros ativos (`id`, `name`, `avatar`) | `GET public/{slug}/staff` | público, `throttle:public` |
| Horários livres, por barbeiro ou de qualquer um | `GET public/{slug}/availability?service_ids[]=..&staff_id=` | público |
| Pedir horário, com ou sem barbeiro | `POST public/{slug}/booking-requests` (`staff_id` opcional; nulo = qualquer um) | público |
| Horários semanais do barbeiro | `GET`/`PUT team/staff/{id}/hours`, corpo `{weekly_hours: [{weekday 0-6, is_open, opens_at "HH:mm", closes_at "HH:mm"}]}` | só dona |
| Serviços do barbeiro | `GET`/`PUT team/staff/{id}/services`, corpo `{service_ids: [...]}` | só dona |
| Bloqueios | `GET`/`POST team/staff/{id}/blocks`, `DELETE team/staff/{id}/blocks/{blockId}`. Corpo: `{starts_at "YYYY-MM-DD HH:mm", ends_at, recurrence: null\|"weekly", weekdays: [0-6]\|null, note}` | só dona |
| Passar atendimento | `POST schedule/requests/{id}/reassign`, corpo `{staff_id}`; erro `SLOT_UNAVAILABLE` se o outro estiver ocupado | só dona |
| Criar filial | `POST organization/units`, corpo `{name, slug, address{zip_code,street,number,district,city,state,complement,latitude,longitude}, copy_settings}`; devolve `{id,name,slug,is_main}` com 201 | **hoje sem trava de papel (P0)** |
| Link livre? | `GET sign-up/slug?slug=` devolve `{available, suggestion}` (já usado por `checkSlug`) | público |

### Concorrência (AppBarber, artigos salvos pelo dono no Drive em 25/11)
- **Horário de trabalho:** por dia da semana, com "Período 1" (obrigatório) e "Período 2" (opcional); quem não faz intervalo preenche só o 1.
- **"Profissional não aparece na agenda"** tem duas causas: nenhum serviço associado ou nenhum horário de trabalho. Por isso o linkcut nasce com todos os serviços e o horário copiado da unidade, e o P3 avisa quando a agenda fica vazia.
- **Bloqueio:** horário único ou recorrente, feito no painel de gestão. "Desbloquear" apaga um bloqueio; "Desbloquear horário" remove os bloqueios de um profissional dentro de um período (datas e horas).
- **Acesso do profissional:** a dona libera ou bloqueia, por profissional, o acesso ao web e ao app; um "Perfil de Acesso" escolhe os menus. O "Gestor" vê tudo. A recepção é cadastrada como profissional sem serviço e sem horário (o linkcut deixou o modo recepção fora do escopo na 0001).
- **AppBarber PRO** é o app do profissional, sem custo extra; não é um plano pago.
- **Rodízio de profissionais** é a fila de ordem de chegada (Livre, Em atendimento, Ausente). Não é o "qualquer um" do agendamento online; fica para o futuro.

`BookingRequestData` já traz `staff_id`, mas não o nome. **Não** criar `staff_name` no backend: o painel da dona já tem a lista da equipe (`useTeamStore`), e o barbeiro só vê os próprios atendimentos.

### Código do frontend a reaproveitar
- `Domain/Settings/AddressEditor.vue` + `cep.api.ts` (CEP preenche o endereço) e `useGeolocation` para o endereço da filial.
- `Domain/Settings/WorkingHoursEditor.vue` para os horários do barbeiro (ver se já aceita `v-model` genérico; se for preso ao Settings, extrair o corpo para `components/` sem mudar o comportamento).
- `Domain/Onboarding/slug.ts` (`slugify`, `RESERVED_SLUGS`, limites) e `checkSlug`.
- `composables/useQueryOverlay.ts` para o seletor de barbeiro e o "passar para" (o "voltar" do celular fecha).
- `components/ui/`: `BaseButton`, `TextField`, `SelectField`, `ToggleSwitch`, `BaseCard`.
- `Domain/Tenant/organization.store.ts`: hoje `ensureLoaded()` só carrega uma vez. Precisa de `reload()` para enxergar a filial nova. `selectUnit()` já recarrega settings, tenant, agenda, mês e equipe.
- Erros: `useApiErrors().messageKey` e `t('errors.api.' + chave)` (o `TeamView` usa a chave crua: corrigir de passagem, defeito 3 da revisão).

### Estrutura de pastas (frontend)
```
Domain/Tenant/
  NewUnitView.vue              # P1: formulário da filial   (rota /painel/unidades/nova, name: unit-new)
  UnitsCard.vue                # P1: cartão "Filiais" nas Configurações
  organization.api.ts          # + createUnit()
  organization.store.ts        # + reload(), createUnit()
Domain/Team/
  StaffAgendaView.vue          # P3: telas do barbeiro      (rota /painel/equipe/:id/agenda, name: staff-agenda)
  StaffHoursSection.vue        # P3
  StaffServicesSection.vue     # P3
  StaffBlocksSection.vue       # P3: lista + formulário de bloqueio
  staffSchedule.api.ts         # P3: hours, services, blocks
  staffSchedule.store.ts       # P3
Domain/Schedule/
  StaffPicker.vue              # P2: "Qualquer um" + barbeiros, dentro do BookingModal
  ReassignSheet.vue            # P4: "Passar para outro barbeiro" (overlay)
  booking.api.ts               # + fetchPublicStaff(), staffId em fetchAvailability/createBookingRequest, reassignBookingRequest()
```
`BookingModal.vue` já tem 415 linhas: o picker entra como componente e o modal só orquestra. Nenhuma lógica de regra no componente.

## 4. Como aplicar
Ordem: **P0 → P1 → P2 → P3 → P4**. P1 e P2 não dependem um do outro (podem ir em PRs paralelas); P3 e P4 vêm depois porque só fazem sentido com equipe.

- [x] **P0 — Trava de papel ao criar filial (backend).**
  - `CreateUnit` (ou o controller) recusa quem não é `owner` com `OwnerRequiredException`, igual ao `ManageStaffSchedule::ensureOwner()`.
  - **Aceite:** teste AAA em `CreateUnitTest`: barbeiro recebe 403 e nada é criado; dona continua com 201. `ArchitectureTest` e `pint` passam.

- [x] **P1 — Tela "Nova filial".**
  - **Rota:** `/painel/unidades/nova` (`requiresAuth`), só dona; barbeiro vai para `/painel`.
  - **Campos:** nome; link (`slug`, sugerido por `slugify(nome)`, editável); endereço (`AddressEditor`, obrigatório, como no resto do app); caixa "Copiar as configurações da matriz" (`copy_settings`, desligada por padrão, com a explicação de que fotos não são copiadas: o backend zera os ids de imagem).
  - **Link livre:** checa com `checkSlug` (debounce) e mostra "disponível" ou a sugestão; slugs reservados e fora do padrão são recusados antes de enviar.
  - **Entradas:** `UnitsCard.vue` nas Configurações (lista as unidades e o botão "Adicionar filial"; para a conta individual, um texto curto de que ela vira uma marca com filiais) e a opção "Nova filial" no fim do `UnitSelector`.
  - **Depois de salvar:** `organization.reload()`, `selectUnit(nova.id)`, volta ao `/painel` com um aviso que mostra o link da filial.
  - **Textos:** tudo em `pt-BR.ts` (grupo `organization.newUnit.*`) e erros novos em `errors.api.*`.
  - **Aceite:**
    - teste de tela (AAA): preenche, envia o corpo certo (`snake_case`), troca para a unidade nova; link ocupado mostra a sugestão; barbeiro não vê o botão;
    - teste de `organization.store`: `reload()` traz a filial e `isMultiUnit` vira `true` depois da 2ª unidade;
    - **conferir no código e cobrir com teste de backend, se faltar:** a filial recém-criada tem os horários do barbeiro-dono copiados (`SeedStaffSchedule`). Sem isso a agenda da filial nasce fechada e o cliente não vê horário;
    - **conferir:** o ponto no mapa do endereço (latitude/longitude). Se o `CreateUnit` não o resolve como o `UpdateProfileSettings`, usar o mesmo mecanismo e registrar na execução;
    - `/{slug-da-filial}` e `/{slug-da-filial}/agendar` abrem a página e o agendamento da filial nova.

- [x] **P2 — O cliente escolhe o barbeiro ou "qualquer um" (agendamento).**
  - **Backend, distribuição do "qualquer um":** hoje `RequestBooking::resolveStaffId` pega o **primeiro** barbeiro livre por `id`, então a dona (o `staff` mais antigo) recebe todos os pedidos sem preferência. Trocar pela regra da seção 2 (menos atendimentos no dia → menor buraco → menor id), dentro da mesma transação e da mesma trava. A regra fica numa classe pura do `Team` ou do `Orchestrator/PublicBooking/Support` (ex.: `PickAnyStaff`), testável sem banco.
    - **Aceite:** testes AAA unitários da regra (os 3 critérios e os empates) e um teste de feature: dois barbeiros livres, um com 3 atendimentos no dia e outro com 1 → o pedido "qualquer um" vai para o de 1.
  - **Backend, nome do barbeiro para o cliente:** `BookingConfirmationData` ganha `staff_name`; os e-mails de pedido recebido/confirmado e a lista da área do cliente mostram o barbeiro. O nome vem do `Team` (já há `FindStaff`/`ListActiveStaff`) composto no `Orchestrator`, nunca pela tabela direto.
  - **Backend (pequeno):** `GET public/{slug}/staff` passa a aceitar `service_ids[]` e devolve só os barbeiros ativos que fazem **todos** os serviços escolhidos (tabela `staff_services`; reaproveitar o `ListActiveStaff($serviceIds)` que o agendamento já usa). Sem `service_ids`, devolve todos os ativos, como hoje. Teste AAA e `QueryBudgetTest` da rota (uma consulta a mais, no máximo).
  - **Frontend:**
    - `booking.api.ts`: `fetchPublicStaff(slug, serviceIds)`, `staffId` opcional em `fetchAvailability` e `createBookingRequest` (`staff_id`).
    - `StaffPicker.vue`: cartões "Qualquer um disponível" (padrão) e um por barbeiro, com foto e nome. Só aparece com 2 ou mais barbeiros; com 1 não aparece e o modal fica igual ao de hoje.
    - Trocar serviço ou barbeiro **busca os horários de novo** e limpa a hora escolhida se ela deixar de existir. Se o barbeiro escolhido deixar de fazer os serviços novos, volta para "Qualquer um".
    - A confirmação ("Pedido enviado") mostra sempre o barbeiro (`staff_name`), inclusive no "qualquer um".
    - O picker é overlay com `useQueryOverlay` quando for em tela cheia no celular.
  - **Aceite:**
    - teste do `BookingModal` (AAA): unidade com 1 barbeiro não mostra o picker; com 2, mostra; escolher barbeiro manda `staff_id` nos dois pedidos (horários e agendamento); "qualquer um" não manda `staff_id`; `SLOT_UNAVAILABLE` mostra a mensagem existente e atualiza os horários;
    - a tela de agendamento de uma unidade só com o dono passa nos testes atuais sem mudar uma linha;
    - CLS: o picker reserva a altura final enquanto carrega (esqueleto).

- [x] **P3 — Horários, serviços e bloqueios do barbeiro (dona).**
  - **Entrada:** em "Minha equipe" (`TeamView`), cada barbeiro ganha o botão "Agenda" que abre `/painel/equipe/:id/agenda`.
  - **Backend do intervalo (antes da tela):**
    - migração: `staff_business_hours` ganha `break_starts_at` e `break_ends_at` (`string(5)`, nulos). Os registros atuais ficam sem intervalo;
    - `StaffBusinessHourData` ganha os dois campos (nulos); validação: os dois juntos ou nenhum, dentro do período e com o fim depois do início;
    - a disponibilidade por barbeiro (`StaffAvailability` / `AvailableTimes`) tira o intervalo, do mesmo jeito que tira um bloqueio: um serviço que encostaria no intervalo não é oferecido;
    - a cópia dos horários da unidade (`SeedStaffSchedule`, `BackfillStaffSchedule`) continua sem intervalo.
    - **Aceite:** testes AAA: horário com intervalo 12:00–13:00 não oferece 11:30 para um serviço de 60 min nem 12:30; sem intervalo, nada muda; intervalo fora do período é recusado (422). `QueryBudgetTest` igual.
  - **Tela `StaffAgendaView`**, três seções (nada de abas escondidas no celular, tudo rolável):
    1. **Horários:** os 7 dias, aberto/fechado, início/fim e "Intervalo" opcional (início/fim), no mesmo visual do `WorkingHoursEditor`. Atalho "Repetir nos outros dias" para não digitar o mesmo intervalo 6 vezes. Aviso se a agenda ficar toda fechada ("o cliente não verá horário com este barbeiro").
    2. **Serviços:** lista com os serviços da unidade (um interruptor por serviço). Pelo menos um serviço ligado; se não houver, o barbeiro some do agendamento e a tela avisa.
    3. **Bloqueios:** lista dos bloqueios (único e recorrente) com o botão "Bloquear horário". O formulário tem: dia e hora de início e fim, "Repetir toda semana" com os dias da semana, observação. Cada item tem "Desbloquear" (apaga o bloqueio); o recorrente mostra "toda quarta, 13:00–18:00".
  - **Regras de borda:** o fim é depois do início (o backend também valida); `starts_at`/`ends_at` seguem `YYYY-MM-DD HH:mm` no fuso da unidade (usar os utilitários de `utils/date`, nunca `new Date(string)` direto); salvar horários e serviços é um botão só ("Salvar") com `SaveBar`, como nas Configurações.
  - **Aceite:**
    - testes AAA do `staffSchedule.api` (corpo e conversão `snake_case` ↔ `camelCase`) e da tela (carrega, edita, salva, cria e apaga bloqueio, recorrente envia `weekdays`);
    - barbeiro (role `barber`) que abre a URL é mandado para `/painel`;
    - um bloqueio recorrente criado na tela tira o horário do `GET availability` (teste de ponta a ponta no backend já existe para a regra; aqui basta o frontend mandar o corpo certo).
  - **Desbloqueio "por período"** (0001): fica como o `DELETE` do bloqueio inteiro. Liberar só uma ocorrência de um bloqueio recorrente depende da pergunta 2.

- [x] **P4 — Passar um atendimento para outro barbeiro e ver quem atende.**
  - **Painel da dona, com 2 ou mais barbeiros:**
    - cada cartão da agenda (`TodayAgendaCard`, `UpcomingAppointmentCard`, `PendingRequestCard`) mostra o nome do barbeiro (pelo `staffId` + `useTeamStore`);
    - filtro "Barbeiro" no topo da agenda (todos / um), guardado só na sessão.
  - **"Passar para outro barbeiro":** ação no cartão (só dona, só atendimento ainda não concluído) que abre `ReassignSheet` (overlay) com os barbeiros ativos, menos o atual. Se o backend recusar com `SLOT_UNAVAILABLE`, mostra o erro e mantém o atendimento onde estava. Sucesso: atualiza o cartão no `agenda.store` sem recarregar a página.
  - **Barbeiro (role `barber`):** não vê a ação nem o filtro; continua vendo só os próprios atendimentos.
  - **Aceite:**
    - teste do `agenda.store` (AAA): `reassign` troca o `staffId`; erro mantém o estado;
    - teste de tela: dona vê o botão; barbeiro não; unidade com 1 barbeiro não mostra nome nem filtro;
    - os 4 testes de `DashboardView` que falham hoje (fixtures sem `role`) passam: ajustar as fixtures, não o produto.

**Notas para todos os passos**
- Texto de tela em `frontend/src/i18n/pt-BR.ts`; código em inglês; AAA nos testes; tipos sem `any`.
- Cada passo é uma PR, na ordem. Depois de cada uma: `npm run typecheck`, `npx vitest run`, `npm run build` e, se tocou o backend, `vendor/bin/pint --test`, `php artisan test` e o `ArchitectureTest`.
- Capturas de tela em 390 px na descrição da PR (regra do `proximos-passos.md`, seção 4).
- Nada de `localStorage` para coisa que precisa persistir; só preferência de tela.

## 5. Fora do escopo
- Planos, limites de barbeiros e de filiais.
- Mover um barbeiro de uma filial para outra (recusado na 0001: um barbeiro, uma filial).
- Excluir ou desativar uma filial (precisa de decisão sobre o que acontece com histórico, link e clientes).
- Trocar o slug de uma filial existente.
- Agenda visual em grade por barbeiro (colunas lado a lado). Por ora é lista com filtro.
- Rodízio / fila de ordem de chegada (atendimento sem hora marcada).
- Perfil de acesso por menus e bloqueio de acesso web/app por barbeiro: as 3 chaves atuais bastam por ora.
- Avatar do barbeiro (upload) e o visual do Figma da página da marca.
- LGPD de barbeiros, convites, comissões e organização: segue pendente da 0001 e **precisa entrar antes de divulgar o recurso**; é a próxima decisão depois desta.
- Forma de pagamento, periodicidade do fechamento e textos das permissões (perguntas 1 a 3 da 0001).

## 6. Perguntas abertas
1. **Autonomia do barbeiro por permissão.** Respondido em parte (25/11): por padrão o barbeiro não mexe na agenda; a dona controla (ACL). Falta decidir **se e quando** entra uma 4ª chave em `staff_permissions`, por exemplo `manage_own_blocks` ("bloquear os próprios horários"), desligada por padrão. Exige migração (coluna nova), liberar o barbeiro só no próprio `staff_id` em `ManageStaffSchedule` e uma tela no painel dele. Não bloqueia P0 a P4.
2. **Desbloquear um período.** O AppBarber tem "Desbloquear horário": remove os bloqueios de um profissional entre duas datas e horas. Recomendação: um passo futuro com o mesmo comportamento. Não bloqueia: P3 entrega só o apagar do bloqueio inteiro.
3. ~~Cliente sem preferência vê o barbeiro?~~ Respondido em 25/11: sim, sempre (seção 2).
4. ~~Copiar a equipe ao criar a filial?~~ Respondido em 25/11: não. Cada filial tem os próprios barbeiros; mover um barbeiro entre filiais (transferência ou empréstimo) é a [0003](0003-transferencia-e-emprestimo-de-barbeiro.md).

## 7. Execução
<!-- Preenchido pelo executor. Uma entrada por execução, a mais nova em cima. -->

### 2026-11-25 — execução 1
- **Feito:**
  - **P0:** só a dona cria filial. Um barbeiro recebe 403 `OWNER_REQUIRED`, e nada é criado. Teste `CreateUnitTest::test_a_barber_cannot_create_a_unit_and_nothing_is_created`. Commit `4caef33`.
  - **P1:** tela "Nova filial" em `/painel/unidades/nova` (só a dona). A dona chega por um cartão "Filiais" na aba Link das Configurações ou pela opção "+ Nova filial" no fim do seletor de unidade. O link é checado enquanto ela digita; a checagem foi extraída do cadastro para `useSlugCheck`/`SlugField`, e o cadastro usa a mesma. Depois de salvar, o painel troca para a filial nova e mostra o link dela. Testes: `NewUnitView.test.ts` (corpo em snake_case, link ocupado, link reservado, cartão da dona/barbeiro, `reload()` e `isMultiUnit`), `UnitSelector.test.ts`, `auth.guard.test.ts` (barbeiro vai para `/painel`). Commit `9e31721`.
    - **Conferido no código:** a filial nova já nascia com o horário do dono copiado (`CreateOwnerStaff` → `SeedStaffSchedule`). Agora tem teste: `test_the_new_unit_gets_the_owner_as_barber_with_the_unit_hours`.
    - `/{slug-da-filial}` e `/staff` respondem logo depois de criar: `test_the_new_unit_public_page_and_booking_open_on_its_own_slug`.
    - **Ponto no mapa:** o `CreateUnit` gravava latitude/longitude nulas. Agora usa o mesmo `AddressGeocoder` das Configurações, fora da transação. Teste `test_an_address_without_a_point_is_located_like_in_settings`.
  - **P2 backend:**
    - **"Qualquer um":** a regra `PickAnyStaff` escolhe o barbeiro com menos atendimentos no dia; no empate, o de menor buraco antes do horário; depois, o de menor id. Roda dentro da mesma trava.
    - **Nome do barbeiro:** aparece na confirmação (`staff_name`), nos e-mails (linha "Barbeiro: …") e na área do cliente.
    - **Filtro por serviço:** `GET public/{slug}/staff?service_ids[]=` lista só quem faz todos os serviços escolhidos.
    - **Evidência:** `PickAnyStaffTest` (5 testes); em `StaffAgendaTest`, o pedido "qualquer um" vai para o barbeiro com 1 atendimento, não para o de 3, e o filtro de serviço tem teste; `BookingEmailsTest`, `ClientAppointmentsTest`, `QueryBudgetTest` (a rota `/staff` faz 1 consulta a mais, constante com 1 ou 5 barbeiros; o agendamento segue em 24 e os lembretes seguem constantes). Commit `7e6bab0`.
  - **P2 frontend:** `StaffPicker` dentro do `BookingModal`, só com 2 ou mais barbeiros ativos. O padrão é "Qualquer um disponível".
    - Trocar serviço ou barbeiro busca os horários de novo.
    - Se o barbeiro escolhido não faz um serviço novo, a escolha volta para "Qualquer um".
    - Enquanto carrega, um esqueleto ocupa o lugar da lista.
    - A confirmação mostra "· com {barbeiro}", e a área do cliente mostra "Com {barbeiro}".
    - A lista de barbeiros vem junto com a página pública (`publicPage.store`). Assim, uma unidade com 1 barbeiro não faz nenhuma chamada a mais no modal, e o `BookingModal.test.ts` passou sem mudar nenhuma linha.
    - Testes em `BookingModalStaff.test.ts` (6) e `ClientAccount.test.ts`. Commit `c9cdc97`.
  - **P3 backend:** intervalo opcional (`break_starts_at`/`break_ends_at`) em `staff_business_hours`. Precisa dos dois campos ou de nenhum, dentro do expediente, com o fim depois do início; senão, 422. A disponibilidade e o agendamento pulam o intervalo como um bloqueio. Testes em `StaffAgendaTest`: com intervalo 12:00–13:00, um Corte de 45 min não aparece às 11:30, 12:00 nem 12:30; aparece às 11:00 e às 13:00, e o dia sem intervalo não muda. Um intervalo fora do expediente ou pela metade dá 422. Migração `2026_11_25_100000`. Commit `aad25e5`.
  - **P3 frontend:** "Agenda" em cada barbeiro de "Minha equipe" abre `/painel/equipe/:id/agenda` (só a dona; o barbeiro vai para `/painel`).
    - **Horários:** o `WorkingHoursEditor` ganhou a opção `with-break`, com intervalo por dia e "Repetir nos outros dias". As Configurações continuam iguais.
    - **Serviços:** um interruptor por serviço.
    - **Bloqueios:** único ou semanal, com os dias da semana e a data final opcional. "Desbloquear" apaga o bloqueio.
    - **Salvar:** um `SaveBar` só para horários e serviços.
    - **Avisos:** "agenda toda fechada" e "nenhum serviço" (sem serviço, o botão Salvar fica bloqueado, porque a API recusa a lista vazia).
    - Testes: `staffSchedule.api.test.ts` (4), `StaffAgendaView.test.ts` (4), `TeamView.test.ts`, `auth.guard.test.ts`. Commit `6a37db5`.
  - **P4:** com 2 ou mais barbeiros ativos, os cartões da agenda da dona mostram "Com {barbeiro}".
    - Um filtro "Barbeiro" no topo da agenda vale só para a sessão do navegador (`sessionStorage`).
    - "Passar para outro" abre o `ReassignSheet` (`?passar=<id>`, com `useQueryOverlay`) com os outros barbeiros ativos. Só aparece em atendimento pendente ou confirmado.
    - Se o barbeiro estiver ocupado (`SLOT_UNAVAILABLE`), a tela mostra "{nome} não está livre neste horário. Nada mudou." e o atendimento fica onde estava.
    - O barbeiro e a unidade com 1 barbeiro não veem nada disso.
    - Testes: `agenda.store.test.ts` (troca o `staffId`; o erro mantém o estado), `DashboardTeam.test.ts` (5). Commit `2594d7d`.
  - **Checagens:** `pint --test` OK; `php artisan test` 333 OK, incluindo `ArchitectureTest` e `QueryBudgetTest`; `typecheck` OK; `vitest` 441 OK; o build passou com `--outDir` temporário, porque o `frontend/dist` local pertence ao root.
- **Já estava aplicado:**
  - Os 4 testes do `DashboardView` (P4) já passavam: as fixtures ganharam `role` no #76 (`c3a4450`).
  - O defeito 3 da revisão (o `TeamView` mostrava a chave de erro crua) já estava corrigido: o `TeamView` usa `errors.api.`.
  - A cópia do horário para o barbeiro-dono na filial nova (P1) também já estava feita.
- **Pendente:**
  - Capturas em 390 px para a PR: não há PR, e não testei no navegador. Só os testes automatizados cobrem as telas.
  - O seletor de barbeiro fica dentro do modal, sem tela cheia própria. Por isso não usa `useQueryOverlay`; o "passar para outro" usa.
- **Perguntas para o dono/arquiteto:**
  1. **Passar atendimento:** a rota `reassign`, que já existia, só confere se o outro barbeiro tem atendimento sobreposto. Ela não confere se ele faz o serviço, se o horário cai no expediente, no intervalo ou num bloqueio dele, nem se o atendimento já foi concluído. A tela só oferece a ação em atendimento pendente ou confirmado. A seção 2 diz "precisa fazer o serviço e estar livre": a API deve recusar esses casos também? Com qual código de erro (`SLOT_UNAVAILABLE` para tudo?)
  2. **Barbeiro com convite pendente:** ele está `active` e entra na lista pública e no "qualquer um", mesmo antes de aceitar o convite. Isso vem do comportamento atual do backend, que não mudei. Ele deve ficar de fora até aceitar?
  3. As perguntas 1 e 2 da seção 6 seguem abertas e não bloquearam nada.
- **Riscos ou desvios:**
  - **Filiais na tela:** o cartão "Filiais" ficou na aba **Link** das Configurações, porque as Configurações são em abas e a decisão não diz qual.
  - **Fim do bloqueio:** a data final opcional segue a regra da API: num bloqueio semanal, `ends_at` em outro dia marca a última semana.
  - **Barbeiro sem serviço:** `ListActiveStaffForServices` agora faz 2 consultas em vez de 1 por barbeiro. Ela mantém a regra antiga: um barbeiro sem nenhuma linha em `staff_services` conta como apto. Hoje isso não acontece, porque a API recusa uma lista vazia.
- **Branch / PR:** `claude/decision-0002-branch-screens` (sem PR).
