<?php
/**
 * Sistema de logs avançado
 * 
 * @package App\Core
 * @author Sistema Administrativo MVC
 */

namespace App\Core;

class Logger
{
    /**
     * Níveis de log (PSR-3 compliant)
     */
    public const EMERGENCY = 'emergency';
    public const ALERT = 'alert';
    public const CRITICAL = 'critical';
    public const ERROR = 'error';
    public const WARNING = 'warning';
    public const NOTICE = 'notice';
    public const INFO = 'info';
    public const DEBUG = 'debug';
    
    /**
     * Canais de log
     */
    public const CHANNEL_SYSTEM = 'system';
    public const CHANNEL_SECURITY = 'security';
    public const CHANNEL_API = 'api';
    public const CHANNEL_DATABASE = 'database';
    public const CHANNEL_AUTH = 'auth';
    public const CHANNEL_AUDIT = 'audit';
    public const CHANNEL_PERFORMANCE = 'performance';
    public const CHANNEL_ERROR = 'error';
    
    private string $logPath;
    private string $channel;
    private int $maxFileSize;
    private int $maxFiles;
    private bool $enabled;
    private array $processors;
    
    /**
     * Construtor
     * 
     * @param string $channel
     */
    public function __construct(string $channel = self::CHANNEL_SYSTEM)
    {
        $this->channel = $channel;
        $this->logPath = __DIR__ . '/../../storage/logs';
        $this->maxFileSize = (int)($_ENV['LOG_MAX_FILE_SIZE'] ?? 10485760); // 10MB
        $this->maxFiles = (int)($_ENV['LOG_MAX_FILES'] ?? 30);
        $this->enabled = ($_ENV['LOG_ENABLED'] ?? 'true') === 'true';
        $this->processors = [];
        
        $this->ensureLogDirectory();
    }
    
    /**
     * Log de emergência - sistema inutilizável
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    public function emergency(string $message, array $context = []): void
    {
        $this->log(self::EMERGENCY, $message, $context);
    }
    
    /**
     * Log de alerta - ação deve ser tomada imediatamente
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    public function alert(string $message, array $context = []): void
    {
        $this->log(self::ALERT, $message, $context);
    }
    
    /**
     * Log crítico - condições críticas
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    public function critical(string $message, array $context = []): void
    {
        $this->log(self::CRITICAL, $message, $context);
    }
    
    /**
     * Log de erro - erros de runtime que não requerem ação imediata
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    public function error(string $message, array $context = []): void
    {
        $this->log(self::ERROR, $message, $context);
    }
    
    /**
     * Log de aviso - ocorrências excepcionais que não são erros
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log(self::WARNING, $message, $context);
    }
    
    /**
     * Log de notificação - eventos normais mas significativos
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    public function notice(string $message, array $context = []): void
    {
        $this->log(self::NOTICE, $message, $context);
    }
    
    /**
     * Log informativo - eventos interessantes
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    public function info(string $message, array $context = []): void
    {
        $this->log(self::INFO, $message, $context);
    }
    
    /**
     * Log de debug - informações detalhadas de debug
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    public function debug(string $message, array $context = []): void
    {
        // Debug logs apenas em desenvolvimento
        if ($_ENV['APP_ENV'] === 'development' || $_ENV['APP_DEBUG'] === 'true') {
            $this->log(self::DEBUG, $message, $context);
        }
    }
    
    /**
     * Log genérico
     * 
     * @param string $level
     * @param string $message
     * @param array $context
     * @return void
     */
    public function log(string $level, string $message, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }
        
        $logEntry = $this->formatLogEntry($level, $message, $context);
        
        // Escreve no arquivo
        $this->writeToFile($logEntry);
        
        // Escreve no banco de dados se configurado
        if (($_ENV['LOG_TO_DATABASE'] ?? 'false') === 'true') {
            $this->writeToDatabase($level, $message, $context);
        }
        
