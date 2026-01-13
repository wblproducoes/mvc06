<?php
/**
 * Model de níveis de acesso
 * 
 * @package App\Models
 * @author Sistema Administrativo MVC
 */

namespace App\Models;

class Level extends BaseModel
{
    protected string $table = 'levels';
    protected array $fillable = [
        'name', 'translate', 'description'
    ];
    
    /**
     * Busca nível por nome
     * 
     * @param string $name
     * @return array|null
     */
    public function findByName(string $name): ?array
    {
        return $this->findOne(['name' => $name, 'deleted_at' => null]);
    }
    
    /**
     * Busca todos os níveis ativos
     * 
     * @return array
     */
    public function findAllActive(): array
    {
        return $this->findAll(['deleted_at' => null], 'id ASC');
    }
    
    /**
     * Verifica se é nível administrativo
     * 
     * @param int $levelId
     * @return bool
     */
    public function isAdmin(int $levelId): bool
    {
        return in_array($levelId, [1, 2, 3]); // master, admin, direction
    }
    
    /**
     * Verifica se é nível de professor
     * 
     * @param int $levelId
     * @return bool
     */
    public function isTeacher(int $levelId): bool
    {
        return $levelId === 7;
    }
    
    /**
     * Verifica se é nível de aluno
     * 
     * @param int $levelId
     * @return bool
     */
    public function isStudent(int $levelId): bool
    {
        return $levelId === 9;
    }
    
    /**
     * Soft delete do nível
     * 
     * @param int $id
     * @return bool
     */
    public function softDelete(int $id): bool
    {
        // Não permite deletar níveis básicos do sistema
        if ($id <= 11) {
            return false;
        }
        
        $sql = "UPDATE {$this->getTableName()} 
                SET deleted_at = NOW(), dh_update = NOW() 
                WHERE id = :id";
        
        $stmt = $this->database->query($sql, ['id' => $id]);
        return $stmt->rowCount() > 0;
    }
}