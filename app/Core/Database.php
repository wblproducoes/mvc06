<?php
/**
 * Classe de conexão e gerenciamento do banco de dados
 * 
 * @package App\Core
 * @author Sistema Administrativo MVC
 */

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;
    private string $host;
    private string $port;
    private string $database;
    private string $username;
    private string $password;
    private string $prefix;
    
    /**
     * Construtor da classe Database
     */
    public function __construct()
    {
        $this->host = $_ENV['DB_HOST'];
        $this->port = $_ENV['DB_PORT'];
        $this->database = $_ENV['DB_NAME'];
        $this->username = $_ENV['DB_USERNAME'];
        $this->password = $_ENV['DB_PASSWORD'];
        $this->prefix = $_ENV['DB_PREFIX'];
    }
    
    /**
     * Retorna a conexão PDO (Singleton)
     * 
     * @return PDO
     * @throws PDOException
     */
    public function getConnection(): PDO
    {
        if (self::$connection === null) {
            try {
                $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->database};charset=utf8mb4";
                
                self::$connection = new PDO($dsn, $this->username, $this->password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]);
                
            } catch (PDOException $e) {
                throw new PDOException("Erro na conexão com o banco de dados: " . $e->getMessage());
            }
        }
        
        return self::$connection;
    }
    
    /**
     * Retorna o prefixo das tabelas
     * 
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }
    
    /**
     * Executa uma query preparada
     * 
     * @param string $sql
     * @param array $params
     * @return \PDOStatement
     */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        $startTime = microtime(true);
        
        try {
            $sql = $this->replacePrefixes($sql);
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            
            $executionTime = microtime(true) - $startTime;
            
            // Log da query se habilitado
            if (($_ENV['LOG_ENABLED'] ?? 'true') === 'true') {
                Logger::channel(Logger::CHANNEL_DATABASE)->sqlQuery($sql, $params, $executionTime);
            }
            
            return $stmt;
            
        } catch (PDOException $e) {
            $executionTime = microtime(true) - $startTime;
            
            // Log do erro
            Logger::channel(Logger::CHANNEL_DATABASE)->error('Database query failed', [
                'query' => $sql,
                'params' => $params,
                'execution_time' => $executionTime,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Executa query e retorna resultados como array
     * 
     * @param string $sql
     * @param array $params
     * @return array
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Executa query e retorna primeira linha
     * 
     * @param string $sql
     * @param array $params
     * @return array|null
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Insere dados na tabela
     * 
     * @param string $table
     * @param array $data
     * @return int
     */
    public function insert(string $table, array $data): int
    {
        $table = $this->replacePrefixes($table);
        $fields = array_keys($data);
        $placeholders = ':' . implode(', :', $fields);
        
        $sql = "INSERT INTO {$table} (" . implode(', ', $fields) . ") VALUES ({$placeholders})";
        
        $this->query($sql, $data);
        
        return (int)$this->lastInsertId();
    }
    
    /**
     * Atualiza dados na tabela
     * 
     * @param string $table
     * @param array $data
     * @param array $where
     * @return int
     */
    public function update(string $table, array $data, array $where): int
    {
        $table = $this->replacePrefixes($table);
        
        $setClause = [];
        foreach ($data as $field => $value) {
            $setClause[] = "{$field} = :{$field}";
        }
        
        $whereClause = [];
        foreach ($where as $field => $value) {
            $whereClause[] = "{$field} = :where_{$field}";
            $data["where_{$field}"] = $value;
        }
        
        $sql = "UPDATE {$table} SET " . implode(', ', $setClause) . " WHERE " . implode(' AND ', $whereClause);
        
        $stmt = $this->query($sql, $data);
        
        return $stmt->rowCount();
    }
    
    /**
     * Remove dados da tabela
     * 
     * @param string $table
     * @param array $where
     * @return int
     */
    public function delete(string $table, array $where): int
    {
        $table = $this->replacePrefixes($table);
        
        $whereClause = [];
        foreach ($where as $field => $value) {
            $whereClause[] = "{$field} = :{$field}";
        }
        
        $sql = "DELETE FROM {$table} WHERE " . implode(' AND ', $whereClause);
        
        $stmt = $this->query($sql, $where);
        
        return $stmt->rowCount();
    }
    
    /**
     * Retorna número de linhas afetadas
     * 
     * @return int
     */
    public function getAffectedRows(): int
    {
        return $this->getConnection()->query("SELECT ROW_COUNT()")->fetchColumn();
    }
    
    /**
     * Substitui prefixos nas queries
     * 
     * @param string $sql
     * @return string
     */
    private function replacePrefixes(string $sql): string
    {
        return str_replace('{prefix}', $this->prefix, $sql);
    }
    
    /**
     * Retorna o último ID inserido
     * 
     * @return string
     */
    public function lastInsertId(): string
    {
        return $this->getConnection()->lastInsertId();
    }
    
    /**
     * Inicia uma transação
     * 
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->getConnection()->beginTransaction();
    }
    
    /**
     * Confirma uma transação
     * 
     * @return bool
     */
    public function commit(): bool
    {
        return $this->getConnection()->commit();
    }
    
    /**
     * Desfaz uma transação
     * 
     * @return bool
     */
    public function rollback(): bool
    {
        return $this->getConnection()->rollback();
    }
}