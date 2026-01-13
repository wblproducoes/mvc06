<?php
/**
 * Controller para gerenciamento de logs
 * 
 * @package App\Controllers
 * @author Sistema Administrativo MVC
 */

namespace App\Controllers;

use App\Core\Logger;
use App\Services\LogAnalyzer;
use App\Middleware\AuthMiddleware;

class LogController extends BaseController
{
    private LogAnalyzer $logAnalyzer;
    
    public function __construct()
    {
        parent::__construct();
        
        // Verifica se usuário tem permissão para logs
        if (!$this->isAuthenticated() || !$this->hasLogPermission()) {
            $this->redirect('/dashboard');
            return;
        }
        
        $this->logAnalyzer = new LogAnalyzer();
    }
    
    /**
     * Dashboard de logs
     * 
     * @return void
     */
    public function index(): void
    {
        $days = (int)($_GET['days'] ?? 7);
        $statistics = $this->logAnalyzer->getStatistics($days);
        $anomalies = $this->logAnalyzer->detectAnomalies(24);
        
        $this->render('logs/dashboard.twig', [
            'statistics' => $statistics,
            'anomalies' => $anomalies,
            'selected_days' => $days
        ]);
    }
    
    /**
     * Lista logs com filtros
     * 
     * @return void
     */
    public function list(): void
    {
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 50);
        $level = $_GET['level'] ?? '';
        $channel = $_GET['channel'] ?? '';
        $search = $_GET['search'] ?? '';
        $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        
        $query = "SELECT * FROM {prefix}system_logs WHERE created_at BETWEEN ? AND ?";
        $params = [$startDate . ' 00:00:00', $endDate . ' 23:59:59'];
        
        if ($level) {
            $query .= " AND level = ?";
            $params[] = $level;
        }
        
        if ($channel) {
            $query .= " AND channel = ?";
            $params[] = $channel;
        }
        
