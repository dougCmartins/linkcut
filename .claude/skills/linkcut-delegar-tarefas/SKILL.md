---
name: linkcut-delegar-tarefas
description: Delegação de execução do linkcut para o Cursor enquanto os tokens do Claude estão baixos. Monta o contexto e o prompt prontos para colar no Cursor, e garante que ao final o Cursor registre o que implementou em docs/decisoes/ e docs/proximos-passos.md, sem revisão. Na restauração dos tokens, o Claude lê esses registros, revisa e repassa o feito, os próximos passos e as lacunas. Use quando pedirem "delegar para o Cursor", "preparar prompt para o Cursor", "modo aceleração", ou "revisar o que o Cursor fez".
---

# Delegar tarefas ao Cursor (modo aceleração)

Dois modos. Descubra qual pelo pedido do dono.

| Modo | Quando | O que se faz |
|---|---|---|
| **A. Delegar** | Tokens baixos, "só acelerar" | Monta o pacote de contexto + prompt para o Cursor. Não revisa nada. |
| **B. Restauração** | Tokens voltaram (domingo) | Lê os registros do Cursor, revisa o código, reporta feito, próximos passos e lacunas. |

Fonte do trabalho: `docs/decisoes/` (skill `executar-decisoes`) e `docs/proximos-passos.md`. O Cursor também segue `CLAUDE.md` e `.cursor/rules/`.

## Modo A: Delegar

Regra: **gaste o mínimo de tokens aqui.** Não implemente, não revise, não rode a suíte. Só prepare e entregue.

1. **Escolha a fatia.** Leia `docs/decisoes/README.md` e a decisão `Decidido`/`Em execução`. Pegue o próximo passo `[ ]` na ordem (ex.: P1). Passo grande vira fatia que termina verde (ex.: só backend com testes). Se o passo depende de "Pergunta aberta", pule para o próximo independente.
2. **Crie o arquivo da tarefa** `docs/delegacao/AAAA-MM-DD-<decisao>-<passo>.md` com o modelo abaixo e confirme que está commitado/pushado no branch para o Cursor enxergar.
3. **Entregue ao dono** o prompt (o bloco "Prompt para colar no Cursor") em um bloco de código, e o caminho do arquivo. Diga em uma linha qual fatia é.
4. Atualize `docs/delegacao/README.md` (tabela) com a linha `Delegada`.

### Modelo do arquivo da tarefa
```markdown
# Tarefa: <decisão> — <passo>: <título>
- **Status:** Delegada  <!-- Delegada → Feita pelo Cursor (não revisada) → Revisada -->
- **Decisão:** docs/decisoes/NNNN-....md, passo PX
- **Branch:** <branch>

## Contexto (resumo para quem não viu o chat)
<3 a 8 linhas: o que é o produto, a decisão do dono, o que já existe no código, o que este passo entrega>

## Escopo desta fatia
- Fazer: ...
- NÃO fazer: ... (o que fica para depois)

## Aceite
<copiar o "Aceite" do passo>

## Prompt para colar no Cursor
(ver abaixo)

## Relatório do Cursor
<!-- preenchido pelo Cursor ao terminar -->
```

### Prompt para colar no Cursor (gere assim, preenchendo os <>)
```text
Você é o executor do projeto linkcut (Laravel + Vue). Modo aceleração: implemente rápido e deixe registrado. Não há revisão agora.

LEIA ANTES: CLAUDE.md, .cursor/rules/, docs/proximos-passos.md (seção 0), docs/decisoes/README.md, docs/decisoes/<arquivo>.md e docs/delegacao/<tarefa>.md.

TAREFA: <passo + fatia>. Escopo e aceite estão no arquivo da tarefa. Não decida nada que não esteja escrito: se faltar informação, pare nesse ponto, registre a pergunta e siga para o que for independente.

REGRAS: código em inglês; textos de tela via i18n; Actions + Data; envelope de erro; testes AAA; isolamento por tenant com teste; throttle em rota pública; respeite as fronteiras de domínio (ArchitectureTest). Um commit por passo, em inglês, no estilo do histórico. Não abra PR.

CHECAGENS (rode o que couber e informe o resultado, sem travar se algo falhar):
backend: vendor/bin/pint --test; php artisan test
frontend: npm run typecheck; npx vitest run; VITE_API_URL=/api npm run build

AO TERMINAR (obrigatório, mesmo se incompleto):
1. Em docs/delegacao/<tarefa>.md, seção "Relatório do Cursor", escreva: o que foi feito (linguagem simples), arquivos/rotas/migrações/telas criados, testes escritos e resultado das checagens, o que NÃO foi feito, atalhos ou suposições, perguntas ao dono, commits. Troque o Status para "Feita pelo Cursor (não revisada)".
2. Em docs/decisoes/<arquivo>.md, seção 7 "Execução", adicione no topo uma entrada: data, "Feito" (passos), "Pendente", "Perguntas", "Riscos ou desvios", marcando "executado pelo Cursor, NÃO REVISADO". Marque [x] só o que está completo; o que ficou parcial continua [ ] com nota. Não edite as seções 1 a 6.
3. Atualize a tabela de docs/decisoes/README.md (status "Em execução") e docs/delegacao/README.md.
4. Em docs/proximos-passos.md, anote em uma linha o que avançou.
5. Commit e push no branch atual.
```

## Modo B: Restauração (revisar e repassar)

Quando o dono disser que os tokens voltaram:
1. `git fetch` e leia `docs/delegacao/README.md` e cada tarefa `Feita pelo Cursor (não revisada)`, mais a seção 7 das decisões.
2. **Confira no código, não no relatório:** rotas, migrações, Actions, testes do "Aceite". Rode pint, `php artisan test`, typecheck, vitest e build.
3. Procure: fronteiras quebradas, falta de isolamento por tenant, texto sem i18n, testes que não provam o aceite, suposições do Cursor onde havia pergunta aberta.
4. Corrija ou liste. Marque a tarefa como `Revisada` e acerte os `[x]`/`[ ]` das decisões.
5. Reporte ao dono em português simples, em 4 blocos:
   - **Feito e verificado**;
   - **Feito mas com problema** (e o que corrigi);
   - **Próximos passos** (na ordem);
   - **Lacunas e perguntas** (o que ficou de fora, riscos, decisões que dependem do dono).
6. Se o dono quiser continuar delegando, volte ao Modo A.

## Não faça
- Não revise nem rode testes no Modo A.
- Não edite as seções 1 a 6 das decisões.
- Não abra PR sem o dono pedir.
