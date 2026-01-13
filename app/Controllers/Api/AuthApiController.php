<?php
/**
 * Controller de autenticação da API
 * 
 * @package App\Controllers\Api
 * @author Sistema Administrativo MVC
 */

namespace App\Controllers\Api;

use App\Core\JwtAuth;
use App\Core\Security;
use App\Core\ApiResponse;
use App\Models\User;

class AuthApiController extends BaseApiController
{
    private JwtAuth $jwtAuth;
    private User $userModel;
    
    public function __construct()
    {
        $this->jwtAuth = new JwtAuth();
        $this->userModel = new User();
        
        // Não chama parent::__construct() para evitar validação de auth
        $this->database = new \App\Core\Database();
    }
    
    /**
     * Login via API
     * 
     * @return void
     */
    public function login(): void
    {
        $data = $this->getJsonInput();
        
        // Validação
        $errors = $this->validateInput($data, [
            'username' => [
                'required' => true,
                'type' => 'length',
                'options' => ['min' => 3, 'max' => 50],
                'message' => 'Username deve ter entre 3 e 50 caracteres'
            ],
            'password' => [
                'required' => true,
                'type' => 'length',
                'options' => ['min' => 6],
                'message' => 'Senha deve ter pelo menos 6 caracteres'
            ]
        ]);
        
        if (!empty($errors)) {
            ApiResponse::validation($errors);
            return;
        }
        
        $username = Security::sanitizeInput($data['username']);
        $password = $data['password'];
        
        // Verifica tentativas de login
        if (!Security::checkLoginAttempts($username)) {
            ApiResponse::error('Muitas tentativas de login. Tente novamente em 15 minutos.', 
                             ApiResponse::HTTP_TOO_MANY_REQUESTS);
            return;
        }
        
        // Busca usuário
        $user = $this->userModel->findByUsername($username);
        
        if (!$user || !Security::verifyPassword($password, $user['password'])) {
            Security::recordLoginAttempt($username, false);
            Security::logSecurityEvent('api_login_failed', [
                'username' => $username,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            ApiResponse::unauthorized('Credenciais inválidas');
            return;
        }
        
        // Verifica se usuário está ativo
        if ($user['status_id'] != 1) {
            ApiResponse::forbidden('Usuário inativo');
            return;
        }
        
        // Login bem-sucedido
        Security::recordLoginAttempt($username, true);
        
        // Gera tokens
        $tokenPayload = [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'level_id' => $user['level_id']
        ];
        
        $accessToken = $this->jwtAuth->generateToken($tokenPayload);
        $refreshToken = $this->jwtAuth->generateRefreshToken($user['id']);
        
        // Atualiza último acesso
        $this->userModel->updateLastAccess($user['id']);
        
        // Log de sucesso
        Security::logSecurityEvent('api_login_success', [
            'user_id' => $user['id'],
            'username' => $username
        ]);
        
        ApiResponse::success([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'username' => $user['username'],
                'email' => $user['email'],
                'level_id' => $user['level_id']
            ]
        ], 'Login realizado com sucesso');
    }
    
    /**
     * Refresh token
     * 
     * @return void
     */
    public function refresh(): void
    {
        $data = $this->getJsonInput();
        
        if (empty($data['refresh_token'])) {
            ApiResponse::error('Refresh token é obrigatório', ApiResponse::HTTP_BAD_REQUEST);
            return;
        }
        
        $payload = $this->jwtAuth->validateToken($data['refresh_token']);
        
        if (!$payload || !isset($payload['type']) || $payload['type'] !== 'refresh') {
            ApiResponse::unauthorized('Refresh token inválido');
            return;
        }
        
        // Busca usuário
        $user = $this->userModel->findById($payload['user_id']);
        
        if (!$user || $user['status_id'] != 1) {
            ApiResponse::unauthorized('Usuário não encontrado ou inativo');
            return;
        }
        
        // Gera novo access token
        $tokenPayload = [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'level_id' => $user['level_id']
        ];
        
        $accessToken = $this->jwtAuth->generateToken($tokenPayload);
        
        ApiResponse::success([
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => 3600
        ], 'Token renovado com sucesso');
    }
    
    /**
     * Logout (invalidar token - implementação básica)
     * 
     * @return void
     */
    public function logout(): void
    {
        // Em uma implementação completa, você manteria uma blacklist de tokens
        // Por simplicidade, apenas retornamos sucesso
        
        $user = $this->getAuthenticatedUser();
        if ($user) {
            Security::logSecurityEvent('api_logout', [
                'user_id' => $user['id']
            ]);
        }
        
        ApiResponse::success(null, 'Logout realizado com sucesso', ApiResponse::HTTP_NO_CONTENT);
    }
    
    /**
     * Informações do usuário autenticado
     * 
     * @return void
     */
    public function me(): void
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            ApiResponse::unauthorized();
            return;
        }
        
        // Remove dados sensíveis
        unset($user['password'], $user['session_token'], $user['password_reset_token']);
        
        ApiResponse::success($user, 'Dados do usuário');
    }
}