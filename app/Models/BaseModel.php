<?php
/**
 * Model base com funcionalidades comuns
 * 
 * @package App\Models
 * @author Sistema Administrativo MVC
 */

namespace App\Models;

use App\Core\Database;
use PDO;

abstract class BaseModel
{
    protected Database $database;
    protected string $table;
    protected array $fillable = [];
    protected string $primaryKey = 'id';
    
    /**
     * Construtor do model base
     */
    public function __construct()
    {
        $this->database = new Database();
    }
    
    /**
     * Retorna o nome completo da tabela com prefixo
     * 
     * @return string
     */
    protected function getTableName(): string
    {
        return $this->database->getPrefix() . $this->table;
    }
    
    /**
     * Busca todos os registros
     * 
     * @param array $conditions
     * @param string $orderBy
     * @param int|null $limit
     * @param int $offset
     * @return array
     */
    public function findAll(array $conditions = [], string $orderBy = '', ?int $limit = null, int $offset = 0): array
    {
        $sql = "SELECT * FROM {$this->getTableName()}";
        $params = [];
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $field => $value) {
                $whereClause[] = "$field = :$field";
                $params[$field] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $whereClause);
        }
        
        if (!empty($orderBy)) {
            $sql .= " ORDER BY $orderBy";
        }
        
        if ($limit !== null) {
            $sql .= " LIMIT $limit OFFSET $offset";
        }
        
        $stmt = $this->database->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Busca um registro por ID
     * 
     * @param mixed $id
     * @return array|null
     */
    public function findById($id): ?array
    {
        $sql = "SELECT * FROM {$this->getTableName()} WHERE {$this->primaryKey} = :id";
        $stmt = $this->database->query($sql, ['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Busca um registro por condições
     * 
     * @param array $conditions
     * @return array|null
     */
    public function findOne(array $conditions): ?array
    {
        $whereClause = [];
        $params = [];
        
        foreach ($conditions as $field => $value) {
            $whereClause[] = "$field = :$field";
            $params[$field] = $value;
        }
        
        $sql = "SELECT * FROM {$this->getTableName()} WHERE " . implode(' AND ', $whereClause);
        $stmt = $this->database->query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Cria um novo registro
     * 
     * @param array $data
     * @return string
     */
    public function create(array $data): string
    {
        $data = $this->filterFillable($data);
        $data['created_at'] = date('Y-m-d H:i:s');
        
        $fields = array_keys($data);
        $placeholders = array_map(fn($field) => ":$field", $fields);
        
        $sql = "INSERT INTO {$this->getTableName()} (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        $this->database->query($sql, $data);
        return $this->database->lastInsertId();
    }
    
    /**
     * Atualiza um registro
     * 
     * @param mixed $id
     * @param array $data
     * @return bool
     */
    public function update($id, array $data): bool
    {
        $data = $this->filterFillable($data);
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        $setClause = [];
        foreach (array_keys($data) as $field) {
            $setClause[] = "$field = :$field";
        }
        
        $data['id'] = $id;
        
        $sql = "UPDATE {$this->getTableName()} SET " . implode(', ', $setClause) . 
               " WHERE {$this->primaryKey} = :id";
        
        $stmt = $this->database->query($sql, $data);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Deleta um registro
     * 
     * @param mixed $id
     * @return bool
     */
    public function delete($id): bool
    {
        $sql = "DELETE FROM {$this->getTableName()} WHERE {$this->primaryKey} = :id";
        $stmt = $this->database->query($sql, ['id' => $id]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Conta registros
     * 
     * @param array $conditions
     * @return int
     */
    public function count(array $conditions = []): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->getTableName()}";
        $params = [];
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $field => $value) {
                $whereClause[] = "$field = :$field";
                $params[$field] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $whereClause);
        }
        
        $stmt = $this->database->query($sql, $params);
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }
    
    /**
     * Filtra dados pelos campos fillable
     * 
     * @param array $data
     * @return array
     */
    protected function filterFillable(array $data): array
    {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($this->fillable));
    }
    
    /**
     * Executa uma query personalizada
     * 
     * @param string $sql
     * @param array $params
     * @return \PDOStatement
     */
    protected function query(string $sql, array $params = []): \PDOStatement
    {
        return $this->database->query($sql, $params);
    }
}