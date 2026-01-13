<?php
/**
 * Middleware de proteção CSRF
 * 
 * @package App\Middleware
 * @author Sistema Administrativo MVC
 */

namespace App\Middleware;

class CsrfMiddleware
{
    /**
     * Verifica token CSRF em requisições POST
     * 
     * @return bool
     */
    public function handle(): bool
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            
            if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
                http_response_code(403);
                echo '<h1>403 - Token CSRF inválido</h1>';
                exit;
            }
        }
        
        return true;
    }
}