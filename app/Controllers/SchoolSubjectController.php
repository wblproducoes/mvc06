<?php
/**
 * Controller de matérias escolares
 * 
 * @package App\Controllers
 * @author Sistema Administrativo MVC
 */

namespace App\Controllers;

use App\Models\SchoolSubject;
use App\Models\Status;

class SchoolSubjectController extends BaseController
{
    private SchoolSubject $subjectModel;
    private Status $statusModel;
    
    /**
     * Construtor do SchoolSubjectController
     */
    public function __construct()
    {
        parent::__construct();
        $this->subjectModel = new SchoolSubject();
        $this->statusModel = new Status();
    }
    
    /**
     * Lista todas as matérias
     * 
     * @return void
     */
    public function index(): void
    {
        $search = $_GET['search'] ?? '';
        $statusFilter = $_GET['status'] ?? '';
        
        $conditions = [];
        if (!empty($statusFilter)) {
            $conditions['s.status_id'] = $statusFilter;
        }
        
        $subjects = $this->subjectModel->findWithStatus($conditions);
        
        // Filtro de busca no PHP (pode ser otimizado para SQL)
        if (!empty($search)) {
            $subjects = array_filter($subjects, function($subject) use ($search) {
                return stripos($subject['name'], $search) !== false || 
                       stripos($subject['translate'], $search) !== false;
            });
        }
        
        $data = [
            'titulo' => 'Matérias Escolares',
            'subjects' => $subjects,
            'statuses' => $this->statusModel->findAllActive(),
            'filters' => [
                'search' => $search,
                'status' => $statusFilter
            ]
        ];
        
        $this->render('school-subjects/index.twig', $data);
    }
    
    /**
     * Exibe formulário de criação
     * 
     * @return void
     */
    public function create(): void
    {
        $data = [
            'titulo' => 'Nova Matéria',
            'statuses' => $this->statusModel->findAllActive()
        ];
        
        $this->render('school-subjects/create.twig', $data);
    }
    
    /**
     * Processa criação da matéria
     * 
     * @return void
     */
    public function store(): void
    {
        if (!$this->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->addFlashMessage('error', 'Token CSRF inválido');
            $this->redirect('/materias/criar');
            return;
        }
        
        $data = [
            'name' => $_POST['name'] ?? '',
            'translate' => $_POST['translate'] ?? '',
            'description' => $_POST['description'] ?? null,
            'status_id' => $_POST['status_id'] ?? 1
        ];
        
        // Validações
        $errors = $this->validateSubjectData($data);
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addFlashMessage('error', $error);
            }
            $this->redirect('/materias/criar');
            return;
        }
        
        try {
            $subjectId = $this->subjectModel->create($data);
            $this->addFlashMessage('success', 'Matéria criada com sucesso!');
            $this->redirect('/materias');
        } catch (\Exception $e) {
            $this->addFlashMessage('error', 'Erro ao criar matéria: ' . $e->getMessage());
            $this->redirect('/materias/criar');
        }
    }
    
    /**
     * Exibe detalhes da matéria
     * 
     * @param int $id
     * @return void
     */
    public function show(int $id): void
    {
        $subjects = $this->subjectModel->findWithStatus(['s.id' => $id]);
        $subject = $subjects[0] ?? null;
        
        if (!$subject) {
            $this->addFlashMessage('error', 'Matéria não encontrada');
            $this->redirect('/materias');
            return;
        }
        
        $teacherCount = $this->subjectModel->countTeachers($id);
        
        $data = [
            'titulo' => 'Detalhes da Matéria',
            'subject' => $subject,
            'teacher_count' => $teacherCount
        ];
        
        $this->render('school-subjects/show.twig', $data);
    }
    
    /**
     * Exibe formulário de edição
     * 
     * @param int $id
     * @return void
     */
    public function edit(int $id): void
    {
        $subject = $this->subjectModel->findById($id);
        
        if (!$subject) {
            $this->addFlashMessage('error', 'Matéria não encontrada');
            $this->redirect('/materias');
            return;
        }
        
        $data = [
            'titulo' => 'Editar Matéria',
            'subject' => $subject,
            'statuses' => $this->statusModel->findAllActive()
        ];
        
        $this->render('school-subjects/edit.twig', $data);
    }
    
    /**
     * Processa atualização da matéria
     * 
     * @param int $id
     * @return void
     */
    public function update(int $id): void
    {
        if (!$this->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->addFlashMessage('error', 'Token CSRF inválido');
            $this->redirect('/materias/' . $id . '/editar');
            return;
        }
        
        $subject = $this->subjectModel->findById($id);
        if (!$subject) {
            $this->addFlashMessage('error', 'Matéria não encontrada');
            $this->redirect('/materias');
            return;
        }
        
        $data = [
            'name' => $_POST['name'] ?? '',
            'translate' => $_POST['translate'] ?? '',
            'description' => $_POST['description'] ?? null,
            'status_id' => $_POST['status_id'] ?? 1
        ];
        
        // Validações
        $errors = $this->validateSubjectData($data, $id);
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addFlashMessage('error', $error);
            }
            $this->redirect('/materias/' . $id . '/editar');
            return;
        }
        
        try {
            $this->subjectModel->update($id, $data);
            $this->addFlashMessage('success', 'Matéria atualizada com sucesso!');
            $this->redirect('/materias/' . $id);
        } catch (\Exception $e) {
            $this->addFlashMessage('error', 'Erro ao atualizar matéria: ' . $e->getMessage());
            $this->redirect('/materias/' . $id . '/editar');
        }
    }
    
    /**
     * Soft delete da matéria
     * 
     * @param int $id
     * @return void
     */
    public function delete(int $id): void
    {
        if (!$this->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->addFlashMessage('error', 'Token CSRF inválido');
            $this->redirect('/materias');
            return;
        }
        
        try {
            $this->subjectModel->softDelete($id);
            $this->addFlashMessage('success', 'Matéria excluída com sucesso!');
        } catch (\Exception $e) {
            $this->addFlashMessage('error', 'Erro ao excluir matéria: ' . $e->getMessage());
        }
        
        $this->redirect('/materias');
    }
    
    /**
     * Valida dados da matéria
     * 
     * @param array $data
     * @param int|null $excludeId
     * @return array
     */
    private function validateSubjectData(array $data, ?int $excludeId = null): array
    {
        $errors = [];
        
        if (empty($data['name'])) {
            $errors[] = 'Nome da matéria é obrigatório';
        }
        
        if (empty($data['translate'])) {
            $errors[] = 'Nome traduzido é obrigatório';
        }
        
        // Verifica se o nome já existe
        $existing = $this->subjectModel->findByName($data['name']);
        if ($existing && (!$excludeId || $existing['id'] != $excludeId)) {
            $errors[] = 'Já existe uma matéria com este nome';
        }
        
        return $errors;
    }
}