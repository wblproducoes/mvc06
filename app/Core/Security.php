<?php
/**
 * Classe central de segurança do sistema
 * 
 * @package App\Core
 * @author Sistema Administrativo MVC
 */

namespace App\Core;

class Security
{
    /**
     * Configurações de segurança
     */
    private const HASH_ALGORITHM = 'sha256';
    private const ENCRYPTION_METHOD = 'AES-256-CBC';
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCKOUT_TIME = 900; // 15 minutos
    private const SESSION_TIMEOUT = 3600; // 1 hora
    
    /**
     * Inicializa configurações de segurança
     * 
     * @return void
     */
    public static function initialize(): void
    {
        // Configurações de sessão seguras
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', $_ENV['APP_ENV'] === 'production' ? 1 : 0);
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', 1);
        ini_set('session.gc_maxlifetime', self::SESSION_TIMEOUT);
        ini_set('session.name', 'SECURE_SESSION_ID');
        
        // Headers de segurança
        self::setSecurityHeaders();
        
        // Regenera ID da sessão periodicamente
        self::regenerateSessionId();
    }
    
    /**
     * Define headers de segurança
     * 
     * @return void
     */
    public static function setSecurityHeaders(): void
    {
        // Previne XSS
        header('X-XSS-Protection: 1; mode=block');
        
        // Previne MIME sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Previne clickjacking
        header('X-Frame-Options: DENY');
        
        // Política de referrer
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Content Security Policy
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; " .
               "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; " .
               "img-src 'self' data: https:; " .
               "font-src 'self' https://cdn.jsdelivr.net; " .
               "connect-src 'self'; " .
               "frame-ancestors 'none'; " .
               "base-uri 'self'; " .
               "form-action 'self'";
        header("Content-Security-Policy: $csp");
        
        // HSTS (apenas em produção)
        if ($_ENV['APP_ENV'] === 'production') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
        
        // Permissions Policy
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    }
    
    /**
     * Regenera ID da sessão se necessário
     * 
     * @return void
     */
    public static function regenerateSessionId(): void
    {
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        }
        
