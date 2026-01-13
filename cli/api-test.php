<?php
/**
 * Script para testar a API REST
 * 
 * Uso: php cli/api-test.php [endpoint] [method] [data]
 * 
 * Exemplos:
 * php cli/api-test.php auth/login POST '{"username":"admin","password":"123456"}'
 * php cli/api-test.php users GET
 * php cli/api-test.php users/1 GET
 * 
 * @package CLI
 * @author Sistema Administrativo MVC
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Carrega variáveis de ambiente
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && !str_starts_with($line, '#')) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

class ApiTester
{
    private string $baseUrl;
    private ?string $token = null;
    
    public function __construct()
    {
        $this->baseUrl = ($_ENV['APP_URL'] ?? 'http://localhost:8000') . '/api';
    }
    
    /**
     * Executa teste da API
     * 
     * @param string $endpoint
     * @param string $method
     * @param string|null $data
     * @return void
     */
    public function test(string $endpoint, string $method = 'GET', ?string $data = null): void
    {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        
        echo "🚀 Testando API: {$method} {$url}\n";
        echo str_repeat('=', 60) . "\n";
        
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $this->getHeaders($data !== null),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            echo "📤 Dados enviados:\n{$data}\n\n";
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            echo "❌ Erro cURL: {$error}\n";
            return;
        }
        
        echo "📊 Status HTTP: {$httpCode}\n";
        echo "📥 Resposta:\n";
        
        $decodedResponse = json_decode($response, true);
        if ($decodedResponse) {
            echo json_encode($decodedResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            
            // Salva token se for login bem-sucedido
            if ($httpCode === 200 && isset($decodedResponse['data']['access_token'])) {
                $this->token = $decodedResponse['data']['access_token'];
                echo "\n🔑 Token salvo para próximas requisições\n";
            }
        } else {
            echo $response . "\n";
        }
        
        echo "\n" . str_repeat('=', 60) . "\n\n";
    }
    
    /**
     * Retorna headers para requisição
     * 
     * @param bool $hasData
     * @return array
     */
    private function getHeaders(bool $hasData = false): array
    {
        $headers = [
            'User-Agent: API-Tester/1.0',
            'Accept: application/json'
        ];
        
        if ($hasData) {
            $headers[] = 'Content-Type: application/json';
        }
        
        if ($this->token) {
            $headers[] = "Authorization: Bearer {$this->token}";
        }
        
        return $headers;
    }
    
    /**
     * Executa bateria de testes
     * 
     * @return void
     */
    public function runTestSuite(): void
    {
        echo "🧪 Executando bateria de testes da API\n";
        echo "====================================\n\n";
        
        // 1. Teste de informações da API
        $this->test('info');
        
        // 2. Teste de login
        $this->test('auth/login', 'POST', json_encode([
            'username' => 'admin',
            'password' => '123456'
        ]));
        
        // 3. Teste de usuário autenticado
        if ($this->token) {
            $this->test('auth/me');
            
            // 4. Teste de listagem de usuários
            $this->test('users?page=1&per_page=5');
            
            // 5. Teste de listagem de matérias
            $this->test('subjects');
            
            // 6. Teste de listagem de turmas
            $this->test('teams');
            
            // 7. Teste de documentação
            $this->test('docs/openapi.json');
        }
        
        echo "✅ Bateria de testes concluída!\n";
    }
}

// Execução do script
if ($argc < 2) {
    echo "📖 Uso: php api-test.php [endpoint|test-suite] [method] [data]\n\n";
    echo "Exemplos:\n";
    echo "  php api-test.php test-suite                    # Executa bateria completa\n";
    echo "  php api-test.php info                          # Informações da API\n";
    echo "  php api-test.php auth/login POST '{\"username\":\"admin\",\"password\":\"123456\"}'\n";
    echo "  php api-test.php users GET                     # Lista usuários\n";
    echo "  php api-test.php users/1 GET                   # Usuário específico\n";
    echo "  php api-test.php subjects GET                  # Lista matérias\n";
    echo "  php api-test.php teams GET                     # Lista turmas\n\n";
    exit(1);
}

$tester = new ApiTester();

if ($argv[1] === 'test-suite') {
    $tester->runTestSuite();
} else {
    $endpoint = $argv[1];
    $method = $argv[2] ?? 'GET';
    $data = $argv[3] ?? null;
    
    $tester->test($endpoint, $method, $data);
}