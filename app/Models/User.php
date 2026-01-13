<?php
/**
 * Model de usuários (nova estrutura)
 * 
 * @package App\Models
 * @author Sistema Administrativo MVC
 */

namespace App\Models;

class User extends BaseModel
{
    protected string $table = 'users';
    protected array $fillable = [
        'name', 'alias', 'email', 'cpf', 'birth_date', 'gender_id',
        'phone_home', 'phone_mobile', 'phone_message', 'photo',
        'username', 'password', 'google_access_token', 'google_refresh_token',
        'google_token_expires', 'google_calendar_id', 'message_signature',
        'signature_include_logo', 'permissions_updated_at', 'unique_code',
        'session_token', 'last_access', 'password_reset_token',
        'password_reset_expires', 'level_id', 'status_id', 'register_id'
    ];
    
    /**
     * Busca usuário por email
     * 
     * @param string $email
     * @return array|null
     */
    public function findByEmail(string $email): ?array
    {
        return $this->findOne(['email' => $email, 'deleted_at' => null]);
    }
    
    /**
     * Busca usuário por username
     * 
     * @param string $username
     * @return array|null
     */
    public function findByUsername(string $username): ?array
    {
        return $this->findOne(['username' => $username, 'deleted_at' => null]);
    }
    
    /**
     * Busca usuário por código único
     * 
     * @param string $uniqueCode
     * @return array|null
     */
    public function findByUniqueCode(string $uniqueCode): ?array
    {
        return $this->findOne(['unique_code' => $uniqueCode, 'deleted_at' => null]);
    }
    
    /**
     * Busca usuário por CPF
     * 
     * @param string $cpf
     * @return array|null
     */
    public function findByCpf(string $cpf): ?array
    {
        return $this->findOne(['cpf' => $cpf, 'deleted_at' => null]);
    }
    
    /**
     * Atualiza último acesso do usuário
     * 
     * @param int $userId
     * @return bool
     */
    public function updateLastAccess(int $userId): bool
    {
        $sql = "UPDATE {$this->getTableName()} SET last_access = NOW() WHERE id = :id";
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
        
        $sql = "UPDATE {$this->getTableName()} 
                SET password_reset_token = :token, password_reset_expires = :expires_at 
                WHERE id = :user_id";
        
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
        $sql = "SELECT COUNT(*) as total FROM {$this->getTableName()} 
                WHERE password_reset_token = :token 
                AND password_reset_expires > NOW() 
                AND deleted_at IS NULL";
        
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
        $sql = "UPDATE {$this->getTableName()} 
                SET password = :password, 
                    password_reset_token = NULL, 
                    password_reset_expires = NULL,
                    dh_update = NOW()
                WHERE password_reset_token = :token 
                AND password_reset_expires > NOW() 
                AND deleted_at IS NULL";
        
        $stmt = $this->database->query($sql, [
            'password' => $hashedPassword,
            'token' => $token
        ]);
        
        return $stmt->rowCount() > 0;
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
        $sql = "SELECT COUNT(*) as total FROM {$this->getTableName()} 
                WHERE email = :email AND deleted_at IS NULL";
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
     * Verifica se o username já existe
     * 
     * @param string $username
     * @param int|null $excludeId
     * @return bool
     */
    public function usernameExists(string $username, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->getTableName()} 
                WHERE username = :username AND deleted_at IS NULL";
        $params = ['username' => $username];
        
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
    public function findActive(string $orderBy = 'name ASC', ?int $limit = null): array
    {
        return $this->findAll(['status_id' => 1, 'deleted_at' => null], $orderBy, $limit);
    }
    
    /**
     * Busca usuários por nível
     * 
     * @param int $levelId
     * @return array
     */
    public function findByLevel(int $levelId): array
    {
        return $this->findAll(['level_id' => $levelId, 'status_id' => 1, 'deleted_at' => null]);
    }
    
    /**
     * Busca usuários com relacionamentos
     * 
     * @param array $conditions
     * @param string $orderBy
     * @param int|null $limit
     * @return array
     */
    public function findWithRelations(array $conditions = [], string $orderBy = 'u.name ASC', ?int $limit = null): array
    {
        $whereClause = ['u.deleted_at IS NULL'];
        $params = [];
        
        foreach ($conditions as $field => $value) {
            $whereClause[] = "u.$field = :$field";
            $params[$field] = $value;
        }
        
        $sql = "SELECT u.*, 
                       g.translate as gender_name,
                       l.translate as level_name,
                       s.translate as status_name,
                       s.color as status_color
                FROM {$this->getTableName()} u
                LEFT JOIN {$this->database->getPrefix()}genders g ON u.gender_id = g.id
                LEFT JOIN {$this->database->getPrefix()}levels l ON u.level_id = l.id
                LEFT JOIN {$this->database->getPrefix()}status s ON u.status_id = s.id
                WHERE " . implode(' AND ', $whereClause) . "
                ORDER BY $orderBy";
        
        if ($limit !== null) {
            $sql .= " LIMIT $limit";
        }
        
        $stmt = $this->database->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Gera código único para usuário
     * 
     * @return string
     */
    public function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(substr(md5(uniqid()), 0, 8));
        } while ($this->findByUniqueCode($code));
        
        return $code;
    }
    
    /**
     * Soft delete do usuário
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
    
    /**
     * Restaura usuário deletado
     * 
     * @param int $id
     * @return bool
     */
    public function restore(int $id): bool
    {
        $sql = "UPDATE {$this->getTableName()} 
                SET deleted_at = NULL, dh_update = NOW() 
                WHERE id = :id";
        
        $stmt = $this->database->query($sql, ['id' => $id]);
        return $stmt->rowCount() > 0;
    }
}