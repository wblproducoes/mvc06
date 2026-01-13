<?php
/**
 * Controller da API de usuários
 * 
 * @package App\Controllers\Api
 * @author Sistema Administrativo MVC
 */

namespace App\Controllers\Api;

use App\Core\ApiResponse;
use App\Core\Security;
use App\Models\User;

class UserApiController extends BaseApiController
{
    private User $userModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
    }
    
    /**
     * Lista usuários com paginação
     * 
     * @return void
     */
    public function index(): void
    {
        if (!$this->hasPermission('users.read')) {
            ApiResponse::forbidden('Sem permissão para listar usuários');
            return;
        }
        
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 20);
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        
        $query = "SELECT u.id, u.name, u.username, u.email, u.cpf, u.phone_mobile, 
                         u.last_access, u.dh, u.status_id, s.name as status_name,
                         l.name as level_name
                  FROM {prefix}users u
                  LEFT JOIN {prefix}status s ON u.status_id = s.id
                  LEFT JOIN {prefix}levels l ON u.level_id = l.id
                  WHERE u.deleted_at IS NULL";
        
        $params = [];
        
        if ($search) {
            $query .= " AND (u.name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if ($status) {
            $query .= " AND u.status_id = ?";
            $params[] = $status;
        }
        
        $query .= " ORDER BY u.name ASC";
        
        $result = $this->paginate($query, $params, $page, $perPage);
        
        ApiResponse::paginated(
            $result['data'],
            $result['total'],
            $result['page'],
            $result['per_page'],
            'Lista de usuários'
        );
    }
    
    /**
     * Exibe um usuário específico
     * 
     * @param int $id
     * @return void
     */
    public function show(int $id): void
    {
        if (!$this->hasPermission('users.read')) {
            ApiResponse::forbidden('Sem permissão para visualizar usuário');
            return;
        }
        
        $user = $this->userModel->findById($id);
        
        if (!$user) {
            ApiResponse::notFound('Usuário não encontrado');
            return;
        }
        
        // Remove dados sensíveis
        unset($user['password'], $user['session_token'], $user['password_reset_token']);
        
        ApiResponse::success($user, 'Dados do usuário');
    }
    
    /**
     * Cria novo usuário
     * 
     * @return void
     */
    public function store(): void
    {
        if (!$this->hasPermission('users.create')) {
            ApiResponse::forbidden('Sem permissão para criar usuário');
            return;
        }
        
        $data = $this->getJsonInput();
        
        // Validação
        $errors = $this->validateInput($data, [
            'name' => [
                'required' => true,
                'type' => 'length',
                'options' => ['min' => 2, 'max' => 255],
                'message' => 'Nome deve ter entre 2 e 255 caracteres'
            ],
            'username' => [
                'required' => true,
                'type' => 'length',
                'options' => ['min' => 3, 'max' => 20],
                'message' => 'Username deve ter entre 3 e 20 caracteres'
            ],
            'email' => [
                'required' => true,
                'type' => 'email',
                'message' => 'Email inválido'
            ],
            'password' => [
                'required' => true,
                'type' => 'length',
                'options' => ['min' => 6],
                'message' => 'Senha deve ter pelo menos 6 caracteres'
            ]
        ]);
        
        if (!empty($errors)) {
            ApiResponse::validation($errors);
            return;
        }
        
        // Sanitiza dados
        $sanitizedData = $this->sanitizeInput($data, [
            'name' => 'string',
            'username' => 'alphanumeric',
            'email' => 'email',
            'cpf' => 'string',
            'phone_mobile' => 'string'
        ]);
        
        // Verifica se username já existe
        if ($this->userModel->findByUsername($sanitizedData['username'])) {
            ApiResponse::error('Username já está em uso', ApiResponse::HTTP_CONFLICT);
            return;
        }
        
        // Verifica se email já existe
        if ($this->userModel->findByEmail($sanitizedData['email'])) {
            ApiResponse::error('Email já está em uso', ApiResponse::HTTP_CONFLICT);
            return;
        }
        
        // Criptografa senha
        $sanitizedData['password'] = Security::hashPassword($data['password']);
        
        // Define valores padrão
        $sanitizedData['unique_code'] = Security::generateSecureToken(10);
        $sanitizedData['level_id'] = $data['level_id'] ?? 11; // Usuário comum
        $sanitizedData['status_id'] = $data['status_id'] ?? 1; // Ativo
        $sanitizedData['register_id'] = $this->getAuthenticatedUser()['id'];
        
        try {
            $userId = $this->userModel->create($sanitizedData);
            
            // Log da criação
            Security::logSecurityEvent('user_created_via_api', [
                'created_user_id' => $userId,
                'created_by' => $this->getAuthenticatedUser()['id']
            ]);
            
            $user = $this->userModel->findById($userId);
            unset($user['password'], $user['session_token'], $user['password_reset_token']);
            
            ApiResponse::success($user, 'Usuário criado com sucesso', ApiResponse::HTTP_CREATED);
            
        } catch (\Exception $e) {
            ApiResponse::error('Erro ao criar usuário: ' . $e->getMessage(), 
                             ApiResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /**
     * Atualiza usuário
     * 
     * @param int $id
     * @return void
     */
    public function update(int $id): void
    {
        if (!$this->hasPermission('users.update')) {
            ApiResponse::forbidden('Sem permissão para atualizar usuário');
            return;
        }
        
        $user = $this->userModel->findById($id);
        
        if (!$user) {
            ApiResponse::notFound('Usuário não encontrado');
            return;
        }
        
        $data = $this->getJsonInput();
        
        // Validação (campos opcionais para update)
        $errors = $this->validateInput($data, [
            'name' => [
                'type' => 'length',
                'options' => ['min' => 2, 'max' => 255],
                'message' => 'Nome deve ter entre 2 e 255 caracteres'
            ],
            'email' => [
                'type' => 'email',
                'message' => 'Email inválido'
            ]
        ]);
        
        if (!empty($errors)) {
            ApiResponse::validation($errors);
            return;
        }
        
        // Sanitiza dados
        $allowedFields = ['name', 'email', 'cpf', 'phone_mobile', 'status_id', 'level_id'];
        $updateData = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = Security::sanitizeInput($data[$field]);
            }
        }
        
        // Verifica se email já existe (exceto o próprio usuário)
        if (isset($updateData['email'])) {
            $existingUser = $this->userModel->findByEmail($updateData['email']);
            if ($existingUser && $existingUser['id'] != $id) {
                ApiResponse::error('Email já está em uso', ApiResponse::HTTP_CONFLICT);
                return;
            }
        }
        
        // Atualiza senha se fornecida
        if (!empty($data['password'])) {
            if (strlen($data['password']) < 6) {
                ApiResponse::validation(['password' => 'Senha deve ter pelo menos 6 caracteres']);
                return;
            }
            $updateData['password'] = Security::hashPassword($data['password']);
        }
        
        try {
            $this->userModel->update($id, $updateData);
            
            // Log da atualização
            Security::logSecurityEvent('user_updated_via_api', [
                'updated_user_id' => $id,
                'updated_by' => $this->getAuthenticatedUser()['id'],
                'fields' => array_keys($updateData)
            ]);
            
            $updatedUser = $this->userModel->findById($id);
            unset($updatedUser['password'], $updatedUser['session_token'], $updatedUser['password_reset_token']);
            
            ApiResponse::success($updatedUser, 'Usuário atualizado com sucesso');
            
        } catch (\Exception $e) {
            ApiResponse::error('Erro ao atualizar usuário: ' . $e->getMessage(), 
                             ApiResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /**
     * Remove usuário (soft delete)
     * 
     * @param int $id
     * @return void
     */
    public function destroy(int $id): void
    {
        if (!$this->hasPermission('users.delete')) {
            ApiResponse::forbidden('Sem permissão para excluir usuário');
            return;
        }
        
        $user = $this->userModel->findById($id);
        
        if (!$user) {
            ApiResponse::notFound('Usuário não encontrado');
            return;
        }
        
        // Não permite excluir o próprio usuário
        $currentUser = $this->getAuthenticatedUser();
        if ($currentUser['id'] == $id) {
            ApiResponse::error('Não é possível excluir o próprio usuário', ApiResponse::HTTP_BAD_REQUEST);
            return;
        }
        
        try {
            $this->userModel->softDelete($id);
            
            // Log da exclusão
            Security::logSecurityEvent('user_deleted_via_api', [
                'deleted_user_id' => $id,
                'deleted_by' => $currentUser['id']
            ]);
            
            ApiResponse::success(null, 'Usuário excluído com sucesso', ApiResponse::HTTP_NO_CONTENT);
            
        } catch (\Exception $e) {
            ApiResponse::error('Erro ao excluir usuário: ' . $e->getMessage(), 
                             ApiResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}