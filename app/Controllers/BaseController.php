<?php
/**
 * Controller base com funcionalidades comuns
 * 
 * @package App\Controllers
 * @author Sistema Administrativo MVC
 */

namespace App\Controllers;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use App\Core\Database;
use App\Core\Security;
use App\Services\ThemeService;

abstract class BaseController
{
    protected Environment $twig;
    protected Database $database;
    protected ThemeService $themeService;
    
    /**
     * Construtor do controller base
     */
    public function __construct()
    {
        $this->initializeTwig();
        $this->database = new Database();
        $this->themeService = new ThemeService();
        $this->validateSession();
    }
    
    /**
     * Inicializa o Twig
     * 
     * @return void
     */
    private function initializeTwig(): void
    {
        $loader = new FilesystemLoader(__DIR__ . '/../Views');
        $this->twig = new Environment($loader, [
            'cache' => $_ENV['APP_ENV'] === 'production' ? __DIR__ . '/../../storage/cache' : false,
            'debug' => $_ENV['APP_DEBUG'] === 'true',
            'auto_reload' => $_ENV['APP_ENV'] !== 'production'
        ]);
        
        // Adiciona variáveis globais
        $this->twig->addGlobal('app_name', $_ENV['APP_NAME']);
        $this->twig->addGlobal('app_url', $_ENV['APP_URL']);
        $this->twig->addGlobal('csrf_token', $this->generateCsrfToken());
        $this->twig->addGlobal('user', $this->getCurrentUser());
        $this->twig->addGlobal('flash_messages', $this->getFlashMessages());
        $this->twig->addGlobal('app_version', \App\Core\Version::get());
        $this->twig->addGlobal('app_version_full', \App\Core\Version::getFull());
        
        // Adiciona configuração de tema
        $user = $this->getCurrentUser();
        $this->twig->addGlobal('theme_config', $this->themeService->getThemeConfig($user['id'] ?? null));
        $this->twig->addGlobal('theme_classes', $this->themeService->getThemeClasses($user['id'] ?? null));
    }
    
    /**
     * Renderiza uma view
     * 
     * @param string $template
     * @param array $data
     * @return void
     */
    protected function render(string $template, array $data = []): void
    {
        echo $this->twig->render($template, $data);
    }
    
    /**
     * Redireciona para uma URL
     * 
     * @param string $url
     * @return void
     */
    protected function redirect(string $url): void
    {
        header("Location: $url");
        exit;
    }
    
    /**
     * Retorna dados em JSON
     * 
     * @param array $data
     * @param int $statusCode
     * @return void
     */
    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Valida sessão ativa
     * 
     * @return void
     */
    private function validateSession(): void
    {
        if (isset($_SESSION['user'])) {
            // Verifica timeout da sessão
            $loginTime = $_SESSION['user']['login_time'] ?? 0;
            if (time() - $loginTime > 3600) { // 1 hora
                Security::logSecurityEvent('session_timeout', [
                    'user_id' => $_SESSION['user']['id']
                ]);
                session_destroy();
                $this->redirect('/login');
                return;
            }
            
            // Verifica se IP mudou (possível session hijacking)
            $currentIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $sessionIp = $_SESSION['user']['ip'] ?? '';
            
            if ($sessionIp !== $currentIp) {
                Security::logSecurityEvent('session_hijack_attempt', [
                    'user_id' => $_SESSION['user']['id'],
                    'session_ip' => $sessionIp,
                    'current_ip' => $currentIp
                ]);
                session_destroy();
                $this->redirect('/login');
                return;
            }
            
            // Atualiza timestamp da sessão
            $_SESSION['user']['last_activity'] = time();
        }
    }
    
    /**
     * Gera token CSRF
     * 
     * @return string
     */
    protected function generateCsrfToken(): string
    {
        if (!isset($_SESSION['csrf_token']) || 
            !isset($_SESSION['csrf_token_time']) || 
            time() - $_SESSION['csrf_token_time'] > 3600) {
            
            $_SESSION['csrf_token'] = Security::generateSecureToken(32);
            $_SESSION['csrf_token_time'] = time();
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verifica token CSRF
     * 
     * @param string $token
     * @return bool
     */
    protected function verifyCsrfToken(string $token): bool
    {
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
            return false;
        }
        
        // Verifica se token não expirou (1 hora)
        if (time() - $_SESSION['csrf_token_time'] > 3600) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Adiciona mensagem flash
     * 
     * @param string $type
     * @param string $message
     * @return void
     */
    protected function addFlashMessage(string $type, string $message): void
    {
        if (!isset($_SESSION['flash_messages'])) {
            $_SESSION['flash_messages'] = [];
        }
        $_SESSION['flash_messages'][] = ['type' => $type, 'message' => $message];
    }
    
    /**
     * Retorna e limpa mensagens flash
     * 
     * @return array
     */
    protected function getFlashMessages(): array
    {
        $messages = $_SESSION['flash_messages'] ?? [];
        unset($_SESSION['flash_messages']);
        return $messages;
    }
    
    /**
     * Retorna o usuário atual
     * 
     * @return array|null
     */
    protected function getCurrentUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }
    
    /**
     * Verifica se o usuário está autenticado
     * 
     * @return bool
     */
    protected function isAuthenticated(): bool
    {
        return isset($_SESSION['user']);
    }
}