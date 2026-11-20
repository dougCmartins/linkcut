# 0001. Filiais e equipe: estrutura completa (organização, unidades, equipe, agenda por barbeiro e comissão)

- **Status:** Em execução
  <!-- Decidido → Em execução → Aplicado. Bloqueado: o executor parou por uma pergunta aberta (ver "Execução"). -->
- **Data da decisão:** 2026-11-17. Base de 15/11, com as respostas do dono de 17/11.
- **Origem:** chat do arquiteto (conversas com o dono de 29/10, 15/11, 16/11 e 17/11)
- **Base:**
  - [ADR 0002](../adr/0002-organizacao-com-filiais.md) (organização acima do tenant);
  - [ADR 0001](../adr/0001-monolito-modular-e-fronteiras.md) (fronteiras entre módulos);
  - [ATAM](../arquitetura/contas-e-filiais-atam.md) e [CBAM](../arquitetura/contas-e-filiais-cbam.md);
  - [análise da concorrência](../arquitetura/concorrencia-equipe-e-comissao.md).

<!--
Seções 1 a 6: escreve o CHAT DO ARQUITETO. O executor não as altera.
Seção 7: escreve o EXECUTOR (skill executar-decisoes). O arquiteto só lê.
-->

## 1. Contexto
Hoje "1 tenant = 1 barbeiro = 1 endereço". O produto passa a ter **dois tipos de conta**:
- o **barbeiro individual**, que continua exatamente como hoje;
- a **barbearia com filiais**, com equipe de barbeiros, administrada num painel só.

**A arquitetura já está decidida** (ADR 0002):
- cada filial é um **tenant**, com tudo o que um tenant já tem (configurações, slug, tema, horários, cache e isolamento);
- uma **organização** (a marca) agrupa a matriz e as filiais;
- o barbeiro individual é uma organização com um tenant.

**Pedido do dono em 17/11:** "Podemos ter a liberdade de pensar também como fazer melhor. O ideal é termos já pra
desenvolvimento toda uma estrutura e depois o refinamento dos módulos."
- Por isso esta decisão monta **a estrutura inteira de uma vez**: dados, papéis, agenda por barbeiro e um motor de comissão genérico.
- Os detalhes de cada módulo (taxa do cartão, vale, gorjeta, planos) entram depois, **sem migração nova**, como "tipos" novos dentro dessa estrutura.

**Respostas do dono às perguntas abertas (17/11), nas palavras dele:**
1. **Comissão:** "Pra mim seria configurável… o dono pode configurar como calcular isso, porque tem o custo total da barbearia, né, porque o funcionário usa todos os recursos; ele pode querer adicionar no desconto os seus custos."
   - Como no AppBarber, o app **calcula e relata**: o pagamento ao barbeiro é feito por fora.
2. **Limite de barbeiros:** "Por enquanto não tem limites, só monte o cadastro; depois discutiremos isso em uma nova etapa."
3. **O que o barbeiro vê:** "Configurável também, o adm controla; ele pode ver só os procedimentos que fez e a agenda; o valor é configurável."
   - "No nosso caso, o barbeiro é responsável por fechar o corte."
4. **Plano Unidades:** "Ainda não discutiremos planos."

## 2. Decisões do dono
**Contas e painel (15/11)**
- O barbeiro individual (`organizations.type = individual`) não vê nada de filiais; as telas e os links dele não mudam.
- **Painel único:** um seletor no topo escolhe a unidade (matriz ou filial). Agenda, barbeiros, histórico, faturamento e configurações seguem a unidade escolhida.
- O que se muda com a matriz selecionada vale **só para a matriz**. Para mudar uma filial, troca-se a unidade no topo.

**Página pública (15/11)**
- O **link da marca** tem um select de filiais; trocar no select muda o "Quem somos" (bio, fotos, endereço, horários).
- "Agendar" pelo link da marca abre com a filial selecionada e mostra as outras e os endereços.
- **Cada filial tem o próprio link** (ex.: para outro Instagram).

