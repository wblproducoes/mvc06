#!/usr/bin/env php
<?php
/**
 * Script para gerenciamento de versões
 * 
 * @package Cli
 * @author Sistema Administrativo MVC
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Version;

$command = $argv[1] ?? 'show';

switch ($command) {
    case 'show':
    case 'current':
        showCurrentVersion();
        break;
        
    case 'bump':
        $type = $argv[2] ?? 'patch';
        bumpVersion($type);
        break;
        
    case 'set':
        $newVersion = $argv[2] ?? null;
        if ($newVersion) {
            setVersion($newVersion);
        } else {
            echo "❌ Uso: php cli/version.php set <versao>\n";
            exit(1);
        }
        break;
        
    case 'changelog':
        generateChangelog();
        break;
        
    case 'help':
    default:
        showHelp();
        break;
}

/**
 * Mostra a versão atual
 */
function showCurrentVersion(): void
{
    echo "=== Informações da Versão ===\n\n";
    echo Version::getDisplayInfo() . "\n\n";
    
    $data = Version::getAll();
    
    echo "Versão: " . $data['version'] . "\n";
    echo "Build: " . $data['build'] . "\n";
    echo "Data de Lançamento: " . date('d/m/Y', strtotime($data['release_date'])) . "\n";
    echo "Codinome: " . $data['codename'] . "\n";
    echo "PHP Mínimo: " . $data['php_min'] . "\n";
    echo "Estabilidade: " . $data['stability'] . "\n";
}

/**
 * Incrementa a versão
 */
function bumpVersion(string $type): void
{
    $currentVersion = Version::get();
    $parts = explode('.', $currentVersion);
    
    if (count($parts) !== 3) {
        echo "❌ Formato de versão inválido: $currentVersion\n";
        exit(1);
    }
    
    [$major, $minor, $patch] = array_map('intval', $parts);
    
    switch ($type) {
        case 'major':
            $major++;
            $minor = 0;
            $patch = 0;
            break;
            
        case 'minor':
            $minor++;
            $patch = 0;
            break;
            
        case 'patch':
        default:
            $patch++;
            break;
    }
    
    $newVersion = "$major.$minor.$patch";
    setVersion($newVersion);
    
    echo "✓ Versão incrementada de $currentVersion para $newVersion\n";
}

/**
 * Define uma nova versão
 */
function setVersion(string $newVersion): void
{
    if (!preg_match('/^\d+\.\d+\.\d+$/', $newVersion)) {
        echo "❌ Formato de versão inválido. Use: major.minor.patch (ex: 1.2.3)\n";
        exit(1);
    }
    
    $versionFile = __DIR__ . '/../version.json';
    $data = [];
    
    if (file_exists($versionFile)) {
        $content = file_get_contents($versionFile);
        $data = json_decode($content, true) ?? [];
    }
    
    // Atualiza dados
    $data['version'] = $newVersion;
    $data['build'] = date('YmdHis');
    $data['release_date'] = date('Y-m-d');
    
    // Salva arquivo
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    file_put_contents($versionFile, $json);
    
    // Atualiza composer.json
    updateComposerVersion($newVersion);
    
    echo "✓ Versão definida para: $newVersion\n";
}

/**
 * Atualiza versão no composer.json
 */
function updateComposerVersion(string $version): void
{
    $composerFile = __DIR__ . '/../composer.json';
    
    if (file_exists($composerFile)) {
        $content = file_get_contents($composerFile);
        $data = json_decode($content, true);
        
        if ($data) {
            $data['version'] = $version;
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            file_put_contents($composerFile, $json);
            echo "✓ composer.json atualizado\n";
        }
    }
}

/**
 * Gera entrada no changelog
 */
function generateChangelog(): void
{
    $version = Version::get();
    $date = date('Y-m-d');
    
    echo "=== Gerador de Changelog ===\n\n";
    echo "Versão: $version\n";
    echo "Data: $date\n\n";
    
    echo "Digite as mudanças (uma por linha, termine com linha vazia):\n";
    
    $changes = [];
    while (true) {
        $line = trim(fgets(STDIN));
        if (empty($line)) break;
        $changes[] = $line;
    }
    
    if (empty($changes)) {
        echo "❌ Nenhuma mudança informada\n";
        exit(1);
    }
    
    // Gera entrada do changelog
    $entry = "\n## [$version] - $date\n\n";
    $entry .= "### ✨ Adicionado\n";
    
    foreach ($changes as $change) {
        $entry .= "- $change\n";
    }
    
    echo "\n=== Entrada do Changelog ===\n";
    echo $entry;
    
    echo "\nAdicionar ao CHANGELOG.md? (s/n): ";
    $confirm = trim(fgets(STDIN));
    
    if (strtolower($confirm) === 's') {
        $changelogFile = __DIR__ . '/../CHANGELOG.md';
        $content = file_get_contents($changelogFile);
        
        // Insere após a linha "## [Não Lançado]"
        $content = str_replace(
            "## [Não Lançado]\n\n### Planejado",
            "## [Não Lançado]\n\n### Planejado" . $entry,
            $content
        );
        
        file_put_contents($changelogFile, $content);
        echo "✓ Changelog atualizado\n";
    }
}

/**
 * Mostra ajuda
 */
function showHelp(): void
{
    echo "=== Gerenciador de Versões ===\n\n";
    echo "Uso: php cli/version.php <comando> [argumentos]\n\n";
    echo "Comandos disponíveis:\n";
    echo "  show              Mostra a versão atual\n";
    echo "  bump <tipo>       Incrementa a versão (major|minor|patch)\n";
    echo "  set <versao>      Define uma versão específica\n";
    echo "  changelog         Gera entrada no changelog\n";
    echo "  help              Mostra esta ajuda\n\n";
    echo "Exemplos:\n";
    echo "  php cli/version.php show\n";
    echo "  php cli/version.php bump patch\n";
    echo "  php cli/version.php set 1.2.3\n";
    echo "  php cli/version.php changelog\n";
}