<?php
/**
 * Classe para padronizar respostas da API
 * 
 * @package App\Core
 * @author Sistema Administrativo MVC
 */

namespace App\Core;

class ApiResponse
{
    /**
     * Códigos de status HTTP
     */
    public const HTTP_OK = 200;
    public const HTTP_CREATED = 201;
    public const HTTP_ACCEPTED = 202;
    public const HTTP_NO_CONTENT = 204;
    public const HTTP_BAD_REQUEST = 400;
    public const HTTP_UNAUTHORIZED = 401;
    public const HTTP_FORBIDDEN = 403;
    public const HTTP_NOT_FOUND = 404;
    public const HTTP_METHOD_NOT_ALLOWED = 405;
    public const HTTP_CONFLICT = 409;
    public const HTTP_UNPROCESSABLE_ENTITY = 422;
    public const HTTP_TOO_MANY_REQUESTS = 429;
    public const HTTP_INTERNAL_SERVER_ERROR = 500;
    
    /**
     * Resposta de sucesso
     * 
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @param array $meta
     * @return void
     */
    public static function success($data = null, string $message = 'Success', int $statusCode = self::HTTP_OK, array $meta = []): void
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c'),
            'version' => \App\Core\Version::get()
        ];
        
        if (!empty($meta)) {
            $response['meta'] = $meta;
        }
        
        self::sendResponse($response, $statusCode);
    }
    
    /**
     * Resposta de erro
     * 
     * @param string $message
     * @param int $statusCode
     * @param array $errors
     * @param mixed $debug
     * @return void
     */
    public static function error(string $message = 'Error', int $statusCode = self::HTTP_BAD_REQUEST, array $errors = [], $debug = null): void
    {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => date('c'),
            'version' => \App\Core\Version::get()
        ];
        
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        
        if ($debug !== null && $_ENV['APP_DEBUG'] === 'true') {
            $response['debug'] = $debug;
        }
        
        self::sendResponse($response, $statusCode);
    }
    
    /**
     * Resposta paginada
     * 
     * @param array $data
     * @param int $total
     * @param int $page
     * @param int $perPage
     * @param string $message
     * @return void
     */
    public static function paginated(array $data, int $total, int $page, int $perPage, string $message = 'Success'): void
    {
        $totalPages = ceil($total / $perPage);
        
        $meta = [
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ]
        ];
        
        self::success($data, $message, self::HTTP_OK, $meta);
    }
    
    /**
     * Resposta de validação
     * 
     * @param array $errors
     * @param string $message
     * @return void
     */
    public static function validation(array $errors, string $message = 'Validation failed'): void
    {
        self::error($message, self::HTTP_UNPROCESSABLE_ENTITY, $errors);
    }
    
    /**
     * Resposta não autorizada
     * 
     * @param string $message
     * @return void
     */
    public static function unauthorized(string $message = 'Unauthorized'): void
    {
        self::error($message, self::HTTP_UNAUTHORIZED);
    }
    
    /**
     * Resposta proibida
     * 
     * @param string $message
     * @return void
     */
    public static function forbidden(string $message = 'Forbidden'): void
    {
        self::error($message, self::HTTP_FORBIDDEN);
    }
    
    /**
     * Resposta não encontrada
     * 
     * @param string $message
     * @return void
     */
    public static function notFound(string $message = 'Not found'): void
    {
        self::error($message, self::HTTP_NOT_FOUND);
    }
    
    /**
     * Resposta de rate limit
     * 
     * @param string $message
     * @param int $retryAfter
     * @return void
     */
    public static function rateLimit(string $message = 'Too many requests', int $retryAfter = 60): void
    {
        header("Retry-After: $retryAfter");
        self::error($message, self::HTTP_TOO_MANY_REQUESTS);
    }
    
    /**
     * Envia resposta JSON
     * 
     * @param array $response
     * @param int $statusCode
     * @return void
     */
    private static function sendResponse(array $response, int $statusCode): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        
        // Headers CORS se necessário
        if (isset($_ENV['API_CORS_ENABLED']) && $_ENV['API_CORS_ENABLED'] === 'true') {
            $allowedOrigins = $_ENV['API_CORS_ORIGINS'] ?? '*';
            header("Access-Control-Allow-Origin: $allowedOrigins");
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        }
        
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Resposta para OPTIONS (CORS preflight)
     * 
     * @return void
     */
    public static function options(): void
    {
        if (isset($_ENV['API_CORS_ENABLED']) && $_ENV['API_CORS_ENABLED'] === 'true') {
            $allowedOrigins = $_ENV['API_CORS_ORIGINS'] ?? '*';
            header("Access-Control-Allow-Origin: $allowedOrigins");
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
            header('Access-Control-Max-Age: 86400'); // 24 horas
        }
        
        http_response_code(204);
        exit;
    }
}