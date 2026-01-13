<?php
/**
 * Controller de autenticação
 * 
 * @package App\Controllers
 * @author Sistema Administrativo MVC
 */

namespace App\Controllers;

use App\Models\User;
use App\Services\EmailService;
use App\Core\Security;

class AuthController extends BaseController
{
    private User $userModel;
    private EmailService $emailService;
    
    /**
     * Construtor do AuthController
     */
    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
        $this->emailService = new EmailService();
    }
    
    /**
     * Exibe a página de login
     * 
     * @return void
     */
    public function login(): void
    {
        if ($this->isAuthenticated()) {
            $this->redirect('/');
            return;
        }
        
        $this->render('auth/login.twig', [
            'titulo' => 'Login'
        ]);
    }
    
    /**
     * Processa o login
     * 
     * @return void
     */
    public function processLogin(): void
    {
        // Rate limiting para login
        if (!Security::rateLimit('login', 5, 900)) { // 5 tentativas por 15 min
            Security::logSecurityEvent('login_rate_limit_exceeded');
            $this->addFlashMessage('error', 'Muitas tentativas de login. Tente novamente em 15 minutos.');
            $this->redirect('/login');
            return;
        }
        
        if (!$this->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            Security::logSecurityEvent('csrf_token_invalid', ['action' => 'login']);
            $this->addFlashMessage('error', 'Token CSRF inválido');
            $this->redirect('/login');
            return;
        }
        
        $email = Security::sanitizeInput($_POST['email'] ?? '', 'email');
        $senha = $_POST['senha'] ?? '';
        
        if (empty($email) || empty($senha)) {
            $this->addFlashMessage('error', 'Email e senha são obrigatórios');
            $this->redirect('/login');
            return;
        }
        
        // Verifica tentativas de login por IP/email
        $identifier = $email . '_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        if (!Security::checkLoginAttempts($identifier)) {
            Security::logSecurityEvent('login_blocked_attempts', ['email' => $email]);
            $this->addFlashMessage('error', 'Conta temporariamente bloqueada devido a muitas tentativas de login');
            $this->redirect('/login');
            return;
        }
        
        // Tenta login por email ou username
        $usuario = $this->userModel->findByEmail($email);
        if (!$usuario) {
            $usuario = $this->userModel->findByUsername($email);
        }
        
        $loginSuccess = false;
        
        if ($usuario && Security::verifyPassword($senha, $usuario['password'])) {
            if ($usuario['status_id'] == 1) {
                $loginSuccess = true;
                
                // Atualiza último acesso
                $this->userModel->updateLastAccess($usuario['id']);
                
                // Regenera sessão por segurança
                session_regenerate_id(true);
                
                // Cria sessão
                $_SESSION['user'] = [
                    'id' => $usuario['id'],
                    'name' => $usuario['name'],
                    'email' => $usuario['email'],
                    'username' => $usuario['username'],
                    'level_id' => $usuario['level_id'],
                    'photo' => $usuario['photo'],
                    'login_time' => time(),
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ];
                
                Security::logSecurityEvent('login_success', [
                    'user_id' => $usuario['id'],
                    'email' => $usuario['email']
                ]);
                
                $this->addFlashMessage('success', 'Login realizado com sucesso!');
                $this->redirect('/');
            } else {
                Security::logSecurityEvent('login_inactive_user', ['email' => $email]);
                $this->addFlashMessage('error', 'Usuário inativo ou bloqueado');
            }
        } else {
            Security::logSecurityEvent('login_failed', ['email' => $email]);
            $this->addFlashMessage('error', 'Credenciais inválidas');
        }
        
        // Registra tentativa de login
        Security::recordLoginAttempt($identifier, $loginSuccess);
        
        if (!$loginSuccess) {
            $this->redirect('/login');
        }
    }
    
    /**
     * Realiza logout
     * 
     * @return void
     */
    public function logout(): void
    {
        if (isset($_SESSION['user'])) {
            Security::logSecurityEvent('logout', [
                'user_id' => $_SESSION['user']['id']
            ]);
        }
        
        // Destrói sessão completamente
        $_SESSION = [];
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
        $this->redirect('/login');
    }
    
    /**
     * Exibe página de esqueci minha senha
     * 
     * @return void
     */
    public function forgotPassword(): void
    {
        $this->render('auth/forgot-password.twig', [
            'titulo' => 'Esqueci minha senha'
        ]);
    }
    
    /**
     * Processa solicitação de reset de senha
     * 
     * @return void
     */
    public function processForgotPassword(): void
    {
        if (!$this->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->addFlashMessage('error', 'Token CSRF inválido');
            $this->redirect('/forgot-password');
            return;
        }
        
        $email = $_POST['email'] ?? '';
        
        if (empty($email)) {
            $this->addFlashMessage('error', 'Email é obrigatório');
            $this->redirect('/forgot-password');
            return;
        }
        
        $usuario = $this->userModel->findByEmail($email);
        
        if ($usuario) {
            // Gera token de reset
            $token = bin2hex(random_bytes(32));
            $this->userModel->createPasswordResetToken($usuario['id'], $token);
            
            // Envia email
            $resetUrl = $_ENV['APP_URL'] . '/reset-password?token=' . $token;
            $this->emailService->sendPasswordReset($email, $usuario['name'], $resetUrl);
        }
        
        // Sempre mostra a mesma mensagem por segurança
        $this->addFlashMessage('success', 'Se o email existir, você receberá instruções para redefinir sua senha');
        $this->redirect('/login');
    }
    
    /**
     * Exibe página de reset de senha
     * 
     * @return void
     */
    public function resetPassword(): void
    {
        $token = $_GET['token'] ?? '';
        
        if (empty($token) || !$this->userModel->isValidResetToken($token)) {
            $this->addFlashMessage('error', 'Token inválido ou expirado');
            $this->redirect('/login');
            return;
        }
        
        $this->render('auth/reset-password.twig', [
            'titulo' => 'Redefinir senha',
            'token' => $token
        ]);
    }
    
    /**
     * Processa reset de senha
     * 
     * @return void
     */
    public function processResetPassword(): void
    {
        if (!$this->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->addFlashMessage('error', 'Token CSRF inválido');
            $this->redirect('/login');
            return;
        }
        
        $token = $_POST['token'] ?? '';
        $senha = $_POST['senha'] ?? '';
        $confirmarSenha = $_POST['confirmar_senha'] ?? '';
        
        if (empty($token) || empty($senha) || empty($confirmarSenha)) {
            $this->addFlashMessage('error', 'Todos os campos são obrigatórios');
            $this->redirect('/reset-password?token=' . $token);
            return;
        }
        
        if ($senha !== $confirmarSenha) {
            $this->addFlashMessage('error', 'As senhas não coincidem');
            $this->redirect('/reset-password?token=' . $token);
            return;
        }
        
        if (strlen($senha) < 8) {
            $this->addFlashMessage('error', 'A senha deve ter pelo menos 8 caracteres');
            $this->redirect('/reset-password?token=' . $token);
            return;
        }
        
        if (!$this->userModel->isValidResetToken($token)) {
            $this->addFlashMessage('error', 'Token inválido ou expirado');
            $this->redirect('/login');
            return;
        }
        
        // Atualiza senha
        $this->userModel->updatePasswordByToken($token, password_hash($senha, PASSWORD_BCRYPT));
        
        $this->addFlashMessage('success', 'Senha redefinida com sucesso!');
        $this->redirect('/login');
    }
}