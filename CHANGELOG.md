# Changelog

Todas as mudanças notáveis neste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/),
e este projeto adere ao [Semantic Versioning](https://semver.org/lang/pt-BR/).

## [Não Lançado]

### Planejado
- Sistema de permissões granulares
- Dashboard com widgets personalizáveis
- Sistema de notificações em tempo real
- Integração com storage em nuvem

---

## [1.7.0] - 2025-01-13

### 📅 Sistema de Calendário/Agenda Completo
- **FullCalendar 6.x**: Integração completa com localização em português
- **Gestão de Eventos**: Criar, editar, excluir e visualizar eventos
- **Drag & Drop**: Arrastar eventos para alterar datas e horários
- **Sistema de Convites**: Convidar participantes e gerenciar respostas
- **Eventos Recorrentes**: Suporte a repetições diárias, semanais, mensais e anuais
- **Cores Personalizadas**: 10 opções de cores para categorizar eventos
- **Eventos Públicos**: Opção de tornar eventos visíveis para todos os usuários
- **Lembretes**: Configurar notificações antes dos eventos
- **Múltiplas Visualizações**: Mês, semana, dia e lista
- **Responsivo**: Interface adaptada para dispositivos móveis
- **Rotas em Português**: `/calendario/*` para melhor UX brasileira
- **Integração com Schema Principal**: Tabelas do calendário integradas ao `schema.sql` principal
- **Configurações Automáticas**: Configurações padrão criadas automaticamente para usuários existentes

### 🔧 Configuração e Deploy
- **Arquivo .env**: Criado arquivo de configuração baseado no .env.example
- **Dependências**: Instalação automática das dependências do Composer
- **Redirecionamento**: Configurado .htaccess para redirecionamento automático para /public
- **Configuração de Servidor**: Suporte para Apache com mod_rewrite e servidor PHP interno
- **URL Correta**: Sistema configurado para funcionar em `http://localhost/mvc06/public/`
- **Instalação Automática**: Sistema detecta automaticamente necessidade de instalação

### 🛠️ Melhorias Técnicas
- **Schema Consolidado**: Todas as tabelas do calendário integradas ao schema principal
- **Migração Simplificada**: Processo de migração unificado sem arquivos SQL separados
- **Configurações Padrão**: Seeds automáticos para configurações do calendário
- **Estrutura Limpa**: Remoção de arquivos SQL duplicados e consolidação da estrutura

---

## [1.6.0] - 2025-01-13

### 🌙 Modo Escuro Completo e Sistema de Temas

#### ✨ Adicionado
- **Sistema de Temas Completo**
  - Modo Claro, Escuro e Automático (segue preferência do sistema)
  - Alternância suave entre temas com transições CSS
  - Persistência de preferência por usuário no banco de dados
  - Fallback para localStorage e cookies para usuários não logados
  - Sincronização automática com servidor

- **Interface de Modo Escuro**
  - Design escuro completo e consistente em todos os componentes
  - Paleta de cores otimizada para baixa luminosidade
  - Suporte a todos os componentes Bootstrap 5.3
  - Variáveis CSS customizáveis para fácil manutenção
  - Compatibilidade com preferência do sistema operacional

- **Controles de Tema Intuitivos**
  - Botão de alternância rápida na navbar
  - Seletor de tema no menu do usuário
  - Atalho de teclado (Ctrl/Cmd + Shift + T)
  - Ícones visuais para cada modo de tema
  - Tooltips informativos

- **Gerenciamento Avançado de Temas**
  - Service dedicado (`ThemeService`) para lógica de temas
  - Controller específico (`ThemeController`) para API de temas
  - Middleware automático para aplicação de temas
  - Sistema de migração para usuários existentes

- **Ferramentas CLI de Temas**
  - Script `cli/theme-manager.php` para gerenciamento via terminal
  - Estatísticas de uso de temas
  - Migração em lote de usuários
  - Configuração de tema para usuários específicos
  - Operações em massa para todos os usuários

#### 🎨 Design e UX
- **Modo Escuro Profissional**
  - Cores baseadas no GitHub Dark Theme
  - Contraste otimizado para acessibilidade
  - Redução de fadiga visual em ambientes com pouca luz
  - Consistência visual em todos os componentes

- **Transições Suaves**
  - Animações CSS para mudanças de tema
  - Transições de 300ms para cores e backgrounds
  - Efeitos visuais no botão de alternância
  - Feedback visual imediato

- **Responsividade Completa**
  - Suporte a dispositivos móveis e tablets
  - Meta tag theme-color dinâmica para mobile
  - Adaptação automática em diferentes tamanhos de tela
  - Otimização para touch interfaces

#### 🔧 Funcionalidades Técnicas

**Detecção Automática:**
- Detecção da preferência do sistema operacional
- Aplicação automática do tema no carregamento
- Monitoramento de mudanças na preferência do sistema
- Fallback inteligente para temas não suportados

**Persistência Multi-Camada:**
- Banco de dados para usuários logados
- localStorage para persistência local
- Cookies para compatibilidade cross-session
- Sessão PHP como backup

**API de Temas:**
- Endpoints REST para gerenciamento de temas
- Sincronização automática com servidor
- Estatísticas de uso para administradores
- Configuração via JSON

#### 📊 Componentes Suportados
- **Bootstrap 5.3**: Cards, Modals, Forms, Tables, Buttons
- **Navegação**: Navbar, Sidebar, Breadcrumbs, Pagination
- **Formulários**: Inputs, Selects, Textareas, Floating Labels
- **Feedback**: Alerts, Toasts, Progress Bars
- **Dados**: Tables, Lists, Dropdowns
- **Gráficos**: Compatibilidade com Chart.js
- **Código**: Syntax highlighting em modo escuro

#### 🛠️ Arquitetura Técnica

**ThemeService:**
- Gerenciamento centralizado de temas
- Persistência em banco de dados
- Configuração por usuário
- Estatísticas de uso
- Migração automática

**ThemeController:**
- API REST para alternância de temas
- Endpoints de configuração
- Sincronização com frontend
- Logs de auditoria

**CSS Avançado:**
- Variáveis CSS customizáveis
- Media queries para preferência do sistema
- Seletores específicos por tema
- Otimização para performance

**JavaScript Inteligente:**
- Classe ThemeManager completa
- Event listeners para mudanças
- Sincronização automática
- Detecção de preferências do sistema

#### 📋 Endpoints da API
```bash
POST /theme/toggle          # Alterna tema do usuário
GET  /theme/config          # Configuração atual do tema
GET  /api/theme/stats       # Estatísticas de uso (admin)
```

#### 🛠️ Ferramentas CLI
```bash
# Estatísticas de uso
php cli/theme-manager.php stats

# Migrar usuários existentes
php cli/theme-manager.php migrate

# Definir tema para usuário
php cli/theme-manager.php set 1 dark

# Reset tema do usuário
php cli/theme-manager.php reset 1

# Definir tema para todos
php cli/theme-manager.php bulk-set auto
```

#### 🔒 Recursos de Segurança
- Validação de entrada para temas
- Sanitização de dados de preferência
- Logs de auditoria para mudanças
- Proteção contra ataques de preferência
- Rate limiting para mudanças de tema

#### ⚡ Performance
- CSS otimizado com variáveis
- JavaScript assíncrono
- Cache de preferências
- Transições GPU-aceleradas
- Carregamento otimizado de estilos

#### 🌐 Compatibilidade
- **Navegadores**: Chrome 90+, Firefox 88+, Safari 14+, Edge 90+
- **Dispositivos**: Desktop, Tablet, Mobile
- **Sistemas**: Windows, macOS, Linux, iOS, Android
- **Acessibilidade**: WCAG 2.1 AA compliant

#### 📱 Mobile First
- Design responsivo completo
- Touch-friendly controls
- Meta theme-color dinâmica
- Otimização para PWA
- Suporte a gestos nativos

---

## [1.5.0] - 2025-01-13

### 🚀 Sistema de Instalação Inteligente

#### ✨ Adicionado
- **Sistema de Instalação Inteligente**
  - Detecção automática se o sistema precisa ser instalado
  - Verificação de existência de tabelas essenciais
  - Instalação sem senha quando tabelas não existem
  - Middleware `InstallationMiddleware` para verificação automática
  - Endpoint `/install/status` para verificar status via API

- **Funcionalidades de Instalação Automática**
  - Redirecionamento automático para `/install` quando necessário
  - Diferenciação entre primeira instalação e reinstalação
  - Configuração do nome do sistema durante instalação
  - Verificação de usuários existentes no banco
  - Status detalhado da instalação

- **Melhorias no Processo de Instalação**
  - Campo obrigatório para nome do sistema
  - Criação automática do usuário master (level_id = 1)
  - Configuração automática das settings do sistema
  - Validação de requisitos aprimorada
  - Tratamento de erros mais robusto

- **Interface de Instalação Moderna**
  - Design responsivo com Bootstrap 5.3
  - Indicadores visuais de progresso
  - Verificação de requisitos em tempo real
  - Validação de formulário client-side
  - Feedback visual aprimorado

- **Ferramentas CLI de Instalação**
  - Script `cli/install-check.php` para verificação de status
  - Comandos para forçar reinstalação
  - Reset completo do sistema via CLI
  - Verificação de integridade do banco

#### 🔧 Melhorado
- **Experiência do Usuário**
  - Instalação mais fluida e intuitiva
  - Não pede senha na primeira instalação
  - Feedback visual melhorado
  - Redirecionamento automático inteligente
  - Página de sucesso com countdown automático

- **Segurança**
  - Senha de instalação apenas para reinstalações
  - Verificação de integridade do banco
  - Validação de tabelas essenciais
  - Proteção contra instalações desnecessárias
  - Logs de segurança durante instalação

- **Robustez**
  - Tratamento de erros de conexão
  - Fallback para instalação em caso de erro
  - Verificação de arquivos estáticos
  - Logs de erro detalhados
  - Transações de banco para instalação

#### 🛠️ Técnico
- **InstallationMiddleware**
  - Verificação automática de necessidade de instalação
  - Detecção de primeira instalação vs reinstalação
  - Status detalhado do sistema
  - Tratamento de arquivos estáticos
  - Integração com sistema de roteamento

- **InstallController Atualizado**
  - Lógica de instalação inteligente
  - Configuração automática do sistema
  - Validação aprimorada de dados
  - API de status de instalação
  - Criação automática de tabelas e dados

- **Configurações**
  - Variável `APP_TIMEZONE` no `.env`
  - Configuração automática de timezone
  - Settings do sistema configuráveis
  - Suporte a prefixos de tabelas
  - Tabela `system_settings` para configurações

#### 📋 Fluxo de Instalação

**Primeira Instalação (Tabelas não existem):**
1. Sistema detecta ausência de tabelas
2. Redireciona automaticamente para `/install`
3. **Não pede senha de instalação**
4. Solicita apenas dados do administrador e nome do sistema
5. Cria todas as tabelas e configurações
6. Redireciona para página de sucesso

**Reinstalação (Tabelas existem):**
1. Sistema detecta tabelas existentes mas sem usuários
2. Redireciona para `/install`
3. **Pede senha de instalação** (segurança)
4. Permite reconfiguração do sistema
5. Mantém dados existentes ou recria conforme necessário

**Sistema Instalado:**
1. Sistema detecta tabelas e usuários existentes
2. Funciona normalmente
3. Não redireciona para instalação

#### 🔌 API de Status
```bash
GET /install/status
{
  "success": true,
  "data": {
    "needs_install": false,
    "is_first_install": false,
    "tables_exist": true,
    "has_users": true,
    "database_connected": true,
    "system_ready": true
  }
}
```

#### 🛠️ Ferramentas CLI
```bash
# Verificar status
php cli/install-check.php status

# Forçar reinstalação
php cli/install-check.php force

# Reset completo
php cli/install-check.php reset
```

#### 🔒 Recursos de Segurança
- Middleware de instalação executado antes de qualquer outro
- Verificação de integridade de tabelas essenciais
- Proteção contra instalações desnecessárias
- Logs de auditoria durante processo de instalação
- Validação de dados de entrada robusta

#### ⚡ Performance
- Verificação otimizada de tabelas essenciais
- Cache de status de instalação
- Redirecionamentos eficientes
- Queries otimizadas para verificação
- Transações de banco para consistência

---

## [1.4.0] - 2025-01-13

### 📊 Sistema de Logs Avançado - Nível Empresarial

#### ✨ Adicionado
- **Logger PSR-3 Compliant**
  - 8 níveis de log (Emergency, Alert, Critical, Error, Warning, Notice, Info, Debug)
  - 8 canais especializados (System, Security, API, Database, Auth, Audit, Performance, Error)
  - Processadores customizáveis para enriquecimento de dados
  - Contexto estruturado com metadados automáticos
  - Rotação automática de arquivos por tamanho e data

- **Armazenamento Multi-Destino**
  - Arquivos JSON estruturados com compressão automática
  - Banco de dados com índices otimizados
  - Webhooks para serviços externos (Sentry, LogStash, etc.)
  - Configuração flexível de destinos

- **Análise e Monitoramento**
  - Detector de anomalias em tempo real
  - Estatísticas detalhadas por período
  - Análise de padrões e tendências
  - Alertas automáticos para eventos críticos
  - Dashboard visual com gráficos interativos

- **Interface Web Completa**
  - Dashboard de logs com estatísticas visuais
  - Listagem com filtros avançados (nível, canal, período, busca)
  - Visualização detalhada de logs individuais
  - Gerador de relatórios personalizáveis
  - Monitor em tempo real com Server-Sent Events

- **Ferramentas CLI Avançadas**
  - Gerenciador completo via linha de comando
  - Análise de logs por período
  - Limpeza automática de logs antigos
  - Exportação em múltiplos formatos (JSON, CSV, TXT)
  - Monitor em tempo real no terminal
  - Detector de anomalias via CLI

- **Recursos de Performance**
  - Log de queries SQL com tempo de execução
  - Monitoramento de uso de memória
  - Rastreamento de tempo de resposta
  - Detecção automática de queries lentas
  - Métricas de performance por endpoint

#### 🔧 Funcionalidades Técnicas

**Rotação e Retenção**
- Rotação automática por tamanho (10MB padrão)
- Compressão GZIP de arquivos antigos
- Limpeza automática após período configurável
- Retenção configurável (90 dias padrão)
- Backup automático antes da limpeza

**Detecção de Anomalias**
- Picos de erro por período
- IPs com atividade suspeita
- Falhas de autenticação em massa
- Queries com performance degradada
- Padrões de acesso anômalos

**Integração com Sistema**
- Log automático de todas as queries SQL
- Rastreamento de ações de usuários
- Log de eventos de segurança
- Monitoramento de API requests
- Auditoria de mudanças de dados

#### 📊 Dashboard e Relatórios
- **Estatísticas Visuais**: Gráficos de distribuição por nível e canal
- **Atividade Temporal**: Análise de atividade por hora/dia
- **Top Lists**: IPs mais ativos, erros mais frequentes
- **Anomalias**: Alertas visuais para comportamentos suspeitos
- **Filtros Avançados**: Busca por múltiplos critérios
- **Exportação**: Relatórios em PDF, Excel, CSV

#### 🛠️ Ferramentas CLI
```bash
# Análise de logs
php cli/log-manager.php analyze 30

# Estatísticas gerais
php cli/log-manager.php stats 7

# Detecção de anomalias
php cli/log-manager.php anomalies 24

# Limpeza de logs antigos
php cli/log-manager.php cleanup 90

# Exportação de logs
php cli/log-manager.php export 2025-01-01 2025-01-31 json

# Monitor em tempo real
php cli/log-manager.php monitor

# Teste do sistema
php cli/log-manager.php test
```

#### 🔒 Segurança e Compliance
- Sanitização automática de dados sensíveis
- Logs de auditoria para compliance
- Rastreamento de todas as ações administrativas
- Detecção de tentativas de ataque
- Logs de segurança separados e protegidos

#### ⚡ Performance e Escalabilidade
- Logs assíncronos para não impactar performance
- Índices otimizados no banco de dados
- Compressão automática de arquivos antigos
- Configuração de níveis por ambiente
- Rate limiting para evitar spam de logs

#### 📈 Métricas e Monitoramento
- Tempo de execução de queries
- Uso de memória por requisição
- Estatísticas de API por endpoint
- Monitoramento de recursos do sistema
- Alertas automáticos para thresholds

#### 🔧 Configuração Flexível
- Níveis de log por ambiente
- Canais customizáveis
- Destinos configuráveis
- Rotação personalizada
- Retenção por tipo de log

---

## [1.3.0] - 2025-01-13

### 🚀 API REST Completa - Nível Empresarial

#### ✨ Adicionado
- **Sistema de Autenticação JWT**
  - Autenticação via JSON Web Tokens
  - Access tokens com expiração configurável (1 hora)
  - Refresh tokens para renovação (7 dias)
  - Middleware de autenticação específico para API
  - Logout com invalidação de tokens

- **Controllers da API REST**
  - `AuthApiController` - Login, refresh, logout, informações do usuário
  - `UserApiController` - CRUD completo de usuários
  - `SchoolSubjectApiController` - Gestão de matérias escolares
  - `SchoolTeamApiController` - Gestão de turmas e horários
  - `DocsApiController` - Documentação automática da API

- **Recursos Avançados da API**
  - Paginação automática com metadados
  - Filtros e busca em endpoints
  - Validação robusta de entrada
  - Sanitização automática de dados
  - Rate limiting específico para API
  - Respostas padronizadas (ApiResponse)

- **Segurança da API**
  - Detecção de SQL Injection e XSS
  - Headers de segurança automáticos
  - CORS configurável
  - Rate limiting por IP e endpoint
  - Logs de auditoria para todas as operações
  - Validação de Content-Type

- **Documentação Automática**
  - Especificação OpenAPI 3.0 completa
  - Interface Swagger UI integrada
  - Documentação de todos os endpoints
  - Exemplos de requisições e respostas
  - Schemas de dados detalhados

- **Ferramentas de Teste**
  - Script CLI para testes da API
  - Bateria de testes automatizada
  - Exemplos de uso para cada endpoint
  - Validação de respostas JSON

#### 🔧 Endpoints Implementados

**Autenticação**
- `POST /api/auth/login` - Login com username/password
- `POST /api/auth/refresh` - Renovação de token
- `POST /api/auth/logout` - Logout
- `GET /api/auth/me` - Dados do usuário autenticado

**Usuários**
- `GET /api/users` - Lista usuários (paginado, filtros)
- `POST /api/users` - Cria novo usuário
- `GET /api/users/{id}` - Dados de usuário específico
- `PUT /api/users/{id}` - Atualiza usuário
- `DELETE /api/users/{id}` - Remove usuário (soft delete)

**Matérias Escolares**
- `GET /api/subjects` - Lista matérias (paginado, filtros)
- `POST /api/subjects` - Cria nova matéria
- `GET /api/subjects/{id}` - Dados de matéria específica
- `PUT /api/subjects/{id}` - Atualiza matéria
- `DELETE /api/subjects/{id}` - Remove matéria

**Turmas Escolares**
- `GET /api/teams` - Lista turmas (paginado, filtros)
- `POST /api/teams` - Cria nova turma
- `GET /api/teams/{id}` - Dados de turma específica
- `PUT /api/teams/{id}` - Atualiza turma
- `DELETE /api/teams/{id}` - Remove turma
- `POST /api/teams/{id}/public-link` - Ativa/desativa link público
- `GET /api/teams/{id}/schedules` - Horários da turma

**Sistema**
- `GET /api/info` - Informações gerais da API
- `GET /api/version` - Versão do sistema
- `GET /api/docs` - Documentação Swagger UI
- `GET /api/docs/openapi.json` - Especificação OpenAPI
- `OPTIONS /api/*` - Suporte CORS

#### 🛡️ Recursos de Segurança
- **JWT Security**: Tokens assinados com chave secreta
- **Rate Limiting**: 100 requisições por hora por IP
- **Input Validation**: Validação rigorosa de todos os dados
- **SQL Injection Protection**: Detecção automática de padrões
- **XSS Protection**: Sanitização de entrada e saída
- **CORS**: Configuração flexível de origens permitidas
- **Audit Logging**: Log de todas as operações da API

#### 📊 Recursos de Paginação
- Paginação automática com limite de 100 itens por página
- Metadados de paginação (total, páginas, navegação)
- Filtros por campos específicos
- Busca textual em campos relevantes
- Ordenação configurável

#### 🔧 Configuração
- Variáveis de ambiente para JWT
- Configuração CORS flexível
- Rate limiting configurável
- Documentação habilitável/desabilitável
- Logs de API separados

#### 📚 Documentação
- Especificação OpenAPI 3.0 completa
- Interface Swagger UI responsiva
- Exemplos de código para cada endpoint
- Schemas de dados detalhados
- Códigos de erro padronizados

#### 🧪 Testes
- Script CLI para testes (`cli/api-test.php`)
- Bateria de testes automatizada
- Testes de autenticação e autorização
- Validação de respostas JSON
- Testes de rate limiting

#### ⚡ Performance
- Respostas JSON otimizadas
- Paginação eficiente
- Queries otimizadas com índices
- Cache de documentação
- Headers de cache apropriados

---

## [1.2.0] - 2025-01-13

### 🔒 Segurança Avançada - Nível Empresarial

#### ✨ Adicionado
- **Classe Security Central**
  - Gerenciamento centralizado de segurança
  - Headers de segurança automáticos (CSP, HSTS, XSS Protection)
  - Criptografia AES-256-CBC para dados sensíveis
  - Rate limiting configurável por ação
  - Detecção de SQL Injection e XSS
  - Validação e sanitização avançada de inputs

- **Sistema de Auditoria Completo**
  - Log de todas as ações do sistema
  - Rastreamento de mudanças em dados
  - Logs de segurança detalhados
  - Retenção configurável de logs
  - Sanitização automática de dados sensíveis

- **Autenticação Fortificada**
  - Bloqueio por tentativas de login (5 tentativas/15min)
  - Detecção de session hijacking por IP
  - Regeneração automática de sessão
  - Timeout de sessão configurável
  - Tokens CSRF com expiração

- **Middleware de Segurança**
  - Verificação de IP whitelist/blacklist
  - Detecção de User-Agents suspeitos
  - Controle de tamanho de requisições
  - Rate limiting por IP e ação
  - Bloqueio automático de ameaças

- **Monitoramento e Alertas**
  - Script de verificação de segurança
  - Logs estruturados de eventos
  - Detecção de atividades suspeitas
  - Score de segurança do sistema

#### 🔧 Melhorias de Segurança
- **Senhas**: Hash Argon2ID com configurações otimizadas
- **Sessões**: Configuração segura com HttpOnly, Secure, SameSite
- **Headers**: Content Security Policy, HSTS, X-Frame-Options
- **Validação**: Sanitização automática de todos os inputs
- **Criptografia**: Chaves rotacionáveis e algoritmos modernos

#### 📊 Auditoria e Compliance
- Tabela de auditoria com foreign keys
- Log de todas as operações CRUD
- Rastreamento de mudanças de dados
- Logs de eventos de segurança
- Retenção configurável de logs

#### 🛡️ Proteções Implementadas
- **SQL Injection**: Detecção por padrões + Prepared Statements
- **XSS**: Detecção + Escape automático no Twig
- **CSRF**: Tokens seguros com expiração
- **Session Hijacking**: Verificação de IP e User-Agent
- **Brute Force**: Rate limiting + Bloqueio temporário
- **Clickjacking**: X-Frame-Options DENY
- **MIME Sniffing**: X-Content-Type-Options nosniff

#### 🔍 Monitoramento
- Logs de segurança estruturados
- Detecção de padrões suspeitos
- Alertas automáticos de segurança
- Score de segurança em tempo real
- Auditoria de configurações

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

- [Repositório](https://github.com/wblproducoes/mvc06)
- [Issues](https://github.com/wblproducoes/mvc06/issues)
- [Releases](https://github.com/wblproducoes/mvc06/releases)