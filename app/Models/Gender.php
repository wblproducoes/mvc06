<?php
/**
 * Model de gêneros
 * 
 * @package App\Models
 * @author Sistema Administrativo MVC
 */

namespace App\Models;

class Gender extends BaseModel
{
    protected string $table = 'genders';
    protected array $fillable = [
        'name', 'translate', 'description'
    ];
    
    /**
     * Busca gênero por nome
     * 
     * @param string $name
     * @return array|null
     */
    public function findByName(string $name): ?array
    {
        return $this->findOne(['name' => $name, 'deleted_at' => null]);
    }
    
    /**
     * Busca todos os gêneros ativos
     * 
     * @return array
     */
    public function findAllActive(): array
    {
        return $this->findAll(['deleted_at' => null], 'translate ASC');
    }
    
    /**
     * Soft delete do gênero
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