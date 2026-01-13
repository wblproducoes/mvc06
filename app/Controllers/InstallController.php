<?php
/**
 * Controller de instalação inteligente
 * 
 * @package App\Controllers
 * @author Sistema Administrativo MVC
 */

namespace App\Controllers;

use App\Core\Database;
use App\Core\Security;
use App\Core\Logger;
use App\Core\ApiResponse;
use App\Middleware\InstallationMiddleware;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class InstallController
{
    private Database $database;
    private Environment $twig;
    private InstallationMiddleware $installationMiddleware;
    
    public function __construct()
    {
        $this->database = new Database();
        $this->installationMiddleware = new InstallationMiddleware();
        $this->initializeTwig();
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
            'cache' => false,
            'debug' => true
        ]);
        
        // Adiciona variáveis globais básicas
        $this->twig->addGlobal('app_name', $_ENV['APP_NAME'] ?? 'Sistema Administrativo');
        $this->twig->addGlobal('app_url', $_ENV['APP_URL'] ?? 'http://localhost');
    }
    
    /**
     * Página principal de instalação
     * 
     * @return void
     */
    public function index(): void
    {
        $status = $this->installationMiddleware->getInstallationStatus();
        
        // Se sistema já está instalado, redireciona
        if (!$status['needs_install']) {
            header('Location: /dashboard');
            exit;
        }
        
        $this->render('install/index.twig', [
            'status' => $status,
            'requirements' => $this->checkRequirements(),
            'is_first_install' => $status['is_first_install']
        ]);
    }
    
    /**
     * Processa a instalação
     * 
     * @return void
     */
    public function process(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /install');
            exit;
        }
        
        $status = $this->installationMiddleware->getInstallationStatus();
        
        try {
            // Validação de dados
            $errors = $this->validateInstallationData($_POST, $status);
            
            if (!empty($errors)) {
                $this->render('install/index.twig', [
                    'status' => $status,
                    'requirements' => $this->checkRequirements(),
                    'errors' => $errors,
                    'form_data' => $_POST,
                    'is_first_install' => $status['is_first_install']
                ]);
                return;
            }
            
            // Executa instalação
            $this->performInstallation($_POST, $status);
            
            // Redireciona para sucesso
            header('Location: /install/success');
            exit;
            
        } catch (\Exception $e) {
            Logger::channel(Logger::CHANNEL_ERROR)->error('Installation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->render('install/index.twig', [
                'status' => $status,
                'requirements' => $this->checkRequirements(),
                'errors' => ['general' => 'Erro na instalação: ' . $e->getMessage()],
                'form_data' => $_POST,
                'is_first_install' => $status['is_first_install']
            ]);
        }
    }
    
    /**
     * Página de sucesso da instalação
     * 
     * @return void
     */
    public function success(): void
    {
        $status = $this->installationMiddleware->getInstallationStatus();
        
        // Se ainda precisa instalar, redireciona
        if ($status['needs_install']) {
            header('Location: /install');
            exit;
        }
        
        $this->render('install/success.twig', [
            'app_url' => $_ENV['APP_URL'] ?? 'http://localhost'
        ]);
    }
    
    /**
     * API de status da instalação
     * 
     * @return void
     */
    public function status(): void
    {
        $status = $this->installationMiddleware->getInstallationStatus();
        
        ApiResponse::success($status, 'Status da instalação');
    }
    
    /**
     * Valida dados da instalação
     * 
     * @param array $data
     * @param array $status
     * @return array
     */
    private function validateInstallationData(array $data, array $status): array
    {
        $errors = [];
        
        // Validação da senha de instalação (apenas para reinstalação)
        if (!$status['is_first_install']) {
            $installPassword = $data['install_password'] ?? '';
            $expectedPassword = $_ENV['INSTALL_PASSWORD'] ?? 'admin123';
            
            if ($installPassword !== $expectedPassword) {
                $errors['install_password'] = 'Senha de instalação incorreta';
            }
        }
        
        // Validação do nome do sistema
        $systemName = trim($data['system_name'] ?? '');
        if (empty($systemName)) {
            $errors['system_name'] = 'Nome do sistema é obrigatório';
        } elseif (strlen($systemName) < 3) {
            $errors['system_name'] = 'Nome do sistema deve ter pelo menos 3 caracteres';
        }
        
        // Validação dos dados do administrador
        $adminName = trim($data['admin_name'] ?? '');
        if (empty($adminName)) {
            $errors['admin_name'] = 'Nome do administrador é obrigatório';
        }
        
        $adminUsername = trim($data['admin_username'] ?? '');
        if (empty($adminUsername)) {
            $errors['admin_username'] = 'Username do administrador é obrigatório';
        } elseif (strlen($adminUsername) < 3) {
            $errors['admin_username'] = 'Username deve ter pelo menos 3 caracteres';
        }
        
        $adminEmail = trim($data['admin_email'] ?? '');
        if (empty($adminEmail)) {
            $errors['admin_email'] = 'Email do administrador é obrigatório';
        } elseif (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $errors['admin_email'] = 'Email inválido';
        }
        
        $adminPassword = $data['admin_password'] ?? '';
        if (empty($adminPassword)) {
            $errors['admin_password'] = 'Senha do administrador é obrigatória';
        } elseif (strlen($adminPassword) < 6) {
            $errors['admin_password'] = 'Senha deve ter pelo menos 6 caracteres';
        }
        
        $adminPasswordConfirm = $data['admin_password_confirm'] ?? '';
        if ($adminPassword !== $adminPasswordConfirm) {
            $errors['admin_password_confirm'] = 'Confirmação de senha não confere';
        }
        
        return $errors;
    }
    
    /**
     * Executa a instalação
     * 
     * @param array $data
     * @param array $status
     * @return void
     */
    private function performInstallation(array $data, array $status): void
    {
        $this->database->beginTransaction();
        
        try {
            // 1. Cria tabelas se necessário
            if ($status['is_first_install']) {
                $this->createTables();
                Logger::channel(Logger::CHANNEL_SYSTEM)->info('Database tables created during installation');
            }
            
            // 2. Insere dados básicos
            $this->insertBasicData();
            
            // 3. Cria usuário administrador
            $adminId = $this->createAdminUser($data);
            
            // 4. Configura sistema
            $this->configureSystem($data);
            
            // 5. Atualiza arquivo .env se necessário
            $this->updateEnvironmentFile($data);
            
            $this->database->commit();
            
            Logger::channel(Logger::CHANNEL_SYSTEM)->info('System installation completed successfully', [
                'admin_id' => $adminId,
                'system_name' => $data['system_name'],
                'is_first_install' => $status['is_first_install']
            ]);
            
        } catch (\Exception $e) {
            $this->database->rollback();
            throw $e;
        }
    }
    
    /**
     * Cria tabelas do banco de dados
     * 
     * @return void
     */
    private function createTables(): void
    {
        $schemaFile = __DIR__ . '/../../database/schema.sql';
        
        if (!file_exists($schemaFile)) {
            throw new \Exception('Arquivo de schema não encontrado');
        }
        
        $sql = file_get_contents($schemaFile);
        $prefix = $this->database->getPrefix();
        $sql = str_replace('{prefix}', $prefix, $sql);
        
        // Executa cada statement separadamente
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            fn($stmt) => !empty($stmt) && !str_starts_with($stmt, '--')
        );
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $this->database->query($statement);
            }
        }
    }
    
    /**
     * Insere dados básicos do sistema
     * 
     * @return void
     */
    private function insertBasicData(): void
    {
        $seedsFile = __DIR__ . '/../../database/seeds.sql';
        
        if (file_exists($seedsFile)) {
            $sql = file_get_contents($seedsFile);
            $prefix = $this->database->getPrefix();
            $sql = str_replace('{prefix}', $prefix, $sql);
            
            // Executa seeds
            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                fn($stmt) => !empty($stmt) && !str_starts_with($stmt, '--')
            );
            
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    try {
                        $this->database->query($statement);
                    } catch (\Exception $e) {
                        // Ignora erros de dados duplicados
                        if (!str_contains($e->getMessage(), 'Duplicate entry')) {
                            throw $e;
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Cria usuário administrador
     * 
     * @param array $data
     * @return int
     */
    private function createAdminUser(array $data): int
    {
        // Remove usuário admin existente se houver
        $this->database->query(
            "DELETE FROM {prefix}users WHERE username = ? OR email = ?",
            [$data['admin_username'], $data['admin_email']]
        );
        
        // Cria novo usuário admin
        $userData = [
            'name' => Security::sanitizeInput($data['admin_name']),
            'username' => Security::sanitizeInput($data['admin_username']),
            'email' => Security::sanitizeInput($data['admin_email']),
            'password' => Security::hashPassword($data['admin_password']),
            'unique_code' => Security::generateSecureToken(10),
            'level_id' => 1, // Master Admin
            'status_id' => 1, // Ativo
            'dh' => date('Y-m-d H:i:s')
        ];
        
        return $this->database->insert('{prefix}users', $userData);
    }
    
    /**
     * Configura sistema
     * 
     * @param array $data
     * @return void
     */
    private function configureSystem(array $data): void
    {
        // Atualiza configurações do sistema se houver tabela de settings
        try {
            $settings = [
                'system_name' => $data['system_name'],
                'installation_date' => date('Y-m-d H:i:s'),
                'system_version' => \App\Core\Version::get(),
                'timezone' => $_ENV['APP_TIMEZONE'] ?? 'America/Sao_Paulo'
            ];
            
            foreach ($settings as $key => $value) {
                $this->database->query(
                    "INSERT INTO {prefix}system_settings (setting_key, setting_value, created_at) 
                     VALUES (?, ?, ?) 
                     ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = ?",
                    [$key, $value, date('Y-m-d H:i:s'), $value, date('Y-m-d H:i:s')]
                );
            }
        } catch (\Exception $e) {
            // Ignora se tabela de settings não existir
        }
    }
    
    /**
     * Atualiza arquivo .env
     * 
     * @param array $data
     * @return void
     */
    private function updateEnvironmentFile(array $data): void
    {
        $envFile = __DIR__ . '/../../.env';
        
        if (file_exists($envFile)) {
            $envContent = file_get_contents($envFile);
            
            // Atualiza nome do sistema
            $envContent = preg_replace(
                '/^APP_NAME=.*$/m',
                'APP_NAME="' . addslashes($data['system_name']) . '"',
                $envContent
            );
            
            // Adiciona timezone se não existir
            if (!str_contains($envContent, 'APP_TIMEZONE=')) {
                $envContent .= "\nAPP_TIMEZONE=" . ($_ENV['APP_TIMEZONE'] ?? 'America/Sao_Paulo');
            }
            
            file_put_contents($envFile, $envContent);
        }
    }
    
    /**
     * Verifica requisitos do sistema
     * 
     * @return array
     */
    private function checkRequirements(): array
    {
        return [
            'php_version' => [
                'required' => '8.4.0',
                'current' => PHP_VERSION,
                'status' => version_compare(PHP_VERSION, '8.4.0', '>=')
            ],
            'extensions' => [
                'pdo' => extension_loaded('pdo'),
                'pdo_mysql' => extension_loaded('pdo_mysql'),
                'mbstring' => extension_loaded('mbstring'),
                'openssl' => extension_loaded('openssl'),
                'json' => extension_loaded('json'),
                'curl' => extension_loaded('curl')
            ],
            'directories' => [
                'storage' => is_writable(__DIR__ . '/../../storage'),
                'logs' => is_writable(__DIR__ . '/../../storage/logs') || 
                         mkdir(__DIR__ . '/../../storage/logs', 0755, true)
            ],
            'database' => $this->checkDatabaseConnection()
        ];
    }
    
    /**
     * Verifica conexão com banco de dados
     * 
     * @return bool
     */
    private function checkDatabaseConnection(): bool
    {
        try {
            $this->database->getConnection();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Renderiza template
     * 
     * @param string $template
     * @param array $data
     * @return void
     */
    private function render(string $template, array $data = []): void
    {
        echo $this->twig->render($template, $data);
    }
}