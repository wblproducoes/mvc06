<?php
/**
 * Controller da página inicial
 * 
 * @package App\Controllers
 * @author Sistema Administrativo MVC
 */

namespace App\Controllers;

class HomeController extends BaseController
{
    /**
     * Página inicial do sistema
     * 
     * @return void
     */
    public function index(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('/login');
            return;
        }
        
        // Dados do dashboard
        $data = [
            'titulo' => 'Dashboard',
            'total_usuarios' => $this->getTotalUsuarios(),
            'usuarios_online' => $this->getUsuariosOnline(),
            'total_turmas' => $this->getTotalTurmas(),
            'total_materias' => $this->getTotalMaterias(),
            'usuarios_por_nivel' => $this->getUsuariosPorNivel(),
            'atividade_recente' => $this->getAtividadeRecente()
        ];
        
        $this->render('dashboard/index.twig', $data);
    }
    
    /**
     * Retorna o total de usuários
     * 
     * @return int
     */
    private function getTotalUsuarios(): int
    {
        $stmt = $this->database->query("SELECT COUNT(*) as total FROM {$this->database->getPrefix()}users WHERE deleted_at IS NULL");
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }
    
    /**
     * Retorna usuários online (últimos 15 minutos)
     * 
     * @return int
     */
    private function getUsuariosOnline(): int
    {
        $stmt = $this->database->query(
            "SELECT COUNT(*) as total FROM {$this->database->getPrefix()}users 
             WHERE last_access >= DATE_SUB(NOW(), INTERVAL 15 MINUTE) AND deleted_at IS NULL"
        );
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }
    
    /**
     * Retorna o total de turmas
     * 
     * @return int
     */
    private function getTotalTurmas(): int
    {
        $stmt = $this->database->query("SELECT COUNT(*) as total FROM {$this->database->getPrefix()}school_teams WHERE deleted_at IS NULL");
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }
    
    /**
     * Retorna o total de matérias
     * 
     * @return int
     */
    private function getTotalMaterias(): int
    {
        $stmt = $this->database->query("SELECT COUNT(*) as total FROM {$this->database->getPrefix()}school_subjects WHERE deleted_at IS NULL");
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }
    
    /**
     * Retorna usuários por nível
     * 
     * @return array
     */
    private function getUsuariosPorNivel(): array
    {
        $stmt = $this->database->query(
            "SELECT l.translate as nivel, COUNT(u.id) as total
             FROM {$this->database->getPrefix()}levels l
             LEFT JOIN {$this->database->getPrefix()}users u ON l.id = u.level_id AND u.deleted_at IS NULL
             WHERE l.deleted_at IS NULL
             GROUP BY l.id, l.translate
             ORDER BY l.id"
        );
        return $stmt->fetchAll();
    }
    
    /**
     * Retorna atividade recente do sistema
     * 
     * @return array
     */
    private function getAtividadeRecente(): array
    {
        // Busca usuários criados recentemente
        $stmt = $this->database->query(
            "SELECT 'user_created' as type, u.name as description, u.dh as created_at
             FROM {$this->database->getPrefix()}users u
             WHERE u.dh >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND u.deleted_at IS NULL
             ORDER BY u.dh DESC
             LIMIT 10"
        );
        
        $activities = $stmt->fetchAll();
        
        // Formata as atividades
        foreach ($activities as &$activity) {
            switch ($activity['type']) {
                case 'user_created':
                    $activity['icon'] = 'bi-person-plus';
                    $activity['color'] = 'success';
                    $activity['message'] = "Novo usuário: {$activity['description']}";
                    break;
            }
            
            $activity['time_ago'] = $this->timeAgo($activity['created_at']);
        }
        
        return $activities;
    }
    
    /**
     * Calcula tempo decorrido
     * 
     * @param string $datetime
     * @return string
     */
    private function timeAgo(string $datetime): string
    {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) return 'agora';
        if ($time < 3600) return floor($time/60) . 'm';
        if ($time < 86400) return floor($time/3600) . 'h';
        if ($time < 2592000) return floor($time/86400) . 'd';
        
        return date('d/m/Y', strtotime($datetime));
    }
}