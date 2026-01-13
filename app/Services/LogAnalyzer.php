<?php
/**
 * Analisador de logs avançado
 * 
 * @package App\Services
 * @author Sistema Administrativo MVC
 */

namespace App\Services;

use App\Core\Database;

class LogAnalyzer
{
    private Database $database;
    private string $logPath;
    
    public function __construct()
    {
        $this->database = new Database();
        $this->logPath = __DIR__ . '/../../storage/logs';
    }
    
    /**
     * Analisa logs por período
     * 
     * @param string $startDate
     * @param string $endDate
     * @param string|null $channel
     * @return array
     */
    public function analyzeByPeriod(string $startDate, string $endDate, ?string $channel = null): array
    {
        $query = "SELECT 
                    level,
                    channel,
                    COUNT(*) as count,
                    DATE(created_at) as date
                  FROM {prefix}system_logs 
                  WHERE created_at BETWEEN ? AND ?";
        
        $params = [$startDate, $endDate];
        
        if ($channel) {
            $query .= " AND channel = ?";
            $params[] = $channel;
        }
        
        $query .= " GROUP BY level, channel, DATE(created_at) ORDER BY date DESC, count DESC";
        
        return $this->database->query($query, $params);
    }
    
    /**
     * Estatísticas gerais de logs
     * 
     * @param int $days
     * @return array
     */
    public function getStatistics(int $days = 7): array
    {
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        
        // Total por nível
        $levelStats = $this->database->query(
            "SELECT level, COUNT(*) as count 
             FROM {prefix}system_logs 
             WHERE created_at >= ? 
             GROUP BY level 
             ORDER BY count DESC",
            [$startDate]
        );
        
        // Total por canal
        $channelStats = $this->database->query(
            "SELECT channel, COUNT(*) as count 
             FROM {prefix}system_logs 
             WHERE created_at >= ? 
             GROUP BY channel 
             ORDER BY count DESC",
            [$startDate]
        );
        
        // Atividade por hora
        $hourlyStats = $this->database->query(
            "SELECT HOUR(created_at) as hour, COUNT(*) as count 
             FROM {prefix}system_logs 
             WHERE created_at >= ? 
             GROUP BY HOUR(created_at) 
             ORDER BY hour",
            [$startDate]
        );
        
        // Top IPs
        $topIps = $this->database->query(
            "SELECT ip_address, COUNT(*) as count 
             FROM {prefix}system_logs 
             WHERE created_at >= ? AND ip_address IS NOT NULL 
             GROUP BY ip_address 
             ORDER BY count DESC 
             LIMIT 10",
            [$startDate]
        );
        
        // Erros mais frequentes
        $topErrors = $this->database->query(
            "SELECT message, COUNT(*) as count 
             FROM {prefix}system_logs 
             WHERE created_at >= ? AND level IN ('ERROR', 'CRITICAL', 'EMERGENCY') 
             GROUP BY message 
             ORDER BY count DESC 
             LIMIT 10",
            [$startDate]
        );
        
        return [
            'period' => $days,
            'levels' => $levelStats,
            'channels' => $channelStats,
            'hourly_activity' => $hourlyStats,
            'top_ips' => $topIps,
            'top_errors' => $topErrors,
            'total_logs' => array_sum(array_column($levelStats, 'count'))
        ];
    }
    
