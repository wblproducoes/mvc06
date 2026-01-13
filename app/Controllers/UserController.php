<?php
/**
 * Controller de usuários
 * 
 * @package App\Controllers
 * @author Sistema Administrativo MVC
 */

namespace App\Controllers;

use App\Models\User;
use App\Models\Gender;
use App\Models\Level;
use App\Models\Status;

class UserController extends BaseController
{
    private User $userModel;
    private Gender $genderModel;
    private Level $levelModel;
    private Status $statusModel;
    
    /**
     * Construtor do UserController
     */
    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
        $this->genderModel = new Gender();
        $this->levelModel = new Level();
        $this->statusModel = new Status();
    }
    
    /**
     * Lista todos os usuários
     * 
     * @return void
     */
    public function index(): void
    {
        $page = (int)($_GET['page'] ?? 1);
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $search = $_GET['search'] ?? '';
        $levelFilter = $_GET['level'] ?? '';
        $statusFilter = $_GET['status'] ?? '';
        
        $conditions = [];
        if (!empty($search)) {
            // Implementar busca por nome, email, username
        }
        if (!empty($levelFilter)) {
            $conditions['level_id'] = $levelFilter;
        }
        if (!empty($statusFilter)) {
            $conditions['status_id'] = $statusFilter;
        }
        
        $users = $this->userModel->findWithRelations($conditions, 'u.name ASC', $limit);
        $total = $this->userModel->count($conditions);
        
        $data = [
            'titulo' => 'Usuários',
            'users' => $users,
            'levels' => $this->levelModel->findAllActive(),
            'statuses' => $this->statusModel->findAllActive(),
            'pagination' => [
                'current' => $page,
                'total' => ceil($total / $limit),
                'limit' => $limit
            ],
            'filters' => [
                'search' => $search,
                'level' => $levelFilter,
                'status' => $statusFilter
            ]
        ];
        
        $this->render('users/index.twig', $data);
    }
    
    /**
     * Exibe formulário de criação
     * 
     * @return void
     */
    public function create(): void
    {
        $data = [
            'titulo' => 'Novo Usuário',
            'genders' => $this->genderModel->findAllActive(),
            'levels' => $this->levelModel->findAllActive(),
            'statuses' => $this->statusModel->findAllActive()
        ];
        
        $this->render('users/create.twig', $data);
    }
    
    /**
     * Processa criação do usuário
     * 
     * @return void
     */
    public function store(): void
    {
        if (!$this->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->addFlashMessage('error', 'Token CSRF inválido');
            $this->redirect('/usuarios/criar');
            return;
        }
        
        $data = [
            'name' => $_POST['name'] ?? '',
            'alias' => $_POST['alias'] ?? null,
            'email' => $_POST['email'] ?? '',
            'cpf' => $_POST['cpf'] ?? null,
            'birth_date' => $_POST['birth_date'] ?? null,
            'gender_id' => $_POST['gender_id'] ?? null,
            'phone_home' => $_POST['phone_home'] ?? null,
            'phone_mobile' => $_POST['phone_mobile'] ?? null,
            'phone_message' => $_POST['phone_message'] ?? null,
            'username' => $_POST['username'] ?? '',
            'password' => password_hash($_POST['password'] ?? '', PASSWORD_BCRYPT),
            'level_id' => $_POST['level_id'] ?? 11,
            'status_id' => $_POST['status_id'] ?? 1,
            'unique_code' => $this->userModel->generateUniqueCode()
        ];
        
        // Validações
        $errors = $this->validateUserData($data);
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addFlashMessage('error', $error);
            }
            $this->redirect('/usuarios/criar');
            return;
        }
        
        try {
            $userId = $this->userModel->create($data);
            $this->addFlashMessage('success', 'Usuário criado com sucesso!');
            $this->redirect('/usuarios/' . $userId);
        } catch (\Exception $e) {
            $this->addFlashMessage('error', 'Erro ao criar usuário: ' . $e->getMessage());
            $this->redirect('/usuarios/criar');
        }
    }
    
    /**
     * Exibe detalhes do usuário
     * 
     * @param int $id
     * @return void
     */
    public function show(int $id): void
    {
        $users = $this->userModel->findWithRelations(['u.id' => $id]);
        $user = $users[0] ?? null;
        
        if (!$user) {
            $this->addFlashMessage('error', 'Usuário não encontrado');
            $this->redirect('/usuarios');
            return;
        }
        
        $data = [
            'titulo' => 'Detalhes do Usuário',
            'user' => $user
        ];
        
        $this->render('users/show.twig', $data);
    }
    
    /**
     * Exibe formulário de edição
     * 
     * @param int $id
     * @return void
     */
    public function edit(int $id): void
    {
        $user = $this->userModel->findById($id);
        
        if (!$user) {
            $this->addFlashMessage('error', 'Usuário não encontrado');
            $this->redirect('/usuarios');
            return;
        }
        
        $data = [
            'titulo' => 'Editar Usuário',
            'user' => $user,
            'genders' => $this->genderModel->findAllActive(),
            'levels' => $this->levelModel->findAllActive(),
            'statuses' => $this->statusModel->findAllActive()
        ];
        
        $this->render('users/edit.twig', $data);
    }
    
    /**
     * Processa atualização do usuário
     * 
     * @param int $id
     * @return void
     */
    public function update(int $id): void
    {
        if (!$this->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->addFlashMessage('error', 'Token CSRF inválido');
            $this->redirect('/usuarios/' . $id . '/editar');
            return;
        }
        
        $user = $this->userModel->findById($id);
        if (!$user) {
            $this->addFlashMessage('error', 'Usuário não encontrado');
            $this->redirect('/usuarios');
            return;
        }
        
        $data = [
            'name' => $_POST['name'] ?? '',
            'alias' => $_POST['alias'] ?? null,
            'email' => $_POST['email'] ?? '',
            'cpf' => $_POST['cpf'] ?? null,
            'birth_date' => $_POST['birth_date'] ?? null,
            'gender_id' => $_POST['gender_id'] ?? null,
            'phone_home' => $_POST['phone_home'] ?? null,
            'phone_mobile' => $_POST['phone_mobile'] ?? null,
            'phone_message' => $_POST['phone_message'] ?? null,
            'username' => $_POST['username'] ?? '',
            'level_id' => $_POST['level_id'] ?? 11,
            'status_id' => $_POST['status_id'] ?? 1
        ];
        
        // Atualiza senha apenas se fornecida
        if (!empty($_POST['password'])) {
            $data['password'] = password_hash($_POST['password'], PASSWORD_BCRYPT);
        }
        
        // Validações
        $errors = $this->validateUserData($data, $id);
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addFlashMessage('error', $error);
            }
            $this->redirect('/usuarios/' . $id . '/editar');
            return;
        }
        
        try {
            $this->userModel->update($id, $data);
            $this->addFlashMessage('success', 'Usuário atualizado com sucesso!');
            $this->redirect('/usuarios/' . $id);
        } catch (\Exception $e) {
            $this->addFlashMessage('error', 'Erro ao atualizar usuário: ' . $e->getMessage());
            $this->redirect('/usuarios/' . $id . '/editar');
        }
    }
    
    /**
     * Soft delete do usuário
     * 
     * @param int $id
     * @return void
     */
    public function delete(int $id): void
    {
        if (!$this->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->addFlashMessage('error', 'Token CSRF inválido');
            $this->redirect('/usuarios');
            return;
        }
        
        try {
            $this->userModel->softDelete($id);
            $this->addFlashMessage('success', 'Usuário excluído com sucesso!');
        } catch (\Exception $e) {
            $this->addFlashMessage('error', 'Erro ao excluir usuário: ' . $e->getMessage());
        }
        
        $this->redirect('/usuarios');
    }
    
    /**
     * Valida dados do usuário
     * 
     * @param array $data
     * @param int|null $excludeId
     * @return array
     */
    private function validateUserData(array $data, ?int $excludeId = null): array
    {
        $errors = [];
        
        if (empty($data['name'])) {
            $errors[] = 'Nome é obrigatório';
        }
        
        if (empty($data['email'])) {
            $errors[] = 'Email é obrigatório';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email inválido';
        } elseif ($this->userModel->emailExists($data['email'], $excludeId)) {
            $errors[] = 'Este email já está em uso';
        }
        
        if (empty($data['username'])) {
            $errors[] = 'Username é obrigatório';
        } elseif ($this->userModel->usernameExists($data['username'], $excludeId)) {
            $errors[] = 'Este username já está em uso';
        }
        
        if (!empty($data['cpf']) && !$this->validateCpf($data['cpf'])) {
            $errors[] = 'CPF inválido';
        }
        
        return $errors;
    }
    
    /**
     * Valida CPF
     * 
     * @param string $cpf
     * @return bool
     */
    private function validateCpf(string $cpf): bool
    {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        
        if (strlen($cpf) != 11) {
            return false;
        }
        
        // Verifica se todos os dígitos são iguais
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }
        
        // Calcula os dígitos verificadores
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }
        
        return true;
    }
}