**Equipe (29/10, 15/11 e 17/11)**
- Cada barbeiro pertence a **uma filial só**.
- A dona cadastra o barbeiro, que recebe um **convite por e-mail** e entra com o próprio login. **Sem limite de barbeiros** por enquanto.
- **O barbeiro conclui ("fecha") os próprios atendimentos.** A dona vê e gerencia tudo.
- **O que o barbeiro vê é configurável pela dona.** O padrão é a própria agenda e os atendimentos que fez, sem valores.
- O cliente escolhe o barbeiro ou "qualquer um disponível". A dona pode passar um atendimento de um barbeiro para outro.
- "Minha equipe" só aparece a partir do segundo barbeiro. Toda unidade tem pelo menos um barbeiro: na migração, é o próprio dono.

**Comissão (29/10 e 17/11)**
- A comissão é **configurável pela dona**: percentual por serviço, com exceção por barbeiro.
- A dona escolhe **o que desconta antes e depois** da comissão (custo do serviço, custos da barbearia, taxas).
- O app **só calcula e relata**: o pagamento ao barbeiro é feito por fora.
- Mudar uma regra **nunca altera o passado**.

**Produto**
- Gestão, não marketplace.
- Planos e limites ficam para uma etapa própria.

## 3. Referências
- **Concorrência:** [análise](../arquitetura/concorrencia-equipe-e-comissao.md). O essencial:
  - **AppBarber:** comissão "bruta, líquida, descontada" pela taxa do cartão. Exemplo: R$ 100 a 50% com 4% de taxa dá R$ 50, R$ 48 e R$ 46.
    - Deduções em % por profissional.
    - Mudança só vale daqui para frente.
    - Bloqueio de horário único e recorrente.
    - Sem horário de trabalho, a agenda do profissional fica fechada.
  - **Trinks:** a taxa da operadora é dividida como a dona escolher; a "taxa administrativa" reduz a base, e a "taxa de comanda" é um valor fixo por atendimento.
- **`barber-saas`** (o executor não tem acesso; trechos colados aqui):
  - **Lock por profissional ao agendar:**
    ```php
    // CreateAppointment: impede dois agendamentos sobrepostos do mesmo profissional
    Appointment::withoutGlobalScope('tenant')
        ->where('professional_id', $data->professional_id)
        ->where('starts_at', '<', $endsAt)->where('ends_at', '>', $startsAt)
        ->lockForUpdate()->exists();
    ```
  - **"Foto" do serviço agendado:** a tabela `appointment_services` guarda `name`, `duration`, `price` e `position` copiados no momento do agendamento. No linkcut, `booking_requests` já guarda `service_name`, `price_in_cents` e `service_ids`; a comissão segue a mesma ideia (seção 4, P5).
  - **Não copiar:** papéis sem verificação, escopo que lê o `auth()` direto e preço só por barbearia.
- **Figma "Linktree UI Kit"** (enviado pelo dono) para a página da marca (P2): <https://www.figma.com/design/cZs3bFLUm7YAzUkA8TqpJl/Linktree-UI---Free-UI-Kit--Recreated---Community---c%C3%B3pia-?node-id=1-14517>.
- **Regras do projeto:**
  - `CLAUDE.md`, `.cursor/rules/`;
  - skill `arquiteto-software`, seção 1: domínio novo `Team`; `Organization` no núcleo `Tenant`; fluxos que cruzam domínios no `Orchestrator`;
  - o `ArchitectureTest` continua valendo.

## 4. Como aplicar
Ordem obrigatória: P1 → P2 → P3 → P4 → P5 → P6. P1 e P3 deixam a **estrutura** pronta; os outros passos usam e refinam.

- [x] **P1 — Organização e unidades.** (executado pelo Cursor, NÃO REVISADO)
  - **Dados:** tabela `organizations` (`id`, `owner_user_id`, `type` = `individual`|`multi_unit`, `name`, `slug` único e reservado como os slugs de tenant). Em `tenants`, `organization_id` e `is_main`. Em `users`, `organization_id`.
  - **Migração dos dados atuais:** uma organização `individual` por tenant, com o tenant como `is_main = true`, sem mudar nenhum slug.
  - **Backend:**
    - o middleware `tenant` aceita a unidade ativa mandada pelo painel (header `X-Unit`), validada contra a organização do usuário;
    - sem header, usa a matriz;
    - criar filial (`POST /api/organization/units`) com nome, slug e endereço, e a cópia opcional das configurações da matriz uma vez;
    - mudar o tipo para `multi_unit` ao criar a 2ª unidade.
  - **Frontend:** seletor de unidade no topo do painel, só para `multi_unit`; a troca recarrega os dados da unidade.
  - **Aceite:**
    - teste de migração com dados antigos;
    - o barbeiro individual passa em todos os testes atuais sem mudança;
    - uma unidade de outra organização dá 403;
    - os dados de uma unidade não aparecem na outra (teste de isolamento);
    - o `ArchitectureTest` passa.

