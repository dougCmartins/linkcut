# Roadmap do linkcut

> Ordem combinada com o dono do produto. A migração das telas para a API (Etapas 1 a 3) e o endereço/distância já estão na `main` ([plano da migração](plano-migracao-backend.md), [localização](requisitos-localizacao.md)).

## Fase A: fundação (agora)

O que o barbeiro precisa para usar a plataforma sozinho no dia a dia.

| Etapa | O quê | Observação |
|---|---|---|
| 4 ✅ | **Aviso de novo pedido por push** | Web Push padrão (VAPID): sem conta externa e sem custo. No iPhone, pelo app na Tela de Início. [Requisitos](requisitos-notificacoes.md). |
| 5 ✅ | **Área do cliente e lembretes** | O cliente informa o e-mail ao agendar e recebe tudo por e-mail: pedido recebido, aceito ou recusado e o lembrete na véspera. Cadastro opcional (código no e-mail + senha) para ver os horários em `/conta` e agendar de novo. Quem não se cadastra recebe **um** convite. Para o barbeiro, o botão "Lembrar no WhatsApp" (`wa.me`, sem custo). [Requisitos](requisitos-area-do-cliente.md). |
| 6 | **Provedor de SMS** | Decidido: o código de login vai **por e-mail por padrão** (custo zero). O SMS fica integrado a um provedor real, mas **desligado por configuração** até haver receita. A tela só oferece SMS quando estiver ligado. |
| 7 | **Onboarding** | Decidido: **cadastro aberto** em `/cadastrar`, com o e-mail confirmado por código. Depois, um passo a passo cria a barbearia: nome, link (`/:slug`), WhatsApp, endereço e serviços. Prepara o teste grátis do billing. |

## Fase B: negócio (depois da fundação)

| Etapa | O quê | Observação |
|---|---|---|
| 8 | **Módulos pagos visíveis e bloqueados** | Os módulos pagos aparecem no painel, mas desativados, com um aviso que incentiva a assinar o plano. |
| 9 | **Billing (assinatura)** | Planos, teste grátis, cobrança recorrente por um gateway de pagamento, e bloqueio de quem não paga (usa o "tenant inativo" que já existe). |
| 10 | **Bilíngue** | A interface em mais um idioma além do pt-BR. O i18n já existe; falta escolher o idioma e traduzir. |
| 11 | **Painel admin** | Para o dono da plataforma: barbearias, assinantes, inadimplência, métricas gerais e MRR. |

## Módulos pagos (só com plano ativo)

Ideias guardadas para depois do billing. Cada módulo só funciona com a assinatura em dia. Antes disso, aparece bloqueado com o convite para assinar (Etapa 8).

| Módulo | O que faz |
|---|---|
| **CRM de clientes** | Lista dos clientes com histórico de visitas e quanto cada um gastou. É a base dos módulos abaixo. |
| **Vitrine de produtos** | O barbeiro mostra ou vende produtos (pomadas, óleos) para os clientes. |
| **Mais links no link da bio** | Links extras além de agendar, portfólio e WhatsApp. |
| **Lembrete de retorno** | Avisa o cliente na hora de refazer o procedimento (ex.: corte a cada 20 dias). |
| **Avaliação e depoimentos** | O cliente avalia o atendimento depois de concluído (pela área do cliente) e pode deixar um depoimento. As notas aparecem na página pública. |
| **Visagismo com IA** | O cliente envia fotos e responde um formulário, e recebe o visagismo (o corte que combina com o rosto). O barbeiro vê o resultado para aplicar o corte. Já existe um começo no projeto **barber-saas** (`frontend/src/Domain/Visagism`), para reaproveitar quando chegar a vez. |

## Decisões em aberto

- **WhatsApp automático:** a API oficial do WhatsApp **não será usada por enquanto** (é paga por conversa). Os avisos automáticos ao cliente vão por e-mail; no WhatsApp, só o botão do barbeiro (`wa.me`, ele envia com um toque).
- **Cancelamento pelo cliente** na área do cliente: depois.

## Como cada etapa é entregue

Igual às etapas anteriores:
- plano aprovado antes de começar;
- backend com testes (padrão AAA) e frontend ligado à API;
- teste de ponta a ponta no navegador;
- documentação atualizada;
- um PR por etapa, com merge na `main`.
