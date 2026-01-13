<?php
/**
 * Middleware de verificação de instalação
 * 
 * @package App\Middleware
 * @author Sistema Administrativo MVC
 */

namespace App\Middleware;

use App\Core\Database;
use App\Core\Logger;

class InstallationMiddleware
{
    private Database $database;
    
    public function __construct()
    {
        $this->database = new Database();
    }
    
    /**
     * Executa o middleware
     * 
     * @return void
     */
    public function handle(): void
    {
        $currentPath = $_SERVER['REQUEST_URI'] ?? '/';
        $currentPath = parse_url($currentPath, PHP_URL_PATH);
        
        // Ignora verificação para rotas de instalação e arquivos estáticos
        if ($this->shouldSkipCheck($currentPath)) {
            return;
        }
        
        $installationStatus = $this->getInstallationStatus();
        
        // Se precisa instalar e não está na página de instalação
        if ($installationStatus['needs_install'] && !str_starts_with($currentPath, '/install')) {
            $this->redirectToInstall();
            return;
        }
        
        // Se não precisa instalar mas está na página de instalação
        if (!$installationStatus['needs_install'] && str_starts_with($currentPath, '/install')) {
            $this->redirectToDashboard();
            return;
        }
    }
    
    /**
     * Verifica se deve pular a verificação de instalação
     * 
     * @param string $path
     * @return bool
     */
    private function shouldSkipCheck(string $path): bool
    {
        $skipPaths = [
            '/install',
            '/install/status',
            '/install/process',
            '/api/install',
            '/assets',
            '/css',
            '/js',
            '/images',
            '/favicon.ico'
        ];
        
        foreach ($skipPaths as $skipPath) {
            if (str_starts_with($path, $skipPath)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Retorna status da instalação
     * 
     * @return array
     */
    public function getInstallationStatus(): array
    {
        try {
            // Verifica conexão com banco
            $this->database->getConnection();
            $databaseConnected = true;
            
            // Verifica se tabelas essenciais existem
            $tablesExist = $this->checkEssentialTables();
            
            // Verifica se há usuários no sistema
            $hasUsers = false;
            if ($tablesExist) {
                $hasUsers = $this->checkUsersExist();
            }
            
            // Determina se precisa instalar
            $needsInstall = !$tablesExist || !$hasUsers;
            
            // Determina se é primeira instalação
            $isFirstInstall = !$tablesExist;
            
            return [
                'needs_install' => $needsInstall,
                'is_first_install' => $isFirstInstall,
                'tables_exist' => $tablesExist,
                'has_users' => $hasUsers,
                'database_connected' => $databaseConnected,
                'system_ready' => !$needsInstall
            ];
            
        } catch (\Exception $e) {
            // Erro de conexão - precisa instalar
            return [
                'needs_install' => true,
                'is_first_install' => true,
                'tables_exist' => false,
                'has_users' => false,
                'database_connected' => false,
                'system_ready' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Verifica se tabelas essenciais existem
     * 
     * @return bool
     */
    private function checkEssentialTables(): bool
    {
        $essentialTables = [
            'users',
            'levels',
            'status',
            'genders',
            'system_logs'
        ];
        
        $prefix = $this->database->getPrefix();
        
        try {
            foreach ($essentialTables as $table) {
                $fullTableName = $prefix . $table;
                
                $result = $this->database->fetchOne(
                    "SHOW TABLES LIKE ?",
                    [$fullTableName]
                );
                
                if (!$result) {
                    return false;
                }
            }
            
            return true;
            
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Verifica se existem usuários no sistema
     * 
     * @return bool
     */
    private function checkUsersExist(): bool
    {
        try {
            $result = $this->database->fetchOne(
                "SELECT COUNT(*) as count FROM {prefix}users WHERE deleted_at IS NULL"
            );
            
            return ($result['count'] ?? 0) > 0;
            
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Redireciona para instalação
     * 
     * @return void
     */
    private function redirectToInstall(): void
    {
        // Log da necessidade de instalação
        try {
            Logger::channel(Logger::CHANNEL_SYSTEM)->info('System needs installation, redirecting to /install');
        } catch (\Exception $e) {
            // Ignora erro de log durante instalação
        }
        
        header('Location: /install');
        exit;
    }
    
    /**
     * Redireciona para dashboard
     * 
     * @return void
     */
    private function redirectToDashboard(): void
    {
        header('Location: /dashboard');
        exit;
    }
    
    /**
     * Verifica se sistema está instalado (método estático)
     * 
     * @return bool
     */
    public static function isSystemInstalled(): bool
    {
        try {
            $middleware = new self();
            $status = $middleware->getInstallationStatus();
            return !$status['needs_install'];
        } catch (\Exception $e) {
            return false;
        }
    }
}