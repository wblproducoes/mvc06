<?php
/**
 * Controller base para API
 * 
 * @package App\Controllers\Api
 * @author Sistema Administrativo MVC
 */

namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\Security;
use App\Core\ApiResponse;

abstract class BaseApiController
{
    protected Database $database;
    
    public function __construct()
    {
        $this->database = new Database();
        $this->validateApiRequest();
    }
    
    /**
     * Valida requisição da API
     * 
     * @return void
     */
    private function validateApiRequest(): void
    {
        // Verifica se é requisição JSON
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if ($_SERVER['REQUEST_METHOD'] !== 'GET' && !str_contains($contentType, 'application/json')) {
            ApiResponse::error('Content-Type deve ser application/json', ApiResponse::HTTP_BAD_REQUEST);
            return;
        }
        
        // Detecta tentativas de ataque
        $input = file_get_contents('php://input');
        if ($input && Security::detectSqlInjection($input)) {
            Security::logSecurityEvent('api_sql_injection_attempt', [
                'input' => substr($input, 0, 500),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            ApiResponse::error('Requisição suspeita detectada', ApiResponse::HTTP_BAD_REQUEST);
            return;
        }
        
        if ($input && Security::detectXss($input)) {
            Security::logSecurityEvent('api_xss_attempt', [
                'input' => substr($input, 0, 500),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            ApiResponse::error('Requisição suspeita detectada', ApiResponse::HTTP_BAD_REQUEST);
            return;
        }
    }
    
    /**
     * Retorna dados JSON de entrada
     * 
     * @return array
     */
    protected function getJsonInput(): array
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            ApiResponse::error('JSON inválido', ApiResponse::HTTP_BAD_REQUEST);
            return [];
        }
        
        return $data ?? [];
    }
    
    /**
     * Sanitiza dados de entrada
     * 
     * @param array $data
     * @param array $rules
     * @return array
     */
    protected function sanitizeInput(array $data, array $rules = []): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            $type = $rules[$key] ?? 'string';
            $sanitized[$key] = Security::sanitizeInput($value, $type);
        }
        
        return $sanitized;
    }
    
    /**
     * Valida dados de entrada
     * 
     * @param array $data
     * @param array $rules
     * @return array Retorna array de erros (vazio se válido)
     */
    protected function validateInput(array $data, array $rules): array
    {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            
            // Verifica campos obrigatórios
            if (isset($rule['required']) && $rule['required'] && empty($value)) {
                $errors[$field] = "Campo {$field} é obrigatório";
                continue;
            }
            
            // Se campo não é obrigatório e está vazio, pula validação
            if (empty($value) && (!isset($rule['required']) || !$rule['required'])) {
                continue;
            }
            
            // Validações específicas
            if (isset($rule['type'])) {
                $options = $rule['options'] ?? [];
                if (!Security::validateInput($value, $rule['type'], $options)) {
                    $errors[$field] = $rule['message'] ?? "Campo {$field} é inválido";
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Retorna usuário autenticado da API
     * 
     * @return array|null
     */
    protected function getAuthenticatedUser(): ?array
    {
        return $_SESSION['api_user'] ?? null;
    }
    
    /**
     * Verifica se usuário tem permissão
     * 
     * @param string $permission
     * @return bool
     */
    protected function hasPermission(string $permission): bool
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return false;
        }
        
        // Admin tem todas as permissões
        if ($user['level_id'] == 1) {
            return true;
        }
        
        // Implementar lógica de permissões específicas aqui
        return true;
    }
    
    /**
     * Aplica paginação nos resultados
     * 
     * @param string $query
     * @param array $params
     * @param int $page
     * @param int $perPage
     * @return array
     */
    protected function paginate(string $query, array $params = [], int $page = 1, int $perPage = 20): array
    {
        // Limita per_page
        $perPage = min($perPage, 100);
        $page = max($page, 1);
        
        // Query para contar total
        $countQuery = preg_replace('/SELECT .* FROM/i', 'SELECT COUNT(*) as total FROM', $query);
        $countQuery = preg_replace('/ORDER BY .*/i', '', $countQuery);
        
        $totalResult = $this->database->query($countQuery, $params);
        $total = $totalResult[0]['total'] ?? 0;
        
        // Query com LIMIT
        $offset = ($page - 1) * $perPage;
        $query .= " LIMIT {$offset}, {$perPage}";
        
        $data = $this->database->query($query, $params);
        
        return [
            'data' => $data,
            'total' => (int)$total,
            'page' => $page,
            'per_page' => $perPage
        ];
    }
}