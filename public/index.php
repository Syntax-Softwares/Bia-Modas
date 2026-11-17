<?php

require_once __DIR__ . '/../core/html.php';
require_once __DIR__ . '/../core/sessao.php';
require_once __DIR__ . '/../core/validacao.php';
require_once __DIR__ . '/../core/rate_limit.php';
require_once __DIR__ . '/../core/router.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../repositories/perfil_repository.php';
require_once __DIR__ . '/../repositories/endereco_repository.php';
require_once __DIR__ . '/../controllers/home_controller.php';
require_once __DIR__ . '/../controllers/perfil_controller.php';

date_default_timezone_set(getenv('TZ') ?: 'America/Sao_Paulo');

Sessao::iniciar();

$pdo = Database::conenct();

$homeController = new HomeController($pdo);
$perfilController = new PerfilController($pdo);

$router = new Router();

$router->get('/', [$homeController, 'index']);

// MVC06 - perfil, enderecos e seguranca
$router->get('/perfil', [$perfilController, 'index']);
$router->get('/perfil/enderecos', [$perfilController, 'enderecos']);
$router->get('/perfil/seguranca', [$perfilController, 'seguranca']);

$router->post('/perfil/dados', [$perfilController, 'atualizarDados']);
$router->post('/perfil/senha', [$perfilController, 'alterarSenha']);
$router->post('/perfil/sair', [$perfilController, 'sair']);

$router->post('/perfil/enderecos/criar', [$perfilController, 'criarEndereco']);
$router->post('/perfil/enderecos/atualizar', [$perfilController, 'atualizarEndereco']);
$router->post('/perfil/enderecos/excluir', [$perfilController, 'excluirEndereco']);
$router->post('/perfil/enderecos/principal', [$perfilController, 'definirPrincipal']);

try {
    $caminho = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $caminho);
} catch (Throwable $excecao) {
    // Nunca exibe stack trace ou dados do banco para o usuario final.
    error_log(
        '[app] ' . get_class($excecao)
        . ' codigo=' . $excecao->getCode()
        . ' em ' . $excecao->getFile() . ':' . $excecao->getLine()
    );

    http_response_code(500);
    $codigo = 500;
    $titulo = 'Erro interno';
    $mensagem = 'Nao foi possivel concluir a solicitacao agora. Tente novamente em instantes.';
    $voltar = '/';

    require __DIR__ . '/../views/erro.php';
}
