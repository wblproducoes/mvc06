<?php
/**
 * Controller da API de matérias escolares
 * 
 * @package App\Controllers\Api
 * @author Sistema Administrativo MVC
 */

namespace App\Controllers\Api;

use App\Core\ApiResponse;
use App\Core\Security;
use App\Models\SchoolSubject;

class SchoolSubjectApiController extends BaseApiController
{
    private SchoolSubject $subjectModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->subjectModel = new SchoolSubject();
    }
    
    /**
     * Lista matérias com paginação
     * 
     * @return void
     */
    public function index(): void
    {
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 20);
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        
        $query = "SELECT s.id, s.name, s.translate, s.description, s.status_id, 
                         s.dh, st.name as status_name
                  FROM {prefix}school_subjects s
                  LEFT JOIN {prefix}status st ON s.status_id = st.id
                  WHERE s.deleted_at IS NULL";
        
        $params = [];
        
        if ($search) {
            $query .= " AND (s.name LIKE ? OR s.description LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if ($status) {
            $query .= " AND s.status_id = ?";
            $params[] = $status;
        }
        
        $query .= " ORDER BY s.name ASC";
        
        $result = $this->paginate($query, $params, $page, $perPage);
        
        ApiResponse::paginated(
            $result['data'],
            $result['total'],
            $result['page'],
            $result['per_page'],
            'Lista de matérias'
        );
    }
    
    /**
     * Exibe uma matéria específica
     * 
     * @param int $id
     * @return void
     */
    public function show(int $id): void
    {
        $subject = $this->subjectModel->findById($id);
        
        if (!$subject) {
            ApiResponse::notFound('Matéria não encontrada');
            return;
        }
        
        ApiResponse::success($subject, 'Dados da matéria');
    }
    
    /**
     * Cria nova matéria
     * 
     * @return void
     */
    public function store(): void
    {
        $data = $this->getJsonInput();
        
        // Validação
        $errors = $this->validateInput($data, [
            'name' => [
                'required' => true,
                'type' => 'length',
                'options' => ['min' => 2, 'max' => 100],
                'message' => 'Nome deve ter entre 2 e 100 caracteres'
            ]
        ]);
        
        if (!empty($errors)) {
            ApiResponse::validation($errors);
            return;
        }
        
        // Sanitiza dados
        $sanitizedData = $this->sanitizeInput($data, [
            'name' => 'string',
            'translate' => 'string',
            'description' => 'string'
        ]);
        
        // Define valores padrão
        $sanitizedData['status_id'] = $data['status_id'] ?? 1;
        
        try {
            $subjectId = $this->subjectModel->create($sanitizedData);
            
            $subject = $this->subjectModel->findById($subjectId);
            
            ApiResponse::success($subject, 'Matéria criada com sucesso', ApiResponse::HTTP_CREATED);
            
        } catch (\Exception $e) {
            ApiResponse::error('Erro ao criar matéria: ' . $e->getMessage(), 
                             ApiResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /**
     * Atualiza matéria
     * 
     * @param int $id
     * @return void
     */
    public function update(int $id): void
    {
        $subject = $this->subjectModel->findById($id);
        
        if (!$subject) {
            ApiResponse::notFound('Matéria não encontrada');
            return;
        }
        
        $data = $this->getJsonInput();
        
        // Validação
        $errors = $this->validateInput($data, [
            'name' => [
                'type' => 'length',
                'options' => ['min' => 2, 'max' => 100],
                'message' => 'Nome deve ter entre 2 e 100 caracteres'
            ]
        ]);
        
        if (!empty($errors)) {
            ApiResponse::validation($errors);
            return;
        }
        
        // Sanitiza dados
        $allowedFields = ['name', 'translate', 'description', 'status_id'];
        $updateData = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = Security::sanitizeInput($data[$field]);
            }
        }
        
        try {
            $this->subjectModel->update($id, $updateData);
            
            $updatedSubject = $this->subjectModel->findById($id);
            
            ApiResponse::success($updatedSubject, 'Matéria atualizada com sucesso');
            
        } catch (\Exception $e) {
            ApiResponse::error('Erro ao atualizar matéria: ' . $e->getMessage(), 
                             ApiResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /**
     * Remove matéria (soft delete)
     * 
     * @param int $id
     * @return void
     */
    public function destroy(int $id): void
    {
        $subject = $this->subjectModel->findById($id);
        
        if (!$subject) {
            ApiResponse::notFound('Matéria não encontrada');
            return;
        }
        
        try {
            $this->subjectModel->softDelete($id);
            
            ApiResponse::success(null, 'Matéria excluída com sucesso', ApiResponse::HTTP_NO_CONTENT);
            
        } catch (\Exception $e) {
            ApiResponse::error('Erro ao excluir matéria: ' . $e->getMessage(), 
                             ApiResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}