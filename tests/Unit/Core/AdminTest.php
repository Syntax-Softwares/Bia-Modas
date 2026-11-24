<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AdminTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testUsuarioSemSessaoRetornaNull(): void
    {
        self::assertNull(Admin::usuario());
    }

    public function testUsuarioComumNaoEhAdministrador(): void
    {
        $_SESSION['usuario'] = [
            'id_usuario' => 7,
            'nome' => 'Maria',
            'email' => 'maria@exemplo.com',
            'is_admin' => false,
        ];

        self::assertNull(Admin::usuario());
    }

    public function testAdministradorEhReconhecido(): void
    {
        $usuario = [
            'id_usuario' => 1,
            'nome' => 'Admin',
            'email' => 'admin@biamodas.com',
            'is_admin' => true,
        ];
        $_SESSION['usuario'] = $usuario;

        self::assertSame($usuario, Admin::usuario());
    }
}
