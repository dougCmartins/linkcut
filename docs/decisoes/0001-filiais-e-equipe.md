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

- [x] **P1 — Organização e unidades.**
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

- [ ] **P2 — Link da marca com select.**
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

- [ ] **P3 — Equipe: cadastro, papéis e permissões.** Esta é a estrutura base.
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

### 2026-11-19 — execução 3
- **Feito: P3, equipe — só o backend** (o passo é grande; o frontend fica para a próxima execução).
  - **Dados:**
    - domínio novo `Team`, com `staff` (um barbeiro por unidade, `user_id` nulo até aceitar o convite);
    - `staff_permissions`, com `see_prices`, `see_own_commission` e `see_unit_agenda`, todas desligadas;
    - `staff_invitations`, onde só o hash do token é guardado, com uso único e validade de 7 dias.
  - **Migração:** o dono de cada barbearia existente virou o primeiro barbeiro dela. O cadastro novo faz o mesmo.
  - **Rotas (só a dona):**
    - `GET` e `POST /api/team/staff` (lista e convida);
    - `PUT /api/team/staff/{id}` (liga ou desliga e ajusta as 3 chaves);
    - `POST /api/team/staff/{id}/invitation` (reenvia o convite e invalida os anteriores).
    - Sem limite de barbeiros.
  - **Convite:** `POST /api/team/invitations/accept` (público, `throttle:login`) recebe o token, a senha e o aceite dos termos.
    - Cria o login do barbeiro (papel `barber`) na unidade que convidou e já abre a sessão.
    - O link do e-mail é `/equipe/convite?token=…`.
  - **Papéis verificados:** o middleware `role.owner` protege finanças, configurações, produtos, avaliações, conta, unidades e equipe. O barbeiro recebe 403 `OWNER_ONLY`.
  - **Valores:** sem `see_prices`, nenhuma chave `*_in_cents` sai da API para o barbeiro, em qualquer rota. O corte é feito na resposta.
  - **Desligar um barbeiro** encerra todas as sessões dele na hora, e o login de quem está desligado é recusado (`STAFF_INACTIVE`).
  - **LGPD:** a exportação da barbearia inclui a equipe. A exclusão já levava a equipe junto, porque tudo é apagado em cascata com a unidade.
  - **Evidência:**
    - `backend/src/Domain/Orchestrator/Team/Tests/Feature/TeamTest.php`: 7 testes (convite e aceite; uso único e validade; sem valores na resposta sem `see_prices` e com valores com ela; 403 nas áreas da dona; desligar encerra a sessão e bloqueia o login; uma unidade não vê a equipe de outra; a dona não se desliga e não convida o mesmo e-mail duas vezes);
    - `backend/src/Domain/Team/Tests/Feature/TeamMigrationTest.php` (migração com dados antigos);
    - `SignUpTest` (o dono vira barbeiro);
    - 289 testes no total, o `ArchitectureTest` incluído;
    - commit `cba9d67`.
- **Já estava aplicado:** P1 e P2 (execuções 1 e 2).
- **Pendente:**
  - **P3 no frontend:**
    - "Minha equipe" (a partir do 2º barbeiro, ou pelo botão "Adicionar barbeiro"), com a lista, o convite, o ligar e desligar e as 3 chaves;
    - a tela `/equipe/convite` para aceitar;
    - o painel do barbeiro mostrando só o que as chaves permitem.
    - **Até essa tela existir, não mande convites de verdade:** o link do e-mail ainda não abre nada.
  - **"O barbeiro não lê dados de outro barbeiro"** (aceite do P3): depende do `booking_requests.staff_id`, que é do P4. Hoje o barbeiro vê a agenda da unidade inteira, sem valores. Fica provado no P4, junto com `see_unit_agenda`.
  - **P4 a P6.**
- **Perguntas para o dono/arquiteto:**
  1. Continuam abertas as perguntas das execuções 1 e 2 (o botão "Nova filial", o assistente na filial nova, a exclusão da matriz e o que é o "Quem somos").
  2. **Filial nova sem barbeiro:** "Toda unidade tem pelo menos um barbeiro", mas a dona é barbeira só da matriz, e "cada barbeiro pertence a uma filial só". Uma filial nova nasce sem ninguém até a dona convidar alguém. Está certo?
  3. **Barbeiro desligado:** tirei as sessões e o login dele. Os atendimentos e o histórico dele ficam (nada é apagado). Confirmar.
