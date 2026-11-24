<?php

declare(strict_types=1);

/**
 * Bootstrap da suite de testes.
 *
 * A aplicacao nao usa autoloader proprio (cada arquivo faz require_once), e o
 * autoload do Composer so existe depois de `composer install`. Por isso os
 * testes carregam explicitamente as classes de producao necessarias.
 */

$autoload = __DIR__ . '/../vendor/autoload.php';

if (is_file($autoload)) {
    require_once $autoload;
}

require_once __DIR__ . '/Support/FakeResult.php';
require_once __DIR__ . '/Support/FakeStatement.php';
require_once __DIR__ . '/Support/FakePDO.php';
require_once __DIR__ . '/Support/InvocaMetodoPrivado.php';

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/html.php';
require_once __DIR__ . '/../core/sessao.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/validacao.php';
require_once __DIR__ . '/../core/rate_limit.php';
require_once __DIR__ . '/../core/catalogo.php';
require_once __DIR__ . '/../core/router.php';
require_once __DIR__ . '/../core/comprador.php';
require_once __DIR__ . '/../core/mailer.php';

require_once __DIR__ . '/../repositories/usuario_repository.php';
require_once __DIR__ . '/../repositories/produto_repository.php';
require_once __DIR__ . '/../repositories/catalogo_repository.php';
require_once __DIR__ . '/../repositories/favorito_repository.php';
require_once __DIR__ . '/../repositories/carrinho_repository.php';
require_once __DIR__ . '/../repositories/endereco_repository.php';
require_once __DIR__ . '/../repositories/medida_repository.php';
require_once __DIR__ . '/../repositories/perfil_repository.php';
require_once __DIR__ . '/../repositories/codigo_recuperacao_repository.php';
require_once __DIR__ . '/../repositories/pedido_repository.php';
require_once __DIR__ . '/../repositories/admin_repository.php';

require_once __DIR__ . '/../controllers/auth_controller.php';
require_once __DIR__ . '/../controllers/catalogo_controller.php';
require_once __DIR__ . '/../controllers/checkout_controller.php';
require_once __DIR__ . '/../controllers/perfil_controller.php';
require_once __DIR__ . '/../controllers/recuperacao_senha_controller.php';
require_once __DIR__ . '/../controllers/provador_controller.php';
require_once __DIR__ . '/../controllers/admin_produtos_controller.php';

date_default_timezone_set('America/Sao_Paulo');

/*
 * O Router e as views chamam header()/http_response_code() ao responder 404
 * e 405. Em CLI, qualquer saida anterior faz o PHP emitir warnings de
 * "headers already sent" e o PHPUnit converteria isso em falha. Um buffer
 * mantem a saida retida (headers_sent() === false) ate o fim da suite.
 */
ob_start();
register_shutdown_function(static function (): void {
    while (ob_get_level() > 0) {
        ob_end_flush();
    }
});
