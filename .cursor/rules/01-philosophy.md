---
alwaysApply: true
---

# 🚀 Engineering Philosophy & Code Standards

## 1. Core Principles
- **KISS (Keep It Simple, Stupid):** A simplicidade é a sofisticação máxima. Prefira sempre código óbvio e legível a atalhos inteligentes ou abstrações prematuras.
- **DRY with Caution:** Evite duplicação de código, mas nunca sacrifice a legibilidade ou crie acoplamento rígido apenas para reutilizar algumas linhas.
- **Single Responsibility (SoC):** Cada classe, módulo e serviço deve possuir uma única e bem definida responsabilidade de negócio.

## 2. Naming & Language Policy
- **English Everywhere in Code:** Todos os nomes de variáveis, funções, classes, tabelas de banco de dados, colunas, comentários, logs e exceções **devem ser escritos estritamente em inglês**.
- **User-Facing Text:** Textos exibidos diretamente aos usuários finais (UI, mensagens de erro, notificações) devem utilizar o sistema de internacionalização (i18n) da aplicação, nunca hardcoded no código-fonte.
- **Conventions:**
    - **Classes:** `PascalCase` (ex: `PaymentService`, `UserOrderController`)
    - **Variables & Functions:** `camelCase` (ex: `totalAmount`, `calculateTax()`)
    - **Database Tables & Columns:** `snake_case` (ex: `user_subscriptions`, `created_at`)
    - **Routes:** `kebab-case` (ex: `user-profile`, `order-checkout`)

## 3. Code Review & Mindset
- **Ambiente livre de ego:** As revisões de código focam exclusivamente na qualidade, manutenibilidade, desempenho e arquitetura — nunca em críticas pessoais.
- **Commits Atômicos:** Siga estritamente o padrão Conventional Commits (`feat:`, `fix:`, `refactor:`, `perf:`, `test:`, `chore:`).
- **Pull Requests:** Devem ser concisos, focados em uma única funcionalidade/correção e acompanhados de testes automatizados quando aplicável.