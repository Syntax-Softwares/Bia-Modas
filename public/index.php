<?php

if (!is_file(__DIR__ . '/../vendor/autoload.php')) {
    http_response_code(500);
    exit('Dependências PHP ausentes. Execute: docker compose run --rm composer');
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/router.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../controllers/home_controller.php';
require_once __DIR__ . '/../controllers/auth_controller.php';
require_once __DIR__ . '/../controllers/categoria_controller.php';
require_once __DIR__ . '/../controllers/produto_controller.php';
require_once __DIR__ . '/../controllers/favoritos_controller.php';
require_once __DIR__ . '/../controllers/recuperacao_senha_controller.php';

date_default_timezone_set(getenv('TZ') ?: 'America/Sao_Paulo');

Auth::start();

$pdo = Database::conenct();

$homeController = new HomeController($pdo);
$authController = new AuthController($pdo);
$categoriaController = new CategoriaController($pdo);
$produtoController = new ProdutoController($pdo);
$favoritosController = new FavoritosController($pdo);
$recuperacaoSenhaController = new RecuperacaoSenhaController($pdo);

$router = new Router();

$router->get('/', [$homeController, 'index']);

$router->get('/login', [$authController, 'showLogin']);
$router->post('/login', [$authController, 'login']);

$router->get('/registro', [$authController, 'showRegister']);
$router->post('/registro', [$authController, 'register']);

// Alias em português para o cadastro.
$router->get('/cadastro', [$authController, 'showRegister']);
$router->post('/cadastro', [$authController, 'register']);

$router->get('/conta', [$authController, 'account']);
$router->post('/logout', [$authController, 'logout']);

$router->get('/recuperar-senha', [$recuperacaoSenhaController, 'showSolicitar']);
$router->post('/recuperar-senha', [$recuperacaoSenhaController, 'solicitar']);

$router->get('/recuperar-senha/verificar', [$recuperacaoSenhaController, 'showVerificar']);
$router->post('/recuperar-senha/verificar', [$recuperacaoSenhaController, 'verificar']);

$router->post('/recuperar-senha/reenviar', [$recuperacaoSenhaController, 'reenviar']);

$router->get('/categoria', [$categoriaController, 'index']);
$router->get('/produto', [$produtoController, 'show']);
$router->get('/favoritos', [$favoritosController, 'index']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
