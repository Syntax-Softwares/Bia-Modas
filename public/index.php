<?php

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/html.php';
require_once __DIR__ . '/../core/sessao.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/validacao.php';
require_once __DIR__ . '/../core/rate_limit.php';
require_once __DIR__ . '/../core/catalogo.php';
require_once __DIR__ . '/../core/router.php';
require_once __DIR__ . '/../core/database.php';

require_once __DIR__ . '/../repositories/produto_repository.php';
require_once __DIR__ . '/../repositories/usuario_repository.php';
require_once __DIR__ . '/../repositories/favorito_repository.php';
require_once __DIR__ . '/../repositories/carrinho_repository.php';
require_once __DIR__ . '/../repositories/perfil_repository.php';
require_once __DIR__ . '/../repositories/endereco_repository.php';
require_once __DIR__ . '/../repositories/catalogo_repository.php';
require_once __DIR__ . '/../repositories/codigo_recuperacao_repository.php';
require_once __DIR__ . '/../repositories/pedido_repository.php';
require_once __DIR__ . '/../repositories/admin_repository.php';

require_once __DIR__ . '/../controllers/home_controller.php';
require_once __DIR__ . '/../controllers/auth_controller.php';
require_once __DIR__ . '/../controllers/catalogo_controller.php';
require_once __DIR__ . '/../controllers/perfil_controller.php';
require_once __DIR__ . '/../controllers/recuperacao_senha_controller.php';
require_once __DIR__ . '/../controllers/favoritos_controller.php';
require_once __DIR__ . '/../controllers/carrinho_controller.php';
require_once __DIR__ . '/../controllers/checkout_controller.php';
require_once __DIR__ . '/../controllers/pedidos_controller.php';
require_once __DIR__ . '/../controllers/provador_controller.php';
require_once __DIR__ . '/../controllers/admin_controller.php';
require_once __DIR__ . '/../controllers/admin_produtos_controller.php';
require_once __DIR__ . '/../controllers/admin_categorias_controller.php';
require_once __DIR__ . '/../controllers/admin_tamanhos_controller.php';
require_once __DIR__ . '/../controllers/admin_pedidos_controller.php';
require_once __DIR__ . '/../controllers/admin_usuarios_controller.php';

date_default_timezone_set(getenv('TZ') ?: 'America/Sao_Paulo');

Sessao::iniciar();
Auth::start();
// Politica restritiva padrao; o provador virtual a substitui para liberar a camera.
Sessao::politicaPermissoes();

$pdo = Database::conenct();

$homeController = new HomeController($pdo);
$authController = new AuthController($pdo);
$catalogoController = new CatalogoController($pdo);
$perfilController = new PerfilController($pdo);
$recuperacaoSenhaController = new RecuperacaoSenhaController($pdo);
$favoritosController = new FavoritosController($pdo);
$carrinhoController = new CarrinhoController($pdo);
$checkoutController = new CheckoutController($pdo);
$pedidosController = new PedidosController($pdo);
$provadorController = new ProvadorController($pdo);

$router = new Router();

// Home
$router->get('/', [$homeController, 'index']);

// Conta / autenticacao (MVC05)
$router->get('/login', [$authController, 'showLogin']);
$router->post('/login', [$authController, 'login']);
$router->get('/registro', [$authController, 'showRegister']);
$router->post('/registro', [$authController, 'register']);
$router->get('/cadastro', [$authController, 'showRegister']);
$router->post('/cadastro', [$authController, 'register']);
$router->get('/conta', [$authController, 'account']);
$router->post('/logout', [$authController, 'logout']);

// Recuperacao de senha (MVC10)
$router->get('/recuperar-senha', [$recuperacaoSenhaController, 'showSolicitar']);
$router->post('/recuperar-senha', [$recuperacaoSenhaController, 'solicitar']);
$router->get('/recuperar-senha/verificar', [$recuperacaoSenhaController, 'showVerificar']);
$router->post('/recuperar-senha/verificar', [$recuperacaoSenhaController, 'verificar']);
$router->post('/recuperar-senha/reenviar', [$recuperacaoSenhaController, 'reenviar']);

// Catalogo, busca e filtros (MVC07)
$router->get('/categoria', [$catalogoController, 'index']);
$router->get('/produto', [$catalogoController, 'produto']);
$router->get('/produto/variacoes', [$catalogoController, 'variacoes']);

// Perfil, enderecos e seguranca (MVC06)
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

