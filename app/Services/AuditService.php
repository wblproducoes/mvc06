<?php
/**
 * Serviço de auditoria e logs
 * 
 * @package App\Services
 * @author Sistema Administrativo MVC
 */

namespace App\Services;

use App\Core\Database;

class AuditService
{
    private Database $database;
    
    /**
     * Construtor do serviço de auditoria
     */
    public function __construct()
    {
        $this->database = new Database();
    }
    
    /**
     * Registra evento de auditoria
     * 
     * @param string $action
     * @param string $table
     * @param int|null $recordId
     * @param array $oldData
     * @param array $newData
     * @param int|null $userId
     * @return void
     */
    public function logAction(
        string $action, 
        string $table, 
        ?int $recordId = null, 
        array $oldData = [], 
        array $newData = [], 
        ?int $userId = null
    ): void {
        $userId = $userId ?? ($_SESSION['user']['id'] ?? null);
        
        $auditData = [
            'user_id' => $userId,
            'action' => $action,
            'table_name' => $table,
            'record_id' => $recordId,
            'old_data' => !empty($oldData) ? json_encode($oldData) : null,
            'new_data' => !empty($newData) ? json_encode($newData) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Remove dados sensíveis
        $auditData = $this->sanitizeAuditData($auditData);
        
        try {
            $sql = "INSERT INTO {$this->database->getPrefix()}audit_logs 
                    (user_id, action, table_name, record_id, old_data, new_data, ip_address, user_agent, created_at)
                    VALUES (:user_id, :action, :table_name, :record_id, :old_data, :new_data, :ip_address, :user_agent, :created_at)";
            
            $this->database->query($sql, $auditData);
        } catch (\Exception $e) {
            // Log erro mas não interrompe execução
            error_log("Erro ao registrar auditoria: " . $e->getMessage());
        }
    }
    
    /**
     * Remove dados sensíveis dos logs
     * 
     * @param array $data
     * @return array
     */
    private function sanitizeAuditData(array $data): array
    {
        $sensitiveFields = ['password', 'senha', 'token', 'secret', 'key'];
        
        foreach (['old_data', 'new_data'] as $field) {
            if (!empty($data[$field])) {
                $decoded = json_decode($data[$field], true);
                if (is_array($decoded)) {
                    foreach ($sensitiveFields as $sensitive) {
                        if (isset($decoded[$sensitive])) {
                            $decoded[$sensitive] = '[REDACTED]';
                        }
                    }
                    $data[$field] = json_encode($decoded);
                }
            }
        }
        
        return $data;
    }
    
    /**
     * Busca logs de auditoria
     * 
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getAuditLogs(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $whereClause = ['1=1'];
        $params = [];
        
        if (!empty($filters['user_id'])) {
            $whereClause[] = 'a.user_id = :user_id';
            $params['user_id'] = $filters['user_id'];
        }
        
        if (!empty($filters['action'])) {
            $whereClause[] = 'a.action = :action';
            $params['action'] = $filters['action'];
        }
        
        if (!empty($filters['table_name'])) {
            $whereClause[] = 'a.table_name = :table_name';
            $params['table_name'] = $filters['table_name'];
        }
        
        if (!empty($filters['date_from'])) {
            $whereClause[] = 'a.created_at >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $whereClause[] = 'a.created_at <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }
        
        $sql = "SELECT a.*, u.name as user_name, u.email as user_email
                FROM {$this->database->getPrefix()}audit_logs a
                LEFT JOIN {$this->database->getPrefix()}users u ON a.user_id = u.id
                WHERE " . implode(' AND ', $whereClause) . "
                ORDER BY a.created_at DESC
                LIMIT $limit OFFSET $offset";
        
        $stmt = $this->database->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Conta logs de auditoria
     * 
     * @param array $filters
     * @return int
     */
    public function countAuditLogs(array $filters = []): int
    {
        $whereClause = ['1=1'];
        $params = [];
        
        if (!empty($filters['user_id'])) {
            $whereClause[] = 'user_id = :user_id';
            $params['user_id'] = $filters['user_id'];
        }
        
        if (!empty($filters['action'])) {
            $whereClause[] = 'action = :action';
            $params['action'] = $filters['action'];
        }
        
        if (!empty($filters['table_name'])) {
            $whereClause[] = 'table_name = :table_name';
            $params['table_name'] = $filters['table_name'];
        }
        
        $sql = "SELECT COUNT(*) as total 
                FROM {$this->database->getPrefix()}audit_logs 
                WHERE " . implode(' AND ', $whereClause);
        
        $stmt = $this->database->query($sql, $params);
        $result = $stmt->fetch();
        
        return $result['total'] ?? 0;
    }
    
    /**
     * Limpa logs antigos
     * 
     * @param int $days
     * @return int
     */
    public function cleanOldLogs(int $days = 90): int
    {
        $sql = "DELETE FROM {$this->database->getPrefix()}audit_logs 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
        
        $stmt = $this->database->query($sql, ['days' => $days]);
        return $stmt->rowCount();
    }
}