<?php
/**
 * Ponto de entrada da aplicação
 * Sistema Administrativo MVC
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Application;
use App\Core\Router;
use Dotenv\Dotenv;

// Carrega variáveis de ambiente
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Configurações de erro baseadas no ambiente
if ($_ENV['APP_DEBUG'] === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Configurações de sessão
ini_set('session.cookie_httponly', $_ENV['SESSION_HTTP_ONLY']);
ini_set('session.cookie_secure', $_ENV['SESSION_SECURE']);
ini_set('session.gc_maxlifetime', $_ENV['SESSION_LIFETIME'] * 60);

// Inicia a sessão
session_start();

// Cria e executa a aplicação
try {
    $app = new Application();
    $router = new Router();
    
    // Define as rotas
    require_once __DIR__ . '/../app/Config/routes.php';
    
    // Executa a aplicação
    $app->run($router);
    
} catch (Exception $e) {
    if ($_ENV['APP_DEBUG'] === 'true') {
        echo '<h1>Erro na Aplicação</h1>';
        echo '<p>' . $e->getMessage() . '</p>';
        echo '<pre>' . $e->getTraceAsString() . '</pre>';
    } else {
        echo '<h1>Erro interno do servidor</h1>';
        error_log($e->getMessage());
    }
}