// Favoritos (MVC08)
$router->get('/favoritos', [$favoritosController, 'index']);
$router->get('/favoritos/dados', [$favoritosController, 'dados']);
$router->post('/favoritos/alternar', [$favoritosController, 'alternar']);

// Carrinho (MVC08)
$router->get('/carrinho', [$carrinhoController, 'index']);
$router->get('/carrinho/dados', [$carrinhoController, 'dados']);
$router->post('/carrinho/alternar', [$carrinhoController, 'alternar']);
$router->post('/carrinho/adicionar', [$carrinhoController, 'adicionar']);
$router->post('/carrinho/atualizar', [$carrinhoController, 'atualizar']);
$router->post('/carrinho/remover', [$carrinhoController, 'remover']);

// Checkout, pedidos e pagamentos (MVC09)
$router->get('/checkout', [$checkoutController, 'index']);
$router->post('/checkout', [$checkoutController, 'confirmar']);
$router->get('/pedidos', [$pedidosController, 'index']);
$router->get('/pedido', [$pedidosController, 'ver']);

// Provador virtual e medidas (MVC10)
$router->get('/provador-virtual', [$provadorController, 'index']);
$router->post('/provador-virtual/medidas', [$provadorController, 'salvarMedidas']);

// Painel administrativo
$router->get('/admin', static function () use ($pdo): void {
    (new AdminController($pdo))->index();
});
$router->get('/admin/produtos', static function () use ($pdo): void {
    (new AdminProdutosController($pdo))->index();
});
$router->get('/admin/produtos/novo', static function () use ($pdo): void {
    (new AdminProdutosController($pdo))->novo();
});
$router->get('/admin/produtos/editar', static function () use ($pdo): void {
    (new AdminProdutosController($pdo))->editar();
});
$router->post('/admin/produtos/salvar', static function () use ($pdo): void {
    (new AdminProdutosController($pdo))->salvar();
});
$router->post('/admin/produtos/excluir', static function () use ($pdo): void {
    (new AdminProdutosController($pdo))->excluir();
});

$router->get('/admin/categorias', static function () use ($pdo): void {
    (new AdminCategoriasController($pdo))->index();
});
$router->post('/admin/categorias/salvar', static function () use ($pdo): void {
    (new AdminCategoriasController($pdo))->salvar();
});
$router->post('/admin/categorias/excluir', static function () use ($pdo): void {
    (new AdminCategoriasController($pdo))->excluir();
});

$router->get('/admin/tamanhos', static function () use ($pdo): void {
    (new AdminTamanhosController($pdo))->index();
});
$router->post('/admin/tamanhos/salvar', static function () use ($pdo): void {
    (new AdminTamanhosController($pdo))->salvar();
});
$router->post('/admin/tamanhos/excluir', static function () use ($pdo): void {
    (new AdminTamanhosController($pdo))->excluir();
});

$router->get('/admin/pedidos', static function () use ($pdo): void {
    (new AdminPedidosController($pdo))->index();
});
$router->get('/admin/pedidos/ver', static function () use ($pdo): void {
    (new AdminPedidosController($pdo))->ver();
});
$router->post('/admin/pedidos/atualizar', static function () use ($pdo): void {
    (new AdminPedidosController($pdo))->atualizar();
});

$router->get('/admin/usuarios', static function () use ($pdo): void {
    (new AdminUsuariosController($pdo))->index();
});
$router->get('/admin/usuarios/ver', static function () use ($pdo): void {
    (new AdminUsuariosController($pdo))->ver();
});
$router->post('/admin/usuarios/alternar-admin', static function () use ($pdo): void {
    (new AdminUsuariosController($pdo))->alternarAdmin();
});
$router->post('/admin/usuarios/excluir', static function () use ($pdo): void {
    (new AdminUsuariosController($pdo))->excluir();
});

$metodo = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

try {
    $caminho = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $router->dispatch($metodo, $caminho);
} catch (Throwable $excecao) {
    // Nunca exibe stack trace ou dados do banco para o usuario final.
    error_log(
        '[app] ' . get_class($excecao)
        . ' codigo=' . $excecao->getCode()
        . ' em ' . $excecao->getFile() . ':' . $excecao->getLine()
        . ' | ' . $excecao->getMessage()
    );

    http_response_code(500);
    $codigo = 500;
    $titulo = 'Erro interno';
    $mensagem = 'Não foi possível concluir a solicitação agora. Tente novamente em instantes.';
    $voltar = '/';

    require __DIR__ . '/../views/erro.php';
}
