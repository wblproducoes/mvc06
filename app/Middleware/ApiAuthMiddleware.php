<?php
/**
 * Middleware de autenticação para API
 * 
 * @package App\Middleware
 * @author Sistema Administrativo MVC
 */

namespace App\Middleware;

use App\Core\JwtAuth;
use App\Core\ApiResponse;
use App\Core\Security;
use App\Models\User;

class ApiAuthMiddleware
{
    private JwtAuth $jwtAuth;
    private User $userModel;
    
    public function __construct()
    {
        $this->jwtAuth = new JwtAuth();
        $this->userModel = new User();
    }
    
    /**
     * Executa o middleware
     * 
     * @return void
     */
    public function handle(): void
    {
        // Rate limiting para API
        if (!Security::rateLimit('api_request', 100, 3600)) {
            ApiResponse::rateLimit('API rate limit exceeded');
            return;
        }
        
        // Extrai token do header
        $token = $this->jwtAuth->extractTokenFromHeader();
        
        if (!$token) {
            ApiResponse::unauthorized('Token de acesso requerido');
            return;
        }
        
        // Valida token
        $payload = $this->jwtAuth->validateToken($token);
        
        if (!$payload) {
            ApiResponse::unauthorized('Token inválido ou expirado');
            return;
        }
        
        // Verifica se é refresh token
        if (isset($payload['type']) && $payload['type'] === 'refresh') {
            ApiResponse::unauthorized('Refresh token não pode ser usado para acesso à API');
            return;
        }
        
        // Busca usuário
        $user = $this->userModel->findById($payload['user_id']);
        
        if (!$user || $user['status_id'] != 1) {
            ApiResponse::unauthorized('Usuário não encontrado ou inativo');
            return;
        }
        
        // Define usuário atual para uso nos controllers
        $_SESSION['api_user'] = $user;
        
        // Log de acesso à API
        Security::logSecurityEvent('api_access', [
            'user_id' => $user['id'],
            'endpoint' => $_SERVER['REQUEST_URI'] ?? '',
            'method' => $_SERVER['REQUEST_METHOD'] ?? ''
        ]);
    }
}