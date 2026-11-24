<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PerfilRepositoryTest extends TestCase
{
    public function testBuscarPorIdRetornaDadosDoUsuario(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::linha([
            'id_usuario' => 7,
            'nome' => 'Maria',
            'email' => 'maria@exemplo.com',
        ]));

        $usuario = (new PerfilRepository($pdo))->buscarPorId(7);

        self::assertSame('Maria', $usuario['nome']);
        self::assertSame(7, $pdo->parametrosDaPrimeira('FROM usuarios')[':id']);

        $pdoVazio = new FakePDO(static fn (): FakeResult => FakeResult::vazio());
        self::assertNull((new PerfilRepository($pdoVazio))->buscarPorId(7));
    }

    public function testBuscarSenhaHashRetornaStringOuNull(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::coluna('hash'));
        self::assertSame('hash', (new PerfilRepository($pdo))->buscarSenhaHash(7));

        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::coluna(false));
        self::assertNull((new PerfilRepository($pdo))->buscarSenhaHash(7));

        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::coluna(null));
        self::assertNull((new PerfilRepository($pdo))->buscarSenhaHash(7));
    }

    public function testEmailEmUsoPorOutroConsideraOutroUsuario(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::coluna(1));
        self::assertTrue((new PerfilRepository($pdo))->emailEmUsoPorOutro('x@x.com', 7));

        $parametros = $pdo->parametrosDaPrimeira('WHERE email = :email');

        self::assertSame('x@x.com', $parametros[':email']);
        self::assertSame(7, $parametros[':id']);

        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::coluna(0));
        self::assertFalse((new PerfilRepository($pdo))->emailEmUsoPorOutro('x@x.com', 7));
    }

    public function testCpfEmUsoPorOutroConsideraOutroUsuario(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::coluna(1));
        self::assertTrue((new PerfilRepository($pdo))->cpfEmUsoPorOutro('52998224725', 7));

        $parametros = $pdo->parametrosDaPrimeira('WHERE cpf = :cpf');

        self::assertSame('52998224725', $parametros[':cpf']);
        self::assertSame(7, $parametros[':id']);
    }

    public function testAtualizarDadosGravaCamposOpcionaisComoNull(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::afetadas(1));

        (new PerfilRepository($pdo))->atualizarDados(7, [
            'nome' => 'Maria',
            'email' => 'maria@exemplo.com',
            'telefone' => '14999999999',
            'cpf' => null,
            'data_nascimento' => null,
        ]);

        $parametros = $pdo->parametrosDaPrimeira('UPDATE usuarios');

        self::assertSame('Maria', $parametros[':nome']);
        self::assertSame('maria@exemplo.com', $parametros[':email']);
        self::assertSame('14999999999', $parametros[':telefone']);
        self::assertNull($parametros[':cpf']);
        self::assertNull($parametros[':data_nascimento']);
        self::assertSame(7, $parametros[':id']);
    }

    public function testAtualizarDadosGravaCpfEDataPreenchidos(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::afetadas(1));

        (new PerfilRepository($pdo))->atualizarDados(7, [
            'nome' => 'Maria',
            'email' => 'maria@exemplo.com',
            'telefone' => '14999999999',
            'cpf' => '52998224725',
            'data_nascimento' => '1990-05-10',
        ]);

        $parametros = $pdo->parametrosDaPrimeira('UPDATE usuarios');

        self::assertSame('52998224725', $parametros[':cpf']);
        self::assertSame('1990-05-10', $parametros[':data_nascimento']);
    }

    public function testAtualizarSenhaGravaHashDoUsuario(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::afetadas(1));

        (new PerfilRepository($pdo))->atualizarSenha(7, 'novo-hash');

        $parametros = $pdo->parametrosDaPrimeira('UPDATE usuarios');

        self::assertSame('novo-hash', $parametros[':senha_hash']);
        self::assertSame(7, $parametros[':id']);
    }
}
