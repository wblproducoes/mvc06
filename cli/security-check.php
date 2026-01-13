#!/usr/bin/env php
<?php
/**
 * Script de verificação de segurança
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

echo "=== Verificação de Segurança do Sistema ===\n\n";

$issues = [];
$warnings = [];
$passed = [];

// Verifica configurações PHP
echo "1. Verificando configurações PHP...\n";

// Verifica se display_errors está desabilitado em produção
if ($_ENV['APP_ENV'] === 'production' && ini_get('display_errors')) {
    $issues[] = "display_errors deve estar desabilitado em produção";
} else {
    $passed[] = "display_errors configurado corretamente";
}

// Verifica se expose_php está desabilitado
if (ini_get('expose_php')) {
    $warnings[] = "expose_php deveria estar desabilitado";
} else {
    $passed[] = "expose_php desabilitado";
}

// Verifica configurações de sessão
if (!ini_get('session.cookie_httponly')) {
    $issues[] = "session.cookie_httponly deve estar habilitado";
} else {
    $passed[] = "session.cookie_httponly habilitado";
}

if ($_ENV['APP_ENV'] === 'production' && !ini_get('session.cookie_secure')) {
    $issues[] = "session.cookie_secure deve estar habilitado em produção";
} else {
    $passed[] = "session.cookie_secure configurado corretamente";
}

// Verifica extensões de segurança
$requiredExtensions = ['openssl', 'hash', 'filter'];
foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $issues[] = "Extensão PHP '$ext' não está carregada";
    } else {
        $passed[] = "Extensão '$ext' carregada";
    }
}

echo "2. Verificando arquivos e permissões...\n";

// Verifica se .env existe e não é acessível via web
$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) {
    $issues[] = "Arquivo .env não encontrado";
} else {
    $passed[] = "Arquivo .env encontrado";
    
    // Verifica permissões do .env
    $perms = fileperms($envFile) & 0777;
    if ($perms > 0600) {
        $warnings[] = "Permissões do .env muito abertas (recomendado: 600)";
    } else {
        $passed[] = "Permissões do .env adequadas";
    }
}

// Verifica se storage é gravável
$storageDir = __DIR__ . '/../storage';
if (!is_writable($storageDir)) {
    $issues[] = "Diretório storage não é gravável";
} else {
    $passed[] = "Diretório storage é gravável";
}

// Verifica se vendor não é acessível via web
$htaccessVendor = __DIR__ . '/../vendor/.htaccess';
if (!file_exists($htaccessVendor)) {
    $warnings[] = "Arquivo .htaccess não encontrado em vendor/";
} else {
    $passed[] = "Diretório vendor protegido";
}

echo "3. Verificando configurações de banco de dados...\n";

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $passed[] = "Conexão com banco de dados estabelecida";
    
    // Verifica se usuário do banco tem privilégios mínimos
    $stmt = $pdo->query("SHOW GRANTS");
    $grants = $stmt->fetchAll();
    
    $hasAllPrivileges = false;
    foreach ($grants as $grant) {
        if (stripos($grant['Grants for ' . $_ENV['DB_USERNAME'] . '@%'] ?? '', 'ALL PRIVILEGES') !== false) {
            $hasAllPrivileges = true;
            break;
        }
    }
    
    if ($hasAllPrivileges) {
        $warnings[] = "Usuário do banco tem ALL PRIVILEGES (recomendado: privilégios mínimos)";
    } else {
        $passed[] = "Usuário do banco com privilégios adequados";
    }
    
} catch (Exception $e) {
    $issues[] = "Erro na conexão com banco: " . $e->getMessage();
}

echo "4. Verificando configurações de segurança...\n";

// Verifica se APP_KEY está definida
if (empty($_ENV['APP_KEY']) || $_ENV['APP_KEY'] === 'base64:exemplo_chave_32_caracteres_aqui') {
    $issues[] = "APP_KEY não está definida ou usa valor padrão";
} else {
    $passed[] = "APP_KEY definida";
}

// Verifica se HTTPS está habilitado em produção
if ($_ENV['APP_ENV'] === 'production' && !isset($_SERVER['HTTPS'])) {
    $warnings[] = "HTTPS não detectado em produção";
} else {
    $passed[] = "HTTPS configurado adequadamente";
}

// Verifica configurações de email
if (empty($_ENV['MAIL_HOST']) || empty($_ENV['MAIL_USERNAME'])) {
    $warnings[] = "Configurações de email não estão completas";
} else {
    $passed[] = "Configurações de email definidas";
}

echo "5. Verificando logs de segurança...\n";

$securityLogFile = __DIR__ . '/../storage/logs/security.log';
if (file_exists($securityLogFile)) {
    $logSize = filesize($securityLogFile);
    if ($logSize > 10 * 1024 * 1024) { // 10MB
        $warnings[] = "Log de segurança muito grande ($logSize bytes)";
    } else {
        $passed[] = "Log de segurança em tamanho adequado";
    }
} else {
    $passed[] = "Log de segurança ainda não criado";
}

// Verifica senhas padrão
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM {$database->getPrefix()}users WHERE username = 'admin' AND password = ?");
    $defaultPasswordHash = password_hash('admin123', PASSWORD_BCRYPT);
    
    // Como não podemos verificar hash exato, verificamos se existe usuário admin
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM {$database->getPrefix()}users WHERE username = 'admin'");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        $warnings[] = "Usuário 'admin' encontrado - altere a senha padrão se ainda estiver em uso";
    } else {
        $passed[] = "Nenhum usuário 'admin' padrão encontrado";
    }
} catch (Exception $e) {
    $warnings[] = "Não foi possível verificar usuários padrão";
}

// Relatório final
echo "\n=== RELATÓRIO DE SEGURANÇA ===\n\n";

if (!empty($issues)) {
    echo "🔴 PROBLEMAS CRÍTICOS (" . count($issues) . "):\n";
    foreach ($issues as $issue) {
        echo "  ❌ $issue\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "🟡 AVISOS (" . count($warnings) . "):\n";
    foreach ($warnings as $warning) {
        echo "  ⚠️  $warning\n";
    }
    echo "\n";
}

echo "🟢 VERIFICAÇÕES APROVADAS (" . count($passed) . "):\n";
foreach ($passed as $pass) {
    echo "  ✅ $pass\n";
}

echo "\n=== RESUMO ===\n";
echo "Críticos: " . count($issues) . "\n";
echo "Avisos: " . count($warnings) . "\n";
echo "Aprovados: " . count($passed) . "\n";

$score = (count($passed) / (count($passed) + count($warnings) + count($issues))) * 100;
echo "Score de Segurança: " . round($score, 1) . "%\n\n";

if (count($issues) > 0) {
    echo "⚠️  AÇÃO NECESSÁRIA: Corrija os problemas críticos antes de usar em produção!\n";
    exit(1);
} elseif (count($warnings) > 0) {
    echo "ℹ️  Considere corrigir os avisos para melhorar a segurança.\n";
    exit(0);
} else {
    echo "✅ Sistema aprovado na verificação de segurança!\n";
    exit(0);
}