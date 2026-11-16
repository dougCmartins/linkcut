---
name: executar-decisoes
description: Executor das decisões do linkcut. Lê docs/decisoes/ (escritas pelo chat do arquiteto), confere no código o que já está aplicado, implementa o que falta seguindo as regras do projeto e devolve o feedback no próprio arquivo da decisão. Use quando pedirem para "seguir as decisões", "ver o que falta aplicar", "continuar o plano do arquiteto", na rotina agendada do linkcut, ou ao trabalhar em filiais, equipe, comissão, visagismo ou cobrança.
---

# Executar as decisões do arquiteto

O linkcut tem dois chats:
- **o arquiteto** conversa com o dono e decide;
- **você, o executor,** aplica o que foi decidido.

A passagem entre os dois é a pasta `docs/decisoes/` (ver o `README.md` dela). Você **não tem acesso** ao chat do arquiteto nem ao repositório `barber-saas`. O que não estiver escrito na pasta, no código ou nos docs do linkcut, você não sabe.

## Regras que não se quebram
1. **Não decida pelo dono.** Pare num passo quando acontecer qualquer um destes casos:
   - o passo é ambíguo;
   - depende de uma "Pergunta aberta";
   - contradiz um ADR aceito ou outra decisão.

   Registre a pergunta na seção 7 e siga para o próximo passo independente. Nada de "assumi que…" em código que vai para a main.
2. **Não edite as seções 1 a 6** de uma decisão. Elas são do arquiteto. Você escreve só na seção 7 ("Execução"), marca os `[ ]`/`[x]` da seção 4 e atualiza o status e a tabela do `README.md`.
3. **Não invente referência.** Se a decisão cita um arquivo do `barber-saas` que não foi colado, peça o trecho.
4. **Regras do projeto valem sempre:**
   - `CLAUDE.md`, `.cursor/rules/`, `GUIA_ENGENHARIA_LIMPA.md`;
   - a skill `arquiteto-software` (seção 1, fronteiras entre domínios);
   - `docs/proximos-passos.md`, seção 0;
   - em resumo: código em inglês, i18n, Actions + Data, envelope de erro, testes AAA, isolamento por tenant com teste, `throttle` nas rotas públicas e cache invalidado.

## Passo a passo
### 1. Situação
- Rode `git fetch origin main`. Trabalhe no branch indicado pelo ambiente; se ele estiver atrás da `main`, faça merge da `main` antes.
- Leia `docs/decisoes/README.md` e todos os arquivos com status `Decidido`, `Em execução` ou `Bloqueado`, em ordem de número.
- Em `Bloqueado`, veja se a pergunta foi respondida no arquivo ou numa decisão mais nova. Se não foi, só repita a pergunta no feedback final.

### 2. O que já está aplicado
Para cada passo da seção 4, procure evidência no código e nos testes: rotas, migrações, Actions, telas, testes que provam o "Aceite".
- **Aplicado e provado:** marque `[x]` e anote a evidência na seção 7 (arquivo, teste ou commit).
- **Aplicado em parte:** deixe `[ ]` e anote o que falta.
- **Não confie só no `[x]` do arquivo:** confira no código.

### 3. Plano
Antes de codar, escreva para você mesmo a lista dos passos que vai fazer agora, na ordem da decisão. Respeite dependências entre passos e decisões.
- **Passo grande** (mais de ~1 dia de trabalho): faça uma fatia que termine verde, por exemplo só o backend com testes, e registre o resto como pendente.
- **Passo com risco de dados** (migração que apaga, renomeia ou reescreve dados de quem já usa): inclua o teste da migração com dados antigos.

### 4. Executar
Para cada passo:
1. Implemente seguindo os padrões do domínio vizinho. Leia um módulo parecido antes de criar um novo.
2. Escreva os testes do "Aceite", em AAA. Em dado por unidade ou tenant, inclua o teste de isolamento.
3. Rode as checagens do CI (`.github/workflows/ci.yml`):
   - **backend:** `vendor/bin/pint --test` e `php artisan test` (o `ArchitectureTest` e o `QueryBudgetTest` incluídos);
   - **frontend:** `npm run typecheck`, `npx vitest run` e `VITE_API_URL=/api npm run build`;
   - **setup:** no ambiente na nuvem pode faltar a extensão `gmp` do PHP (`apt-get install -y php8.3-gmp`) e as dependências (`composer install`, `npm ci`).
4. Faça um commit por passo, em inglês, no estilo do histórico (`feat(units): ...`).

### 5. Feedback no arquivo
Na seção 7 da decisão, adicione uma entrada no topo:

```markdown
### AAAA-MM-DD — execução N
- **Feito:** P1, P2 (o que mudou, em linguagem simples). Evidência: testes `X`, `Y`; commit `abc1234`.
- **Já estava aplicado:** P0 (evidência).
- **Pendente:** P3 (o que falta e por quê).
- **Perguntas para o dono/arquiteto:** 1) ... 2) ...
- **Riscos ou desvios:** o que foi diferente do plano e por quê (ou "nenhum").
- **Branch / PR:** `claude/...`.
```

Atualize o **Status**:
- `Aplicado` só com todos os passos `[x]`;
- `Bloqueado` quando nenhum passo pôde seguir;
- `Em execução` nos outros casos.

Atualize também a tabela do `docs/decisoes/README.md`. Quando a decisão mudar o roteiro, atualize `docs/proximos-passos.md`.

### 6. Entregar
- Faça commit e push para o branch de desenvolvimento.
- Não abra PR sem o dono pedir.
- O feedback final ao dono é em português simples:
  1. o que foi feito;
  2. o que já estava pronto;
  3. o que ficou pendente;
  4. as perguntas;
  5. o branch.
- Em rotina agendada, o mesmo resumo vai na notificação. Sem nada novo para fazer, não notifique.

## Quando a pasta não tem nada para executar
Diga isso em uma linha e sugira o próximo item de `docs/proximos-passos.md`. Não comece esse item sem uma decisão escrita, a menos que o dono peça no chat.
