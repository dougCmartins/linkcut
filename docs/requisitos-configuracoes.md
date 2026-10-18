# Requisitos — Módulo de Configurações do Barbeiro

> **Escopo:** frontend (Vue 3 + Tailwind CSS v4). Enquanto o backend não existir, os dados ficam salvos no navegador, atrás de uma camada única (`Settings/settings.api.ts`) que depois será trocada pela API.
> **Rota:** `/painel/configuracoes` (acessível pelo ícone de engrenagem no Painel).

## 1. Objetivo

Hoje as telas públicas (Link da Bio, Portfólio, Agendamento) e o Painel usam dados fixos no código. O módulo de Configurações passa a ser a **única fonte de verdade** de tudo o que o cliente vê. Assim o barbeiro edita textos, imagens, serviços, horários e cores sem depender de um desenvolvedor.

## 2. Mapa: o que estava fixo → onde passa a ser configurado

| Tela pública | Elemento | Antes (fixo) | Aba de configuração |
|---|---|---|---|
| Link da Bio | Nome, @usuário, bio curta | `mocks/barber.ts` | **Link** |
| Link da Bio | Foto de perfil (avatar) | URL fixa | **Link** (upload) |
| Link da Bio | Imagem de fundo | Usava a capa do portfólio | **Link** (upload) |
| Link da Bio | Botão WhatsApp (número + mensagem) | Número fixo e texto no i18n | **Link** |
| Link da Bio | Ícones de redes sociais | 3 URLs fixas | **Link** |
| Portfólio | Foto de capa | URL fixa | **Portfólio** (upload) |
| Portfólio | Frase de apresentação (citação) | Texto fixo | **Portfólio** |
| Portfólio | História | 2 parágrafos fixos | **Portfólio** (textarea) |
| Portfólio | 3 destaques (Estilo / Foco / Agenda) | Fixos | **Portfólio** |
| Portfólio | 4 fotos de trabalhos | URLs fixas | **Portfólio** (upload, até 4) |
| Agendamento | Serviços (nome, duração, preço) | Corte e Barba fixos | **Agenda** |
| Agendamento | Dias e horários disponíveis | Função `availableTimes` fixa | **Agenda** (horário por dia) |
| Agendamento | Intervalo entre horários | Fixo | **Agenda** |
| Agendamento | Janela de dias para agendar | 7 dias fixos | **Agenda** |
| Agendamento | Bloqueio por férias | Não existia | **Agenda** (trava de férias) |
| Todas | Cores de botões e textos, tema claro/escuro | 2 paletas fixas no CSS | **Aparência** |
| Painel | Nome e foto do barbeiro no cabeçalho | `mocks/barber.ts` | **Link** (reaproveitado) |

## 3. Requisitos funcionais

### Estrutura da tela
- **RF01 — Abas.** A tela tem 4 abas: **Link**, **Portfólio**, **Agenda** e **Aparência**. A aba ativa fica na URL (`?aba=link|portfolio|agenda|aparencia`), o que permite enviar um link direto para uma aba. As abas funcionam com as setas do teclado.
- **RF02 — Pré-visualização ao vivo.** As abas Link, Portfólio e Aparência mostram um mini celular que reflete as alterações antes de salvar.
- **RF03 — Salvar e descartar.** Cada aba tem seu próprio rascunho. Quando há alterações, aparece uma barra fixa com **Salvar** e **Descartar**. O botão Salvar fica desabilitado enquanto houver erro. A aba com alterações pendentes ganha um ponto indicador. Ao sair da tela com alterações pendentes, o sistema pede confirmação.

### Aba Link
- **RF04 — Perfil.** Campos: nome (obrigatório, 2–40 caracteres), @usuário (até 30), texto de apresentação (até 160), foto de perfil e imagem de fundo.
- **RF05 — WhatsApp.** Telefone com DDD (10 ou 11 dígitos, ou já com o 55 na frente) e mensagem padrão (até 200 caracteres). O botão público abre `https://wa.me/55<número>?text=<mensagem>`.
- **RF06 — Redes sociais.** Instagram, TikTok e YouTube. Todas opcionais; se preenchidas, precisam começar com `https://`. A tela pública só mostra as que estiverem preenchidas.

### Aba Portfólio
- **RF07 — Textos.** Frase de apresentação (textarea, até 200 caracteres) e história (textarea, até 1.200; uma linha em branco separa os parágrafos).
- **RF08 — Destaques.** 3 destaques com rótulo (até 16) e valor (até 24). O destaque sem valor não aparece.
- **RF09 — Fotos de trabalhos.** No máximo **4 fotos**. Cada foto tem um texto alternativo obrigatório (até 80 caracteres). Dá para trocar ou remover cada foto. Com 4 fotos, o botão de adicionar fica bloqueado. Sem fotos, a galeria some da página pública.
- **RF10 — Capa.** Upload da foto de capa do portfólio.

