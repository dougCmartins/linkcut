---

### 2. `frontend.md` (Padrões Gerais de Frontend — Agnóstico)

```markdown
---
globs: *.vue, *.tsx, *.jsx, *.ts, *.js
alwaysApply: false
---

# 💻 General Frontend Standards

## 1. Tipagem e Segurança de Código
- **TypeScript Obrigatório:** Utilize tipagem estrita em todo o código-fonte frontend (componentes, serviços, estados e stores) para garantir segurança, contratos claros de dados e melhor *intellisense*.
- **Contratos Alinhados com o Backend:** O sucesso é a Data. A interface do frontend espelha esse corpo. `message` e `code` só aparecem no erro.
- **Escopo de pasta:** O frontend não define domínio. A pasta usa o nome do domínio que já existe no backend. Ecrã, store, model e teste desse domínio ficam lá.

## 2. Componentização e Arquitetura de UI
- **Componentização Granular (Smart/Dumb):** Divida interfaces complexas em componentes pequenos, coesos e reutilizáveis. Separe componentes de apresentação pura (que recebem props e emitem eventos) de componentes inteligentes (que gerem lógica de negócio e chamadas de API).
- **Templates Limpos (Anti-complexidade no HTML):** Evite árvores profundas e poluídas de condicionais aninhadas (`v-if`, ternários complexos) diretamente no markup. Extraia lógicas condicionais complexas para propriedades computadas (*computed properties*) ou subcomponentes dedicados.
- **Fluxo de Dados Unidirecional:** O fluxo de dados deve ser estritamente unidirecional: os dados descem via propriedades (`props`) e os eventos sobem via emissões (`emits`), evitando mutações laterais imprevistas.

## 3. Gestão de Estado Global
- Utilize ferramentas de gestão de estado reativo (como Pinia, Redux Toolkit, Zustand ou Context API otimizada) exclusivamente para dados partilhados entre múltiplos ecrãs, fluxos assíncronos globais ou sessões de utilizador. Estados puramente locais devem permanecer encapsulados no próprio componente.

## 4. Testes Automatizados de Componentes e Lógica
- **Foco Comportamental:** Priorize testes focados no comportamento do utilizador e na integridade dos fluxos (ex: se as props renderizam corretamente, se os cliques disparam os eventos esperados e se os *composables* / *hooks* calculam os estados com exatidão), evitando testar detalhes rígidos de implementação interna do framework.