- **Riscos ou desvios:**
  - O barbeiro agora recebe 403 nas configurações, inclusive na leitura (`GET /api/settings` traz preços e custos).
  - A tela do barbeiro do P3 vai precisar de uma leitura própria, sem valores.
- **Branch / PR:** `claude/charming-euler-dh3nfa` (sem PR).

### 2026-11-18 — execução 2
- **Feito: P2, link da marca com select** (faltou só o visual do Figma; ver "Pendente").
  - **Backend:**
    - `GET /api/public/brand/{slug}` lista as unidades ativas da marca, com a matriz primeiro e o nome, a foto e o endereço de cada uma;
    - a rota tem `throttle:public`, ETag e cache por marca de 5 min, esquecido quando uma unidade é criada;
    - `GET /api/public/{slug}` ganhou `brand_slug`, preenchido só na matriz de uma marca com filiais;
    - `brand` virou um slug reservado.
  - **Frontend:**
    - na página da matriz de uma marca com filiais aparece o select "Unidade", com o endereço da unidade escolhida;
    - trocar no select mostra a página da unidade no lugar (`?unidade=<slug>`), sem sair da página nem recarregar;
    - um slug que não é unidade da marca é ignorado;
    - "Agendar" e "Sobre" levam a unidade escolhida, e fechar o agendamento também a mantém;
    - o agendamento mostra a unidade de destino com o endereço, e as outras unidades com os endereços e um "Agendar nesta";
    - o link próprio de cada filial (`/{slug-da-filial}`) e o barbeiro individual ficam como antes: sem select e sem chamada a mais.
  - **Evidência:**
    - `backend/src/Domain/Orchestrator/PublicPage/Tests/Feature/BrandLinkTest.php`: 4 testes (a lista da marca, o 404 do individual, só a matriz é link da marca e o agendamento vai para a filial escolhida);
    - `QueryBudgetTest`: as mesmas consultas para 2 ou 6 unidades, e zero com cache;
    - `frontend/src/BioLink/__tests__/BrandLink.test.ts`: 4 testes;
    - todos os testes passaram (281 no backend, 389 no frontend);
    - commits `af0abe6` (backend) e `165eb02` (frontend).
- **Já estava aplicado:** P1 (execução 1).
- **Pendente:**
  - **P2, o visual pelo Figma "Linktree UI Kit":** não tenho acesso ao Figma neste ambiente. O select usa por enquanto os componentes atuais (`SelectField`). Preciso das telas descritas no arquivo da decisão (layout, cores, tamanhos) ou de prints no repositório.
  - **P3 a P6.**
- **Perguntas para o dono/arquiteto:**
  1. As 3 perguntas da execução 1 continuam abertas: onde fica o botão "Nova filial", se a filial nova passa pelo assistente e se excluir a conta na matriz apaga todas as filiais. Este branch ainda não foi para a `main`, então o chat do arquiteto não as viu. Basta fazer o merge para que ele leia esta seção.
  2. **O que é o "Quem somos" que o select troca?** Fiz o select na página de links (`/{marca}`), e a página "Sobre" (`/{marca}/portfolio`) segue a unidade escolhida. Se "Quem somos" for só a página "Sobre", o select também deveria aparecer lá?
- **Riscos ou desvios:**
  - A mudança de nome, foto ou endereço de uma unidade aparece na lista da marca em até 5 minutos. O cache da marca só é apagado na hora quando uma unidade é criada.
  - Trocar de unidade no select também troca o tema da página, porque cada unidade tem o seu.
- **Branch / PR:** `claude/charming-euler-dh3nfa` (sem PR).

