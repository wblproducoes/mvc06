<?php
/**
 * Sistema de roteamento da aplicação
 * 
 * @package App\Core
 * @author Sistema Administrativo MVC
 */

namespace App\Core;

class Router
{
    private array $routes = [];
    private array $middleware = [];
    
    /**
     * Adiciona uma rota GET
     * 
     * @param string $path
     * @param string $controller
     * @param string $action
     * @return self
     */
    public function get(string $path, string $controller, string $action = 'index'): self
    {
        $this->addRoute('GET', $path, $controller, $action);
        return $this;
    }
    
    /**
     * Adiciona uma rota POST
     * 
     * @param string $path
     * @param string $controller
     * @param string $action
     * @return self
     */
    public function post(string $path, string $controller, string $action): self
    {
        $this->addRoute('POST', $path, $controller, $action);
        return $this;
    }
    
    /**
     * Adiciona middleware a uma rota
     * 
     * @param string $middleware
     * @return self
     */
    public function middleware(string $middleware): self
    {
        $lastRoute = array_key_last($this->routes);
        if ($lastRoute !== null) {
            $this->routes[$lastRoute]['middleware'][] = $middleware;
        }
        return $this;
    }
    
    /**
     * Adiciona uma rota ao sistema
     * 
     * @param string $method
     * @param string $path
     * @param string $controller
     * @param string $action
     * @return void
     */
    private function addRoute(string $method, string $path, string $controller, string $action): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'controller' => $controller,
            'action' => $action,
            'middleware' => []
        ];
    }
    
    /**
     * Resolve a rota atual
     * 
     * @return void
     */
    public function resolve(): void
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        foreach ($this->routes as $route) {
            if ($this->matchRoute($route, $requestMethod, $requestPath)) {
                $this->executeRoute($route, $requestPath);
                return;
            }
        }
        
        // Rota não encontrada
        $this->handleNotFound();
    }
    
    /**
     * Verifica se a rota corresponde à requisição
     * 
     * @param array $route
     * @param string $requestMethod
     * @param string $requestPath
     * @return bool
     */
    private function matchRoute(array $route, string $requestMethod, string $requestPath): bool
    {
        if ($route['method'] !== $requestMethod) {
            return false;
        }
        
        $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $route['path']);
        $pattern = '#^' . $pattern . '$#';
        
        return preg_match($pattern, $requestPath);
    }
    
    /**
     * Executa a rota encontrada
     * 
     * @param array $route
     * @param string $requestPath
     * @return void
     */
    private function executeRoute(array $route, string $requestPath): void
    {
        // Executa middleware
        foreach ($route['middleware'] as $middlewareClass) {
            $middleware = new $middlewareClass();
            if (!$middleware->handle()) {
                return;
            }
        }
        
        // Extrai parâmetros da URL
        $params = $this->extractParams($route['path'], $requestPath);
        
        // Instancia e executa o controller
        $controllerClass = 'App\\Controllers\\' . $route['controller'];
        $controller = new $controllerClass();
        $action = $route['action'];
        
        call_user_func_array([$controller, $action], $params);
    }
    
    /**
     * Extrai parâmetros da URL
     * 
     * @param string $routePath
     * @param string $requestPath
     * @return array
     */
    private function extractParams(string $routePath, string $requestPath): array
    {
        $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';
        
        preg_match($pattern, $requestPath, $matches);
        array_shift($matches); // Remove o match completo
        
        return $matches;
    }
    
    /**
     * Trata rota não encontrada
     * 
     * @return void
     */
    private function handleNotFound(): void
    {
        http_response_code(404);
        echo '<h1>404 - Página não encontrada</h1>';
    }
}