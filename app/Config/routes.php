<?php
/**
 * Definição das rotas da aplicação
 * 
 * @package App\Config
 * @author Sistema Administrativo MVC
 */

use App\Middleware\AuthMiddleware;

// Rotas públicas (sem autenticação)
$router->get('/', 'HomeController', 'index');
$router->get('/login', 'AuthController', 'login');
$router->post('/login', 'AuthController', 'processLogin');
$router->get('/logout', 'AuthController', 'logout');
$router->get('/forgot-password', 'AuthController', 'forgotPassword');
$router->post('/forgot-password', 'AuthController', 'processForgotPassword');
$router->get('/reset-password', 'AuthController', 'resetPassword');
$router->post('/reset-password', 'AuthController', 'processResetPassword');

// Rotas protegidas (requerem autenticação)
$router->get('/dashboard', 'HomeController', 'index')
    ->middleware(AuthMiddleware::class);

// Rotas de usuários
$router->get('/usuarios', 'UserController', 'index')
    ->middleware(AuthMiddleware::class);

$router->get('/usuarios/criar', 'UserController', 'create')
    ->middleware(AuthMiddleware::class);

$router->post('/usuarios/criar', 'UserController', 'store')
    ->middleware(AuthMiddleware::class);

$router->get('/usuarios/{id}', 'UserController', 'show')
    ->middleware(AuthMiddleware::class);

$router->get('/usuarios/{id}/editar', 'UserController', 'edit')
    ->middleware(AuthMiddleware::class);

$router->post('/usuarios/{id}/editar', 'UserController', 'update')
    ->middleware(AuthMiddleware::class);

$router->post('/usuarios/{id}/deletar', 'UserController', 'delete')
    ->middleware(AuthMiddleware::class);

// Rotas de matérias escolares
$router->get('/materias', 'SchoolSubjectController', 'index')
    ->middleware(AuthMiddleware::class);

$router->get('/materias/criar', 'SchoolSubjectController', 'create')
    ->middleware(AuthMiddleware::class);

$router->post('/materias/criar', 'SchoolSubjectController', 'store')
    ->middleware(AuthMiddleware::class);

$router->get('/materias/{id}', 'SchoolSubjectController', 'show')
    ->middleware(AuthMiddleware::class);

$router->get('/materias/{id}/editar', 'SchoolSubjectController', 'edit')
    ->middleware(AuthMiddleware::class);

$router->post('/materias/{id}/editar', 'SchoolSubjectController', 'update')
    ->middleware(AuthMiddleware::class);

$router->post('/materias/{id}/deletar', 'SchoolSubjectController', 'delete')
    ->middleware(AuthMiddleware::class);

// Rotas de turmas escolares
$router->get('/turmas', 'SchoolTeamController', 'index')
    ->middleware(AuthMiddleware::class);

$router->get('/turmas/criar', 'SchoolTeamController', 'create')
    ->middleware(AuthMiddleware::class);

$router->post('/turmas/criar', 'SchoolTeamController', 'store')
    ->middleware(AuthMiddleware::class);

$router->get('/turmas/{id}', 'SchoolTeamController', 'show')
    ->middleware(AuthMiddleware::class);

$router->get('/turmas/{id}/editar', 'SchoolTeamController', 'edit')
    ->middleware(AuthMiddleware::class);

$router->post('/turmas/{id}/editar', 'SchoolTeamController', 'update')
    ->middleware(AuthMiddleware::class);

$router->post('/turmas/{id}/link-publico', 'SchoolTeamController', 'togglePublicLink')
    ->middleware(AuthMiddleware::class);

$router->post('/turmas/{id}/deletar', 'SchoolTeamController', 'delete')
    ->middleware(AuthMiddleware::class);

// Rota pública para visualizar turma
$router->get('/turma/{token}', 'SchoolTeamController', 'publicView');

$router->get('/perfil', 'PerfilController', 'index')
    ->middleware(AuthMiddleware::class);

$router->post('/perfil', 'PerfilController', 'update')
    ->middleware(AuthMiddleware::class);

$router->post('/perfil/senha', 'PerfilController', 'updatePassword')
    ->middleware(AuthMiddleware::class);

