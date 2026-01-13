<?php
/**
 * Classe para gerenciamento de versões do sistema
 * 
 * @package App\Core
 * @author Sistema Administrativo MVC
 */

namespace App\Core;

class Version
{
    private static ?array $versionData = null;
    
    /**
     * Carrega dados da versão
     * 
     * @return array
     */
    private static function loadVersionData(): array
    {
        if (self::$versionData === null) {
            $versionFile = __DIR__ . '/../../version.json';
            
            if (file_exists($versionFile)) {
                $content = file_get_contents($versionFile);
                self::$versionData = json_decode($content, true) ?? [];
            } else {
                self::$versionData = [
                    'version' => '0.0.0',
                    'build' => '00000000000',
                    'release_date' => date('Y-m-d'),
                    'codename' => 'Unknown',
                    'php_min' => '8.4.0',
                    'stability' => 'dev'
                ];
            }
        }
        
        return self::$versionData;
    }
    
    /**
     * Retorna a versão atual
     * 
     * @return string
     */
    public static function get(): string
    {
        $data = self::loadVersionData();
        return $data['version'] ?? '0.0.0';
    }
    
    /**
     * Retorna a versão completa com build
     * 
     * @return string
     */
    public static function getFull(): string
    {
        $data = self::loadVersionData();
        return ($data['version'] ?? '0.0.0') . '+' . ($data['build'] ?? '000');
    }
    
    /**
     * Retorna o número do build
     * 
     * @return string
     */
    public static function getBuild(): string
    {
        $data = self::loadVersionData();
        return $data['build'] ?? '00000000000';
    }
    
    /**
     * Retorna a data de lançamento
     * 
     * @return string
     */
    public static function getReleaseDate(): string
    {
        $data = self::loadVersionData();
        return $data['release_date'] ?? date('Y-m-d');
    }
    
    /**
     * Retorna o codinome da versão
     * 
     * @return string
     */
    public static function getCodename(): string
    {
        $data = self::loadVersionData();
        return $data['codename'] ?? 'Unknown';
    }
    
    /**
     * Retorna a versão mínima do PHP
     * 
     * @return string
     */
    public static function getPhpMin(): string
    {
        $data = self::loadVersionData();
        return $data['php_min'] ?? '8.4.0';
    }
    
    /**
     * Retorna o status de estabilidade
     * 
     * @return string
     */
    public static function getStability(): string
    {
        $data = self::loadVersionData();
        return $data['stability'] ?? 'dev';
    }
    
    /**
     * Retorna todas as informações da versão
     * 
     * @return array
     */
    public static function getAll(): array
    {
        return self::loadVersionData();
    }
    
    /**
     * Verifica se é uma versão de desenvolvimento
     * 
     * @return bool
     */
    public static function isDev(): bool
    {
        return self::getStability() === 'dev';
    }
    
    /**
     * Verifica se é uma versão beta
     * 
     * @return bool
     */
    public static function isBeta(): bool
    {
        return self::getStability() === 'beta';
    }
    
    /**
     * Verifica se é uma versão estável
     * 
     * @return bool
     */
    public static function isStable(): bool
    {
        return self::getStability() === 'stable';
    }
    
    /**
     * Compara versões usando semantic versioning
     * 
     * @param string $version
     * @return int (-1: menor, 0: igual, 1: maior)
     */
    public static function compare(string $version): int
    {
        return version_compare(self::get(), $version);
    }
    
    /**
     * Verifica se a versão atual é maior que a fornecida
     * 
     * @param string $version
     * @return bool
     */
    public static function isGreaterThan(string $version): bool
    {
        return self::compare($version) > 0;
    }
    
    /**
     * Verifica se a versão atual é menor que a fornecida
     * 
     * @param string $version
     * @return bool
     */
    public static function isLessThan(string $version): bool
    {
        return self::compare($version) < 0;
    }
    
    /**
     * Verifica se a versão atual é igual à fornecida
     * 
     * @param string $version
     * @return bool
     */
    public static function isEqualTo(string $version): bool
    {
        return self::compare($version) === 0;
    }
    
    /**
     * Retorna informações formatadas para exibição
     * 
     * @return string
     */
    public static function getDisplayInfo(): string
    {
        $data = self::loadVersionData();
        
        $info = "Sistema Administrativo MVC v{$data['version']}";
        
        if (!empty($data['codename'])) {
            $info .= " \"{$data['codename']}\"";
        }
        
        $info .= " (Build {$data['build']})";
        
        if (!empty($data['release_date'])) {
            $info .= " - " . date('d/m/Y', strtotime($data['release_date']));
        }
        
        return $info;
    }
    
    /**
     * Gera badge HTML da versão
     * 
     * @return string
     */
    public static function getBadge(): string
    {
        $version = self::get();
        $stability = self::getStability();
        
        $badgeClass = match($stability) {
            'stable' => 'success',
            'beta' => 'warning',
            'alpha' => 'danger',
            default => 'secondary'
        };
        
        return "<span class=\"badge bg-{$badgeClass}\">v{$version}</span>";
    }
}