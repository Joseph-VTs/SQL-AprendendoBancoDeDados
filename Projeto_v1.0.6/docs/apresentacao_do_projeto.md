# Projeto Sakila SaaS

Sistema modular desenvolvido em PHP seguindo padrão MVC.

## Pilares do Projeto
- **Multi-tenancy:** Isolamento de produtos por `company_id`.
- **Segurança:** Autenticação via RBAC (Admin/Empresa).
- **Rastreabilidade:** Logs de auditoria em `storage/logs/acesso.log`.
- **Público:** Catálogo dinâmico com integração via WhatsApp API.

## Estrutura de Pastas
- `/controllers`: Lógica de negócio.
- `/models`: Acesso a dados e validações.
- `/views`: Interfaces (Layouts, Admin, Catálogo).