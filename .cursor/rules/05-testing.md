---
globs: *Test.php, *.spec.js, *.test.ts
alwaysApply: true
---

# 🧪 Qualidade de Código e Testes

## 1. Testes Incansáveis e Estruturados
- **Padrão AAA:** Todos os testes automatizados devem seguir rigorosamente o padrão Arrange-Act-Assert (Preparar, Agir, Verificar).
- **Cobertura Crítica:** Escreva testes robustos (unitários e de integração) para todos os caminhos críticos de negócio. Recomendamos o uso do Pest PHP para o backend, garantindo uma sintaxe fluente e clara.

## 2. Código Testável
- **Injeção de Dependências:** O código deve ser desenhado para ser testável desde o primeiro dia. Utilize injeção de dependências e evite chamadas estáticas rígidas que impeçam o uso de *mocks*.
- **Isolamento:** Teste as unidades de código isoladamente, simulando (*mocking*) serviços externos e base de dados quando o foco for puramente a regra de negócio.

## 3. Paranoia Pragmática
- Trate as exceções de forma adequada. Não silencie erros graves e utilize asserções para garantir que o sistema falha rapidamente ("Fail Fast") quando encontra um estado inválido.