- [x] **P2 — Link da marca com select.** (executado pelo Cursor, NÃO REVISADO)
  - **Backend:** `GET /api/public/brand/{slug}` lista as unidades ativas (nome, slug, endereço, foto), com cache e `throttle:public`.
  - **Frontend:**
    - a página da marca usa o "Quem somos" da unidade escolhida no select;
    - "Agendar" abre com a unidade escolhida e mostra as outras e os endereços;
    - o link `/{slug-da-filial}` continua funcionando.
    - Visual pelo Figma da seção 3.
  - **Aceite:**
    - trocar no select muda o conteúdo sem recarregar a página;
    - o agendamento grava na unidade escolhida;
    - `QueryBudgetTest` da rota da marca;
    - o `/{slug}` antigo continua igual.

- [x] **P3 — Equipe: cadastro, papéis e permissões.** (executado pelo Cursor, NÃO REVISADO; filtragem profunda agenda/comissão fica na P4/P5) Esta é a estrutura base.
  - **Dados:**
    - domínio novo `Team`, com a tabela `staff` (`tenant_id`, `user_id` nullable até aceitar o convite, `name`, `avatar`, `active`);
    - `staff_permissions` com 3 chaves booleanas por barbeiro: `see_prices`, `see_own_commission` e `see_unit_agenda`, todas **false** por padrão;
    - papéis verificados em `users.role` (`owner`, `barber`), com policy.
  - **Migração:** cria um `staff` para cada dono existente, ligado ao usuário dele.
  - **Backend:**
    - convite por e-mail, de uso único e com validade;
    - aceitar o convite cria o login do barbeiro naquela unidade;
    - sem limite de barbeiros.
  - **Frontend:**
    - "Minha equipe" (aparece a partir do 2º barbeiro, ou pelo botão "Adicionar barbeiro") com a lista, o convite, ativar/desativar e as 3 chaves de permissão;
    - o painel do barbeiro mostra só o que as chaves permitem.
  - **Aceite:**
    - o barbeiro sem `see_prices` não recebe nenhum valor na API (teste na resposta, não só na tela);
    - o barbeiro não lê dados de outro barbeiro;
    - o convite expira e não é reutilizável;
    - o individual não vê "Minha equipe" até adicionar alguém.

- [ ] **P4 — Agenda por barbeiro.**
  - **Dados:**
    - `staff_services` (o que cada barbeiro faz; por padrão, todos os serviços da unidade);
    - `staff_business_hours`, que nasce como **cópia** dos horários da unidade, para a agenda não ficar fechada;
    - `staff_time_blocks` (`staff_id`, início, fim, `recurrence` nula ou semanal com dias da semana, observação);
    - `booking_requests.staff_id`.
  - **Backend:**
    - disponibilidade, lock e "horário ocupado" **por barbeiro**, no padrão do `barber-saas` da seção 3;
    - "qualquer um disponível" escolhe um barbeiro livre dentro da mesma transação;
    - a dona reatribui um atendimento a outro barbeiro;
    - o push vai para o barbeiro do atendimento e para a dona, não para todos os aparelhos;
    - **o barbeiro conclui os próprios atendimentos do dia**; a dona pode concluir ou desfazer qualquer um até o fechamento do período (P5).
  - **Frontend:**
    - horários e serviços do barbeiro;
    - bloqueio de horário único e recorrente (ex.: "toda quarta à tarde"), com desbloqueio por período;
    - o cliente escolhe o barbeiro ou "qualquer um" no agendamento.
  - **Aceite:**
    - teste concorrente: o mesmo barbeiro no mesmo horário recusa o 2º, e outro barbeiro da unidade aceita;
    - um bloqueio recorrente tira os horários da disponibilidade;
    - o barbeiro não conclui atendimento de outro barbeiro.

