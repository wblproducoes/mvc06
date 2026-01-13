<?php
/**
 * Classe principal da aplicação
 * 
 * @package App\Core
 * @author Sistema Administrativo MVC
 */

namespace App\Core;

use App\Core\Router;
use App\Core\Database;
use App\Core\Security;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\SecurityMiddleware;
use App\Middleware\InstallationMiddleware;

class Application
{
    private Router $router;
    private Database $database;
    
    /**
     * Construtor da aplicação
     */
    public function __construct()
    {
        // Inicializa segurança
        Security::initialize();
        
        $this->database = new Database();
    }
    
    /**
     * Executa a aplicação
     * 
     * @param Router $router
     * @return void
     */
    public function run(Router $router): void
    {
        $this->router = $router;
        
        // Aplica middleware global
        $this->applyGlobalMiddleware();
        
        // Resolve a rota atual
        $this->router->resolve();
    }
    
    /**
     * Aplica middleware global
     * 
     * @return void
     */
    private function applyGlobalMiddleware(): void
    {
        // Middleware de instalação (primeiro de todos)
        $installationMiddleware = new InstallationMiddleware();
        $installationMiddleware->handle();
        
        // Middleware de segurança global
        $securityMiddleware = new SecurityMiddleware();
        $securityMiddleware->handle();
        
        // Middleware CSRF para rotas POST (exceto instalação)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/install')) {
            $csrfMiddleware = new CsrfMiddleware();
            $csrfMiddleware->handle();
        }
    }
    
    /**
     * Retorna a instância do banco de dados
     * 
     * @return Database
     */
    public function getDatabase(): Database
    {
        return $this->database;
    }
}