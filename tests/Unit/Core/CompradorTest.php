<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CompradorTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testCompradorLogadoUsaIdDoUsuario(): void
    {
        $_SESSION['usuario'] = ['id_usuario' => 15, 'nome' => 'Maria'];

        $comprador = Comprador::atual();

        self::assertSame(15, $comprador->idUsuario());
        self::assertNull(Sessao::visitanteIdAtual());
    }

    public function testCompradorLogadoLancaExcecaoAoPedirSessaoAnonima(): void
    {
        $_SESSION['usuario'] = ['id_usuario' => 15, 'nome' => 'Maria'];

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Comprador autenticado nao possui sessao anonima.');

        Comprador::atual()->idSessao();
    }

    public function testVisitanteUsaIdentificadorAnonimoDaSessao(): void
    {
        $_SESSION['visitante_id'] = 'abc123';

        $comprador = Comprador::atual();

        self::assertNull($comprador->idUsuario());
        self::assertSame('abc123', $comprador->idSessao());
    }

    public function testVisitanteSemIdentificadorRecebeUmNovo(): void
    {
        $comprador = Comprador::atual();

        self::assertNull($comprador->idUsuario());
        self::assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $comprador->idSessao());
        self::assertSame($comprador->idSessao(), $_SESSION['visitante_id']);
    }
}