// Rotas sobre/versão
$router->get('/sobre', 'AboutController', 'index')
    ->middleware(AuthMiddleware::class);

$router->get('/api/version', 'AboutController', 'apiVersion');

// Rotas de relatórios
$router->get('/relatorios', 'RelatorioController', 'index')
    ->middleware(AuthMiddleware::class);

$router->get('/relatorios/usuarios', 'RelatorioController', 'usuarios')
    ->middleware(AuthMiddleware::class);

$router->get('/relatorios/usuarios/pdf', 'RelatorioController', 'usuariosPdf')
    ->middleware(AuthMiddleware::class);

// ========================================
// API ROUTES (REST API)
// ========================================

use App\Middleware\ApiAuthMiddleware;

// API Authentication (public routes)
$router->post('/api/auth/login', 'Api\\AuthApiController', 'login');
$router->post('/api/auth/refresh', 'Api\\AuthApiController', 'refresh');
$router->post('/api/auth/logout', 'Api\\AuthApiController', 'logout')
    ->middleware(ApiAuthMiddleware::class);
$router->get('/api/auth/me', 'Api\\AuthApiController', 'me')
    ->middleware(ApiAuthMiddleware::class);

// API Users
$router->get('/api/users', 'Api\\UserApiController', 'index')
    ->middleware(ApiAuthMiddleware::class);
$router->post('/api/users', 'Api\\UserApiController', 'store')
    ->middleware(ApiAuthMiddleware::class);
$router->get('/api/users/{id}', 'Api\\UserApiController', 'show')
    ->middleware(ApiAuthMiddleware::class);
$router->put('/api/users/{id}', 'Api\\UserApiController', 'update')
    ->middleware(ApiAuthMiddleware::class);
$router->delete('/api/users/{id}', 'Api\\UserApiController', 'destroy')
    ->middleware(ApiAuthMiddleware::class);

// API School Subjects
$router->get('/api/subjects', 'Api\\SchoolSubjectApiController', 'index')
    ->middleware(ApiAuthMiddleware::class);
$router->post('/api/subjects', 'Api\\SchoolSubjectApiController', 'store')
    ->middleware(ApiAuthMiddleware::class);
$router->get('/api/subjects/{id}', 'Api\\SchoolSubjectApiController', 'show')
    ->middleware(ApiAuthMiddleware::class);
$router->put('/api/subjects/{id}', 'Api\\SchoolSubjectApiController', 'update')
    ->middleware(ApiAuthMiddleware::class);
$router->delete('/api/subjects/{id}', 'Api\\SchoolSubjectApiController', 'destroy')
    ->middleware(ApiAuthMiddleware::class);

// API School Teams
$router->get('/api/teams', 'Api\\SchoolTeamApiController', 'index')
    ->middleware(ApiAuthMiddleware::class);
$router->post('/api/teams', 'Api\\SchoolTeamApiController', 'store')
    ->middleware(ApiAuthMiddleware::class);
$router->get('/api/teams/{id}', 'Api\\SchoolTeamApiController', 'show')
    ->middleware(ApiAuthMiddleware::class);
$router->put('/api/teams/{id}', 'Api\\SchoolTeamApiController', 'update')
    ->middleware(ApiAuthMiddleware::class);
$router->delete('/api/teams/{id}', 'Api\\SchoolTeamApiController', 'destroy')
    ->middleware(ApiAuthMiddleware::class);
$router->post('/api/teams/{id}/public-link', 'Api\\SchoolTeamApiController', 'togglePublicLink')
    ->middleware(ApiAuthMiddleware::class);
$router->get('/api/teams/{id}/schedules', 'Api\\SchoolTeamApiController', 'schedules')
    ->middleware(ApiAuthMiddleware::class);

// API System Info
$router->get('/api/version', 'AboutController', 'apiVersion');
$router->get('/api/docs', 'Api\\DocsApiController', 'swagger');
$router->get('/api/docs/openapi.json', 'Api\\DocsApiController', 'openapi');
$router->get('/api/info', 'Api\\DocsApiController', 'info');
$router->options('/api/{path:.*}', function() {
    \App\Core\ApiResponse::options();
});