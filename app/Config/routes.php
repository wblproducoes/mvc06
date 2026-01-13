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

$router->get('/usuarios', 'UsuarioController', 'index')
    ->middleware(AuthMiddleware::class);

$router->get('/usuarios/criar', 'UsuarioController', 'create')
    ->middleware(AuthMiddleware::class);

$router->post('/usuarios/criar', 'UsuarioController', 'store')
    ->middleware(AuthMiddleware::class);

$router->get('/usuarios/{id}', 'UsuarioController', 'show')
    ->middleware(AuthMiddleware::class);

$router->get('/usuarios/{id}/editar', 'UsuarioController', 'edit')
    ->middleware(AuthMiddleware::class);

$router->post('/usuarios/{id}/editar', 'UsuarioController', 'update')
    ->middleware(AuthMiddleware::class);

$router->post('/usuarios/{id}/deletar', 'UsuarioController', 'delete')
    ->middleware(AuthMiddleware::class);

// Rotas de perfil
$router->get('/perfil', 'PerfilController', 'index')
    ->middleware(AuthMiddleware::class);

$router->post('/perfil', 'PerfilController', 'update')
    ->middleware(AuthMiddleware::class);

$router->post('/perfil/senha', 'PerfilController', 'updatePassword')
    ->middleware(AuthMiddleware::class);

// Rotas de relatórios
$router->get('/relatorios', 'RelatorioController', 'index')
    ->middleware(AuthMiddleware::class);

$router->get('/relatorios/usuarios', 'RelatorioController', 'usuarios')
    ->middleware(AuthMiddleware::class);

$router->get('/relatorios/usuarios/pdf', 'RelatorioController', 'usuariosPdf')
    ->middleware(AuthMiddleware::class);

// API Routes (JSON)
$router->get('/api/usuarios', 'Api\\UsuarioController', 'index')
    ->middleware(AuthMiddleware::class);

$router->post('/api/usuarios', 'Api\\UsuarioController', 'store')
    ->middleware(AuthMiddleware::class);

$router->get('/api/usuarios/{id}', 'Api\\UsuarioController', 'show')
    ->middleware(AuthMiddleware::class);

$router->post('/api/usuarios/{id}', 'Api\\UsuarioController', 'update')
    ->middleware(AuthMiddleware::class);

$router->post('/api/usuarios/{id}/delete', 'Api\\UsuarioController', 'delete')
    ->middleware(AuthMiddleware::class);