        // Envia para serviços externos se configurado
        if (($_ENV['LOG_TO_EXTERNAL'] ?? 'false') === 'true') {
            $this->sendToExternalService($level, $message, $context);
        }
    }
    
    /**
     * Formata entrada do log
     * 
     * @param string $level
     * @param string $message
     * @param array $context
     * @return array
     */
    private function formatLogEntry(string $level, string $message, array $context): array
    {
        $entry = [
            'timestamp' => date('Y-m-d H:i:s.u'),
            'level' => strtoupper($level),
            'channel' => $this->channel,
            'message' => $message,
            'context' => $context,
            'extra' => [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'cli',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'cli',
                'request_uri' => $_SERVER['REQUEST_URI'] ?? 'cli',
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'cli',
                'user_id' => $_SESSION['user']['id'] ?? null,
                'session_id' => session_id() ?: null,
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'execution_time' => microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true))
            ]
        ];
        
        // Aplica processadores
        foreach ($this->processors as $processor) {
            $entry = $processor($entry);
        }
        
        return $entry;
    }
    
    /**
     * Escreve no arquivo de log
     * 
     * @param array $logEntry
     * @return void
     */
    private function writeToFile(array $logEntry): void
    {
        $filename = $this->getLogFilename();
        
        // Verifica rotação de logs
        $this->rotateLogsIfNeeded($filename);
        
        $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        
        file_put_contents($filename, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Escreve no banco de dados
     * 
     * @param string $level
     * @param string $message
     * @param array $context
     * @return void
     */
    private function writeToDatabase(string $level, string $message, array $context): void
    {
        try {
            $database = new Database();
            
            $data = [
                'level' => strtoupper($level),
                'channel' => $this->channel,
                'message' => $message,
                'context' => json_encode($context),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'cli',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'cli',
                'user_id' => $_SESSION['user']['id'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $database->insert('{prefix}system_logs', $data);
            
        } catch (\Exception $e) {
            // Falha silenciosa para evitar loops de log
            error_log("Failed to write log to database: " . $e->getMessage());
        }
    }
    
    /**
     * Envia para serviço externo
     * 
     * @param string $level
     * @param string $message
     * @param array $context
     * @return void
     */
    private function sendToExternalService(string $level, string $message, array $context): void
    {
        // Implementar integração com serviços como Sentry, LogStash, etc.
        $webhook = $_ENV['LOG_WEBHOOK_URL'] ?? null;
        
        if (!$webhook) {
            return;
        }
        
        $payload = [
            'timestamp' => date('c'),
            'level' => $level,
            'channel' => $this->channel,
            'message' => $message,
            'context' => $context,
            'server' => $_ENV['APP_URL'] ?? 'localhost',
            'environment' => $_ENV['APP_ENV'] ?? 'production'
        ];
        
        // Envio assíncrono para não bloquear a aplicação
        $this->sendWebhookAsync($webhook, $payload);
    }
    
    /**
     * Envia webhook de forma assíncrona
     * 
     * @param string $url
     * @param array $payload
     * @return void
     */
    private function sendWebhookAsync(string $url, array $payload): void
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'User-Agent: Sistema-Admin-Logger/1.0'
            ],
            CURLOPT_TIMEOUT => 5,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        curl_exec($ch);
        curl_close($ch);
    }
    
    /**
     * Retorna nome do arquivo de log
     * 
     * @return string
     */
    private function getLogFilename(): string
    {
        $date = date('Y-m-d');
        return "{$this->logPath}/{$this->channel}-{$date}.log";
    }
    
    /**
     * Rotaciona logs se necessário
     * 
     * @param string $filename
     * @return void
     */
    private function rotateLogsIfNeeded(string $filename): void
    {
        if (!file_exists($filename)) {
            return;
        }
        
        // Verifica tamanho do arquivo
        if (filesize($filename) >= $this->maxFileSize) {
            $this->rotateLog($filename);
        }
        
        // Remove arquivos antigos
        $this->cleanOldLogs();
    }
    
    /**
     * Rotaciona um arquivo de log
     * 
     * @param string $filename
     * @return void
     */
    private function rotateLog(string $filename): void
    {
        $timestamp = date('His');
        $rotatedName = str_replace('.log', "-{$timestamp}.log", $filename);
        
        rename($filename, $rotatedName);
        
        // Comprime o arquivo rotacionado
        if (function_exists('gzopen')) {
            $this->compressLogFile($rotatedName);
        }
    }
    
    /**
     * Comprime arquivo de log
     * 
     * @param string $filename
     * @return void
     */
    private function compressLogFile(string $filename): void
    {
        $compressedName = $filename . '.gz';
        
        $file = fopen($filename, 'rb');
        $gz = gzopen($compressedName, 'wb9');
        
        while (!feof($file)) {
            gzwrite($gz, fread($file, 8192));
        }
        
        fclose($file);
        gzclose($gz);
        
        unlink($filename);
    }
    
    /**
     * Remove logs antigos
     * 
     * @return void
     */
    private function cleanOldLogs(): void
    {
        $files = glob("{$this->logPath}/{$this->channel}-*.log*");
        
        if (count($files) <= $this->maxFiles) {
            return;
        }
        
        // Ordena por data de modificação
        usort($files, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });
        
        // Remove arquivos mais antigos
        $filesToRemove = array_slice($files, 0, count($files) - $this->maxFiles);
        
        foreach ($filesToRemove as $file) {
            unlink($file);
        }
    }
    
    /**
     * Garante que o diretório de logs existe
     * 
     * @return void
     */
    private function ensureLogDirectory(): void
    {
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }
    
    /**
     * Adiciona processador de log
     * 
     * @param callable $processor
     * @return void
     */
    public function addProcessor(callable $processor): void
    {
        $this->processors[] = $processor;
    }
    
    /**
     * Cria logger para canal específico
     * 
     * @param string $channel
     * @return static
     */
    public static function channel(string $channel): static
    {
        return new static($channel);
    }
    
    /**
     * Logger de performance
     * 
     * @param string $operation
     * @param float $startTime
     * @param array $context
     * @return void
     */
    public function performance(string $operation, float $startTime, array $context = []): void
    {
        $executionTime = microtime(true) - $startTime;
        
        $context = array_merge($context, [
            'execution_time' => $executionTime,
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true)
        ]);
        
        $level = $executionTime > 1.0 ? self::WARNING : self::INFO;
        
        $this->log($level, "Performance: {$operation}", $context);
    }
    
    /**
     * Logger de query SQL
     * 
     * @param string $query
     * @param array $params
     * @param float $executionTime
     * @return void
     */
    public function sqlQuery(string $query, array $params = [], float $executionTime = 0): void
    {
        $context = [
            'query' => $query,
            'params' => $params,
            'execution_time' => $executionTime
        ];
        
        $level = $executionTime > 0.1 ? self::WARNING : self::DEBUG;
        
        Logger::channel(self::CHANNEL_DATABASE)->log($level, 'SQL Query executed', $context);
    }
    
    /**
     * Logger de exceções
     * 
     * @param \Throwable $exception
     * @param array $context
     * @return void
     */
    public function exception(\Throwable $exception, array $context = []): void
    {
        $context = array_merge($context, [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
        
        $this->error('Exception occurred', $context);
    }
}