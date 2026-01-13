# Changelog

Todas as mudanças notáveis neste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/),
e este projeto adere ao [Semantic Versioning](https://semver.org/lang/pt-BR/).

## [Não Lançado]

### Planejado
- Sistema de permissões granulares
- Dashboard com widgets personalizáveis
- API REST completa
- Sistema de notificações em tempo real
- Integração com storage em nuvem
- Auditoria completa de ações

---

## [1.1.0] - 2025-01-13

### ✨ Adicionado
- **Sistema Escolar Completo**
  - Tabelas para gêneros, níveis de acesso e status
  - Gestão de matérias escolares
  - Períodos escolares (matutino, vespertino, noturno, integral)
  - Turmas escolares com links públicos
  - Horários escolares com grade de aulas
  - Sistema de usuários expandido com CPF, telefones, foto

- **Models Avançados**
  - Model User com funcionalidades completas
  - Models para Gender, Level, Status
  - Models para SchoolSubject, SchoolPeriod, SchoolTeam
  - Model SchoolSchedule com grade de horários
  - Soft delete em todos os models
  - Relacionamentos com foreign keys

- **Dashboard Escolar**
  - Estatísticas de usuários, turmas e matérias
  - Gráfico de distribuição por níveis
  - Atividade recente do sistema
  - Cards informativos atualizados

- **Funcionalidades de Segurança**
  - Login por email ou username
  - Códigos únicos para usuários
  - Tokens para links públicos de turmas
  - Controle de expiração de links

### 🔧 Alterado
- Migração do banco atualizada com schema completo
- AuthController adaptado para novo modelo User
- HomeController com estatísticas escolares
- Dashboard redesenhado para ambiente escolar

### 📦 Estrutura
- Schema MySQL/MariaDB profissional
- Índices otimizados para performance
- Foreign keys para integridade referencial
- Campos de auditoria (dh, dh_update, deleted_at)
- Suporte a soft delete em todas as tabelas

---

## [1.0.0] - 2025-01-13

### 🎉 Lançamento Inicial

#### ✨ Adicionado
- **Arquitetura MVC Completa**
  - Sistema de roteamento com middleware
  - Controllers base com funcionalidades comuns
  - Models com Active Record pattern
  - Views com Twig templating engine

- **Sistema de Autenticação**
  - Login/logout seguro
  - Reset de senha via email
  - Proteção CSRF em formulários
  - Middleware de autenticação
  - Senhas criptografadas com bcrypt

- **Interface Moderna**
  - Dashboard responsivo com Bootstrap 5.3
  - Sidebar colapsível para mobile
  - Cards de estatísticas
  - Gráficos interativos com Chart.js
  - Flash messages com auto-hide
  - Tooltips e modais

- **Gerenciamento de Usuários**
  - CRUD completo de usuários
  - Sistema de roles (admin, manager, user)
  - Controle de usuários ativos/inativos
  - Avatar de usuários

- **Recursos Avançados**
  - Envio de emails com PHPMailer
  - Geração de PDFs com DomPDF
  - Sistema de logs de auditoria
  - Auto-save em formulários
  - Validação client-side e server-side

- **Ferramentas CLI**
  - Script de migração do banco de dados
  - Criador de usuário administrador
  - Estrutura para novos comandos

- **Configuração e Deploy**
  - Variáveis de ambiente (.env)
  - Configuração de desenvolvimento/produção
  - Cache de templates Twig
  - Headers de segurança
  - .htaccess otimizado

#### 🔧 Técnico
- **PHP 8.4+** com orientação a objetos
- **Composer** para gerenciamento de dependências
- **PSR-4** autoloading
- **MySQL/MariaDB** com prepared statements
- **Twig 3.0** template engine
- **Bootstrap 5.3** framework CSS
- **Documentação PHPDoc** completa

#### 📦 Dependências
- `twig/twig: ^3.0` - Template engine
- `phpmailer/phpmailer: ^6.9` - Envio de emails
- `dompdf/dompdf: ^3.1` - Geração de PDFs
- `vlucas/phpdotenv: ^5.6` - Variáveis de ambiente

#### 🛡️ Segurança
- Proteção contra SQL Injection
- Proteção CSRF
- Escape automático XSS
- Validação de entrada
- Sessões seguras
- Headers de segurança

#### 📱 Compatibilidade
- PHP 8.4 e 8.5
- MySQL 5.7+ / MariaDB 10.4+
- Navegadores modernos (Chrome, Firefox, Safari, Edge)
- Dispositivos móveis e tablets

---

## Tipos de Mudanças

- `✨ Adicionado` para novas funcionalidades
- `🔧 Alterado` para mudanças em funcionalidades existentes
- `🐛 Corrigido` para correções de bugs
- `🗑️ Removido` para funcionalidades removidas
- `🔒 Segurança` para correções de vulnerabilidades
- `📦 Dependências` para atualizações de dependências
- `📚 Documentação` para mudanças na documentação
- `⚡ Performance` para melhorias de performance
- `🎨 Estilo` para mudanças que não afetam funcionalidade

---

## Links

- [Repositório](https://github.com/seu-usuario/sistema-administrativo-mvc)
- [Issues](https://github.com/seu-usuario/sistema-administrativo-mvc/issues)
- [Releases](https://github.com/seu-usuario/sistema-administrativo-mvc/releases)