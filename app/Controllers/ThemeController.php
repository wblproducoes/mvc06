<?php
/**
 * Controller de gerenciamento de temas
 * 
 * @package App\Controllers
 * @author Sistema Administrativo MVC
 */

namespace App\Controllers;

use App\Services\ThemeService;
use App\Core\ApiResponse;
use App\Core\Logger;

class ThemeController extends BaseController
{
    private ThemeService $themeService;
    
    public function __construct()
    {
        parent::__construct();
        $this->themeService = new ThemeService();
    }
    
    /**
     * Alterna tema do usuário
     * 
     * @return void
     */
    public function toggle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'Método não permitido'], 405);
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $theme = $data['theme'] ?? '';
        
        if (!in_array($theme, [ThemeService::THEME_LIGHT, ThemeService::THEME_DARK, ThemeService::THEME_AUTO])) {
            $this->json(['success' => false, 'error' => 'Tema inválido'], 400);
            return;
        }
        
        $user = $this->getCurrentUser();
        
        if ($user) {
            // Usuário logado - salva no banco
            $success = $this->themeService->setUserTheme($user['id'], $theme);
        } else {
            // Usuário não logado - salva na sessão
            $success = $this->themeService->setSessionTheme($theme);
        }
        
        if ($success) {
            Logger::channel(Logger::CHANNEL_SYSTEM)->info('Theme changed', [
                'user_id' => $user['id'] ?? null,
                'theme' => $theme,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            $this->json([
                'success' => true,
                'theme' => $theme,
                'message' => 'Tema alterado com sucesso'
            ]);
        } else {
            $this->json(['success' => false, 'error' => 'Erro ao alterar tema'], 500);
        }
    }
    
    /**
     * Retorna configuração atual do tema
     * 
     * @return void
     */
    public function config(): void
    {
        $user = $this->getCurrentUser();
        $config = $this->themeService->getThemeConfig($user['id'] ?? null);
        
        $this->json([
            'success' => true,
            'config' => $config
        ]);
    }
    
    /**
     * API para alternar tema
     * 
     * @return void
     */
    public function apiToggle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ApiResponse::error('Método não permitido', ApiResponse::HTTP_METHOD_NOT_ALLOWED);
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $theme = $data['theme'] ?? '';
        
        if (!in_array($theme, [ThemeService::THEME_LIGHT, ThemeService::THEME_DARK, ThemeService::THEME_AUTO])) {
            ApiResponse::validation(['theme' => 'Tema inválido']);
            return;
        }
        
        $user = $this->getCurrentUser();
        
        if ($user) {
            $success = $this->themeService->setUserTheme($user['id'], $theme);
        } else {
            $success = $this->themeService->setSessionTheme($theme);
        }
        
        if ($success) {
            ApiResponse::success([
                'theme' => $theme,
                'config' => $this->themeService->getThemeConfig($user['id'] ?? null)
            ], 'Tema alterado com sucesso');
        } else {
            ApiResponse::error('Erro ao alterar tema', ApiResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /**
     * Estatísticas de uso de temas (apenas admins)
     * 
     * @return void
     */
    public function stats(): void
    {
        $user = $this->getCurrentUser();
        
        if (!$user || $user['level_id'] != 1) {
            ApiResponse::forbidden('Acesso negado');
            return;
        }
        
        $stats = $this->themeService->getThemeStats();
        
        ApiResponse::success($stats, 'Estatísticas de temas');
    }
}