    /**
     * Detecta anomalias nos logs
     * 
     * @param int $hours
     * @return array
     */
    public function detectAnomalies(int $hours = 24): array
    {
        $startTime = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));
        $anomalies = [];
        
        // Picos de erro
        $errorSpikes = $this->database->query(
            "SELECT 
                DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') as hour,
                COUNT(*) as error_count
             FROM {prefix}system_logs 
             WHERE created_at >= ? AND level IN ('ERROR', 'CRITICAL', 'EMERGENCY')
             GROUP BY DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00')
             HAVING error_count > 10
             ORDER BY error_count DESC",
            [$startTime]
        );
        
        if (!empty($errorSpikes)) {
            $anomalies['error_spikes'] = $errorSpikes;
        }
        
        // IPs suspeitos (muitas requisições)
        $suspiciousIps = $this->database->query(
            "SELECT 
                ip_address,
                COUNT(*) as request_count,
                COUNT(DISTINCT user_id) as unique_users
             FROM {prefix}system_logs 
             WHERE created_at >= ? AND ip_address IS NOT NULL
             GROUP BY ip_address
             HAVING request_count > 1000 OR (request_count > 100 AND unique_users > 10)
             ORDER BY request_count DESC",
            [$startTime]
        );
        
        if (!empty($suspiciousIps)) {
            $anomalies['suspicious_ips'] = $suspiciousIps;
        }
        
        // Falhas de autenticação
        $authFailures = $this->database->query(
            "SELECT 
                ip_address,
                COUNT(*) as failure_count
             FROM {prefix}system_logs 
             WHERE created_at >= ? 
               AND channel = 'auth' 
               AND level = 'WARNING'
               AND message LIKE '%failed%'
             GROUP BY ip_address
             HAVING failure_count > 5
             ORDER BY failure_count DESC",
            [$startTime]
        );
        
        if (!empty($authFailures)) {
            $anomalies['auth_failures'] = $authFailures;
        }
        
        // Performance degradada
        $slowQueries = $this->database->query(
            "SELECT 
                message,
                JSON_EXTRACT(context, '$.execution_time') as execution_time,
                COUNT(*) as occurrence_count
             FROM {prefix}system_logs 
             WHERE created_at >= ? 
               AND channel = 'database'
               AND JSON_EXTRACT(context, '$.execution_time') > 1.0
             GROUP BY message
             ORDER BY execution_time DESC
             LIMIT 10",
            [$startTime]
        );
        
        if (!empty($slowQueries)) {
            $anomalies['slow_queries'] = $slowQueries;
        }
        
        return $anomalies;
    }
    
    /**
     * Gera relatório de logs
     * 
     * @param string $startDate
     * @param string $endDate
     * @param array $options
     * @return array
     */
    public function generateReport(string $startDate, string $endDate, array $options = []): array
    {
        $includeDetails = $options['include_details'] ?? false;
        $channels = $options['channels'] ?? null;
        $levels = $options['levels'] ?? null;
        
        $whereConditions = ["created_at BETWEEN ? AND ?"];
        $params = [$startDate, $endDate];
        
        if ($channels) {
            $placeholders = str_repeat('?,', count($channels) - 1) . '?';
            $whereConditions[] = "channel IN ({$placeholders})";
            $params = array_merge($params, $channels);
        }
        
        if ($levels) {
            $placeholders = str_repeat('?,', count($levels) - 1) . '?';
            $whereConditions[] = "level IN ({$placeholders})";
            $params = array_merge($params, $levels);
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // Resumo geral
        $summary = $this->database->query(
            "SELECT 
                COUNT(*) as total_logs,
                COUNT(DISTINCT ip_address) as unique_ips,
                COUNT(DISTINCT user_id) as unique_users,
                MIN(created_at) as first_log,
                MAX(created_at) as last_log
             FROM {prefix}system_logs 
             WHERE {$whereClause}",
            $params
        )[0];
        
        // Distribuição por nível
        $levelDistribution = $this->database->query(
            "SELECT level, COUNT(*) as count, 
                    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM {prefix}system_logs WHERE {$whereClause}), 2) as percentage
             FROM {prefix}system_logs 
             WHERE {$whereClause}
             GROUP BY level 
             ORDER BY count DESC",
            array_merge($params, $params)
        );
        
        // Distribuição por canal
        $channelDistribution = $this->database->query(
            "SELECT channel, COUNT(*) as count,
                    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM {prefix}system_logs WHERE {$whereClause}), 2) as percentage
             FROM {prefix}system_logs 
             WHERE {$whereClause}
             GROUP BY channel 
             ORDER BY count DESC",
            array_merge($params, $params)
        );
        
        $report = [
            'period' => [
                'start' => $startDate,
                'end' => $endDate
            ],
            'summary' => $summary,
            'level_distribution' => $levelDistribution,
            'channel_distribution' => $channelDistribution
        ];
        
        if ($includeDetails) {
            // Top mensagens de erro
            $report['top_errors'] = $this->database->query(
                "SELECT message, COUNT(*) as count 
                 FROM {prefix}system_logs 
                 WHERE {$whereClause} AND level IN ('ERROR', 'CRITICAL', 'EMERGENCY')
                 GROUP BY message 
                 ORDER BY count DESC 
                 LIMIT 20",
                $params
            );
            
            // Atividade por dia
            $report['daily_activity'] = $this->database->query(
                "SELECT DATE(created_at) as date, COUNT(*) as count 
                 FROM {prefix}system_logs 
                 WHERE {$whereClause}
                 GROUP BY DATE(created_at) 
                 ORDER BY date",
                $params
            );
        }
        
        return $report;
    }
    
    /**
     * Limpa logs antigos
     * 
     * @param int $days
     * @return int Número de registros removidos
     */
    public function cleanOldLogs(int $days = 90): int
    {
        $cutoffDate = date('Y-m-d', strtotime("-{$days} days"));
        
        $result = $this->database->query(
            "DELETE FROM {prefix}system_logs WHERE created_at < ?",
            [$cutoffDate]
        );
        
        return $this->database->getAffectedRows();
    }
    
    /**
     * Exporta logs para arquivo
     * 
     * @param string $startDate
     * @param string $endDate
     * @param string $format
     * @param array $options
     * @return string Caminho do arquivo gerado
     */
    public function exportLogs(string $startDate, string $endDate, string $format = 'json', array $options = []): string
    {
        $logs = $this->database->query(
            "SELECT * FROM {prefix}system_logs 
             WHERE created_at BETWEEN ? AND ? 
             ORDER BY created_at DESC",
            [$startDate, $endDate]
        );
        
        $filename = "logs_export_{$startDate}_{$endDate}." . $format;
        $filepath = $this->logPath . '/exports/' . $filename;
        
        // Garante que o diretório existe
        $exportDir = dirname($filepath);
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }
        
        switch ($format) {
            case 'json':
                file_put_contents($filepath, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                break;
                
            case 'csv':
                $fp = fopen($filepath, 'w');
                
                if (!empty($logs)) {
                    // Header
                    fputcsv($fp, array_keys($logs[0]));
                    
                    // Data
                    foreach ($logs as $log) {
                        fputcsv($fp, $log);
                    }
                }
                
                fclose($fp);
                break;
                
            case 'txt':
                $content = '';
                foreach ($logs as $log) {
                    $content .= "[{$log['created_at']}] {$log['level']}.{$log['channel']}: {$log['message']}\n";
                    if ($log['context']) {
                        $content .= "Context: " . $log['context'] . "\n";
                    }
                    $content .= "\n";
                }
                file_put_contents($filepath, $content);
                break;
        }
        
        return $filepath;
    }
    
    /**
     * Monitora logs em tempo real
     * 
     * @param callable $callback
     * @param array $filters
     * @return void
     */
    public function monitorRealTime(callable $callback, array $filters = []): void
    {
        $lastId = $this->getLastLogId();
        
        while (true) {
            $newLogs = $this->getNewLogs($lastId, $filters);
            
            foreach ($newLogs as $log) {
                $callback($log);
                $lastId = max($lastId, $log['id']);
            }
            
            sleep(1); // Verifica a cada segundo
        }
    }
    
    /**
     * Retorna ID do último log
     * 
     * @return int
     */
    private function getLastLogId(): int
    {
        $result = $this->database->query("SELECT MAX(id) as max_id FROM {prefix}system_logs");
        return (int)($result[0]['max_id'] ?? 0);
    }
    
    /**
     * Retorna novos logs desde o último ID
     * 
     * @param int $lastId
     * @param array $filters
     * @return array
     */
    private function getNewLogs(int $lastId, array $filters = []): array
    {
        $query = "SELECT * FROM {prefix}system_logs WHERE id > ?";
        $params = [$lastId];
        
        if (!empty($filters['levels'])) {
            $placeholders = str_repeat('?,', count($filters['levels']) - 1) . '?';
            $query .= " AND level IN ({$placeholders})";
            $params = array_merge($params, $filters['levels']);
        }
        
        if (!empty($filters['channels'])) {
            $placeholders = str_repeat('?,', count($filters['channels']) - 1) . '?';
            $query .= " AND channel IN ({$placeholders})";
            $params = array_merge($params, $filters['channels']);
        }
        
        $query .= " ORDER BY id ASC";
        
        return $this->database->query($query, $params);
    }
}