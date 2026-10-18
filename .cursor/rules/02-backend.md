---
globs: *.php, *.ts, *.js, *.py
alwaysApply: false
---

# ⚙️ General Backend & Architecture Standards

## 1. Domain-Driven Design (DDD) & Context Boundaries
- **Domínios Bem Definidos:** A aplicação deve ser estruturada com base em contextos de negócio (`Domains`), separando responsabilidades por domínio e não por tipos genéricos de ficheiro (MVC tradicional).
- **Isolamento de Domínio (Actions & Models):** Cada operação de negócio deve residir numa `Action` ou handler dedicado de responsabilidade única. Uma `Action` só pode interagir com os modelos/entidades do seu próprio domínio.
- **Domínio não é tabela:** Não parta o sistema por tabela nem por tipo de ficheiro. Uma Action só não justifica um domínio novo.
- **Orquestradores (Orchestrator):** Quando um fluxo de negócio exigir a coordenação entre múltiplos domínios, utilize um `Orchestrator` em `src/Domain/Orchestrator`. Ele só chama as Actions de cada domínio. Uma Action não acessa outro domínio.
- **Relação no mesmo domínio:** Filtro e navegação que já são do domínio usam-se no Model (scope ou relação). Orchestrator só quando o fluxo cruza domínios.
- **DTOs (Data Transfer Objects):** Utilize DTOs estritos para encapsular payloads de requisições e transferência de dados entre camadas. Evite o tráfego de arrays primitivos ou dados não validados em profundidade.
- **Proibido array no lugar de DTO:** Uma propriedade de DTO, relação aninhada ou coleção que cruza camadas não pode ser `array`. Estrutura que pertence a este contexto tem a sua própria Data class. Campo que é só um valor deste contexto fica escalar (`name`, `store_name` no cliente): não abra domínio vizinho só para evitar o array. Arrays de contexto em logs estruturados não são contrato de domínio e continuam permitidos.

## 2. Camada de Entrada e Respostas da API (Envelope Pattern)
- **Controladores Magros (Thin Controllers):** Os pontos de entrada HTTP recebem a Data já validada e delegam para a `Action` ou `Orchestrator`. O sucesso é essa Data. Não se monta envelope com `response()->json()`.
- **Envelope só no erro:** Falha de domínio sai do handler global neste formato:

```json
{
  "data": { ... },
  "message": "Mensagem descritiva amigável",
  "code": "TAG_UNICA_DE_ERRO_OU_EVENTO",
  "status_code": 200,
  "errors": []
}
```

- **O poder da chave `code`:** No erro, o campo `code` (em formato `SCREAMING_SNAKE_CASE`, ex: `USER_NOT_FOUND`, `PAYMENT_APPROVED`) é o contrato estável para a UI e o i18n. Sucesso não traz `code`: o corpo é a Data.

## 3. Tratamento de Erros e Observabilidade
- **Exceções de Domínio (Domain Exceptions):** Lance exceções semânticas claras para erros de negócio (ex: saldo insuficiente, dados inválidos). Estas devem ser tratadas de forma centralizada por um manipulador global que formata automaticamente a resposta no Envelope Padrão.
- **Logs Estruturados:** Nunca utilize logs genéricos com strings puras. Ao registrar eventos críticos ou falhas, utilize logs estruturados passando metadados e arrays/objetos de contexto (ex: IDs de utilizador, montantes, referências).

## 4. Segurança e Integridade de Dados
- **Validação Antecipada:** Valide rigorosamente todos os payloads de entrada nas fronteiras da aplicação antes de iniciar qualquer regra de negócio ou mutação de estado.
- **Proteção contra Race Conditions:** Garanta a atomicidade em operações financeiras ou de stock críticas através de transações de base de dados e bloqueios de concorrência adequados (`lockForUpdate` ou equivalentes).