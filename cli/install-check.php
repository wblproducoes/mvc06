<?php
/**
 * Script para verificar status de instalação
 * 
 * Uso: php cli/install-check.php [comando]
 * 
 * Comandos:
 * - status    Verifica status da instalação
 * - force     Força reinstalação
 * - reset     Reset completo do sistema
 * 
 * @package CLI
 * @author Sistema Administrativo MVC
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Carrega variáveis de ambiente
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && !str_starts_with($line, '#')) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

use App\Middleware\InstallationMiddleware;
use App\Core\Database;

class InstallChecker
{
    private InstallationMiddleware $middleware;
    private Database $database;
    
    public function __construct()
    {
        $this->middleware = new InstallationMiddleware();
        $this->database = new Database();
    }
    
    /**
     * Executa comando
     * 
     * @param array $args
     * @return void
     */
    public function run(array $args): void
    {
        $command = $args[1] ?? 'status';
        
        switch ($command) {
            case 'status':
                $this->checkStatus();
                break;
                
            case 'force':
                $this->forceReinstall();
                break;
                
            case 'reset':
                $this->resetSystem();
                break;
                
            default:
                $this->showHelp();
        }
    }
    
    /**
     * Verifica status da instalação
     * 
     * @return void
     */
    private function checkStatus(): void
    {
        echo "🔍 Verificando status da instalação...\n";
        echo str_repeat('=', 50) . "\n";
        
        try {
            $status = $this->middleware->getInstallationStatus();
            
            echo "📊 Status da Instalação:\n\n";
            
            // Status geral
            $statusIcon = $status['system_ready'] ? '✅' : '❌';
            echo "{$statusIcon} Sistema Pronto: " . ($status['system_ready'] ? 'Sim' : 'Não') . "\n";
            
            $needsIcon = $status['needs_install'] ? '⚠️' : '✅';
            echo "{$needsIcon} Precisa Instalar: " . ($status['needs_install'] ? 'Sim' : 'Não') . "\n";
            
            $firstIcon = $status['is_first_install'] ? '🆕' : '🔄';
            echo "{$firstIcon} Primeira Instalação: " . ($status['is_first_install'] ? 'Sim' : 'Não') . "\n\n";
            
            // Detalhes técnicos
            echo "🔧 Detalhes Técnicos:\n";
            
            $dbIcon = $status['database_connected'] ? '✅' : '❌';
            echo "  {$dbIcon} Banco Conectado: " . ($status['database_connected'] ? 'Sim' : 'Não') . "\n";
            
            $tablesIcon = $status['tables_exist'] ? '✅' : '❌';
            echo "  {$tablesIcon} Tabelas Existem: " . ($status['tables_exist'] ? 'Sim' : 'Não') . "\n";
            
            $usersIcon = $status['has_users'] ? '✅' : '❌';
            echo "  {$usersIcon} Usuários Existem: " . ($status['has_users'] ? 'Sim' : 'Não') . "\n";
            
            if (isset($status['error'])) {
                echo "\n❌ Erro: " . $status['error'] . "\n";
            }
            
            // Recomendações
            echo "\n💡 Recomendações:\n";
            
            if ($status['needs_install']) {
                if ($status['is_first_install']) {
                    echo "  🚀 Execute a primeira instalação acessando /install\n";
                    echo "  📝 Não será necessária senha de instalação\n";
                } else {
                    echo "  🔄 Execute a reinstalação acessando /install\n";
                    echo "  🔐 Será necessária a senha de instalação\n";
                }
            } else {
                echo "  ✨ Sistema está funcionando normalmente\n";
                echo "  🌐 Acesse /login para entrar no sistema\n";
            }
            
        } catch (\Exception $e) {
            echo "❌ Erro ao verificar status: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Força reinstalação
     * 
     * @return void
     */
    private function forceReinstall(): void
    {
        echo "⚠️  Forçando reinstalação do sistema...\n";
        
        $confirmation = readline("⚠️  Isso irá remover todos os usuários. Confirma? (s/N): ");
        
        if (strtolower($confirmation) !== 's') {
            echo "❌ Operação cancelada.\n";
            return;
        }
        
        try {
            // Remove todos os usuários
            $this->database->query("DELETE FROM {prefix}users");
            
            echo "✅ Usuários removidos. Sistema agora precisa ser reinstalado.\n";
            echo "🌐 Acesse /install para reinstalar o sistema.\n";
            
        } catch (\Exception $e) {
            echo "❌ Erro ao forçar reinstalação: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Reset completo do sistema
     * 
     * @return void
     */
    private function resetSystem(): void
    {
        echo "💥 Reset completo do sistema...\n";
        
        $confirmation = readline("⚠️  Isso irá APAGAR TODOS OS DADOS. Confirma? (s/N): ");
        
        if (strtolower($confirmation) !== 's') {
            echo "❌ Operação cancelada.\n";
            return;
        }
        
        $finalConfirmation = readline("⚠️  ÚLTIMA CHANCE! Digite 'RESET' para confirmar: ");
        
        if ($finalConfirmation !== 'RESET') {
            echo "❌ Operação cancelada.\n";
            return;
        }
        
        try {
            // Lista de tabelas para remover
            $tables = [
                'audit_logs',
                'system_logs',
                'system_settings',
                'school_schedules',
                'school_teams',
                'school_subjects',
                'school_periods',
                'users',
                'status',
                'levels',
                'genders'
            ];
            
            $prefix = $this->database->getPrefix();
            
            // Desabilita verificação de foreign keys
            $this->database->query("SET FOREIGN_KEY_CHECKS = 0");
            
            foreach ($tables as $table) {
                $fullTableName = $prefix . $table;
                try {
                    $this->database->query("DROP TABLE IF EXISTS `{$fullTableName}`");
                    echo "🗑️  Tabela {$table} removida.\n";
                } catch (\Exception $e) {
                    echo "⚠️  Erro ao remover tabela {$table}: " . $e->getMessage() . "\n";
                }
            }
            
            // Reabilita verificação de foreign keys
            $this->database->query("SET FOREIGN_KEY_CHECKS = 1");
            
            echo "\n✅ Reset completo realizado com sucesso!\n";
            echo "🌐 Acesse /install para instalar o sistema novamente.\n";
            
        } catch (\Exception $e) {
            echo "❌ Erro durante reset: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Exibe ajuda
     * 
     * @return void
     */
    private function showHelp(): void
    {
        echo "🔧 Verificador de Instalação - Sistema Administrativo MVC\n";
        echo str_repeat('=', 60) . "\n\n";
        
        echo "Uso: php cli/install-check.php [comando]\n\n";
        
        echo "Comandos disponíveis:\n";
        echo "  status    Verifica status atual da instalação\n";
        echo "  force     Força reinstalação (remove usuários)\n";
        echo "  reset     Reset completo (remove todas as tabelas)\n\n";
        
        echo "Exemplos:\n";
        echo "  php cli/install-check.php status\n";
        echo "  php cli/install-check.php force\n";
        echo "  php cli/install-check.php reset\n\n";
        
        echo "⚠️  Cuidado: Os comandos 'force' e 'reset' são destrutivos!\n";
    }
}

// Execução do script
$checker = new InstallChecker();
$checker->run($argv);