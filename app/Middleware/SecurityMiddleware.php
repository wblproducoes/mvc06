<?php
/**
 * Middleware de segurança avançado
 * 
 * @package App\Middleware
 * @author Sistema Administrativo MVC
 */

namespace App\Middleware;

use App\Core\Security;

class SecurityMiddleware
{
    /**
     * Executa verificações de segurança
     * 
     * @return bool
     */
    public function handle(): bool
    {
        // Verifica rate limiting
        if (!Security::rateLimit('general', 100, 3600)) {
            Security::logSecurityEvent('rate_limit_exceeded', [
                'action' => 'general',
                'url' => $_SERVER['REQUEST_URI'] ?? ''
            ]);
            
            http_response_code(429);
            echo json_encode(['error' => 'Too Many Requests']);
            exit;
        }
        
        // Verifica IP whitelist (se configurada)
        $clientIp = $this->getClientIp();
        if (!Security::isIpWhitelisted($clientIp)) {
            Security::logSecurityEvent('ip_blocked', [
                'ip' => $clientIp,
                'url' => $_SERVER['REQUEST_URI'] ?? ''
            ]);
            
            http_response_code(403);
            echo json_encode(['error' => 'Access Denied']);
            exit;
        }
        
        // Verifica tentativas de SQL Injection em todos os inputs
        $this->checkSqlInjection();
        
        // Verifica tentativas de XSS
        $this->checkXss();
        
        // Verifica User-Agent suspeito
        $this->checkUserAgent();
        
        // Verifica tamanho da requisição
        $this->checkRequestSize();
        
        return true;
    }
    
    /**
     * Obtém IP real do cliente
     * 
     * @return string
     */
    private function getClientIp(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load Balancer/Proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Verifica tentativas de SQL Injection
     * 
     * @return void
     */
    private function checkSqlInjection(): void
    {
        $inputs = array_merge($_GET, $_POST, $_COOKIE);
        
        foreach ($inputs as $key => $value) {
            if (is_string($value) && Security::detectSqlInjection($value)) {
                Security::logSecurityEvent('sql_injection_attempt', [
                    'field' => $key,
                    'value' => substr($value, 0, 100),
                    'url' => $_SERVER['REQUEST_URI'] ?? ''
                ]);
                
                http_response_code(400);
                echo json_encode(['error' => 'Invalid Request']);
                exit;
            }
        }
    }
    
    /**
     * Verifica tentativas de XSS
     * 
     * @return void
     */
    private function checkXss(): void
    {
        $inputs = array_merge($_GET, $_POST);
        
        foreach ($inputs as $key => $value) {
            if (is_string($value) && Security::detectXss($value)) {
                Security::logSecurityEvent('xss_attempt', [
                    'field' => $key,
                    'value' => substr($value, 0, 100),
                    'url' => $_SERVER['REQUEST_URI'] ?? ''
                ]);
                
                http_response_code(400);
                echo json_encode(['error' => 'Invalid Request']);
                exit;
            }
        }
    }
    
    /**
     * Verifica User-Agent suspeito
     * 
     * @return void
     */
    private function checkUserAgent(): void
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Lista de User-Agents suspeitos
        $suspiciousAgents = [
            'sqlmap',
            'nikto',
            'nessus',
            'openvas',
            'nmap',
            'masscan',
            'zap',
            'burp',
            'wget',
            'curl',
            'python-requests',
            'bot',
            'crawler',
            'spider'
        ];
        
        foreach ($suspiciousAgents as $agent) {
            if (stripos($userAgent, $agent) !== false) {
                Security::logSecurityEvent('suspicious_user_agent', [
                    'user_agent' => $userAgent,
                    'url' => $_SERVER['REQUEST_URI'] ?? ''
                ]);
                
                // Não bloqueia, apenas registra
                break;
            }
        }
        
        // Verifica se User-Agent está vazio
        if (empty($userAgent)) {
            Security::logSecurityEvent('empty_user_agent', [
                'url' => $_SERVER['REQUEST_URI'] ?? ''
            ]);
        }
    }
    
    /**
     * Verifica tamanho da requisição
     * 
     * @return void
     */
    private function checkRequestSize(): void
    {
        $maxSize = 10 * 1024 * 1024; // 10MB
        $contentLength = $_SERVER['CONTENT_LENGTH'] ?? 0;
        
        if ($contentLength > $maxSize) {
            Security::logSecurityEvent('request_too_large', [
                'size' => $contentLength,
                'max_size' => $maxSize,
                'url' => $_SERVER['REQUEST_URI'] ?? ''
            ]);
            
            http_response_code(413);
            echo json_encode(['error' => 'Request Too Large']);
            exit;
        }
    }
}