### Uploads (Link e Portfólio)
- **RF11 — Regras de imagem.** Aceita JPG, PNG e WebP com até 5 MB. Antes de salvar, a imagem é redimensionada para no máximo 1.600 px no lado maior. Arquivos inválidos mostram o erro no próprio campo.

### Aba Agenda
- **RF12 — Serviços.** Criar, editar e remover serviços: nome (obrigatório, até 30), duração (5 a 240 min, em múltiplos de 5) e preço (maior que zero). É obrigatório manter pelo menos 1 serviço.
- **RF13 — Horário de funcionamento.** Para cada dia da semana: aberto/fechado, horário de abertura e de fechamento. O fechamento precisa ser depois da abertura.
- **RF14 — Intervalo e janela.** Intervalo entre horários (15, 30, 45 ou 60 min) e quantos dias à frente o cliente pode agendar (7, 14, 21 ou 30).
- **RF15 — Trava de férias.** Interruptor "Travar agenda", datas de início e fim (opcionais) e mensagem para o cliente (até 160 caracteres). Sem datas, a agenda fica travada até o barbeiro desligar. Com datas, só fica travado esse período. A data final não pode ser anterior à inicial.
- **RF16 — Disponibilidade calculada.** O modal de agendamento só oferece horários que:
  1. estão dentro da janela de dias;
  2. caem num dia aberto e fora do período de férias;
  3. cabem no expediente com a duração do serviço escolhido;
  4. não se sobrepõem a uma solicitação pendente ou confirmada;
  5. ainda não passaram (quando o dia é hoje).
- **RF17 — Mensagem de férias.** Com a agenda toda travada, o modal mostra a mensagem de férias no lugar do formulário. Dias travados aparecem desabilitados no seletor.

### Aba Aparência
- **RF18 — Paletas.** Opções: **Padrão** (as cores atuais do app), Clássico, Oceano, Floresta, Vinho e Minimal. Cada uma tem versão clara e escura.
- **RF19 — Modo.** O barbeiro escolhe o modo padrão: claro ou escuro.
- **RF20 — Cor livre.** Interruptor "Usar cor personalizada" com seletor de cor (hex). O sistema calcula sozinho a cor do texto dos botões (preto ou branco) e a sombra do botão. Se o contraste da cor com o fundo ficar abaixo de 3:1, mostra um aviso.
- **RF21 — Restaurar padrão.** Um botão volta a aparência para Padrão, escuro, sem cor livre.
- **RF22 — Visitante.** O botão claro/escuro continua nas páginas públicas. O visitante pode trocar o modo só para ele (fica salvo no navegador dele), mas o padrão é o que o barbeiro escolheu. No Painel e nas Configurações vale sempre o tema do barbeiro.

## 4. Requisitos não funcionais

| Código | Categoria | Requisito |
|---|---|---|
| RNF01 | Usabilidade | Mobile-first: tudo funciona a partir de 360 px de largura. A pré-visualização fica abaixo do formulário no celular e ao lado dele em telas largas. |
| RNF02 | Acessibilidade | Todo campo tem `label`; erros ligados ao campo (`aria-describedby`, `aria-invalid`); abas com `role="tablist"` e navegação por setas; interruptores com `role="switch"`; foco visível; contraste AA nas paletas prontas; texto alternativo obrigatório nas fotos de trabalhos. |
| RNF03 | i18n | Todo texto de interface vem do arquivo de idioma (`i18n/pt-BR.ts`). Código, variáveis e chaves em inglês. |
| RNF04 | Segurança | Nenhum `v-html` (o Vue escapa o texto, o que evita XSS). Links de redes sociais só com `https://`. WhatsApp guardado só com dígitos. Uploads validados por tipo e tamanho. |
| RNF05 | Desempenho | A troca de tema não recarrega a página: só muda variáveis CSS. As imagens são redimensionadas no navegador antes de serem guardadas. As fotos da galeria carregam sob demanda (`loading="lazy"`). |
| RNF06 | Manutenibilidade | Os tokens de cor ficam num único arquivo (`styles/core.css`) lido pelo Tailwind. Toda a persistência passa por `Settings/settings.api.ts` (para trocar pela API sem mexer nas telas). Validações são funções puras e testáveis. |
| RNF07 | Persistência | Textos, agenda e tema no `localStorage`, com versão de schema e volta aos valores padrão se os dados estiverem corrompidos. Imagens no IndexedDB. Se o navegador bloquear o armazenamento, o app continua funcionando na sessão atual. |
| RNF08 | Qualidade | TypeScript estrito; testes Vitest no padrão AAA para validações, cálculo de horários, cores, store e componentes principais. |

## 5. Fora do escopo (próximas etapas)

- Salvar no servidor (API Laravel + MySQL) e sincronizar entre aparelhos.
- Intervalo de almoço / mais de um turno por dia.
- Reordenar as fotos de trabalho arrastando.
- Bloqueio de horários avulsos (fora das férias).
- Mais idiomas além do português.
