<?php
/**
 * Serviço de gerenciamento de temas
 * 
 * @package App\Services
 * @author Sistema Administrativo MVC
 */

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;

class ThemeService
{
    private Database $database;
    
    public const THEME_LIGHT = 'light';
    public const THEME_DARK = 'dark';
    public const THEME_AUTO = 'auto';
    
    public function __construct()
    {
        $this->database = new Database();
    }
    
    /**
     * Retorna tema do usuário
     * 
     * @param int|null $userId
     * @return string
     */
    public function getUserTheme(?int $userId = null): string
    {
        if (!$userId) {
            // Retorna tema da sessão ou cookie
            return $_SESSION['theme'] ?? $_COOKIE['theme'] ?? self::THEME_AUTO;
        }
        
        try {
            $result = $this->database->fetchOne(
                "SELECT setting_value FROM {prefix}user_settings 
                 WHERE user_id = ? AND setting_key = 'theme'",
                [$userId]
            );
            
            return $result['setting_value'] ?? self::THEME_AUTO;
            
        } catch (\Exception $e) {
            Logger::channel(Logger::CHANNEL_ERROR)->error('Failed to get user theme', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return self::THEME_AUTO;
        }
    }
    
    /**
     * Define tema do usuário
     * 
     * @param int $userId
     * @param string $theme
     * @return bool
     */
    public function setUserTheme(int $userId, string $theme): bool
    {
        if (!in_array($theme, [self::THEME_LIGHT, self::THEME_DARK, self::THEME_AUTO])) {
            return false;
        }
        
        try {
            // Atualiza no banco de dados
            $this->database->query(
                "INSERT INTO {prefix}user_settings (user_id, setting_key, setting_value, updated_at) 
                 VALUES (?, 'theme', ?, NOW()) 
                 ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()",
                [$userId, $theme, $theme]
            );
            
            // Atualiza na sessão
            $_SESSION['theme'] = $theme;
            
            // Atualiza no cookie (30 dias)
            setcookie('theme', $theme, time() + (30 * 24 * 60 * 60), '/', '', false, true);
            
            Logger::channel(Logger::CHANNEL_SYSTEM)->info('User theme updated', [
                'user_id' => $userId,
                'theme' => $theme
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Logger::channel(Logger::CHANNEL_ERROR)->error('Failed to set user theme', [
                'user_id' => $userId,
                'theme' => $theme,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Define tema na sessão (usuário não logado)
     * 
     * @param string $theme
     * @return bool
     */
    public function setSessionTheme(string $theme): bool
    {
        if (!in_array($theme, [self::THEME_LIGHT, self::THEME_DARK, self::THEME_AUTO])) {
            return false;
        }
        
        $_SESSION['theme'] = $theme;
        setcookie('theme', $theme, time() + (30 * 24 * 60 * 60), '/', '', false, true);
        
        return true;
    }
    
    /**
     * Retorna configurações de tema para o frontend
     * 
     * @param int|null $userId
     * @return array
     */
    public function getThemeConfig(?int $userId = null): array
    {
        $currentTheme = $this->getUserTheme($userId);
        
        return [
            'current' => $currentTheme,
            'available' => [
                self::THEME_LIGHT => 'Claro',
                self::THEME_DARK => 'Escuro',
                self::THEME_AUTO => 'Automático'
            ],
            'icons' => [
                self::THEME_LIGHT => 'fas fa-sun',
                self::THEME_DARK => 'fas fa-moon',
                self::THEME_AUTO => 'fas fa-adjust'
            ],
            'storage_key' => 'user_theme_preference'
        ];
    }
    
    /**
     * Retorna CSS classes para o tema atual
     * 
     * @param int|null $userId
     * @return string
     */
    public function getThemeClasses(?int $userId = null): string
    {
        $theme = $this->getUserTheme($userId);
        
        $classes = ['theme-' . $theme];
        
        if ($theme === self::THEME_AUTO) {
            $classes[] = 'theme-auto';
        }
        
        return implode(' ', $classes);
    }
    
    /**
     * Migra tema de usuários antigos
     * 
     * @return int Número de usuários migrados
     */
    public function migrateUserThemes(): int
    {
        try {
            // Busca usuários sem configuração de tema
            $users = $this->database->fetchAll(
                "SELECT u.id FROM {prefix}users u 
                 LEFT JOIN {prefix}user_settings us ON u.id = us.user_id AND us.setting_key = 'theme'
                 WHERE us.id IS NULL AND u.deleted_at IS NULL"
            );
            
            $migrated = 0;
            
            foreach ($users as $user) {
                if ($this->setUserTheme($user['id'], self::THEME_AUTO)) {
                    $migrated++;
                }
            }
            
            Logger::channel(Logger::CHANNEL_SYSTEM)->info('User themes migrated', [
                'migrated_count' => $migrated
            ]);
            
            return $migrated;
            
        } catch (\Exception $e) {
            Logger::channel(Logger::CHANNEL_ERROR)->error('Failed to migrate user themes', [
                'error' => $e->getMessage()
            ]);
            
            return 0;
        }
    }
    
    /**
     * Retorna estatísticas de uso de temas
     * 
     * @return array
     */
    public function getThemeStats(): array
    {
        try {
            $stats = $this->database->fetchAll(
                "SELECT setting_value as theme, COUNT(*) as count 
                 FROM {prefix}user_settings 
                 WHERE setting_key = 'theme' 
                 GROUP BY setting_value"
            );
            
            $result = [
                self::THEME_LIGHT => 0,
                self::THEME_DARK => 0,
                self::THEME_AUTO => 0,
                'total' => 0
            ];
            
            foreach ($stats as $stat) {
                $result[$stat['theme']] = (int)$stat['count'];
                $result['total'] += (int)$stat['count'];
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Logger::channel(Logger::CHANNEL_ERROR)->error('Failed to get theme stats', [
                'error' => $e->getMessage()
            ]);
            
            return [
                self::THEME_LIGHT => 0,
                self::THEME_DARK => 0,
                self::THEME_AUTO => 0,
                'total' => 0
            ];
        }
    }
}