### 2026-11-17 — execução 1
- **Feito: P1, organização e unidades.**
  - **Dados:**
    - tabela `organizations` (dona, tipo, nome e slug);
    - `tenants.organization_id` e `is_main`;
    - `users.organization_id`.
    - Cada barbearia existente virou uma organização `individual`, com ela mesma como matriz e o slug igual.
  - **Unidade ativa:** o painel manda a unidade escolhida no cabeçalho `X-Unit`.
    - Só a dona da organização abre outra unidade, e só uma das unidades dela. O resto dá 403 `UNIT_FORBIDDEN`.
    - Sem o cabeçalho, vale a unidade do próprio usuário, que para a dona é a matriz.
  - **Rotas:**
    - `GET /api/organization/units` lista as unidades: todas para a dona, só a própria para os outros.
    - `POST /api/organization/units` cria a filial, só para a dona (`ORGANIZATION_OWNER_ONLY` para os outros). Recebe nome, slug e endereço, e pode copiar uma vez as configurações da matriz.
    - A 2ª unidade muda a organização para `multi_unit`.
    - O slug da filial não pode repetir o de nenhuma unidade nem o de nenhuma marca.
  - **Frontend:** o seletor "Unidade" fica no topo do painel, do histórico, das despesas e das configurações, e só aparece para `multi_unit`.
    - A troca guarda a unidade no aparelho e recarrega o painel.
    - Chamadas públicas e da área do cliente nunca mandam `X-Unit`.
    - Sair da conta esquece a unidade.
  - **Ajustes para não quebrar com filiais:**
    - excluir a conta na matriz apaga a marca inteira; numa filial, apaga só a filial;
    - o CSV do histórico por e-mail funciona para uma filial da dona;
    - o link de entrada lembrado no aparelho continua sendo o da unidade da própria dona.
  - **Evidência:**
    - `backend/src/Domain/Orchestrator/Units/Tests/Feature/UnitsTest.php`: 8 testes, incluindo isolamento (as configurações da matriz e da filial não se misturam), 403 de outra organização, só a dona cria e abre unidades, slug ocupado ou reservado e exclusão;
    - `backend/src/Domain/Tenant/Tests/Feature/OrganizationsMigrationTest.php`: migração com dados antigos;
    - `SignUpTest`: o cadastro cria a organização com a dona;
    - `frontend/src/Domain/Tenant/__tests__/UnitPicker.test.ts`: 4 testes;
    - os 267 testes de backend e os 381 de frontend que já existiam passaram sem mudança, o `ArchitectureTest` incluído;
    - commits `bafdc2d` (backend) e `e385368` (frontend).
- **Já estava aplicado:** nada desta decisão.
- **Pendente:** P2 a P6, na ordem. O próximo é o P2 (link da marca com select).
- **Perguntas para o dono/arquiteto:**
  1. **Onde a dona cria a filial na tela?** O P1 pede a rota de criar filial e o seletor, mas não diz onde fica o botão "Nova filial". O barbeiro individual "não vê nada de filiais", e o seletor só aparece a partir da 2ª unidade, então hoje a 1ª filial só pode ser criada pela API. Sugestões: um item em Configurações, ou um "Adicionar unidade" no fim do seletor junto de um link discreto para o individual.
  2. **A filial nova passa pelo assistente de boas-vindas?** Fiz assim, como num cadastro novo, para conferir WhatsApp e serviços. Confirmar.
  3. **Excluir a conta na matriz apaga todas as filiais.** Escolhi assim para não deixar filiais sem dona. A outra opção seria recusar enquanto houver filial. Confirmar.
- **Riscos ou desvios:**
  - **A cópia das configurações da matriz não leva as imagens** (foto, capa e fotos do portfólio), porque os arquivos são de cada unidade. A dona sobe as imagens de novo na filial.
  - **Ponto no mapa:** sem as coordenadas enviadas, o endereço da filial é localizado pelo mesmo serviço das configurações. Se o serviço não achar, fica sem ponto no mapa, como hoje.
  - **Avisos no celular da dona:** continuam valendo só na unidade em que ela os ativou. O "push por usuário" é o P4.
  - **Banco:** no teste da migração (SQLite), as chaves estrangeiras ficam desligadas durante a recriação da tabela. Em produção (MySQL), a coluna entra sem recriar a tabela.
- **Branch / PR:** `claude/charming-euler-dh3nfa` (sem PR).

