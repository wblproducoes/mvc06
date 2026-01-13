#!/usr/bin/env php
<?php
/**
 * Script de migração do banco de dados
 * 
 * @package Cli
 * @author Sistema Administrativo MVC
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Core\Database;

// Carrega variáveis de ambiente
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

echo "=== Sistema Administrativo MVC - Migração do Banco ===\n\n";

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $prefix = $database->getPrefix();
    
    echo "Conectado ao banco de dados: " . $_ENV['DB_NAME'] . "\n";
    echo "Prefixo das tabelas: " . $prefix . "\n\n";
    
    // Configurações SQL
    $pdo->exec("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");
    $pdo->exec("SET AUTOCOMMIT = 0");
    $pdo->exec("START TRANSACTION");
    $pdo->exec("SET time_zone = '+00:00'");
    
    echo "Executando schema do banco de dados...\n";
    
    // Lê e executa o arquivo de schema
    $schemaFile = __DIR__ . '/../database/schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("Arquivo de schema não encontrado: $schemaFile");
    }
    
    $schemaSql = file_get_contents($schemaFile);
    $schemaSql = str_replace('{prefix}', $prefix, $schemaSql);
    
    // Divide o SQL em comandos individuais
    $commands = array_filter(array_map('trim', explode(';', $schemaSql)));
    
    foreach ($commands as $command) {
        if (!empty($command) && !preg_match('/^(SET|START|COMMIT|--)/i', $command)) {
            try {
                $pdo->exec($command);
            } catch (PDOException $e) {
                // Ignora erros de foreign key se a tabela já existir
                if (strpos($e->getMessage(), 'foreign key constraint') === false) {
                    echo "Aviso: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    echo "✓ Schema criado com sucesso\n";
    
    // Lê e executa os dados iniciais
    echo "Inserindo dados iniciais...\n";
    
    $seedsFile = __DIR__ . '/../database/seeds.sql';
    if (!file_exists($seedsFile)) {
        throw new Exception("Arquivo de seeds não encontrado: $seedsFile");
    }
    
    $seedsSql = file_get_contents($seedsFile);
    $seedsSql = str_replace('{prefix}', $prefix, $seedsSql);
    
    // Divide o SQL em comandos individuais
    $seedCommands = array_filter(array_map('trim', explode(';', $seedsSql)));
    
    foreach ($seedCommands as $command) {
        if (!empty($command) && !preg_match('/^(--)/i', $command)) {
            try {
                $pdo->exec($command);
            } catch (PDOException $e) {
                echo "Aviso: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "✓ Dados iniciais inseridos\n";
    
    // Verifica se já existe usuário admin
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}users WHERE level_id = 1");
    $stmt->execute();
    $adminCount = $stmt->fetchColumn();
    
    if ($adminCount == 0) {
        echo "\nCriando usuário administrador padrão...\n";
        
        $uniqueCode = strtoupper(substr(md5(uniqid()), 0, 8));
        
        $adminData = [
            'name' => 'Administrador Master',
            'username' => 'admin',
            'email' => 'admin@sistema.com',
            'password' => password_hash('admin123', PASSWORD_BCRYPT),
            'unique_code' => $uniqueCode,
            'level_id' => 1, // Master
            'status_id' => 1, // Ativo
            'gender_id' => 1  // Masculino (padrão)
        ];
        
        $sql = "INSERT INTO {$prefix}users (name, username, email, password, unique_code, level_id, status_id, gender_id, dh) 
                VALUES (:name, :username, :email, :password, :unique_code, :level_id, :status_id, :gender_id, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($adminData);
        
        echo "✓ Usuário administrador criado\n";
        echo "  Username: admin\n";
        echo "  Email: admin@sistema.com\n";
        echo "  Senha: admin123\n";
        echo "  Código Único: $uniqueCode\n";
        echo "  ⚠️  ALTERE A SENHA APÓS O PRIMEIRO LOGIN!\n";
    }
    
    // Commit da transação
    $pdo->exec("COMMIT");
    
    echo "\n=== Migração concluída com sucesso! ===\n";
    echo "Você pode acessar o sistema em: " . $_ENV['APP_URL'] . "\n\n";
    
} catch (Exception $e) {
    echo "❌ Erro na migração: " . $e->getMessage() . "\n";
    exit(1);
}