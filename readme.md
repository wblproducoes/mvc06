# Sistema Administrativo MVC

Sistema administrativo completo desenvolvido em PHP com arquitetura MVC, utilizando as melhores práticas de desenvolvimento e segurança.

## 🚀 Tecnologias

- **PHP 8.4+** - Orientação a Objetos (compatível com PHP 8.4 e 8.5)
- **Twig 3.0** - Template Engine
- **Bootstrap 5.3** - Framework CSS moderno
- **PHPMailer 7.0.3** - Envio de emails
- **DomPDF 3.1.4** - Geração de PDFs
- **MySQL/MariaDB** - Banco de dados (todas as tabelas vão trabalhar com prefixo)
- **Composer** - Gerenciador de dependências

## Caracteristicas

- ✅ Documentação detalhada com PHPDocs
- ✅ Controle de versão com Git
- ✅ Arquitetura MVC profissional
- ✅ Sistema de autenticação seguro
- ✅ Proteção CSRF
- ✅ Senhas criptografadas (bcrypt)
- ✅ Variáveis de ambiente (.env)
- ✅ Middleware de autenticação
- ✅ Envio de emails
- ✅ Design responsivo (Bootstrap 5.3)
- ✅ Interface moderna e intuitiva
- ✅ Código reutilizável e manutenível
- ✅ Prepared Statements (PDO)
- ✅ Validação de dados
- ✅ Flash messages

## 📋 Pré-requisitos

- PHP 8.4+ com extensões: PDO, mbstring, openssl, curl
- Composer
- MySQL/MariaDB 10.4+
- Servidor web (Apache/Nginx) ou PHP built-in server

## 🛠️ Instalação

1. **Clone o repositório**
```bash
git clone <repository-url>
cd sistema-administrativo-mvc
```

2. **Instale as dependências**
```bash
composer install
```

3. **Configure o ambiente**
```bash
cp .env.example .env
```
Edite o arquivo `.env` com suas configurações de banco de dados e email.

4. **Configure o banco de dados**
```bash
php cli/migrate.php
```

5. **Inicie o servidor**
```bash
php -S localhost:8000 -t public/
```

Acesse: `http://localhost:8000`

## 📁 Estrutura do Projeto

```
sistema-administrativo-mvc/
├── app/
│   ├── Controllers/        # Controladores MVC
│   ├── Models/            # Modelos de dados
│   ├── Views/             # Templates Twig
│   ├── Middleware/        # Middleware de autenticação
│   ├── Services/          # Serviços (Email, PDF, etc.)
│   └── Config/            # Configurações
├── public/                # Arquivos públicos
│   ├── assets/           # CSS, JS, imagens
│   └── index.php         # Ponto de entrada
├── cli/                   # Scripts CLI
├── storage/              # Logs, cache, uploads
├── vendor/               # Dependências Composer
├── .env.example          # Exemplo de configuração
└── composer.json         # Dependências
```

## 🔐 Recursos de Segurança

- **Autenticação**: Sistema completo de login/logout
- **Autorização**: Controle de acesso baseado em roles
- **CSRF Protection**: Proteção contra ataques CSRF
- **Password Hashing**: Senhas criptografadas com bcrypt
- **SQL Injection**: Prepared statements em todas as queries
- **XSS Protection**: Escape automático no Twig
- **Session Security**: Configuração segura de sessões

## 📧 Configuração de Email

Configure as variáveis no arquivo `.env`:

```env
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=seu-email@gmail.com
MAIL_PASSWORD=sua-senha-app
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=seu-email@gmail.com
MAIL_FROM_NAME="Sistema Admin"
```

## 🎨 Personalização

### Temas
Os templates estão em `app/Views/` e utilizam Twig. Para personalizar:

1. Edite os arquivos em `app/Views/layouts/`
2. Modifique os estilos em `public/assets/css/`
3. Adicione JavaScript em `public/assets/js/`

### Componentes Bootstrap
O sistema utiliza Bootstrap 5.3 com componentes modernos:
- Cards responsivos
- Formulários validados
- Modais interativos
- Navegação intuitiva

## 🚀 Uso

### Criando um novo Controller

```php
<?php
namespace App\Controllers;

class ExemploController extends BaseController
{
    public function index()
    {
        return $this->render('exemplo/index.twig', [
            'titulo' => 'Exemplo'
        ]);
    }
}
```

### Criando um Model

```php
<?php
namespace App\Models;

class ExemploModel extends BaseModel
{
    protected $table = 'exemplos';
    protected $fillable = ['nome', 'email'];
}
```

## 🧪 Comandos CLI

```bash
# Criar migration
php cli/create-migration.php nome_da_migration

# Executar migrations
php cli/migrate.php

# Criar usuário admin
php cli/create-admin.php
```

## 📝 Licença

Este projeto está sob a licença MIT. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.

## 🤝 Contribuição

1. Fork o projeto
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

## 📞 Suporte

Para suporte, envie um email para suporte@exemplo.com ou abra uma issue no GitHub.