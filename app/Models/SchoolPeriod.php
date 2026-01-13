<?php
/**
 * Model de períodos escolares
 * 
 * @package App\Models
 * @author Sistema Administrativo MVC
 */

namespace App\Models;

class SchoolPeriod extends BaseModel
{
    protected string $table = 'school_periods';
    protected array $fillable = [
        'name', 'translate', 'description', 'status_id'
    ];
    
    /**
     * Busca período por nome
     * 
     * @param string $name
     * @return array|null
     */
    public function findByName(string $name): ?array
    {
        return $this->findOne(['name' => $name, 'deleted_at' => null]);
    }
    
    /**
     * Busca todos os períodos ativos
     * 
     * @return array
     */
    public function findAllActive(): array
    {
        return $this->findAll(['status_id' => 1, 'deleted_at' => null], 'translate ASC');
    }
    
    /**
     * Busca períodos com status
     * 
     * @param array $conditions
     * @param string $orderBy
     * @param int|null $limit
     * @return array
     */
    public function findWithStatus(array $conditions = [], string $orderBy = 'p.translate ASC', ?int $limit = null): array
    {
        $whereClause = ['p.deleted_at IS NULL'];
        $params = [];
        
        foreach ($conditions as $field => $value) {
            $whereClause[] = "p.$field = :$field";
            $params[$field] = $value;
        }
        
        $sql = "SELECT p.*, 
                       s.translate as status_name,
                       s.color as status_color
                FROM {$this->getTableName()} p
                LEFT JOIN {$this->database->getPrefix()}status s ON p.status_id = s.id
                WHERE " . implode(' AND ', $whereClause) . "
                ORDER BY $orderBy";
        
        if ($limit !== null) {
            $sql .= " LIMIT $limit";
        }
        
        $stmt = $this->database->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Conta turmas do período
     * 
     * @param int $periodId
     * @return int
     */
    public function countTeams(int $periodId): int
    {
        $sql = "SELECT COUNT(*) as total 
                FROM {$this->database->getPrefix()}school_teams 
                WHERE period_id = :period_id AND deleted_at IS NULL";
        
        $stmt = $this->database->query($sql, ['period_id' => $periodId]);
        $result = $stmt->fetch();
        
        return $result['total'] ?? 0;
    }
    
    /**
     * Soft delete do período
     * 
     * @param int $id
     * @return bool
     */
    public function softDelete(int $id): bool
    {
        $sql = "UPDATE {$this->getTableName()} 
                SET deleted_at = NOW(), dh_update = NOW() 
                WHERE id = :id";
        
        $stmt = $this->database->query($sql, ['id' => $id]);
        return $stmt->rowCount() > 0;
    }
}