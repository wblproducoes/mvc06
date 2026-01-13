<?php
/**
 * Gerenciador de logs via CLI
 * 
 * Uso: php cli/log-manager.php [comando] [opções]
 * 
 * Comandos:
 * - analyze [days]           Analisa logs dos últimos N dias
 * - cleanup [days]           Remove logs mais antigos que N dias
 * - export [start] [end]     Exporta logs do período
 * - monitor                  Monitor em tempo real
 * - stats                    Estatísticas gerais
 * - anomalies               Detecta anomalias
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

use App\Core\Logger;
use App\Services\LogAnalyzer;

class LogManager
{
    private LogAnalyzer $analyzer;
    
    public function __construct()
    {
        $this->analyzer = new LogAnalyzer();
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
            case 'analyze':
                $days = (int)($args[2] ?? 7);
                $this->analyze($days);
                break;
                
            case 'cleanup':
                $days = (int)($args[2] ?? 90);
                $this->cleanup($days);
                break;
                
            case 'export':
                $startDate = $args[2] ?? date('Y-m-d', strtotime('-7 days'));
                $endDate = $args[3] ?? date('Y-m-d');
                $format = $args[4] ?? 'json';
                $this->export($startDate, $endDate, $format);
                break;
                
            case 'monitor':
                $this->monitor();
                break;
                
            case 'stats':
                $days = (int)($args[2] ?? 7);
                $this->stats($days);
                break;
                
            case 'anomalies':
                $hours = (int)($args[2] ?? 24);
                $this->anomalies($hours);
                break;
                
            case 'test':
                $this->testLogging();
                break;
                
            default:
                echo "❌ Comando desconhecido: {$command}\n\n";
                $this->showHelp();
        }
    }
    
    /**
     * Analisa logs
     * 
     * @param int $days
     * @return void
     */
    private function analyze(int $days): void
    {
        echo "📊 Analisando logs dos últimos {$days} dias...\n";
        echo str_repeat('=', 50) . "\n";
        
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        $endDate = date('Y-m-d');
        
        $analysis = $this->analyzer->analyzeByPeriod($startDate, $endDate);
        
        if (empty($analysis)) {
            echo "ℹ️  Nenhum log encontrado no período.\n";
            return;
        }
        
        // Agrupa por data e nível
        $summary = [];
        foreach ($analysis as $entry) {
            $date = $entry['date'];
            $level = $entry['level'];
            
            if (!isset($summary[$date])) {
                $summary[$date] = [];
            }
            
            $summary[$date][$level] = $entry['count'];
        }
        
        // Exibe resumo
        foreach ($summary as $date => $levels) {
            echo "\n📅 {$date}:\n";
            foreach ($levels as $level => $count) {
                $icon = $this->getLevelIcon($level);
                echo "  {$icon} {$level}: {$count}\n";
            }
        }
        
        echo "\n✅ Análise concluída!\n";
    }
    
    /**
     * Limpa logs antigos
     * 
     * @param int $days
     * @return void
     */
    private function cleanup(int $days): void
    {
        echo "🧹 Limpando logs mais antigos que {$days} dias...\n";
        
        $confirmation = readline("⚠️  Confirma a remoção? (s/N): ");
        
        if (strtolower($confirmation) !== 's') {
            echo "❌ Operação cancelada.\n";
            return;
        }
        
        try {
            $removedCount = $this->analyzer->cleanOldLogs($days);
            
            Logger::channel(Logger::CHANNEL_SYSTEM)->info('Log cleanup performed via CLI', [
                'days' => $days,
                'removed_count' => $removedCount
            ]);
            
            echo "✅ Limpeza concluída! {$removedCount} registros removidos.\n";
            
        } catch (\Exception $e) {
            echo "❌ Erro na limpeza: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Exporta logs
     * 
     * @param string $startDate
     * @param string $endDate
     * @param string $format
     * @return void
     */
    private function export(string $startDate, string $endDate, string $format): void
    {
        echo "📤 Exportando logs de {$startDate} a {$endDate} em formato {$format}...\n";
        
        try {
            $filepath = $this->analyzer->exportLogs($startDate, $endDate, $format);
            $filesize = $this->formatBytes(filesize($filepath));
            
            echo "✅ Exportação concluída!\n";
            echo "📁 Arquivo: {$filepath}\n";
            echo "📏 Tamanho: {$filesize}\n";
            
        } catch (\Exception $e) {
            echo "❌ Erro na exportação: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Monitor em tempo real
     * 
     * @return void
     */
    private function monitor(): void
    {
        echo "👁️  Monitor de logs em tempo real (Ctrl+C para sair)\n";
        echo str_repeat('=', 60) . "\n";
        
        $this->analyzer->monitorRealTime(function($log) {
            $timestamp = date('H:i:s', strtotime($log['created_at']));
            $level = str_pad($log['level'], 8);
            $channel = str_pad($log['channel'], 10);
            $icon = $this->getLevelIcon($log['level']);
            
            echo "[{$timestamp}] {$icon} {$level} {$channel} {$log['message']}\n";
        });
    }
    
    /**
     * Estatísticas gerais
     * 
     * @param int $days
     * @return void
     */
    private function stats(int $days): void
    {
        echo "📈 Estatísticas dos últimos {$days} dias\n";
        echo str_repeat('=', 40) . "\n";
        
        $stats = $this->analyzer->getStatistics($days);
        
        echo "📊 Total de logs: " . number_format($stats['total_logs']) . "\n\n";
        
        // Por nível
        echo "📋 Por nível:\n";
        foreach ($stats['levels'] as $level) {
            $icon = $this->getLevelIcon($level['level']);
            $percentage = round(($level['count'] / $stats['total_logs']) * 100, 1);
            echo "  {$icon} {$level['level']}: " . number_format($level['count']) . " ({$percentage}%)\n";
        }
        
        echo "\n📂 Por canal:\n";
        foreach ($stats['channels'] as $channel) {
            $percentage = round(($channel['count'] / $stats['total_logs']) * 100, 1);
            echo "  📁 {$channel['channel']}: " . number_format($channel['count']) . " ({$percentage}%)\n";
        }
        
        if (!empty($stats['top_ips'])) {
            echo "\n🌐 Top IPs:\n";
            foreach (array_slice($stats['top_ips'], 0, 5) as $ip) {
                echo "  🔗 {$ip['ip_address']}: " . number_format($ip['count']) . "\n";
            }
        }
        
        if (!empty($stats['top_errors'])) {
            echo "\n⚠️  Erros mais frequentes:\n";
            foreach (array_slice($stats['top_errors'], 0, 5) as $error) {
                $message = strlen($error['message']) > 50 ? 
                          substr($error['message'], 0, 50) . '...' : 
                          $error['message'];
                echo "  ❌ {$message}: " . number_format($error['count']) . "\n";
            }
        }
    }
    
    /**
     * Detecta anomalias
     * 
     * @param int $hours
     * @return void
     */
    private function anomalies(int $hours): void
    {
        echo "🔍 Detectando anomalias das últimas {$hours} horas...\n";
        echo str_repeat('=', 50) . "\n";
        
        $anomalies = $this->analyzer->detectAnomalies($hours);
        
        if (empty($anomalies)) {
            echo "✅ Nenhuma anomalia detectada!\n";
            return;
        }
        
        if (isset($anomalies['error_spikes'])) {
            echo "🔥 Picos de erro detectados:\n";
            foreach ($anomalies['error_spikes'] as $spike) {
                echo "  ⚠️  {$spike['hour']}: {$spike['error_count']} erros\n";
            }
            echo "\n";
        }
        
        if (isset($anomalies['suspicious_ips'])) {
            echo "🛡️  IPs suspeitos:\n";
            foreach ($anomalies['suspicious_ips'] as $ip) {
                echo "  🚨 {$ip['ip_address']}: {$ip['request_count']} requisições\n";
            }
            echo "\n";
        }
        
        if (isset($anomalies['auth_failures'])) {
            echo "🔒 Falhas de autenticação:\n";
            foreach ($anomalies['auth_failures'] as $failure) {
                echo "  🚫 {$failure['ip_address']}: {$failure['failure_count']} tentativas\n";
            }
            echo "\n";
        }
        
        if (isset($anomalies['slow_queries'])) {
            echo "🐌 Queries lentas:\n";
            foreach ($anomalies['slow_queries'] as $query) {
                $message = strlen($query['message']) > 60 ? 
                          substr($query['message'], 0, 60) . '...' : 
                          $query['message'];
                echo "  ⏱️  {$query['execution_time']}s: {$message}\n";
            }
        }
    }
    
    /**
     * Testa sistema de logging
     * 
     * @return void
     */
    private function testLogging(): void
    {
        echo "🧪 Testando sistema de logging...\n";
        echo str_repeat('=', 40) . "\n";
        
        $logger = Logger::channel(Logger::CHANNEL_SYSTEM);
        
        // Testa diferentes níveis
        $logger->debug('Teste de log DEBUG');
        $logger->info('Teste de log INFO');
        $logger->notice('Teste de log NOTICE');
        $logger->warning('Teste de log WARNING');
        $logger->error('Teste de log ERROR');
        
        // Testa com contexto
        $logger->info('Teste com contexto', [
            'user_id' => 1,
            'action' => 'test_logging',
            'data' => ['test' => true]
        ]);
        
        // Testa diferentes canais
        Logger::channel(Logger::CHANNEL_SECURITY)->warning('Teste de segurança');
        Logger::channel(Logger::CHANNEL_API)->info('Teste de API');
        Logger::channel(Logger::CHANNEL_DATABASE)->debug('Teste de database');
        
        echo "✅ Logs de teste criados com sucesso!\n";
        echo "📁 Verifique os arquivos em storage/logs/\n";
    }
    
    /**
     * Retorna ícone para nível de log
     * 
     * @param string $level
     * @return string
     */
    private function getLevelIcon(string $level): string
    {
        return match($level) {
            'EMERGENCY' => '🚨',
            'ALERT' => '🔔',
            'CRITICAL' => '💥',
            'ERROR' => '❌',
            'WARNING' => '⚠️',
            'NOTICE' => '📢',
            'INFO' => 'ℹ️',
            'DEBUG' => '🐛',
            default => '📝'
        };
    }
    
    /**
     * Formata bytes em formato legível
     * 
     * @param int $bytes
     * @return string
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }
    
    /**
     * Exibe ajuda
     * 
     * @return void
     */
    private function showHelp(): void
    {
        echo "📚 Gerenciador de Logs - Sistema Administrativo MVC\n";
        echo str_repeat('=', 60) . "\n\n";
        
        echo "Uso: php cli/log-manager.php [comando] [opções]\n\n";
        
        echo "Comandos disponíveis:\n";
        echo "  analyze [days]           Analisa logs dos últimos N dias (padrão: 7)\n";
        echo "  cleanup [days]           Remove logs mais antigos que N dias (padrão: 90)\n";
        echo "  export [start] [end] [format]  Exporta logs do período (json|csv|txt)\n";
        echo "  monitor                  Monitor em tempo real\n";
        echo "  stats [days]             Estatísticas gerais (padrão: 7)\n";
        echo "  anomalies [hours]        Detecta anomalias (padrão: 24)\n";
        echo "  test                     Testa sistema de logging\n\n";
        
        echo "Exemplos:\n";
        echo "  php cli/log-manager.php stats 30\n";
        echo "  php cli/log-manager.php export 2025-01-01 2025-01-31 csv\n";
        echo "  php cli/log-manager.php cleanup 60\n";
        echo "  php cli/log-manager.php anomalies 48\n\n";
    }
}

// Execução do script
$manager = new LogManager();
$manager->run($argv);