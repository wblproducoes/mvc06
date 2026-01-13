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
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
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