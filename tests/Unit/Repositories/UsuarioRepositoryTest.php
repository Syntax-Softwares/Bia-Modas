<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class UsuarioRepositoryTest extends TestCase
{
    public function testBuscarPorEmailRetornaUsuarioOuNull(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::linha([
            'id_usuario' => 7,
            'nome' => 'Maria',
            'email' => 'maria@exemplo.com',
        ]));

        $usuario = (new UsuarioRepository($pdo))->buscarPorEmail('maria@exemplo.com');

        self::assertSame(7, $usuario['id_usuario']);
        self::assertSame('maria@exemplo.com', $pdo->parametrosDaPrimeira('FROM usuarios')[':email']);

        $pdoVazio = new FakePDO(static fn (): FakeResult => FakeResult::vazio());
        self::assertNull((new UsuarioRepository($pdoVazio))->buscarPorEmail('x@x.com'));
    }

    public function testBuscarPorIdRetornaUsuarioOuNull(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::linha(['id_usuario' => 7]));

        self::assertSame(7, (new UsuarioRepository($pdo))->buscarPorId(7)['id_usuario']);
        self::assertSame(7, $pdo->parametrosDaPrimeira('WHERE u.id_usuario = :id')[':id']);
    }

    public function testEmailEmUsoRetornaBooleano(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::coluna(1));
        self::assertTrue((new UsuarioRepository($pdo))->emailEmUso('x@x.com'));

        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::coluna(0));
        self::assertFalse((new UsuarioRepository($pdo))->emailEmUso('x@x.com'));
    }

    public function testCriarSemMedidasNemEnderecoGravaSomenteUsuario(): void
    {
        $pdo = new FakePDO();

        $pdo->definirHandler(static function (string $sql) use ($pdo): FakeResult {
            if (str_contains($sql, 'INSERT INTO usuarios')) {
                $pdo->definirUltimoId(7);

                return FakeResult::afetadas(1);
            }

            return FakeResult::vazio();
        });

        $id = (new UsuarioRepository($pdo))->criar([
            'nome' => 'Maria',
            'email' => 'maria@exemplo.com',
            'senha_hash' => 'hash',
            'telefone' => '14999999999',
            'medidas' => [],
            'endereco' => [],
        ]);

        self::assertSame(7, $id);
        self::assertSame(['begin', 'commit'], $pdo->transacoes());
        self::assertFalse($pdo->executou('INSERT INTO medidas'));
        self::assertFalse($pdo->executou('INSERT INTO enderecos'));

        $parametros = $pdo->parametrosDaPrimeira('INSERT INTO usuarios');

        self::assertNull($parametros[':medida_id']);
        self::assertSame('Maria', $parametros[':nome']);
        self::assertSame('maria@exemplo.com', $parametros[':email']);
        self::assertSame('hash', $parametros[':senha_hash']);
        self::assertNull($parametros[':cpf']);
    }

    public function testCriarComMedidasVinculaRegistroCriado(): void
    {
        $pdo = new FakePDO();

        $pdo->definirHandler(static function (string $sql) use ($pdo): FakeResult {
            if (str_contains($sql, 'INSERT INTO medidas')) {
                $pdo->definirUltimoId(3);

                return FakeResult::afetadas(1);
            }

            if (str_contains($sql, 'INSERT INTO usuarios')) {
                $pdo->definirUltimoId(8);

                return FakeResult::afetadas(1);
            }

            return FakeResult::vazio();
        });

        $id = (new UsuarioRepository($pdo))->criar([
            'nome' => 'Maria',
            'email' => 'maria@exemplo.com',
            'senha_hash' => 'hash',
            'medidas' => ['busto' => 100.5, 'cintura' => null, 'quadril' => 110.0],
            'endereco' => [],
        ]);

        self::assertSame(8, $id);

        $medidas = $pdo->parametrosDaPrimeira('INSERT INTO medidas');

        self::assertSame(100.5, $medidas[':busto']);
        self::assertNull($medidas[':cintura']);
        self::assertSame(110.0, $medidas[':quadril']);
        self::assertSame(3, $pdo->parametrosDaPrimeira('INSERT INTO usuarios')[':medida_id']);
    }

    public function testCriarComEnderecoDefinePrincipal(): void
    {
        $pdo = new FakePDO();

        $pdo->definirHandler(static function (string $sql) use ($pdo): FakeResult {
            if (str_contains($sql, 'INSERT INTO usuarios')) {
                $pdo->definirUltimoId(9);

                return FakeResult::afetadas(1);
            }

            return FakeResult::afetadas(1);
        });

        (new UsuarioRepository($pdo))->criar([
            'nome' => 'Maria',
            'email' => 'maria@exemplo.com',
            'senha_hash' => 'hash',
            'medidas' => [],
            'endereco' => [
                'nome_endereco' => '',
                'logradouro' => 'Rua A',
                'numero' => '10',
                'complemento' => '',
                'bairro' => 'Centro',
                'cidade' => 'Jaú',
                'uf' => 'SP',
                'cep' => '17200000',
            ],
        ]);

        $parametros = $pdo->parametrosDaPrimeira('INSERT INTO enderecos');

        self::assertSame(9, $parametros[':usuario_id']);
        self::assertSame('Principal', $parametros[':nome_endereco']);
        self::assertNull($parametros[':complemento']);
        self::assertStringContainsString('TRUE', $pdo->execucoes()[1]['sql']);
        self::assertSame(['begin', 'commit'], $pdo->transacoes());
    }

    public function testCriarFazRollbackQuandoInsercaoFalha(): void
    {
        $pdo = new FakePDO(static function (string $sql): FakeResult {
            if (str_contains($sql, 'INSERT INTO usuarios')) {
                throw new RuntimeException('falha simulada');
            }

            return FakeResult::afetadas(1);
        });

        try {
            (new UsuarioRepository($pdo))->criar([
                'nome' => 'Maria',
                'email' => 'maria@exemplo.com',
                'senha_hash' => 'hash',
            ]);
            self::fail('Era esperada RuntimeException.');
        } catch (RuntimeException $excecao) {
            self::assertSame('falha simulada', $excecao->getMessage());
        }

        self::assertSame(['begin', 'rollback'], $pdo->transacoes());
    }

    public function testAtualizarSenhaHashGravaNovoHash(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::afetadas(1));

        (new UsuarioRepository($pdo))->atualizarSenhaHash(7, 'novo-hash');

        $parametros = $pdo->parametrosDaPrimeira('UPDATE usuarios SET senha_hash');

        self::assertSame('novo-hash', $parametros[':senha_hash']);
        self::assertSame(7, $parametros[':id']);
    }
}
