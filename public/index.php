<?php

require_once __DIR__ . '/../core/html.php';
require_once __DIR__ . '/../core/sessao.php';
require_once __DIR__ . '/../core/catalogo.php';
require_once __DIR__ . '/../core/rate_limit.php';
require_once __DIR__ . '/../core/router.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../repositories/produto_repository.php';
require_once __DIR__ . '/../repositories/catalogo_repository.php';
require_once __DIR__ . '/../controllers/home_controller.php';
require_once __DIR__ . '/../controllers/catalogo_controller.php';

date_default_timezone_set(getenv('TZ') ?: 'America/Sao_Paulo');

Sessao::iniciar();

$pdo = Database::conenct();

$homeController = new HomeController($pdo);
$catalogoController = new CatalogoController($pdo);

$router = new Router();

$router->get('/', [$homeController, 'index']);

// MVC07 - catalogo, busca, filtros e detalhe
$router->get('/categoria', [$catalogoController, 'index']);
$router->get('/produto', [$catalogoController, 'produto']);

$metodo = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if (!in_array($metodo, ['GET', 'POST'], true)) {
    http_response_code(405);
    header('Allow: GET, POST');
    $codigo = 405;
    $titulo = 'Método não permitido';
    $mensagem = 'Esta página não aceita o método de requisição informado.';
    $voltar = '/categoria';
    require __DIR__ . '/../views/erro.php';
    exit;
}

try {
    $caminho = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $router->dispatch($metodo, $caminho);
} catch (Throwable $excecao) {
    // Nunca exibe stack trace ou dados do banco para o usuario final.
    error_log(
        '[catalogo] ' . get_class($excecao)
        . ' codigo=' . $excecao->getCode()
        . ' em ' . $excecao->getFile() . ':' . $excecao->getLine()
    );

    http_response_code(500);
    $codigo = 500;
    $titulo = 'Erro interno';
    $mensagem = 'Não foi possível concluir a solicitação agora. Tente novamente em instantes.';
    $voltar = '/categoria';
    require __DIR__ . '/../views/erro.php';
}
