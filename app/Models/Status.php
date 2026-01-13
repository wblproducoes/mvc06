<?php
/**
 * Model de status
 * 
 * @package App\Models
 * @author Sistema Administrativo MVC
 */

namespace App\Models;

class Status extends BaseModel
{
    protected string $table = 'status';
    protected array $fillable = [
        'name', 'translate', 'color', 'description'
    ];
    
    /**
     * Busca status por nome
     * 
     * @param string $name
     * @return array|null
     */
    public function findByName(string $name): ?array
    {
        return $this->findOne(['name' => $name, 'deleted_at' => null]);
    }
    
    /**
     * Busca todos os status ativos
     * 
     * @return array
     */
    public function findAllActive(): array
    {
        return $this->findAll(['deleted_at' => null], 'translate ASC');
    }
    
    /**
     * Retorna badge HTML do status
     * 
     * @param array $status
     * @return string
     */
    public function getBadge(array $status): string
    {
        $color = $status['color'] ?? 'secondary';
        $translate = $status['translate'] ?? $status['name'] ?? 'Desconhecido';
        
        return "<span class=\"badge bg-{$color}\">{$translate}</span>";
    }
    
    /**
     * Verifica se é status ativo
     * 
     * @param int $statusId
     * @return bool
     */
    public function isActive(int $statusId): bool
    {
        return $statusId === 1;
    }
    
    /**
     * Verifica se é status inativo
     * 
     * @param int $statusId
     * @return bool
     */
    public function isInactive(int $statusId): bool
    {
        return $statusId === 2;
    }
    
    /**
     * Verifica se é status bloqueado
     * 
     * @param int $statusId
     * @return bool
     */
    public function isBlocked(int $statusId): bool
    {
        return $statusId === 3;
    }
    
    /**
     * Soft delete do status
     * 
     * @param int $id
     * @return bool
     */
    public function softDelete(int $id): bool
    {
        // Não permite deletar status básicos do sistema
        if ($id <= 8) {
            return false;
        }
        
        $sql = "UPDATE {$this->getTableName()} 
                SET deleted_at = NOW(), dh_update = NOW() 
                WHERE id = :id";
        
        $stmt = $this->database->query($sql, ['id' => $id]);
        return $stmt->rowCount() > 0;
    }
}