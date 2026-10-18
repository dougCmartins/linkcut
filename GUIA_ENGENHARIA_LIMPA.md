# 📘 Manual de Engenharia Limpa

> **Versão:** 2.0  
> **Objetivo:** Estabelecer padrões de desenvolvimento sustentável, integrando princípios clássicos de engenharia pragmática e comunicação assertiva, priorizando a entrega de valor, a clareza e a facilidade de manutenção a longo prazo.

Este manual atua como a única fonte de verdade (*Single Source of Truth*) para os padrões e práticas de engenharia da nossa equipa. Todos os contributos, refatorações e novos desenvolvimentos devem respeitar rigorosamente os documentos listados abaixo.

A nossa arquitetura baseia-se em **Domain-Driven Design (DDD)** no backend (Laravel) e em **Composition API** no frontend (Vue 3), sempre com um foco obcecante na qualidade, testes automatizados e performance.

## 🗂️ Índice de Regras

Recomendamos a leitura sequencial para novos membros da equipa, ou a consulta direta aos tópicos específicos durante o desenvolvimento:

1. [Filosofia, Cultura e Comunicação](./01-philosophy.md)
   *O Zelo pelo ofício, o princípio da Janela Partida e o método ASTUTO para comunicação assertiva.*
2. [Padrões de Backend (PHP / Laravel & DDD)](./02-backend.md)
   *Ações de responsabilidade única, Orquestradores, DTOs, Envelope Pattern e tipagem estrita.*
3. [Performance e Otimização](./03-performance.md)
   *Gestão de N+1, coleções lazy, prevenção de Race Conditions (`lockForUpdate`) e caching.*
4. [Arquitetura e Design](./04-architecture.md)
   *Ortogonalidade, isolamento de domínios e a regra do Single Source of Truth.*
5. [Qualidade de Código e Testes](./05-testing.md)
   *O padrão AAA (Arrange-Act-Assert), testes orientados ao domínio (Pest) e injeção de dependências.*
6. [Segurança e Fiabilidade](./06-security.md)
   *Prevenção de XSS/SQL Injection, 2FA, validação estrita (Zod) e gestão isolada de permissões.*
7. [Processos, Code Review e Ferramentas](./07-process-tools.md)
   *Utilização inteligente de IA (Cursor/Gemini), branches isoladas e Code Review sem ego.*
8. [Armadilhas Comuns (Pitfalls)](./08-pitfalls.md)
   *Como evitar God Classes, código esparguete, Over-engineering e copy-paste impaciente.*
9. [Frontend Standards (Vue 3 / TypeScript)](./09-frontend.md)
   *Composition API, Vitest, componentização granular, reatividade inteligente e gestão de estado global com Pinia.*

---
*“A simplicidade é a sofisticação máxima.”*