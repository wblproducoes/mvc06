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
            'total_vendas' => $this->getTotalVendas(),
            'vendas_mes' => $this->getVendasMes()
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
        $stmt = $this->database->query("SELECT COUNT(*) as total FROM {$this->database->getPrefix()}usuarios");
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
            "SELECT COUNT(*) as total FROM {$this->database->getPrefix()}usuarios 
             WHERE ultimo_acesso >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)"
        );
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }
    
    /**
     * Retorna o total de vendas
     * 
     * @return float
     */
    private function getTotalVendas(): float
    {
        $stmt = $this->database->query("SELECT SUM(valor) as total FROM {$this->database->getPrefix()}vendas");
        $result = $stmt->fetch();
        return $result['total'] ?? 0.0;
    }
    
    /**
     * Retorna vendas do mês atual
     * 
     * @return float
     */
    private function getVendasMes(): float
    {
        $stmt = $this->database->query(
            "SELECT SUM(valor) as total FROM {$this->database->getPrefix()}vendas 
             WHERE MONTH(data_venda) = MONTH(NOW()) AND YEAR(data_venda) = YEAR(NOW())"
        );
        $result = $stmt->fetch();
        return $result['total'] ?? 0.0;
    }
}