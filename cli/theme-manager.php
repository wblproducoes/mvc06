<?php
/**
 * Gerenciador de temas via CLI
 * 
 * Uso: php cli/theme-manager.php [comando] [opções]
 * 
 * Comandos:
 * - stats                    Estatísticas de uso de temas
 * - migrate                  Migra usuários para sistema de temas
 * - set [user_id] [theme]    Define tema para usuário específico
 * - reset [user_id]          Reset tema do usuário para automático
 * - bulk-set [theme]         Define tema para todos os usuários
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

use App\Services\ThemeService;
use App\Core\Logger;

class ThemeManagerCLI
{
    private ThemeService $themeService;
    
    public function __construct()
    {
        $this->themeService = new ThemeService();
    }
    
    /**
     * Executa comando
     * 
     * @param array $args
     * @return void
     */
    public function run(array $args): void
    {
        if (count($args) < 2) {
            $this->showHelp();
            return;
        }
        
        $command = $args[1];
        
        switch ($command) {
            case 'stats':
                $this->showStats();
                break;
                
            case 'migrate':
                $this->migrateUsers();
                break;
                
            case 'set':
                if (count($args) < 4) {
                    echo "❌ Uso: php cli/theme-manager.php set [user_id] [theme]\n";
                    return;
                }
                $this->setUserTheme((int)$args[2], $args[3]);
                break;
                
            case 'reset':
                if (count($args) < 3) {
                    echo "❌ Uso: php cli/theme-manager.php reset [user_id]\n";
                    return;
                }
                $this->resetUserTheme((int)$args[2]);
                break;
                
            case 'bulk-set':
                if (count($args) < 3) {
                    echo "❌ Uso: php cli/theme-manager.php bulk-set [theme]\n";
                    return;
                }
                $this->bulkSetTheme($args[2]);
                break;
                
            default:
                echo "❌ Comando desconhecido: {$command}\n\n";
                $this->showHelp();
        }
    }
    
    /**
     * Exibe estatísticas de temas
     * 
     * @return void
     */
    private function showStats(): void
    {
        echo "📊 Estatísticas de Uso de Temas\n";
        echo str_repeat('=', 40) . "\n";
        
        $stats = $this->themeService->getThemeStats();
        
        if ($stats['total'] === 0) {
            echo "ℹ️  Nenhum usuário com tema configurado.\n";
            return;
        }
        
        echo "📈 Total de usuários: " . number_format($stats['total']) . "\n\n";
        
        // Estatísticas por tema
        $themes = [
            'light' => ['name' => 'Claro', 'icon' => '☀️'],
            'dark' => ['name' => 'Escuro', 'icon' => '🌙'],
            'auto' => ['name' => 'Automático', 'icon' => '🔄']
        ];
        
        foreach ($themes as $key => $theme) {
            $count = $stats[$key];
            $percentage = $stats['total'] > 0 ? round(($count / $stats['total']) * 100, 1) : 0;
            
            echo "{$theme['icon']} {$theme['name']}: " . number_format($count) . " ({$percentage}%)\n";
            
            // Barra de progresso visual
            $barLength = 30;
            $filledLength = (int)(($percentage / 100) * $barLength);
            $bar = str_repeat('█', $filledLength) . str_repeat('░', $barLength - $filledLength);
            echo "   [{$bar}]\n\n";
        }
        
        // Tema mais popular
        $mostPopular = array_keys($stats, max(array_slice($stats, 0, 3)))[0];
        $mostPopularName = $themes[$mostPopular]['name'];
        echo "🏆 Tema mais popular: {$mostPopularName}\n";
    }
    
    /**
     * Migra usuários para sistema de temas
     * 
     * @return void
     */
    private function migrateUsers(): void
    {
        echo "🔄 Migrando usuários para sistema de temas...\n";
        
        $migrated = $this->themeService->migrateUserThemes();
        
        if ($migrated > 0) {
            echo "✅ {$migrated} usuários migrados com sucesso!\n";
            echo "📝 Todos os usuários agora têm tema 'Automático' configurado.\n";
        } else {
            echo "ℹ️  Nenhum usuário precisou ser migrado.\n";
        }
    }
    
    /**
     * Define tema para usuário específico
     * 
     * @param int $userId
     * @param string $theme
     * @return void
     */
    private function setUserTheme(int $userId, string $theme): void
    {
        $validThemes = ['light', 'dark', 'auto'];
        
        if (!in_array($theme, $validThemes)) {
            echo "❌ Tema inválido. Use: " . implode(', ', $validThemes) . "\n";
            return;
        }
        
        echo "🎨 Definindo tema '{$theme}' para usuário {$userId}...\n";
        
        $success = $this->themeService->setUserTheme($userId, $theme);
        
        if ($success) {
            echo "✅ Tema definido com sucesso!\n";
        } else {
            echo "❌ Erro ao definir tema. Verifique se o usuário existe.\n";
        }
    }
    
    /**
     * Reset tema do usuário
     * 
     * @param int $userId
     * @return void
     */
    private function resetUserTheme(int $userId): void
    {
        echo "🔄 Resetando tema do usuário {$userId} para automático...\n";
        
        $success = $this->themeService->setUserTheme($userId, 'auto');
        
        if ($success) {
            echo "✅ Tema resetado com sucesso!\n";
        } else {
            echo "❌ Erro ao resetar tema. Verifique se o usuário existe.\n";
        }
    }
    
    /**
     * Define tema para todos os usuários
     * 
     * @param string $theme
     * @return void
     */
    private function bulkSetTheme(string $theme): void
    {
        $validThemes = ['light', 'dark', 'auto'];
        
        if (!in_array($theme, $validThemes)) {
            echo "❌ Tema inválido. Use: " . implode(', ', $validThemes) . "\n";
            return;
        }
        
        echo "⚠️  Definindo tema '{$theme}' para TODOS os usuários...\n";
        
        $confirmation = readline("⚠️  Confirma a operação? (s/N): ");
        
        if (strtolower($confirmation) !== 's') {
            echo "❌ Operação cancelada.\n";
            return;
        }
        
        try {
            $database = new \App\Core\Database();
            
            // Busca todos os usuários ativos
            $users = $database->fetchAll(
                "SELECT id FROM {prefix}users WHERE deleted_at IS NULL"
            );
            
            $updated = 0;
            
            foreach ($users as $user) {
                if ($this->themeService->setUserTheme($user['id'], $theme)) {
                    $updated++;
                }
            }
            
            echo "✅ Tema definido para {$updated} usuários!\n";
            
            Logger::channel(Logger::CHANNEL_SYSTEM)->info('Bulk theme update performed', [
                'theme' => $theme,
                'users_updated' => $updated
            ]);
            
        } catch (\Exception $e) {
            echo "❌ Erro durante operação: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Exibe ajuda
     * 
     * @return void
     */
    private function showHelp(): void
    {
        echo "🎨 Gerenciador de Temas - Sistema Administrativo MVC\n";
        echo str_repeat('=', 60) . "\n\n";
        
        echo "Uso: php cli/theme-manager.php [comando] [opções]\n\n";
        
        echo "Comandos disponíveis:\n";
        echo "  stats                      Estatísticas de uso de temas\n";
        echo "  migrate                    Migra usuários para sistema de temas\n";
        echo "  set [user_id] [theme]      Define tema para usuário específico\n";
        echo "  reset [user_id]            Reset tema do usuário para automático\n";
        echo "  bulk-set [theme]           Define tema para todos os usuários\n\n";
        
        echo "Temas disponíveis:\n";
        echo "  light                      Tema claro\n";
        echo "  dark                       Tema escuro\n";
        echo "  auto                       Automático (segue preferência do sistema)\n\n";
        
        echo "Exemplos:\n";
        echo "  php cli/theme-manager.php stats\n";
        echo "  php cli/theme-manager.php set 1 dark\n";
        echo "  php cli/theme-manager.php reset 1\n";
        echo "  php cli/theme-manager.php bulk-set auto\n";
        echo "  php cli/theme-manager.php migrate\n\n";
        
        echo "💡 Dicas:\n";
        echo "  - Use 'migrate' após atualizar o sistema\n";
        echo "  - 'auto' usa a preferência do navegador/sistema\n";
        echo "  - 'bulk-set' afeta TODOS os usuários\n";
    }
}

// Execução do script
$manager = new ThemeManagerCLI();
$manager->run($argv);