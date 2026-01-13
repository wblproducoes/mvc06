<?php
/**
 * Sistema de autenticação JWT para API
 * 
 * @package App\Core
 * @author Sistema Administrativo MVC
 */

namespace App\Core;

class JwtAuth
{
    private const ALGORITHM = 'HS256';
    private const TOKEN_EXPIRY = 3600; // 1 hora
    private const REFRESH_TOKEN_EXPIRY = 604800; // 7 dias
    
    private string $secretKey;
    
    public function __construct()
    {
        $this->secretKey = $_ENV['JWT_SECRET'] ?? 'default-secret-key-change-in-production';
    }
    
    /**
     * Gera token JWT
     * 
     * @param array $payload
     * @param int $expiry
     * @return string
     */
    public function generateToken(array $payload, int $expiry = null): string
    {
        $expiry = $expiry ?? time() + self::TOKEN_EXPIRY;
        
        $header = [
            'typ' => 'JWT',
            'alg' => self::ALGORITHM
        ];
        
        $payload = array_merge($payload, [
            'iat' => time(),
            'exp' => $expiry,
            'iss' => $_ENV['APP_URL'] ?? 'localhost'
        ]);
        
        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));
        
        $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $this->secretKey, true);
        $signatureEncoded = $this->base64UrlEncode($signature);
        
        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }
    
    /**
     * Valida e decodifica token JWT
     * 
     * @param string $token
     * @return array|false
     */
    public function validateToken(string $token)
    {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return false;
        }
        
        [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;
        
        // Verifica assinatura
        $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $this->secretKey, true);
        $expectedSignature = $this->base64UrlEncode($signature);
        
        if (!hash_equals($expectedSignature, $signatureEncoded)) {
            return false;
        }
        
        $payload = json_decode($this->base64UrlDecode($payloadEncoded), true);
        
        // Verifica expiração
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }
        
        return $payload;
    }
    
    /**
     * Gera refresh token
     * 
     * @param int $userId
     * @return string
     */
    public function generateRefreshToken(int $userId): string
    {
        $payload = [
            'user_id' => $userId,
            'type' => 'refresh'
        ];
        
        return $this->generateToken($payload, time() + self::REFRESH_TOKEN_EXPIRY);
    }
    
    /**
     * Extrai token do header Authorization
     * 
     * @return string|null
     */
    public function extractTokenFromHeader(): ?string
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }
        
        return substr($authHeader, 7);
    }
    
    /**
     * Codifica em base64 URL-safe
     * 
     * @param string $data
     * @return string
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Decodifica base64 URL-safe
     * 
     * @param string $data
     * @return string
     */
    private function base64UrlDecode(string $data): string
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
}