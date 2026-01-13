<?php
/**
 * Controller da API de turmas escolares
 * 
 * @package App\Controllers\Api
 * @author Sistema Administrativo MVC
 */

namespace App\Controllers\Api;

use App\Core\ApiResponse;
use App\Core\Security;
use App\Models\SchoolTeam;

class SchoolTeamApiController extends BaseApiController
{
    private SchoolTeam $teamModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->teamModel = new SchoolTeam();
    }
    
    /**
     * Lista turmas com paginação
     * 
     * @return void
     */
    public function index(): void
    {
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 20);
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        
        $query = "SELECT t.id, t.serie_id, t.period_id, t.education_id, t.status_id,
                         t.public_link_enabled, t.public_link_token, t.public_link_expires_at,
                         t.dh, st.name as status_name, p.name as period_name
                  FROM {prefix}school_teams t
                  LEFT JOIN {prefix}status st ON t.status_id = st.id
                  LEFT JOIN {prefix}school_periods p ON t.period_id = p.id
                  WHERE t.deleted_at IS NULL";
        
        $params = [];
        
        if ($status) {
            $query .= " AND t.status_id = ?";
            $params[] = $status;
        }
        
        $query .= " ORDER BY t.id DESC";
        
        $result = $this->paginate($query, $params, $page, $perPage);
        
        ApiResponse::paginated(
            $result['data'],
            $result['total'],
            $result['page'],
            $result['per_page'],
            'Lista de turmas'
        );
    }
    
    /**
     * Exibe uma turma específica
     * 
     * @param int $id
     * @return void
     */
    public function show(int $id): void
    {
        $team = $this->teamModel->findById($id);
        
        if (!$team) {
            ApiResponse::notFound('Turma não encontrada');
            return;
        }
        
        ApiResponse::success($team, 'Dados da turma');
    }
    
    /**
     * Cria nova turma
     * 
     * @return void
     */
    public function store(): void
    {
        $data = $this->getJsonInput();
        
        // Validação
        $errors = $this->validateInput($data, [
            'period_id' => [
                'required' => true,
                'type' => 'int',
                'options' => ['min' => 1],
                'message' => 'Período é obrigatório'
            ]
        ]);
        
        if (!empty($errors)) {
            ApiResponse::validation($errors);
            return;
        }
        
        // Sanitiza dados
        $sanitizedData = $this->sanitizeInput($data, [
            'serie_id' => 'int',
            'period_id' => 'int',
            'education_id' => 'int'
        ]);
        
        // Define valores padrão
        $sanitizedData['status_id'] = $data['status_id'] ?? 1;
        
        try {
            $teamId = $this->teamModel->create($sanitizedData);
            
            $team = $this->teamModel->findById($teamId);
            
            ApiResponse::success($team, 'Turma criada com sucesso', ApiResponse::HTTP_CREATED);
            
        } catch (\Exception $e) {
            ApiResponse::error('Erro ao criar turma: ' . $e->getMessage(), 
                             ApiResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /**
     * Atualiza turma
     * 
     * @param int $id
     * @return void
     */
    public function update(int $id): void
    {
        $team = $this->teamModel->findById($id);
        
        if (!$team) {
            ApiResponse::notFound('Turma não encontrada');
            return;
        }
        
        $data = $this->getJsonInput();
        
        // Sanitiza dados
        $allowedFields = ['serie_id', 'period_id', 'education_id', 'status_id'];
        $updateData = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = Security::sanitizeInput($data[$field], 'int');
            }
        }
        
        try {
            $this->teamModel->update($id, $updateData);
            
            $updatedTeam = $this->teamModel->findById($id);
            
            ApiResponse::success($updatedTeam, 'Turma atualizada com sucesso');
            
        } catch (\Exception $e) {
            ApiResponse::error('Erro ao atualizar turma: ' . $e->getMessage(), 
                             ApiResponse::HTTP_INTERNAL_SERVER_ERROR);
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
        $team = $this->teamModel->findById($id);
        
        if (!$team) {
            ApiResponse::notFound('Turma não encontrada');
            return;
        }
        
        $data = $this->getJsonInput();
        $enabled = $data['enabled'] ?? !$team['public_link_enabled'];
        
        try {
            if ($enabled) {
                // Gera token se não existir
                $token = $team['public_link_token'] ?: Security::generateSecureToken(5);
                $expiresAt = $data['expires_at'] ?? date('Y-m-d', strtotime('+30 days'));
                
                $this->teamModel->update($id, [
                    'public_link_enabled' => 1,
                    'public_link_token' => $token,
                    'public_link_expires_at' => $expiresAt
                ]);
            } else {
                $this->teamModel->update($id, [
                    'public_link_enabled' => 0
                ]);
            }
            
            $updatedTeam = $this->teamModel->findById($id);
            
            ApiResponse::success($updatedTeam, 'Link público atualizado com sucesso');
            
        } catch (\Exception $e) {
            ApiResponse::error('Erro ao atualizar link público: ' . $e->getMessage(), 
                             ApiResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /**
     * Remove turma (soft delete)
     * 
     * @param int $id
     * @return void
     */
    public function destroy(int $id): void
    {
        $team = $this->teamModel->findById($id);
        
        if (!$team) {
            ApiResponse::notFound('Turma não encontrada');
            return;
        }
        
        try {
            $this->teamModel->softDelete($id);
            
            ApiResponse::success(null, 'Turma excluída com sucesso', ApiResponse::HTTP_NO_CONTENT);
            
        } catch (\Exception $e) {
            ApiResponse::error('Erro ao excluir turma: ' . $e->getMessage(), 
                             ApiResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /**
     * Lista horários da turma
     * 
     * @param int $id
     * @return void
     */
    public function schedules(int $id): void
    {
        $team = $this->teamModel->findById($id);
        
        if (!$team) {
            ApiResponse::notFound('Turma não encontrada');
            return;
        }
        
        $query = "SELECT s.*, sub.name as subject_name, u.name as teacher_name
                  FROM {prefix}school_schedules s
                  LEFT JOIN {prefix}school_subjects sub ON s.subject_id = sub.id
                  LEFT JOIN {prefix}users u ON s.teacher_id = u.id
                  WHERE s.team_id = ? AND s.deleted_at IS NULL
                  ORDER BY s.day_of_week, s.class_number";
        
        $schedules = $this->database->query($query, [$id]);
        
        ApiResponse::success($schedules, 'Horários da turma');
    }
}