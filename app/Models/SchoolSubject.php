<?php
/**
 * Model de matérias escolares
 * 
 * @package App\Models
 * @author Sistema Administrativo MVC
 */

namespace App\Models;

class SchoolSubject extends BaseModel
{
    protected string $table = 'school_subjects';
    protected array $fillable = [
        'name', 'translate', 'description', 'status_id'
    ];
    
    /**
     * Busca matéria por nome
     * 
     * @param string $name
     * @return array|null
     */
    public function findByName(string $name): ?array
    {
        return $this->findOne(['name' => $name, 'deleted_at' => null]);
    }
    
    /**
     * Busca todas as matérias ativas
     * 
     * @return array
     */
    public function findAllActive(): array
    {
        return $this->findAll(['status_id' => 1, 'deleted_at' => null], 'translate ASC');
    }
    
    /**
     * Busca matérias com status
     * 
     * @param array $conditions
     * @param string $orderBy
     * @param int|null $limit
     * @return array
     */
    public function findWithStatus(array $conditions = [], string $orderBy = 's.translate ASC', ?int $limit = null): array
    {
        $whereClause = ['s.deleted_at IS NULL'];
        $params = [];
        
        foreach ($conditions as $field => $value) {
            $whereClause[] = "s.$field = :$field";
            $params[$field] = $value;
        }
        
        $sql = "SELECT s.*, 
                       st.translate as status_name,
                       st.color as status_color
                FROM {$this->getTableName()} s
                LEFT JOIN {$this->database->getPrefix()}status st ON s.status_id = st.id
                WHERE " . implode(' AND ', $whereClause) . "
                ORDER BY $orderBy";
        
        if ($limit !== null) {
            $sql .= " LIMIT $limit";
        }
        
        $stmt = $this->database->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Conta professores que lecionam a matéria
     * 
     * @param int $subjectId
     * @return int
     */
    public function countTeachers(int $subjectId): int
    {
        $sql = "SELECT COUNT(DISTINCT teacher_id) as total 
                FROM {$this->database->getPrefix()}school_schedules 
                WHERE subject_id = :subject_id AND deleted_at IS NULL";
        
        $stmt = $this->database->query($sql, ['subject_id' => $subjectId]);
        $result = $stmt->fetch();
        
        return $result['total'] ?? 0;
    }
    
    /**
     * Soft delete da matéria
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