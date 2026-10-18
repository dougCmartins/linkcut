# GitHub Copilot Instructions

- **Code Language:** All variables, functions, classes, methods, database tables, columns, logs, and exceptions MUST be written strictly in English.
- **Clean Architecture:** Follow Single Responsibility, keep controllers/handlers thin, use DTOs for boundaries, and return standardized Envelope responses (`data`, `message`, `code`, `status_code`).
- **Simplicity (KISS):** Avoid premature optimization and complex abstractions. Write clean, readable, and maintainable code.
- **Testing:** Always ensure business logic is testable and adheres to the AAA pattern.
- **Context:** Refer to `.cursor/rules/` and `GUIA_ENGENHARIA_LIMPA.md` for project-specific stack rules.