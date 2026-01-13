<?php
/**
 * Controller de turmas escolares
 * 
 * @package App\Controllers
 * @author Sistema Administrativo MVC
 */

namespace App\Controllers;

use App\Models\SchoolTeam;
use App\Models\SchoolPeriod;
use App\Models\Status;

class SchoolTeamController extends BaseController
{
    private SchoolTeam $teamModel;
    private SchoolPeriod $periodModel;
    private Status $statusModel;
    
    /**
     * Construtor do SchoolTeamController
     */
    public function __construct()
    {
        parent::__construct();
        $this->teamModel = new SchoolTeam();
        $this->periodModel = new SchoolPeriod();
        $this->statusModel = new Status();
    }
    
    /**
     * Lista todas as turmas
     * 
     * @return void
     */
    public function index(): void
    {
        $periodFilter = $_GET['period'] ?? '';
        $statusFilter = $_GET['status'] ?? '';
        
        $conditions = [];
        if (!empty($periodFilter)) {
            $conditions['t.period_id'] = $periodFilter;
        }
        if (!empty($statusFilter)) {
            $conditions['t.status_id'] = $statusFilter;
        }
        
        $teams = $this->teamModel->findWithRelations($conditions);
        
        $data = [
            'titulo' => 'Turmas Escolares',
            'teams' => $teams,
            'periods' => $this->periodModel->findAllActive(),
            'statuses' => $this->statusModel->findAllActive(),
            'filters' => [
                'period' => $periodFilter,
                'status' => $statusFilter
            ]
        ];
        
        $this->render('school-teams/index.twig', $data);
    }
    
    /**
     * Exibe formulário de criação
     * 
     * @return void
     */
    public function create(): void
    {
        $data = [
            'titulo' => 'Nova Turma',
            'periods' => $this->periodModel->findAllActive(),
            'statuses' => $this->statusModel->findAllActive()
        ];
        
        $this->render('school-teams/create.twig', $data);
    }
    
