#!/usr/bin/env php
<?php
/**
 * Script para automatizar releases
 * 
 * @package Cli
 * @author Sistema Administrativo MVC
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Version;

$type = $argv[1] ?? 'patch';
$message = $argv[2] ?? '';

echo "=== Automatizador de Release ===\n\n";

// Validação do tipo
if (!in_array($type, ['major', 'minor', 'patch'])) {
    echo "❌ Tipo inválido. Use: major, minor ou patch\n";
    exit(1);
}

// Versão atual
$currentVersion = Version::get();
echo "Versão atual: $currentVersion\n";

// Calcula nova versão
$parts = explode('.', $currentVersion);
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
        $patch++;
        break;
}

$newVersion = "$major.$minor.$patch";
echo "Nova versão: $newVersion\n\n";

// Confirmação
echo "Confirma o release da versão $newVersion? (s/n): ";
$confirm = trim(fgets(STDIN));

if (strtolower($confirm) !== 's') {
    echo "Release cancelado.\n";
    exit(0);
}

try {
    // 1. Atualiza versão
    echo "1. Atualizando versão...\n";
    updateVersion($newVersion);
    
    // 2. Gera changelog se não foi fornecida mensagem
    if (empty($message)) {
        echo "2. Gerando entrada no changelog...\n";
        $message = generateChangelogEntry($newVersion);
    }
    
    // 3. Commit das mudanças
    echo "3. Fazendo commit das mudanças...\n";
    gitCommit($newVersion, $message);
    
    // 4. Cria tag
    echo "4. Criando tag...\n";
    gitTag($newVersion, $message);
    
    // 5. Push (opcional)
    echo "5. Fazer push para o repositório? (s/n): ";
    $pushConfirm = trim(fgets(STDIN));
    
    if (strtolower($pushConfirm) === 's') {
        gitPush();
    }
    
    echo "\n✅ Release $newVersion criado com sucesso!\n";
    
    // Mostra próximos passos
    echo "\n📋 Próximos passos:\n";
    echo "- Verifique o changelog em CHANGELOG.md\n";
    echo "- Crie um release no GitHub se necessário\n";
    echo "- Atualize a documentação se necessário\n";
    
} catch (Exception $e) {
    echo "❌ Erro durante o release: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Atualiza a versão nos arquivos
 */
function updateVersion(string $version): void
{
    // Atualiza version.json
    $versionFile = __DIR__ . '/../version.json';
    $data = [];
    
    if (file_exists($versionFile)) {
        $content = file_get_contents($versionFile);
        $data = json_decode($content, true) ?? [];
    }
    
    $data['version'] = $version;
    $data['build'] = date('YmdHis');
    $data['release_date'] = date('Y-m-d');
    
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    file_put_contents($versionFile, $json);
    
    // Atualiza composer.json
    $composerFile = __DIR__ . '/../composer.json';
    if (file_exists($composerFile)) {
        $content = file_get_contents($composerFile);
        $data = json_decode($content, true);
        
        if ($data) {
            $data['version'] = $version;
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            file_put_contents($composerFile, $json);
        }
    }
}

/**
 * Gera entrada no changelog
 */
function generateChangelogEntry(string $version): string
{
    echo "\nDescreva as mudanças desta versão (uma por linha, termine com linha vazia):\n";
    
    $changes = [];
    while (true) {
        $line = trim(fgets(STDIN));
        if (empty($line)) break;
        $changes[] = $line;
    }
    
    if (empty($changes)) {
        return "Versão $version";
    }
    
    // Atualiza CHANGELOG.md
    $changelogFile = __DIR__ . '/../CHANGELOG.md';
    $content = file_get_contents($changelogFile);
    
    $date = date('Y-m-d');
    $entry = "\n## [$version] - $date\n\n";
    
    // Categoriza mudanças
    $categories = [
        '✨ Adicionado' => [],
        '🔧 Alterado' => [],
        '🐛 Corrigido' => [],
        '🗑️ Removido' => []
    ];
    
    foreach ($changes as $change) {
        // Detecta categoria baseada em palavras-chave
        if (preg_match('/^(add|novo|nova|adiciona)/i', $change)) {
            $categories['✨ Adicionado'][] = $change;
        } elseif (preg_match('/^(fix|corrig|resolve)/i', $change)) {
            $categories['🐛 Corrigido'][] = $change;
        } elseif (preg_match('/^(remove|deleta|exclui)/i', $change)) {
            $categories['🗑️ Removido'][] = $change;
        } else {
            $categories['🔧 Alterado'][] = $change;
        }
    }
    
    foreach ($categories as $category => $items) {
        if (!empty($items)) {
            $entry .= "### $category\n";
            foreach ($items as $item) {
                $entry .= "- $item\n";
            }
            $entry .= "\n";
        }
    }
    
    // Insere no changelog
    $content = str_replace(
        "## [Não Lançado]",
        "## [Não Lançado]" . $entry,
        $content
    );
    
    file_put_contents($changelogFile, $content);
    
    return implode(', ', $changes);
}

/**
 * Faz commit das mudanças
 */
function gitCommit(string $version, string $message): void
{
    $commands = [
        'git add version.json composer.json CHANGELOG.md',
        "git commit -m \"chore: release version $version\n\n$message\""
    ];
    
    foreach ($commands as $command) {
        exec($command, $output, $returnCode);
        if ($returnCode !== 0) {
            throw new Exception("Erro ao executar: $command");
        }
    }
}

/**
 * Cria tag Git
 */
function gitTag(string $version, string $message): void
{
    $command = "git tag -a v$version -m \"Release $version\n\n$message\"";
    exec($command, $output, $returnCode);
    
    if ($returnCode !== 0) {
        throw new Exception("Erro ao criar tag: $command");
    }
}

/**
 * Faz push para o repositório
 */
function gitPush(): void
{
    $commands = [
        'git push origin main',
        'git push origin --tags'
    ];
    
    foreach ($commands as $command) {
        echo "Executando: $command\n";
        exec($command, $output, $returnCode);
        if ($returnCode !== 0) {
            throw new Exception("Erro ao fazer push: $command");
        }
    }
    
    echo "✅ Push realizado com sucesso!\n";
}