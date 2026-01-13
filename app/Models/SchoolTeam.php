<?php
/**
 * Model de turmas escolares
 * 
 * @package App\Models
 * @author Sistema Administrativo MVC
 */

namespace App\Models;

class SchoolTeam extends BaseModel
{
    protected string $table = 'school_teams';
    protected array $fillable = [
        'serie_id', 'period_id', 'education_id', 'status_id',
        'public_link_token', 'public_link_enabled', 'public_link_expires_at'
    ];
    
    /**
     * Busca turma por token público
     * 
     * @param string $token
     * @return array|null
     */
    public function findByPublicToken(string $token): ?array
    {
        $conditions = [
            'public_link_token' => $token,
            'public_link_enabled' => 1,
            'deleted_at' => null
        ];
        
        $team = $this->findOne($conditions);
        
        // Verifica se o link não expirou
        if ($team && $team['public_link_expires_at']) {
            $expiresAt = strtotime($team['public_link_expires_at']);
            if ($expiresAt < time()) {
                return null; // Link expirado
            }
        }
        
        return $team;
    }
    
    /**
     * Busca todas as turmas ativas
     * 
     * @return array
     */
    public function findAllActive(): array
    {
        return $this->findAll(['status_id' => 1, 'deleted_at' => null], 'id DESC');
    }
    
    /**
     * Busca turmas com relacionamentos
     * 
     * @param array $conditions
     * @param string $orderBy
     * @param int|null $limit
     * @return array
     */
    public function findWithRelations(array $conditions = [], string $orderBy = 't.id DESC', ?int $limit = null): array
    {
        $whereClause = ['t.deleted_at IS NULL'];
        $params = [];
        
        foreach ($conditions as $field => $value) {
            $whereClause[] = "t.$field = :$field";
            $params[$field] = $value;
        }
        
        $sql = "SELECT t.*, 
                       p.translate as period_name,
                       s.translate as status_name,
                       s.color as status_color
                FROM {$this->getTableName()} t
                LEFT JOIN {$this->database->getPrefix()}school_periods p ON t.period_id = p.id
                LEFT JOIN {$this->database->getPrefix()}status s ON t.status_id = s.id
                WHERE " . implode(' AND ', $whereClause) . "
                ORDER BY $orderBy";
        
        if ($limit !== null) {
            $sql .= " LIMIT $limit";
        }
        
        $stmt = $this->database->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Gera token único para link público
     * 
     * @return string
     */
    public function generatePublicToken(): string
    {
        do {
            $token = strtoupper(substr(md5(uniqid()), 0, 10));
        } while ($this->findByPublicToken($token));
        
        return $token;
    }
    
    /**
     * Ativa link público da turma
     * 
     * @param int $teamId
     * @param string|null $expiresAt
     * @return bool
     */
    public function enablePublicLink(int $teamId, ?string $expiresAt = null): bool
    {
        $token = $this->generatePublicToken();
        
        $data = [
            'public_link_token' => $token,
            'public_link_enabled' => 1,
            'public_link_expires_at' => $expiresAt
        ];
        
        return $this->update($teamId, $data);
    }
    
    /**
     * Desativa link público da turma
     * 
     * @param int $teamId
     * @return bool
     */
    public function disablePublicLink(int $teamId): bool
    {
        $data = [
            'public_link_enabled' => 0,
            'public_link_token' => null,
            'public_link_expires_at' => null
        ];
        
        return $this->update($teamId, $data);
    }
    
    /**
     * Conta alunos da turma
     * 
     * @param int $teamId
     * @return int
     */
    public function countStudents(int $teamId): int
    {
        // Implementar quando houver tabela de matrículas
        return 0;
    }
    
    /**
     * Busca horários da turma
     * 
     * @param int $teamId
     * @return array
     */
    public function getSchedules(int $teamId): array
    {
        $sql = "SELECT sch.*, 
                       sub.translate as subject_name,
                       u.name as teacher_name
                FROM {$this->database->getPrefix()}school_schedules sch
                LEFT JOIN {$this->database->getPrefix()}school_subjects sub ON sch.subject_id = sub.id
                LEFT JOIN {$this->database->getPrefix()}users u ON sch.teacher_id = u.id
                WHERE sch.team_id = :team_id AND sch.deleted_at IS NULL
                ORDER BY sch.day_of_week, sch.class_number";
        
        $stmt = $this->database->query($sql, ['team_id' => $teamId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Soft delete da turma
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