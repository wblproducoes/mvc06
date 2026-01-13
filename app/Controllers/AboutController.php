<?php
/**
 * Controller da página sobre/versão
 * 
 * @package App\Controllers
 * @author Sistema Administrativo MVC
 */

namespace App\Controllers;

use App\Core\Version;

class AboutController extends BaseController
{
    /**
     * Página sobre o sistema
     * 
     * @return void
     */
    public function index(): void
    {
        $versionData = Version::getAll();
        
        // Informações do sistema
        $systemInfo = [
            'php_version' => PHP_VERSION,
            'php_min_required' => Version::getPhpMin(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Desconhecido',
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Desconhecido',
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size')
        ];
        
        // Extensões PHP necessárias
        $requiredExtensions = [
            'pdo' => extension_loaded('pdo'),
            'pdo_mysql' => extension_loaded('pdo_mysql'),
            'mbstring' => extension_loaded('mbstring'),
            'openssl' => extension_loaded('openssl'),
            'curl' => extension_loaded('curl'),
            'gd' => extension_loaded('gd'),
            'zip' => extension_loaded('zip'),
            'json' => extension_loaded('json')
        ];
        
        // Informações do banco de dados
        try {
            $dbInfo = [
                'host' => $_ENV['DB_HOST'],
                'database' => $_ENV['DB_NAME'],
                'prefix' => $_ENV['DB_PREFIX'],
                'connection' => 'Conectado',
                'version' => $this->getDatabaseVersion()
            ];
        } catch (\Exception $e) {
            $dbInfo = [
                'connection' => 'Erro: ' . $e->getMessage(),
                'version' => 'N/A'
            ];
        }
        
        // Dependências do Composer
        $dependencies = $this->getComposerDependencies();
        
        $data = [
            'titulo' => 'Sobre o Sistema',
            'version_data' => $versionData,
            'system_info' => $systemInfo,
            'required_extensions' => $requiredExtensions,
            'db_info' => $dbInfo,
            'dependencies' => $dependencies,
            'changelog_content' => $this->getChangelogContent()
        ];
        
        $this->render('about/index.twig', $data);
    }
    
    /**
     * Retorna a versão do banco de dados
     * 
     * @return string
     */
    private function getDatabaseVersion(): string
    {
        try {
            $stmt = $this->database->query("SELECT VERSION() as version");
            $result = $stmt->fetch();
            return $result['version'] ?? 'Desconhecida';
        } catch (\Exception $e) {
            return 'Erro ao obter versão';
        }
    }
    
    /**
     * Retorna as dependências do Composer
     * 
     * @return array
     */
    private function getComposerDependencies(): array
    {
        $composerFile = __DIR__ . '/../../composer.json';
        
        if (!file_exists($composerFile)) {
            return [];
        }
        
        $content = file_get_contents($composerFile);
        $data = json_decode($content, true);
        
        return $data['require'] ?? [];
    }
    
    /**
     * Retorna o conteúdo do changelog (últimas versões)
     * 
     * @return string
     */
    private function getChangelogContent(): string
    {
        $changelogFile = __DIR__ . '/../../CHANGELOG.md';
        
        if (!file_exists($changelogFile)) {
            return 'Changelog não encontrado.';
        }
        
        $content = file_get_contents($changelogFile);
        
        // Pega apenas as últimas 3 versões
        $lines = explode("\n", $content);
        $result = [];
        $versionCount = 0;
        $inVersion = false;
        
        foreach ($lines as $line) {
            if (preg_match('/^## \[\d+\.\d+\.\d+\]/', $line)) {
                if ($versionCount >= 3) break;
                $versionCount++;
                $inVersion = true;
            }
            
            if ($inVersion) {
                $result[] = $line;
                
                // Para na próxima versão após coletar 3
                if ($versionCount > 3 && preg_match('/^## \[/', $line)) {
                    break;
                }
            }
        }
        
        return implode("\n", $result);
    }
    
    /**
     * API endpoint para informações de versão
     * 
     * @return void
     */
    public function apiVersion(): void
    {
        $this->json([
            'version' => Version::get(),
            'build' => Version::getBuild(),
            'release_date' => Version::getReleaseDate(),
            'codename' => Version::getCodename(),
            'stability' => Version::getStability(),
            'php_version' => PHP_VERSION,
            'timestamp' => time()
        ]);
    }
}