- [ ] **P5 — Comissão configurável** (o motor genérico; refinamentos depois sem migração).
  - **Dados:**
    - **`commission_rules`:** `tenant_id`, `service_id`, `staff_id` nullable (regra geral do serviço ou exceção do barbeiro), `percent` (em décimos de por cento, inteiro) e `starts_on`.
    - **`commission_adjustments`:** os ajustes que a dona configura. Campos:
      - `tenant_id` e `staff_id` nullable (para todos ou para um barbeiro);
      - `name` (ex.: "Uso da estrutura", "Custo do produto");
      - `stage` = `before` (reduz a base) | `after` (sai do valor do barbeiro);
      - `kind` = `percent` | `fixed`;
      - `applies_to` = `price` | `commission` | `appointment`;
      - `value`, `active`, `starts_on`.
      - Um ajuste de sistema "custo do serviço" (`before`) usa o `cost_in_cents` que já existe e pode ser ligado ou desligado.
    - **`commission_entries`:** a "foto" gravada ao concluir. Guarda `booking_request_id`, `staff_id`, a base, o percentual, a lista JSON dos ajustes aplicados (nome, etapa, valor), o valor final em centavos e o `closing_id`.
    - **`commission_closings`:** o fechamento do período. Guarda `tenant_id`, `from`, `to`, `closed_at`, `closed_by` e `reopened_at`.
  - **Cálculo** (centavos inteiros, arredondamento único no fim, meio para cima):
    1. base = preço − ajustes `before`;
    2. comissão = base × percentual;
    3. valor = comissão − ajustes `after`.
  - **Backend:**
    - grava a `commission_entry` na conclusão (P4), no mesmo `Orchestrator` que já grava a receita;
    - relatório por barbeiro e período, com o CSV por e-mail como o do histórico;
    - fechar o período trava os atendimentos dele: desfazer a conclusão de um atendimento fechado é recusado até a dona reabrir o fechamento.
  - **Frontend:**
    - regras por serviço e exceção por barbeiro;
    - lista de ajustes;
    - **simulador** ("serviço de R$ 100 → barbeiro recebe R$ 46") na própria tela;
    - relatório e botão "Fechar período" e "Reabrir";
    - o barbeiro vê a própria comissão só com `see_own_commission`.
  - **Aceite:**
    - testes AAA com os exemplos do AppBarber (com a taxa modelada como um ajuste `after` percentual: R$ 50, R$ 48 e R$ 46) e da Trinks;
    - mudar uma regra não altera as `commission_entries` já gravadas;
    - atendimento de período fechado não pode ser desfeito;
    - o `ArchitectureTest` passa (cálculo no `Finance` ou no `Team`; fluxo no `Orchestrator`).

- [ ] **P6 — Relatórios da marca.**
  - **Backend:** faturamento, histórico e comissões somando as unidades da organização, numa Action do `Orchestrator` liberada só para a dona. É o único ponto que cruza unidades. CSV por e-mail.
  - **Frontend:** "Todas as unidades" no seletor, só para a dona, nos relatórios.
  - **Aceite:**
    - a soma bate com as unidades separadas;
    - o barbeiro recebe 403;
    - outra organização nunca entra na soma.

**Notas para todos os passos**
- **LGPD:** a exportação e a exclusão de dados passam a incluir barbeiros, convites, permissões, comissões e a organização.
- **Desempenho:** índice por `(tenant_id, staff_id, date)` nas tabelas de agenda e de comissão. Seguir o `QueryBudgetTest`.
- **Fronteiras:** `Team` (barbeiros, horários, bloqueios e permissões) e `Finance` (comissões) não se importam; o `Orchestrator` junta os dois.

