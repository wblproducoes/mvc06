<?php
/**
 * Model de usuários
 * 
 * @package App\Models
 * @author Sistema Administrativo MVC
 */

namespace App\Models;

class Usuario extends BaseModel
{
    protected string $table = 'usuarios';
    protected array $fillable = [
        'nome', 'email', 'senha', 'role', 'ativo', 'avatar'
    ];
    
    /**
     * Busca usuário por email
     * 
     * @param string $email
     * @return array|null
     */
    public function findByEmail(string $email): ?array
    {
        return $this->findOne(['email' => $email]);
    }
    
    /**
     * Atualiza último acesso do usuário
     * 
     * @param int $userId
     * @return bool
     */
    public function updateLastAccess(int $userId): bool
    {
        $sql = "UPDATE {$this->getTableName()} SET ultimo_acesso = NOW() WHERE id = :id";
        $stmt = $this->database->query($sql, ['id' => $userId]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Cria token de reset de senha
     * 
     * @param int $userId
     * @param string $token
     * @return bool
     */
    public function createPasswordResetToken(int $userId, string $token): bool
    {
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $sql = "INSERT INTO {$this->database->getPrefix()}password_resets 
                (user_id, token, expires_at, created_at) 
                VALUES (:user_id, :token, :expires_at, NOW())
                ON DUPLICATE KEY UPDATE 
                token = :token, expires_at = :expires_at, created_at = NOW()";
        
        $stmt = $this->database->query($sql, [
            'user_id' => $userId,
            'token' => $token,
            'expires_at' => $expiresAt
        ]);
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Verifica se o token de reset é válido
     * 
     * @param string $token
     * @return bool
     */
    public function isValidResetToken(string $token): bool
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->database->getPrefix()}password_resets 
                WHERE token = :token AND expires_at > NOW()";
        
        $stmt = $this->database->query($sql, ['token' => $token]);
        $result = $stmt->fetch();
        
        return $result['total'] > 0;
    }
    
    /**
     * Atualiza senha por token de reset
     * 
     * @param string $token
     * @param string $hashedPassword
     * @return bool
     */
    public function updatePasswordByToken(string $token, string $hashedPassword): bool
    {
        // Busca o usuário pelo token
        $sql = "SELECT user_id FROM {$this->database->getPrefix()}password_resets 
                WHERE token = :token AND expires_at > NOW()";
        
        $stmt = $this->database->query($sql, ['token' => $token]);
        $reset = $stmt->fetch();
        
        if (!$reset) {
            return false;
        }
        
        // Inicia transação
        $this->database->beginTransaction();
        
        try {
            // Atualiza a senha
            $sql = "UPDATE {$this->getTableName()} SET senha = :senha, updated_at = NOW() 
                    WHERE id = :id";
            $this->database->query($sql, [
                'senha' => $hashedPassword,
                'id' => $reset['user_id']
            ]);
            
            // Remove o token usado
            $sql = "DELETE FROM {$this->database->getPrefix()}password_resets WHERE token = :token";
            $this->database->query($sql, ['token' => $token]);
            
            $this->database->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->database->rollback();
            return false;
        }
    }
    
    /**
     * Verifica se o email já existe
     * 
     * @param string $email
     * @param int|null $excludeId
     * @return bool
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->getTableName()} WHERE email = :email";
        $params = ['email' => $email];
        
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        
        $stmt = $this->database->query($sql, $params);
        $result = $stmt->fetch();
        
        return $result['total'] > 0;
    }
    
    /**
     * Busca usuários ativos
     * 
     * @param string $orderBy
     * @param int|null $limit
     * @return array
     */
    public function findActive(string $orderBy = 'nome ASC', ?int $limit = null): array
    {
        return $this->findAll(['ativo' => 1], $orderBy, $limit);
    }
    
    /**
     * Busca usuários por role
     * 
     * @param string $role
     * @return array
     */
    public function findByRole(string $role): array
    {
        return $this->findAll(['role' => $role, 'ativo' => 1]);
    }
}