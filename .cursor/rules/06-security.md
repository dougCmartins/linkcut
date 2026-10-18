---
globs: *.php, *.js, *.ts
alwaysApply: true
---

# 🔒 Segurança e Fiabilidade

## 1. Prevenção de Vulnerabilidades Comuns
- **SQL Injection & XSS:** Utilize as proteções nativas da *framework* (como o Eloquent ORM e prepared statements no backend, e a interpolação segura no frontend) para sanitizar todas as entradas e saídas.
- **Validação Rigorosa:** Confie na máxima "Nunca confie na entrada do utilizador". No backend, todo o dado recebido deve ser validado na Data class do domínio (`spatie/laravel-data`) antes do processamento. No frontend, use validações estritas (ex: Zod).

## 2. Autenticação e Autorização (ACL)
- **Autenticação Segura:** Exija e suporte mecanismos de segurança robustos, como Duplo Fator de Autenticação (2FA) e rotação de *Refresh Tokens*.
- **Gestão de Permissões (ACL):** Centralize as regras de acesso e permissões. Os controladores e rotas devem validar os perfis de acesso antes de aceder aos dados, mantendo o código de autorização isolado e de fácil auditoria.

## 3. Registos e Monitorização (Logging)
- Adicione *logs* estruturados em pontos críticos da aplicação e em todas as falhas de segurança ou acessos negados, facilitando a análise e a investigação em ferramentas como o Datadog.