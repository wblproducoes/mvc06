#!/usr/bin/env php
<?php
/**
 * Script para criar usuário administrador
 * 
 * @package Cli
 * @author Sistema Administrativo MVC
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Models\Usuario;

// Carrega variáveis de ambiente
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

echo "=== Criar Usuário Administrador ===\n\n";

// Coleta dados do usuário
echo "Nome completo: ";
$nome = trim(fgets(STDIN));

echo "Email: ";
$email = trim(fgets(STDIN));

echo "Senha: ";
$senha = trim(fgets(STDIN));

echo "Confirmar senha: ";
$confirmarSenha = trim(fgets(STDIN));

// Validações
if (empty($nome) || empty($email) || empty($senha)) {
    echo "❌ Todos os campos são obrigatórios!\n";
    exit(1);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "❌ Email inválido!\n";
    exit(1);
}

if (strlen($senha) < 8) {
    echo "❌ A senha deve ter pelo menos 8 caracteres!\n";
    exit(1);
}

if ($senha !== $confirmarSenha) {
    echo "❌ As senhas não coincidem!\n";
    exit(1);
}

try {
    $usuarioModel = new Usuario();
    
    // Verifica se o email já existe
    if ($usuarioModel->emailExists($email)) {
        echo "❌ Este email já está em uso!\n";
        exit(1);
    }
    
    // Cria o usuário
    $userId = $usuarioModel->create([
        'nome' => $nome,
        'email' => $email,
        'senha' => password_hash($senha, PASSWORD_BCRYPT),
        'role' => 'admin',
        'ativo' => 1
    ]);
    
    echo "\n✓ Usuário administrador criado com sucesso!\n";
    echo "ID: $userId\n";
    echo "Nome: $nome\n";
    echo "Email: $email\n";
    echo "Role: admin\n\n";
    
} catch (Exception $e) {
    echo "❌ Erro ao criar usuário: " . $e->getMessage() . "\n";
    exit(1);
}