## 5. Fora do escopo
- Planos, preços e limites (barbeiros e filiais): etapa própria.
- Pagamento ou repasse ao barbeiro dentro do app. O app só calcula e relata.
- Comanda e caixa diários; vale e adiantamento; gorjeta.
- Comissão de produto e de assinatura.
- **Forma de pagamento e taxa do cartão por forma:** o motor da P5 já comporta isso como ajuste; a gravação da forma de pagamento espera a pergunta 1 da seção 6.
- Modo secretária ou recepção.
- Barbeiro em várias filiais (recusado pelo dono).
- Um sistema separado por unidade (recusado no ADR 0002).

## 6. Perguntas abertas
1. **Forma de pagamento ao concluir:** gravar como o cliente pagou (dinheiro, Pix, débito, crédito) para descontar a taxa do cartão de um jeito automático?
   - Não bloqueia a P5: sem isso, a taxa entra como um ajuste percentual fixo.
2. **Periodicidade padrão do fechamento:** semanal, quinzenal ou mensal?
   - Não bloqueia: a dona escolhe o período livre na tela; o padrão sugerido é mensal.
3. **Textos das 3 permissões do barbeiro e se algum padrão deve vir ligado.**
   - Não bloqueia: começam desligadas.

## 7. Execução
<!-- Preenchido pelo executor. Uma entrada por execução, a mais nova em cima. -->

### 2026-11-20 — execução 3 (executado pelo Cursor, NÃO REVISADO)
- **Feito:** P3 — domínio `Team` (`staff` / permissões / convites), papéis owner/barber, aceitar convite, redaction de preços na API, tela `/painel/equipe` e `/convite/:token`. Evidência: `BackfillOwnerStaffTest`, `TeamStaffTest`, vitest Team; commits desta entrega.
- **Já estava aplicado:** P1–P2.
- **Pendente:** P4–P6; filtragem de agenda/comissão pelas chaves (só flags + UI nesta fatia); avatar upload.
- **Perguntas para o dono/arquiteto:** 1) validade do convite em dias (provisório: 7)? 2) textos finais das 3 permissões?
- **Riscos ou desvios:** TTL do convite = 7 dias em `config/team.php` até decisão; suite PHP com 7 falhas pré-existentes (ShareCard URL `localhost:5173` e afins).
- **Branch / PR:** `claude/practical-wozniak-auyi2x` (sem PR).

### 2026-11-20 — execução 2 (executado pelo Cursor, NÃO REVISADO)
- **Feito:** P2 — `GET /api/public/brand/{slug}` com cache/throttle; BioLink com select de unidades (Quem somos sem reload; Agendar no slug da unidade; lista de outras unidades/endereços). Evidência: `PublicBrandTest`, QueryBudget da marca, testes BioLink; commits `d7b7dc4`, `bc47d91`.
- **Já estava aplicado:** P1 (schema/organização/X-Unit).
- **Pendente:** P3–P6; pixel-perfect Figma; tela de criar filial no painel.
- **Perguntas para o dono/arquiteto:** nenhuma bloqueante.
- **Riscos ou desvios:** visual sem Figma (BioLink + SelectField); suite PHP com 7 falhas pré-existentes/flaky (ShareCard URL `localhost:5173`); vitest com timeouts fora do BioLink.
- **Branch / PR:** `claude/practical-wozniak-auyi2x` (sem PR).

### 2026-11-20 — execução 1 (executado pelo Cursor, NÃO REVISADO)
- **Feito:** P1 completo (schema/backfill, middleware `X-Unit`, `GET/POST` organization/units com cópia opcional de settings, seletor no painel + header). Evidência: testes `BackfillOrganizationsTest`, `ResolveTenantTest`, `UnitIsolationTest`, `CreateUnitTest`; commits `055bc4d`, `31190a6`, `c330611`, `264f0aa` (+ fix slug budget).
- **Pendente:** P2–P6; tela de criar filial no painel (só API nesta fatia).
- **Perguntas para o dono/arquiteto:** nenhuma bloqueante.
- **Riscos ou desvios:** `owner_user_id` fica nullable no insert (preenchido na mesma transação); cópia de settings zera ids de imagem (avatar/capa/fotos); `organization_id` NOT NULL só reforçado no MySQL (SQLite de teste deixa nullable após backfill); signup e factories passam a criar organização.
- **Branch / PR:** `claude/practical-wozniak-auyi2x` (sem PR).
