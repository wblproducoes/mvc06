<?php
/**
 * Model de horários escolares
 * 
 * @package App\Models
 * @author Sistema Administrativo MVC
 */

namespace App\Models;

class SchoolSchedule extends BaseModel
{
    protected string $table = 'school_schedules';
    protected array $fillable = [
        'team_id', 'day_of_week', 'class_number', 'teacher_id',
        'subject_id', 'start_time', 'end_time'
    ];
    
    /**
     * Dias da semana
     */
    public const DAYS_OF_WEEK = [
        1 => 'Segunda-feira',
        2 => 'Terça-feira',
        3 => 'Quarta-feira',
        4 => 'Quinta-feira',
        5 => 'Sexta-feira',
        6 => 'Sábado',
        7 => 'Domingo'
    ];
    
    /**
     * Busca horários por turma
     * 
     * @param int $teamId
     * @return array
     */
    public function findByTeam(int $teamId): array
    {
        return $this->findAll(['team_id' => $teamId, 'deleted_at' => null], 'day_of_week, class_number');
    }
    
    /**
     * Busca horários por professor
     * 
     * @param int $teacherId
     * @return array
     */
    public function findByTeacher(int $teacherId): array
    {
        return $this->findAll(['teacher_id' => $teacherId, 'deleted_at' => null], 'day_of_week, class_number');
    }
    
    /**
     * Busca horários com relacionamentos
     * 
     * @param array $conditions
     * @param string $orderBy
     * @param int|null $limit
     * @return array
     */
    public function findWithRelations(array $conditions = [], string $orderBy = 'sch.day_of_week, sch.class_number', ?int $limit = null): array
    {
        $whereClause = ['sch.deleted_at IS NULL'];
        $params = [];
        
        foreach ($conditions as $field => $value) {
            $whereClause[] = "sch.$field = :$field";
            $params[$field] = $value;
        }
        
        $sql = "SELECT sch.*, 
                       sub.translate as subject_name,
                       u.name as teacher_name,
                       t.id as team_name
                FROM {$this->getTableName()} sch
                LEFT JOIN {$this->database->getPrefix()}school_subjects sub ON sch.subject_id = sub.id
                LEFT JOIN {$this->database->getPrefix()}users u ON sch.teacher_id = u.id
                LEFT JOIN {$this->database->getPrefix()}school_teams t ON sch.team_id = t.id
                WHERE " . implode(' AND ', $whereClause) . "
                ORDER BY $orderBy";
        
        if ($limit !== null) {
            $sql .= " LIMIT $limit";
        }
        
        $stmt = $this->database->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Verifica conflito de horário para professor
     * 
     * @param int $teacherId
     * @param int $dayOfWeek
     * @param int $classNumber
     * @param int|null $excludeId
     * @return bool
     */
    public function hasTeacherConflict(int $teacherId, int $dayOfWeek, int $classNumber, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->getTableName()} 
                WHERE teacher_id = :teacher_id 
                AND day_of_week = :day_of_week 
                AND class_number = :class_number 
                AND deleted_at IS NULL";
        
        $params = [
            'teacher_id' => $teacherId,
            'day_of_week' => $dayOfWeek,
            'class_number' => $classNumber
        ];
        
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        
        $stmt = $this->database->query($sql, $params);
        $result = $stmt->fetch();
        
        return $result['total'] > 0;
    }
    
    /**
     * Verifica conflito de horário para turma
     * 
     * @param int $teamId
     * @param int $dayOfWeek
     * @param int $classNumber
     * @param int|null $excludeId
     * @return bool
     */
    public function hasTeamConflict(int $teamId, int $dayOfWeek, int $classNumber, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->getTableName()} 
                WHERE team_id = :team_id 
                AND day_of_week = :day_of_week 
                AND class_number = :class_number 
                AND deleted_at IS NULL";
        
        $params = [
            'team_id' => $teamId,
            'day_of_week' => $dayOfWeek,
            'class_number' => $classNumber
        ];
        
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        
        $stmt = $this->database->query($sql, $params);
        $result = $stmt->fetch();
        
        return $result['total'] > 0;
    }
    
    /**
     * Gera grade de horários para uma turma
     * 
     * @param int $teamId
     * @return array
     */
    public function generateScheduleGrid(int $teamId): array
    {
        $schedules = $this->findWithRelations(['sch.team_id' => $teamId]);
        
        $grid = [];
        
        // Inicializa grid vazio
        for ($day = 1; $day <= 7; $day++) {
            for ($class = 1; $class <= 10; $class++) {
                $grid[$day][$class] = null;
            }
        }
        
        // Preenche grid com horários
        foreach ($schedules as $schedule) {
            $grid[$schedule['day_of_week']][$schedule['class_number']] = $schedule;
        }
        
        return $grid;
    }
    
    /**
     * Retorna nome do dia da semana
     * 
     * @param int $dayOfWeek
     * @return string
     */
    public function getDayName(int $dayOfWeek): string
    {
        return self::DAYS_OF_WEEK[$dayOfWeek] ?? 'Desconhecido';
    }
    
    /**
     * Soft delete do horário
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