    /**
     * Processa criação da turma
     * 
     * @return void
     */
    public function store(): void
    {
        if (!$this->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->addFlashMessage('error', 'Token CSRF inválido');
            $this->redirect('/turmas/criar');
            return;
        }
        
        $data = [
            'serie_id' => $_POST['serie_id'] ?? null,
            'period_id' => $_POST['period_id'] ?? null,
            'education_id' => $_POST['education_id'] ?? null,
            'status_id' => $_POST['status_id'] ?? 1
        ];
        
        // Validações
        $errors = $this->validateTeamData($data);
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addFlashMessage('error', $error);
            }
            $this->redirect('/turmas/criar');
            return;
        }
        
        try {
            $teamId = $this->teamModel->create($data);
            $this->addFlashMessage('success', 'Turma criada com sucesso!');
            $this->redirect('/turmas/' . $teamId);
        } catch (\Exception $e) {
            $this->addFlashMessage('error', 'Erro ao criar turma: ' . $e->getMessage());
            $this->redirect('/turmas/criar');
        }
    }
    
    /**
     * Exibe detalhes da turma
     * 
     * @param int $id
     * @return void
     */
    public function show(int $id): void
    {
        $teams = $this->teamModel->findWithRelations(['t.id' => $id]);
        $team = $teams[0] ?? null;
        
        if (!$team) {
            $this->addFlashMessage('error', 'Turma não encontrada');
            $this->redirect('/turmas');
            return;
        }
        
        $schedules = $this->teamModel->getSchedules($id);
        $studentCount = $this->teamModel->countStudents($id);
        
        $data = [
            'titulo' => 'Detalhes da Turma',
            'team' => $team,
            'schedules' => $schedules,
            'student_count' => $studentCount
        ];
        
        $this->render('school-teams/show.twig', $data);
    }
    
    /**
     * Exibe formulário de edição
     * 
     * @param int $id
     * @return void
     */
    public function edit(int $id): void
    {
        $team = $this->teamModel->findById($id);
        
        if (!$team) {
            $this->addFlashMessage('error', 'Turma não encontrada');
            $this->redirect('/turmas');
            return;
        }
        
        $data = [
            'titulo' => 'Editar Turma',
            'team' => $team,
            'periods' => $this->periodModel->findAllActive(),
            'statuses' => $this->statusModel->findAllActive()
        ];
        
        $this->render('school-teams/edit.twig', $data);
    }
    
    /**
     * Processa atualização da turma
     * 
     * @param int $id
     * @return void
     */
    public function update(int $id): void
    {
        if (!$this->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->addFlashMessage('error', 'Token CSRF inválido');
            $this->redirect('/turmas/' . $id . '/editar');
            return;
        }
        
        $team = $this->teamModel->findById($id);
        if (!$team) {
            $this->addFlashMessage('error', 'Turma não encontrada');
            $this->redirect('/turmas');
            return;
        }
        
        $data = [
            'serie_id' => $_POST['serie_id'] ?? null,
            'period_id' => $_POST['period_id'] ?? null,
            'education_id' => $_POST['education_id'] ?? null,
            'status_id' => $_POST['status_id'] ?? 1
        ];
        
        // Validações
        $errors = $this->validateTeamData($data, $id);
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addFlashMessage('error', $error);
            }
            $this->redirect('/turmas/' . $id . '/editar');
            return;
        }
        
        try {
            $this->teamModel->update($id, $data);
            $this->addFlashMessage('success', 'Turma atualizada com sucesso!');
            $this->redirect('/turmas/' . $id);
        } catch (\Exception $e) {
            $this->addFlashMessage('error', 'Erro ao atualizar turma: ' . $e->getMessage());
            $this->redirect('/turmas/' . $id . '/editar');
        }
    }
    
    /**
     * Ativa/desativa link público da turma
     * 
     * @param int $id
     * @return void
     */
    public function togglePublicLink(int $id): void
    {
        if (!$this->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->addFlashMessage('error', 'Token CSRF inválido');
            $this->redirect('/turmas/' . $id);
            return;
        }
        
        $team = $this->teamModel->findById($id);
        if (!$team) {
            $this->addFlashMessage('error', 'Turma não encontrada');
            $this->redirect('/turmas');
            return;
        }
        
        try {
            if ($team['public_link_enabled']) {
                $this->teamModel->disablePublicLink($id);
                $this->addFlashMessage('success', 'Link público desativado');
            } else {
                $expiresAt = $_POST['expires_at'] ?? null;
                $this->teamModel->enablePublicLink($id, $expiresAt);
                $this->addFlashMessage('success', 'Link público ativado');
            }
        } catch (\Exception $e) {
            $this->addFlashMessage('error', 'Erro ao alterar link público: ' . $e->getMessage());
        }
        
        $this->redirect('/turmas/' . $id);
    }
    
    /**
     * Soft delete da turma
     * 
     * @param int $id
     * @return void
     */
    public function delete(int $id): void
    {
        if (!$this->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->addFlashMessage('error', 'Token CSRF inválido');
            $this->redirect('/turmas');
            return;
        }
        
        try {
            $this->teamModel->softDelete($id);
            $this->addFlashMessage('success', 'Turma excluída com sucesso!');
        } catch (\Exception $e) {
            $this->addFlashMessage('error', 'Erro ao excluir turma: ' . $e->getMessage());
        }
        
        $this->redirect('/turmas');
    }
    
    /**
     * Exibe turma via link público
     * 
     * @param string $token
     * @return void
     */
    public function publicView(string $token): void
    {
        $team = $this->teamModel->findByPublicToken($token);
        
        if (!$team) {
            $this->addFlashMessage('error', 'Link inválido ou expirado');
            $this->redirect('/login');
            return;
        }
        
        $schedules = $this->teamModel->getSchedules($team['id']);
        
        $data = [
            'titulo' => 'Horários da Turma',
            'team' => $team,
            'schedules' => $schedules,
            'is_public' => true
        ];
        
        $this->render('school-teams/public.twig', $data);
    }
    
    /**
     * Valida dados da turma
     * 
     * @param array $data
     * @param int|null $excludeId
     * @return array
     */
    private function validateTeamData(array $data, ?int $excludeId = null): array
    {
        $errors = [];
        
        if (empty($data['period_id'])) {
            $errors[] = 'Período é obrigatório';
        }
        
        return $errors;
    }
}