<?php
/**
 * Middleware de autenticação
 * 
 * @package App\Middleware
 * @author Sistema Administrativo MVC
 */

namespace App\Middleware;

class AuthMiddleware
{
    /**
     * Verifica se o usuário está autenticado
     * 
     * @return bool
     */
    public function handle(): bool
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }
        
        return true;
    }
}