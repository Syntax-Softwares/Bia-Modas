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
require_once __DIR__ . '/../controllers/admin_controller.php';
require_once __DIR__ . '/../controllers/admin_dashboard_controller.php';
require_once __DIR__ . '/../controllers/admin_produto_controller.php';
require_once __DIR__ . '/../controllers/admin_categoria_controller.php';
require_once __DIR__ . '/../controllers/admin_tamanho_controller.php';
require_once __DIR__ . '/../controllers/admin_usuario_controller.php';
require_once __DIR__ . '/../controllers/admin_oferta_controller.php';
require_once __DIR__ . '/../controllers/admin_cupom_controller.php';
require_once __DIR__ . '/../controllers/admin_estoque_controller.php';

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

// ---------------------------------------------------------------------
// Área administrativa (todas as rotas exigem is_admin revalidado no banco)
// Controllers são criados dentro do callback para que o guard só rode nas
// rotas /admin efetivamente acessadas.
// ---------------------------------------------------------------------
$router->get('/admin', static fn () => (new AdminDashboardController($pdo))->index());

$router->get('/admin/produtos', static fn () => (new AdminProdutoController($pdo))->index());
$router->get('/admin/produtos/novo', static fn () => (new AdminProdutoController($pdo))->novo());
$router->get('/admin/produtos/{id}/editar', static fn (string $id) => (new AdminProdutoController($pdo))->editar($id));
$router->post('/admin/produtos', static fn () => (new AdminProdutoController($pdo))->criar());
$router->put('/admin/produtos/{id}', static fn (string $id) => (new AdminProdutoController($pdo))->atualizar($id));
$router->delete('/admin/produtos/{id}', static fn (string $id) => (new AdminProdutoController($pdo))->excluir($id));

$router->get('/admin/categorias', static fn () => (new AdminCategoriaController($pdo))->index());
$router->get('/admin/categorias/novo', static fn () => (new AdminCategoriaController($pdo))->novo());
$router->get('/admin/categorias/{id}/editar', static fn (string $id) => (new AdminCategoriaController($pdo))->editar($id));
$router->post('/admin/categorias', static fn () => (new AdminCategoriaController($pdo))->criar());
$router->put('/admin/categorias/{id}', static fn (string $id) => (new AdminCategoriaController($pdo))->atualizar($id));
$router->delete('/admin/categorias/{id}', static fn (string $id) => (new AdminCategoriaController($pdo))->excluir($id));

$router->get('/admin/tamanhos', static fn () => (new AdminTamanhoController($pdo))->index());
$router->get('/admin/tamanhos/novo', static fn () => (new AdminTamanhoController($pdo))->novo());
$router->get('/admin/tamanhos/{id}/editar', static fn (string $id) => (new AdminTamanhoController($pdo))->editar($id));
$router->post('/admin/tamanhos', static fn () => (new AdminTamanhoController($pdo))->criar());
$router->put('/admin/tamanhos/{id}', static fn (string $id) => (new AdminTamanhoController($pdo))->atualizar($id));
$router->delete('/admin/tamanhos/{id}', static fn (string $id) => (new AdminTamanhoController($pdo))->excluir($id));

$router->get('/admin/usuarios', static fn () => (new AdminUsuarioController($pdo))->index());
$router->get('/admin/usuarios/novo', static fn () => (new AdminUsuarioController($pdo))->novo());
$router->get('/admin/usuarios/{id}/editar', static fn (string $id) => (new AdminUsuarioController($pdo))->editar($id));
$router->post('/admin/usuarios', static fn () => (new AdminUsuarioController($pdo))->criar());
$router->put('/admin/usuarios/{id}', static fn (string $id) => (new AdminUsuarioController($pdo))->atualizar($id));
$router->delete('/admin/usuarios/{id}', static fn (string $id) => (new AdminUsuarioController($pdo))->excluir($id));

$router->get('/admin/ofertas', static fn () => (new AdminOfertaController($pdo))->index());
$router->get('/admin/ofertas/novo', static fn () => (new AdminOfertaController($pdo))->novo());
$router->get('/admin/ofertas/{id}/editar', static fn (string $id) => (new AdminOfertaController($pdo))->editar($id));
$router->post('/admin/ofertas', static fn () => (new AdminOfertaController($pdo))->criar());
$router->put('/admin/ofertas/{id}', static fn (string $id) => (new AdminOfertaController($pdo))->atualizar($id));
$router->delete('/admin/ofertas/{id}', static fn (string $id) => (new AdminOfertaController($pdo))->excluir($id));

$router->get('/admin/cupons', static fn () => (new AdminCupomController($pdo))->index());
$router->get('/admin/cupons/novo', static fn () => (new AdminCupomController($pdo))->novo());
$router->get('/admin/cupons/{id}/editar', static fn (string $id) => (new AdminCupomController($pdo))->editar($id));
$router->post('/admin/cupons', static fn () => (new AdminCupomController($pdo))->criar());
$router->put('/admin/cupons/{id}', static fn (string $id) => (new AdminCupomController($pdo))->atualizar($id));
$router->delete('/admin/cupons/{id}', static fn (string $id) => (new AdminCupomController($pdo))->excluir($id));

$router->get('/admin/estoque', static fn () => (new AdminEstoqueController($pdo))->index());
$router->put('/admin/estoque/{id}', static fn (string $id) => (new AdminEstoqueController($pdo))->atualizar($id));

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
