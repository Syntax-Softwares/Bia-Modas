<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AuthTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testUserRetornaSomenteArrayDeSessao(): void
    {
        self::assertNull(Auth::user());
        self::assertFalse(Auth::check());

        $_SESSION['usuario'] = ['id_usuario' => 1, 'nome' => 'Maria'];
        self::assertSame(['id_usuario' => 1, 'nome' => 'Maria'], Auth::user());
        self::assertTrue(Auth::check());

        $_SESSION['usuario'] = 'invalido';
        self::assertNull(Auth::user());
    }

    public function testLoginNormalizaDadosDoUsuarioNaSessao(): void
    {
        Auth::login([
            'id_usuario' => '7',
            'nome' => 'Maria Silva',
            'email' => 'maria@exemplo.com',
            'is_admin' => '1',
        ]);

        self::assertSame([
            'id_usuario' => 7,
            'nome' => 'Maria Silva',
            'email' => 'maria@exemplo.com',
            'is_admin' => true,
        ], $_SESSION['usuario']);
    }

    public function testLoginConverteFlagAdminAusenteParaFalso(): void
    {
        Auth::login([
            'id_usuario' => 1,
            'nome' => 'Ana',
            'email' => 'ana@exemplo.com',
        ]);

        self::assertFalse($_SESSION['usuario']['is_admin']);
    }

    public function testLogoutRemoveUsuarioETokenCsrf(): void
    {
        $_SESSION = [
            'usuario' => ['id_usuario' => 1],
            'csrf_token' => 'token',
            'carrinho' => 'mantido',
        ];

        Auth::logout();

        self::assertArrayNotHasKey('usuario', $_SESSION);
        self::assertArrayNotHasKey('csrf_token', $_SESSION);
        self::assertSame('mantido', $_SESSION['carrinho']);
    }

    public function testCsrfTokenEhGeradoComEntropiaESeMantemEstavel(): void
    {
        $token = Auth::csrfToken();

        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
        self::assertSame($token, Auth::csrfToken());
        self::assertSame($token, $_SESSION['csrf_token']);
    }

    public function testCsrfFieldEscapaToken(): void
    {
        $_SESSION['csrf_token'] = 'abc"123';

        self::assertSame(
            '<input type="hidden" name="_csrf" value="abc&quot;123">',
            Auth::csrfField()
        );
    }

    public function testValidCsrfComparaComTokenDaSessao(): void
    {
        $_SESSION['csrf_token'] = str_repeat('a', 64);

        self::assertTrue(Auth::validCsrf(str_repeat('a', 64)));
        self::assertFalse(Auth::validCsrf(str_repeat('b', 64)));
        self::assertFalse(Auth::validCsrf(null));
        self::assertFalse(Auth::validCsrf(''));
    }

    public function testValidCsrfRejeitaSessaoSemToken(): void
    {
        self::assertFalse(Auth::validCsrf('qualquer'));
    }

    public function testFlashAcumulaMensagensEehConsumidoUmaVez(): void
    {
        Auth::flash('success', 'Bem-vinda!');
        Auth::flash('danger', 'Erro.');

        self::assertSame([
            ['type' => 'success', 'message' => 'Bem-vinda!'],
            ['type' => 'danger', 'message' => 'Erro.'],
        ], Auth::takeFlashes());

        self::assertSame([], Auth::takeFlashes());
    }

    public function testSetOldRemoveCamposSensiveis(): void
    {
        Auth::setOld([
            'email' => 'maria@exemplo.com',
            'senha' => 'segredo',
            'senha_confirmar' => 'segredo',
            '_csrf' => 'token',
        ]);

        self::assertSame(['email' => 'maria@exemplo.com'], Auth::takeOld());
        self::assertSame([], Auth::takeOld());
    }

    public function testSetErrorsEtakeErrors(): void
    {
        Auth::setErrors(['email' => 'E-mail invalido.']);

        self::assertSame(['email' => 'E-mail invalido.'], Auth::takeErrors());
        self::assertSame([], Auth::takeErrors());
    }
}
