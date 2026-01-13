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
    
    // Tabela de usuários
    echo "Criando tabela de usuários...\n";
    $sql = "CREATE TABLE IF NOT EXISTS {$prefix}usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        senha VARCHAR(255) NOT NULL,
        role ENUM('admin', 'manager', 'user') DEFAULT 'user',
        ativo BOOLEAN DEFAULT TRUE,
        avatar VARCHAR(255) NULL,
        ultimo_acesso DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_role (role),
        INDEX idx_ativo (ativo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✓ Tabela de usuários criada\n";
    
    // Tabela de reset de senhas
    echo "Criando tabela de reset de senhas...\n";
    $sql = "CREATE TABLE IF NOT EXISTS {$prefix}password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token VARCHAR(255) NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES {$prefix}usuarios(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user (user_id),
        INDEX idx_token (token),
        INDEX idx_expires (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✓ Tabela de reset de senhas criada\n";
    
    // Tabela de vendas (exemplo)
    echo "Criando tabela de vendas...\n";
    $sql = "CREATE TABLE IF NOT EXISTS {$prefix}vendas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        produto VARCHAR(100) NOT NULL,
        valor DECIMAL(10,2) NOT NULL,
        quantidade INT DEFAULT 1,
        user_id INT NOT NULL,
        data_venda DATETIME DEFAULT CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES {$prefix}usuarios(id),
        INDEX idx_data_venda (data_venda),
        INDEX idx_user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✓ Tabela de vendas criada\n";
    
    // Tabela de logs do sistema
    echo "Criando tabela de logs...\n";
    $sql = "CREATE TABLE IF NOT EXISTS {$prefix}logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        action VARCHAR(100) NOT NULL,
        description TEXT NULL,
        ip_address VARCHAR(45) NULL,
        user_agent TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES {$prefix}usuarios(id) ON DELETE SET NULL,
        INDEX idx_user_id (user_id),
        INDEX idx_action (action),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✓ Tabela de logs criada\n";
    
    // Verifica se já existe usuário admin
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}usuarios WHERE role = 'admin'");
    $stmt->execute();
    $adminCount = $stmt->fetchColumn();
    
    if ($adminCount == 0) {
        echo "\nCriando usuário administrador padrão...\n";
        
        $adminData = [
            'nome' => 'Administrador',
            'email' => 'admin@sistema.com',
            'senha' => password_hash('admin123', PASSWORD_BCRYPT),
            'role' => 'admin',
            'ativo' => 1
        ];
        
        $sql = "INSERT INTO {$prefix}usuarios (nome, email, senha, role, ativo, created_at) 
                VALUES (:nome, :email, :senha, :role, :ativo, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($adminData);
        
        echo "✓ Usuário administrador criado\n";
        echo "  Email: admin@sistema.com\n";
        echo "  Senha: admin123\n";
        echo "  ⚠️  ALTERE A SENHA APÓS O PRIMEIRO LOGIN!\n";
    }
    
    // Dados de exemplo para vendas
    echo "\nInserindo dados de exemplo...\n";
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}vendas");
    $stmt->execute();
    $vendasCount = $stmt->fetchColumn();
    
    if ($vendasCount == 0) {
        $vendas = [
            ['produto' => 'Produto A', 'valor' => 150.00, 'quantidade' => 2],
            ['produto' => 'Produto B', 'valor' => 89.90, 'quantidade' => 1],
            ['produto' => 'Produto C', 'valor' => 299.99, 'quantidade' => 1],
            ['produto' => 'Produto D', 'valor' => 45.50, 'quantidade' => 3],
            ['produto' => 'Produto E', 'valor' => 199.00, 'quantidade' => 1]
        ];
        
        $adminId = $pdo->lastInsertId() ?: 1;
        
        foreach ($vendas as $venda) {
            $sql = "INSERT INTO {$prefix}vendas (produto, valor, quantidade, user_id, data_venda) 
                    VALUES (:produto, :valor, :quantidade, :user_id, DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 30) DAY))";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'produto' => $venda['produto'],
                'valor' => $venda['valor'],
                'quantidade' => $venda['quantidade'],
                'user_id' => $adminId
            ]);
        }
        
        echo "✓ Dados de exemplo inseridos\n";
    }
    
    echo "\n=== Migração concluída com sucesso! ===\n";
    echo "Você pode acessar o sistema em: " . $_ENV['APP_URL'] . "\n\n";
    
} catch (Exception $e) {
    echo "❌ Erro na migração: " . $e->getMessage() . "\n";
    exit(1);
}