        // Regenera a cada 30 minutos
        if (time() - $_SESSION['last_regeneration'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
    
    /**
     * Valida e sanitiza entrada de dados
     * 
     * @param mixed $data
     * @param string $type
     * @return mixed
     */
    public static function sanitizeInput($data, string $type = 'string')
    {
        if (is_array($data)) {
            return array_map(function($item) use ($type) {
                return self::sanitizeInput($item, $type);
            }, $data);
        }
        
        // Remove caracteres nulos
        $data = str_replace(chr(0), '', $data);
        
        switch ($type) {
            case 'email':
                return filter_var($data, FILTER_SANITIZE_EMAIL);
                
            case 'int':
                return filter_var($data, FILTER_SANITIZE_NUMBER_INT);
                
            case 'float':
                return filter_var($data, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                
            case 'url':
                return filter_var($data, FILTER_SANITIZE_URL);
                
            case 'html':
                return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                
            case 'sql':
                return addslashes($data);
                
            case 'filename':
                return preg_replace('/[^a-zA-Z0-9._-]/', '', $data);
                
            case 'alphanumeric':
                return preg_replace('/[^a-zA-Z0-9]/', '', $data);
                
            default: // string
                return filter_var($data, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        }
    }
    
    /**
     * Valida entrada de dados
     * 
     * @param mixed $data
     * @param string $type
     * @param array $options
     * @return bool
     */
    public static function validateInput($data, string $type, array $options = []): bool
    {
        switch ($type) {
            case 'email':
                return filter_var($data, FILTER_VALIDATE_EMAIL) !== false;
                
            case 'int':
                $min = $options['min'] ?? null;
                $max = $options['max'] ?? null;
                $flags = 0;
                $filterOptions = [];
                
                if ($min !== null) {
                    $filterOptions['min_range'] = $min;
                }
                if ($max !== null) {
                    $filterOptions['max_range'] = $max;
                }
                
                return filter_var($data, FILTER_VALIDATE_INT, [
                    'options' => $filterOptions
                ]) !== false;
                
            case 'float':
                return filter_var($data, FILTER_VALIDATE_FLOAT) !== false;
                
            case 'url':
                return filter_var($data, FILTER_VALIDATE_URL) !== false;
                
            case 'ip':
                return filter_var($data, FILTER_VALIDATE_IP) !== false;
                
            case 'regex':
                return isset($options['pattern']) && preg_match($options['pattern'], $data);
                
            case 'length':
                $min = $options['min'] ?? 0;
                $max = $options['max'] ?? PHP_INT_MAX;
                $length = strlen($data);
                return $length >= $min && $length <= $max;
                
            case 'required':
                return !empty($data);
                
            default:
                return true;
        }
    }
    
    /**
     * Gera hash seguro para senhas
     * 
     * @param string $password
     * @return string
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, // 64 MB
            'time_cost' => 4,       // 4 iterações
            'threads' => 3          // 3 threads
        ]);
    }
    
    /**
     * Verifica senha
     * 
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
    
    /**
     * Gera token seguro
     * 
     * @param int $length
     * @return string
     */
    public static function generateSecureToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Criptografa dados sensíveis
     * 
     * @param string $data
     * @param string $key
     * @return string
     */
    public static function encrypt(string $data, string $key): string
    {
        $iv = random_bytes(openssl_cipher_iv_length(self::ENCRYPTION_METHOD));
        $encrypted = openssl_encrypt($data, self::ENCRYPTION_METHOD, $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Descriptografa dados
     * 
     * @param string $encryptedData
     * @param string $key
     * @return string|false
     */
    public static function decrypt(string $encryptedData, string $key)
    {
        $data = base64_decode($encryptedData);
        $ivLength = openssl_cipher_iv_length(self::ENCRYPTION_METHOD);
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);
        return openssl_decrypt($encrypted, self::ENCRYPTION_METHOD, $key, 0, $iv);
    }
    
    /**
     * Verifica tentativas de login
     * 
     * @param string $identifier
     * @return bool
     */
    public static function checkLoginAttempts(string $identifier): bool
    {
        $key = 'login_attempts_' . hash(self::HASH_ALGORITHM, $identifier);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'last_attempt' => 0];
        }
        
        $attempts = $_SESSION[$key];
        
        // Reset se passou do tempo de lockout
        if (time() - $attempts['last_attempt'] > self::LOCKOUT_TIME) {
            $_SESSION[$key] = ['count' => 0, 'last_attempt' => 0];
            return true;
        }
        
        return $attempts['count'] < self::MAX_LOGIN_ATTEMPTS;
    }
    
    /**
     * Registra tentativa de login
     * 
     * @param string $identifier
     * @param bool $success
     * @return void
     */
    public static function recordLoginAttempt(string $identifier, bool $success): void
    {
        $key = 'login_attempts_' . hash(self::HASH_ALGORITHM, $identifier);
        
        if ($success) {
            unset($_SESSION[$key]);
        } else {
            if (!isset($_SESSION[$key])) {
                $_SESSION[$key] = ['count' => 0, 'last_attempt' => 0];
            }
            
            $_SESSION[$key]['count']++;
            $_SESSION[$key]['last_attempt'] = time();
        }
    }
    
    /**
     * Verifica se IP está na whitelist
     * 
     * @param string $ip
     * @return bool
     */
    public static function isIpWhitelisted(string $ip): bool
    {
        $whitelist = explode(',', $_ENV['IP_WHITELIST'] ?? '');
        $whitelist = array_map('trim', $whitelist);
        
        if (empty($whitelist[0])) {
            return true; // Sem whitelist configurada
        }
        
        foreach ($whitelist as $allowedIp) {
            if ($ip === $allowedIp || self::ipInRange($ip, $allowedIp)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Verifica se IP está em um range
     * 
     * @param string $ip
     * @param string $range
     * @return bool
     */
    private static function ipInRange(string $ip, string $range): bool
    {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }
        
        list($subnet, $mask) = explode('/', $range);
        
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return (ip2long($ip) & ~((1 << (32 - $mask)) - 1)) === ip2long($subnet);
        }
        
        // IPv6 support would go here
        return false;
    }
    
    /**
     * Log de segurança
     * 
     * @param string $event
     * @param array $context
     * @return void
     */
    public static function logSecurityEvent(string $event, array $context = []): void
    {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'user_id' => $_SESSION['user']['id'] ?? null,
            'context' => $context
        ];
        
        $logFile = __DIR__ . '/../../storage/logs/security.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Detecta tentativas de SQL Injection
     * 
     * @param string $input
     * @return bool
     */
    public static function detectSqlInjection(string $input): bool
    {
        $patterns = [
            '/(\bunion\b.*\bselect\b)/i',
            '/(\bselect\b.*\bfrom\b)/i',
            '/(\binsert\b.*\binto\b)/i',
            '/(\bupdate\b.*\bset\b)/i',
            '/(\bdelete\b.*\bfrom\b)/i',
            '/(\bdrop\b.*\btable\b)/i',
            '/(\bexec\b|\bexecute\b)/i',
            '/(\bscript\b.*\>/i',
            '/(\'|\"|;|--|\#|\*|\|)/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Detecta tentativas de XSS
     * 
     * @param string $input
     * @return bool
     */
    public static function detectXss(string $input): bool
    {
        $patterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
            '/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/mi',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<object\b[^<]*(?:(?!<\/object>)<[^<]*)*<\/object>/mi',
            '/<embed\b[^>]*>/i',
            '/<applet\b[^<]*(?:(?!<\/applet>)<[^<]*)*<\/applet>/mi'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Rate limiting
     * 
     * @param string $action
     * @param int $maxRequests
     * @param int $timeWindow
     * @return bool
     */
    public static function rateLimit(string $action, int $maxRequests = 60, int $timeWindow = 3600): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'rate_limit_' . $action . '_' . hash(self::HASH_ALGORITHM, $ip);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'window_start' => time()];
        }
        
        $rateData = $_SESSION[$key];
        
        // Reset se passou da janela de tempo
        if (time() - $rateData['window_start'] > $timeWindow) {
            $_SESSION[$key] = ['count' => 1, 'window_start' => time()];
            return true;
        }
        
        if ($rateData['count'] >= $maxRequests) {
            return false;
        }
        
        $_SESSION[$key]['count']++;
        return true;
    }
}