        if ($search) {
            $query .= " AND (message LIKE ? OR context LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $query .= " ORDER BY created_at DESC";
        
        // Paginação
        $offset = ($page - 1) * $perPage;
        $query .= " LIMIT {$offset}, {$perPage}";
        
        $logs = $this->database->query($query, $params);
        
        // Total para paginação
        $countQuery = str_replace('SELECT *', 'SELECT COUNT(*) as total', $query);
        $countQuery = preg_replace('/LIMIT \d+, \d+/', '', $countQuery);
        $totalResult = $this->database->query($countQuery, $params);
        $total = $totalResult[0]['total'] ?? 0;
        
        // Processa contexto JSON
        foreach ($logs as &$log) {
            if ($log['context']) {
                $log['context_decoded'] = json_decode($log['context'], true);
            }
        }
        
        $this->render('logs/list.twig', [
            'logs' => $logs,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage)
            ],
            'filters' => [
                'level' => $level,
                'channel' => $channel,
                'search' => $search,
                'start_date' => $startDate,
                'end_date' => $endDate
            ],
            'levels' => [
                'EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR', 
                'WARNING', 'NOTICE', 'INFO', 'DEBUG'
            ],
            'channels' => [
                'system', 'security', 'api', 'database', 
                'auth', 'audit', 'performance', 'error'
            ]
        ]);
    }
    
    /**
     * Detalhes de um log específico
     * 
     * @param int $id
     * @return void
     */
    public function show(int $id): void
    {
        $log = $this->database->query(
            "SELECT * FROM {prefix}system_logs WHERE id = ?",
            [$id]
        )[0] ?? null;
        
        if (!$log) {
            $this->addFlashMessage('error', 'Log não encontrado');
            $this->redirect('/logs');
            return;
        }
        
        // Decodifica contexto
        if ($log['context']) {
            $log['context_decoded'] = json_decode($log['context'], true);
        }
        
        $this->render('logs/show.twig', [
            'log' => $log
        ]);
    }
    
    /**
     * Relatórios de logs
     * 
     * @return void
     */
    public function reports(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $startDate = $_POST['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
            $endDate = $_POST['end_date'] ?? date('Y-m-d');
            $channels = $_POST['channels'] ?? [];
            $levels = $_POST['levels'] ?? [];
            $includeDetails = isset($_POST['include_details']);
            
            $options = [
                'include_details' => $includeDetails,
                'channels' => !empty($channels) ? $channels : null,
                'levels' => !empty($levels) ? $levels : null
            ];
            
            $report = $this->logAnalyzer->generateReport($startDate, $endDate, $options);
            
            $this->render('logs/report.twig', [
                'report' => $report,
                'options' => $options
            ]);
            return;
        }
        
        $this->render('logs/reports.twig', [
            'levels' => [
                'EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR', 
                'WARNING', 'NOTICE', 'INFO', 'DEBUG'
            ],
            'channels' => [
                'system', 'security', 'api', 'database', 
                'auth', 'audit', 'performance', 'error'
            ]
        ]);
    }
    
    /**
     * Exporta logs
     * 
     * @return void
     */
    public function export(): void
    {
        $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        $format = $_GET['format'] ?? 'json';
        
        try {
            $filepath = $this->logAnalyzer->exportLogs($startDate, $endDate, $format);
            $filename = basename($filepath);
            
            // Headers para download
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($filepath));
            
            readfile($filepath);
            
            // Remove arquivo temporário
            unlink($filepath);
            
        } catch (\Exception $e) {
            $this->addFlashMessage('error', 'Erro ao exportar logs: ' . $e->getMessage());
            $this->redirect('/logs');
        }
    }
    
    /**
     * Limpa logs antigos
     * 
     * @return void
     */
    public function cleanup(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $days = (int)($_POST['days'] ?? 90);
            
            try {
                $removedCount = $this->logAnalyzer->cleanOldLogs($days);
                
                Logger::channel(Logger::CHANNEL_SYSTEM)->info('Log cleanup performed', [
                    'days' => $days,
                    'removed_count' => $removedCount,
                    'performed_by' => $this->getCurrentUser()['id']
                ]);
                
                $this->addFlashMessage('success', "Limpeza concluída. {$removedCount} registros removidos.");
                
            } catch (\Exception $e) {
                Logger::channel(Logger::CHANNEL_ERROR)->error('Log cleanup failed', [
                    'error' => $e->getMessage(),
                    'performed_by' => $this->getCurrentUser()['id']
                ]);
                
                $this->addFlashMessage('error', 'Erro na limpeza: ' . $e->getMessage());
            }
        }
        
        $this->redirect('/logs');
    }
    
    /**
     * API para dados de logs (AJAX)
     * 
     * @return void
     */
    public function apiData(): void
    {
        $type = $_GET['type'] ?? 'statistics';
        $days = (int)($_GET['days'] ?? 7);
        
        try {
            switch ($type) {
                case 'statistics':
                    $data = $this->logAnalyzer->getStatistics($days);
                    break;
                    
                case 'anomalies':
                    $hours = (int)($_GET['hours'] ?? 24);
                    $data = $this->logAnalyzer->detectAnomalies($hours);
                    break;
                    
                case 'analysis':
                    $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime("-{$days} days"));
                    $endDate = $_GET['end_date'] ?? date('Y-m-d');
                    $channel = $_GET['channel'] ?? null;
                    $data = $this->logAnalyzer->analyzeByPeriod($startDate, $endDate, $channel);
                    break;
                    
                default:
                    throw new \InvalidArgumentException('Tipo de dados inválido');
            }
            
            $this->json(['success' => true, 'data' => $data]);
            
        } catch (\Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }
    
    /**
     * Monitor de logs em tempo real
     * 
     * @return void
     */
    public function monitor(): void
    {
        $this->render('logs/monitor.twig');
    }
    
    /**
     * SSE para logs em tempo real
     * 
     * @return void
     */
    public function streamLogs(): void
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        
        $lastId = (int)($_GET['last_id'] ?? 0);
        
        // Busca novos logs
        $newLogs = $this->database->query(
            "SELECT * FROM {prefix}system_logs 
             WHERE id > ? 
             ORDER BY id ASC 
             LIMIT 10",
            [$lastId]
        );
        
        foreach ($newLogs as $log) {
            echo "data: " . json_encode($log) . "\n\n";
            $lastId = $log['id'];
        }
        
        echo "event: heartbeat\n";
        echo "data: {\"last_id\": {$lastId}}\n\n";
        
        flush();
    }
    
    /**
     * Verifica se usuário tem permissão para logs
     * 
     * @return bool
     */
    private function hasLogPermission(): bool
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            return false;
        }
        
        // Apenas admins e managers podem ver logs
        return in_array($user['level_id'], [1, 2]);
    }
}