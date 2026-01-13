<?php
/**
 * Controller de documentação da API
 * 
 * @package App\Controllers\Api
 * @author Sistema Administrativo MVC
 */

namespace App\Controllers\Api;

use App\Core\ApiResponse;
use App\Core\Version;

class DocsApiController
{
    /**
     * Documentação da API em formato OpenAPI 3.0
     * 
     * @return void
     */
    public function openapi(): void
    {
        $spec = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'Sistema Administrativo MVC - API',
                'description' => 'API REST completa para o sistema administrativo escolar',
                'version' => Version::get(),
                'contact' => [
                    'name' => 'Sistema Administrativo MVC',
                    'url' => $_ENV['APP_URL'] ?? 'http://localhost'
                ]
            ],
            'servers' => [
                [
                    'url' => ($_ENV['APP_URL'] ?? 'http://localhost') . '/api',
                    'description' => 'Servidor da API'
                ]
            ],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT'
                    ]
                ],
                'schemas' => [
                    'Error' => [
                        'type' => 'object',
                        'properties' => [
                            'success' => ['type' => 'boolean', 'example' => false],
                            'message' => ['type' => 'string'],
                            'timestamp' => ['type' => 'string', 'format' => 'date-time'],
                            'version' => ['type' => 'string'],
                            'errors' => ['type' => 'object']
                        ]
                    ],
                    'Success' => [
                        'type' => 'object',
                        'properties' => [
                            'success' => ['type' => 'boolean', 'example' => true],
                            'message' => ['type' => 'string'],
                            'data' => ['type' => 'object'],
                            'timestamp' => ['type' => 'string', 'format' => 'date-time'],
                            'version' => ['type' => 'string']
                        ]
                    ],
                    'User' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'name' => ['type' => 'string'],
                            'username' => ['type' => 'string'],
                            'email' => ['type' => 'string', 'format' => 'email'],
                            'cpf' => ['type' => 'string'],
                            'phone_mobile' => ['type' => 'string'],
                            'level_id' => ['type' => 'integer'],
                            'status_id' => ['type' => 'integer'],
                            'last_access' => ['type' => 'string', 'format' => 'date-time'],
                            'dh' => ['type' => 'string', 'format' => 'date-time']
                        ]
                    ],
                    'SchoolSubject' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'name' => ['type' => 'string'],
                            'translate' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'status_id' => ['type' => 'integer'],
                            'dh' => ['type' => 'string', 'format' => 'date-time']
                        ]
                    ],
                    'SchoolTeam' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'serie_id' => ['type' => 'integer'],
                            'period_id' => ['type' => 'integer'],
                            'education_id' => ['type' => 'integer'],
                            'status_id' => ['type' => 'integer'],
                            'public_link_enabled' => ['type' => 'boolean'],
                            'public_link_token' => ['type' => 'string'],
                            'public_link_expires_at' => ['type' => 'string', 'format' => 'date'],
                            'dh' => ['type' => 'string', 'format' => 'date-time']
                        ]
                    ]
                ]
            ],
            'paths' => [
                '/auth/login' => [
                    'post' => [
                        'tags' => ['Authentication'],
                        'summary' => 'Login do usuário',
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['username', 'password'],
                                        'properties' => [
                                            'username' => ['type' => 'string'],
                                            'password' => ['type' => 'string']
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Login realizado com sucesso',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'allOf' => [
                                                ['$ref' => '#/components/schemas/Success'],
                                                [
                                                    'type' => 'object',
                                                    'properties' => [
                                                        'data' => [
                                                            'type' => 'object',
                                                            'properties' => [
                                                                'access_token' => ['type' => 'string'],
                                                                'refresh_token' => ['type' => 'string'],
                                                                'token_type' => ['type' => 'string', 'example' => 'Bearer'],
                                                                'expires_in' => ['type' => 'integer', 'example' => 3600],
                                                                'user' => ['$ref' => '#/components/schemas/User']
                                                            ]
                                                        ]
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            '401' => [
                                'description' => 'Credenciais inválidas',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/Error']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                '/users' => [
                    'get' => [
                        'tags' => ['Users'],
                        'summary' => 'Lista usuários',
                        'security' => [['bearerAuth' => []]],
                        'parameters' => [
                            [
                                'name' => 'page',
                                'in' => 'query',
                                'schema' => ['type' => 'integer', 'default' => 1]
                            ],
                            [
                                'name' => 'per_page',
                                'in' => 'query',
                                'schema' => ['type' => 'integer', 'default' => 20, 'maximum' => 100]
                            ],
                            [
                                'name' => 'search',
                                'in' => 'query',
                                'schema' => ['type' => 'string']
                            ]
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Lista de usuários',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'allOf' => [
                                                ['$ref' => '#/components/schemas/Success'],
                                                [
                                                    'type' => 'object',
                                                    'properties' => [
                                                        'data' => [
                                                            'type' => 'array',
                                                            'items' => ['$ref' => '#/components/schemas/User']
                                                        ],
                                                        'meta' => [
                                                            'type' => 'object',
                                                            'properties' => [
                                                                'pagination' => [
                                                                    'type' => 'object',
                                                                    'properties' => [
                                                                        'current_page' => ['type' => 'integer'],
                                                                        'per_page' => ['type' => 'integer'],
                                                                        'total' => ['type' => 'integer'],
                                                                        'total_pages' => ['type' => 'integer'],
                                                                        'has_next' => ['type' => 'boolean'],
                                                                        'has_prev' => ['type' => 'boolean']
                                                                    ]
                                                                ]
                                                            ]
                                                        ]
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'post' => [
                        'tags' => ['Users'],
                        'summary' => 'Cria novo usuário',
                        'security' => [['bearerAuth' => []]],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['name', 'username', 'email', 'password'],
                                        'properties' => [
                                            'name' => ['type' => 'string'],
                                            'username' => ['type' => 'string'],
                                            'email' => ['type' => 'string', 'format' => 'email'],
                                            'password' => ['type' => 'string', 'minLength' => 6],
                                            'cpf' => ['type' => 'string'],
                                            'phone_mobile' => ['type' => 'string'],
                                            'level_id' => ['type' => 'integer'],
                                            'status_id' => ['type' => 'integer']
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'responses' => [
                            '201' => [
                                'description' => 'Usuário criado com sucesso',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'allOf' => [
                                                ['$ref' => '#/components/schemas/Success'],
                                                [
                                                    'type' => 'object',
                                                    'properties' => [
                                                        'data' => ['$ref' => '#/components/schemas/User']
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        
        header('Content-Type: application/json');
        echo json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Documentação em HTML (Swagger UI)
     * 
     * @return void
     */
    public function swagger(): void
    {
        $html = '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation - Sistema Administrativo MVC</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui.css" />
    <style>
        html { box-sizing: border-box; overflow: -moz-scrollbars-vertical; overflow-y: scroll; }
        *, *:before, *:after { box-sizing: inherit; }
        body { margin:0; background: #fafafa; }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            const ui = SwaggerUIBundle({
                url: "' . ($_ENV['APP_URL'] ?? 'http://localhost') . '/api/docs/openapi.json",
                dom_id: "#swagger-ui",
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout"
            });
        };
    </script>
</body>
</html>';
        
        echo $html;
    }
    
    /**
     * Informações gerais da API
     * 
     * @return void
     */
    public function info(): void
    {
        ApiResponse::success([
            'name' => 'Sistema Administrativo MVC - API',
            'version' => Version::get(),
            'description' => 'API REST completa para gerenciamento escolar',
            'endpoints' => [
                'authentication' => '/api/auth/*',
                'users' => '/api/users/*',
                'subjects' => '/api/subjects/*',
                'teams' => '/api/teams/*',
                'documentation' => '/api/docs/*'
            ],
            'features' => [
                'JWT Authentication',
                'Rate Limiting',
                'Input Validation',
                'Security Headers',
                'CORS Support',
                'Pagination',
                'Error Handling',
                'Audit Logging'
            ],
            'security' => [
                'HTTPS Recommended',
                'JWT Token Required',
                'Rate Limiting Active',
                'Input Sanitization',
                'SQL Injection Protection',
                'XSS Protection'
            ]
        ], 'Informações da API');
    }
}