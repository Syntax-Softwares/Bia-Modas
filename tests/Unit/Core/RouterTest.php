<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function testGetExecutaCallbackDaRota(): void
    {
        $router = new Router();
        $executou = false;

        $router->get('/', static function () use (&$executou): void {
            $executou = true;
            echo 'home';
        });

        ob_start();
        $router->dispatch('GET', '/');
        $saida = (string) ob_get_clean();

        self::assertTrue($executou);
        self::assertSame('home', $saida);
    }

    public function testRotaIgnoraBarraFinalEQueryString(): void
    {
        $router = new Router();
        $recebido = null;

        $router->get('/produto', static function () use (&$recebido): void {
            $recebido = 'ok';
        });

        ob_start();
        $router->dispatch('GET', '/produto/?id=10&foto=2');
        ob_end_clean();

        self::assertSame('ok', $recebido);
    }

    public function testPostExecutaCallbackDaRota(): void
    {
        $router = new Router();
        $executou = false;

        $router->post('/carrinho/adicionar', static function () use (&$executou): void {
            $executou = true;
        });

        $router->dispatch('POST', '/carrinho/adicionar');

        self::assertTrue($executou);
    }

    public function testHeadUsaRotaGet(): void
    {
        $router = new Router();
        $executou = false;

        $router->get('/categoria', static function () use (&$executou): void {
            $executou = true;
        });

        $router->dispatch('HEAD', '/categoria');

        self::assertTrue($executou);
    }

    public function testRotaAusenteResponde404(): void
    {
        $router = new Router();

        ob_start();
        $router->dispatch('GET', '/rota-inexistente');
        $saida = (string) ob_get_clean();

        self::assertStringContainsString('404', $saida);
        self::assertStringContainsString('Página não encontrada', $saida);
    }

    public function testMetodoNaoPermitidoResponde405(): void
    {
        $router = new Router();
        $router->get('/somente-get', static function (): void {
        });

        ob_start();
        $router->dispatch('POST', '/somente-get');
        $saida = (string) ob_get_clean();

        self::assertStringContainsString('405', $saida);
        self::assertStringContainsString('Método não permitido', $saida);
    }

    public function testRotaCadastradaComBarraFinalEhNormalizada(): void
    {
        $router = new Router();
        $executou = false;

        $router->get('/perfil/', static function () use (&$executou): void {
            $executou = true;
        });

        $router->dispatch('GET', '/perfil');

        self::assertTrue($executou);
    }
}
