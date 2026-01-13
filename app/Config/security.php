<?php
/**
 * Configurações de segurança do sistema
 * 
 * @package App\Config
 * @author Sistema Administrativo MVC
 */

return [
    // Configurações de senha
    'password' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_symbols' => true,
        'max_age_days' => 90,
        'history_count' => 5, // Não reutilizar últimas 5 senhas
    ],
    
    // Configurações de sessão
    'session' => [
        'timeout' => 3600, // 1 hora
        'regenerate_interval' => 1800, // 30 minutos
        'check_ip' => true,
        'check_user_agent' => true,
    ],
    
    // Configurações de login
    'login' => [
        'max_attempts' => 5,
        'lockout_time' => 900, // 15 minutos
        'rate_limit_requests' => 5,
        'rate_limit_window' => 900, // 15 minutos
    ],
    
    // Configurações de CSRF
    'csrf' => [
        'token_lifetime' => 3600, // 1 hora
        'regenerate_on_use' => false,
    ],
    
    // Configurações de criptografia
    'encryption' => [
        'algorithm' => 'AES-256-CBC',
        'key_rotation_days' => 30,
    ],
    
    // Configurações de auditoria
    'audit' => [
        'enabled' => true,
        'log_all_actions' => true,
        'retention_days' => 365,
        'sensitive_fields' => [
            'password', 'senha', 'token', 'secret', 'key',
            'google_access_token', 'google_refresh_token'
        ],
    ],
    
    // Configurações de rate limiting
    'rate_limiting' => [
        'general' => ['requests' => 100, 'window' => 3600],
        'login' => ['requests' => 5, 'window' => 900],
        'api' => ['requests' => 1000, 'window' => 3600],
        'password_reset' => ['requests' => 3, 'window' => 3600],
    ],
    
    // Headers de segurança
    'headers' => [
        'x_frame_options' => 'DENY',
        'x_content_type_options' => 'nosniff',
        'x_xss_protection' => '1; mode=block',
        'referrer_policy' => 'strict-origin-when-cross-origin',
        'permissions_policy' => 'geolocation=(), microphone=(), camera=()',
        'hsts_max_age' => 31536000, // 1 ano
    ],
    
    // Content Security Policy
    'csp' => [
        'default_src' => "'self'",
        'script_src' => "'self' 'unsafe-inline' https://cdn.jsdelivr.net",
        'style_src' => "'self' 'unsafe-inline' https://cdn.jsdelivr.net",
        'img_src' => "'self' data: https:",
        'font_src' => "'self' https://cdn.jsdelivr.net",
        'connect_src' => "'self'",
        'frame_ancestors' => "'none'",
        'base_uri' => "'self'",
        'form_action' => "'self'",
    ],
    
    // Configurações de upload
    'upload' => [
        'max_file_size' => 10485760, // 10MB
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'],
        'scan_for_malware' => true,
        'quarantine_suspicious' => true,
    ],
    
    // Configurações de IP
    'ip' => [
        'whitelist' => [], // IPs permitidos (vazio = todos)
        'blacklist' => [], // IPs bloqueados
        'check_proxy_headers' => true,
        'trusted_proxies' => [], // IPs de proxies confiáveis
    ],
    
    // Configurações de detecção de ameaças
    'threat_detection' => [
        'sql_injection' => true,
        'xss_detection' => true,
        'suspicious_user_agents' => true,
        'brute_force_detection' => true,
        'session_hijacking_detection' => true,
    ],
    
    // Configurações de backup de segurança
    'backup' => [
        'encrypt_backups' => true,
        'backup_logs' => true,
        'retention_days' => 30,
    ],
    
    // Configurações de notificações de segurança
    'notifications' => [
        'security_events' => true,
        'failed_logins' => true,
        'suspicious_activity' => true,
        'admin_emails' => ['admin@sistema.com'],
    ],
];