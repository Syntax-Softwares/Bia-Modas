<?php

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/sessao.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/router.php';

require_once __DIR__ . '/../controllers/home_controller.php';
require_once __DIR__ . '/../controllers/favoritos_controller.php';
require_once __DIR__ . '/../controllers/carrinho_controller.php';

Sessao::iniciar();

$pdo = Database::connect();

$router = new Router();

$home = new HomeController($pdo);
$favoritos = new FavoritosController($pdo);
$carrinho = new CarrinhoController($pdo);

$router->get('/', [$home, 'index']);

$router->get('/favoritos', [$favoritos, 'index']);
$router->get('/favoritos/dados', [$favoritos, 'dados']);
$router->post('/favoritos/alternar', [$favoritos, 'alternar']);

$router->get('/carrinho', [$carrinho, 'index']);
$router->get('/carrinho/dados', [$carrinho, 'dados']);
$router->post('/carrinho/alternar', [$carrinho, 'alternar']);
$router->post('/carrinho/adicionar', [$carrinho, 'adicionar']);
$router->post('/carrinho/atualizar', [$carrinho, 'atualizar']);
$router->post('/carrinho/remover', [$carrinho, 'remover']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
