<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class MedidaRepositoryTest extends TestCase
{
    public function testBuscarDoUsuarioRetornaNullQuandoUsuarioNaoExiste(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::vazio());

        self::assertNull((new MedidaRepository($pdo))->buscarDoUsuario(7));
    }

    public function testBuscarDoUsuarioConverteMedidasParaFloat(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::linha([
            'id_usuario' => 7,
            'busto' => '100.5',
            'cintura' => null,
            'quadril' => '110.0',
        ]));

        self::assertSame([
            'busto' => 100.5,
            'cintura' => null,
            'quadril' => 110.0,
        ], (new MedidaRepository($pdo))->buscarDoUsuario(7));
    }

    public function testSalvarNaoCriaRegistroQuandoNadaFoiPreenchido(): void
    {
        $pdo = new FakePDO(static fn (string $sql): FakeResult => str_contains($sql, 'FROM usuarios')
            ? FakeResult::coluna(null)
            : FakeResult::vazio());

        (new MedidaRepository($pdo))->salvarDoUsuario(7, ['busto' => null, 'cintura' => null, 'quadril' => null]);

        self::assertSame(['begin', 'commit'], $pdo->transacoes());
        self::assertFalse($pdo->executou('INSERT INTO medidas'));
        self::assertFalse($pdo->executou('UPDATE medidas'));
    }

    public function testSalvarCriaMedidaEVinculaAoUsuario(): void
    {
        $pdo = new FakePDO();

        $pdo->definirHandler(static function (string $sql) use ($pdo): FakeResult {
            if (str_contains($sql, 'FROM usuarios')) {
                return FakeResult::coluna(null);
            }

            if (str_contains($sql, 'INSERT INTO medidas')) {
                $pdo->definirUltimoId(9);

                return FakeResult::afetadas(1);
            }

            return FakeResult::afetadas(1);
        });

        (new MedidaRepository($pdo))->salvarDoUsuario(7, [
            'busto' => 90.55,
            'cintura' => 70.0,
            'quadril' => 100.0,
        ]);

        $medidas = $pdo->parametrosDaPrimeira('INSERT INTO medidas');

        self::assertSame(90.6, $medidas[':busto']);
        self::assertSame(70.0, $medidas[':cintura']);
        self::assertSame(100.0, $medidas[':quadril']);
        self::assertSame(9, $pdo->parametrosDaPrimeira('UPDATE usuarios SET medida_id')[':medida_id']);
        self::assertSame(['begin', 'commit'], $pdo->transacoes());
    }

    public function testSalvarAtualizaMedidaExistenteEReativaRegistro(): void
    {
        $pdo = new FakePDO();

        $pdo->definirHandler(static function (string $sql) use ($pdo): FakeResult {
            if (str_contains($sql, 'FROM usuarios')) {
                return FakeResult::coluna(4);
            }

            return FakeResult::afetadas(1);
        });

        (new MedidaRepository($pdo))->salvarDoUsuario(7, [
            'busto' => 88.0,
            'cintura' => null,
            'quadril' => 99.0,
        ]);

        $parametros = $pdo->parametrosDaPrimeira('UPDATE medidas');

        self::assertSame(4, $parametros[':id']);
        self::assertSame(88.0, $parametros[':busto']);
        self::assertNull($parametros[':cintura']);
        self::assertStringContainsString('deletado_em = NULL', $pdo->execucoes()[1]['sql']);
        self::assertSame(['begin', 'commit'], $pdo->transacoes());
    }

    public function testSalvarRetornaSemAlterarQuandoUsuarioNaoExiste(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::coluna(false));

        (new MedidaRepository($pdo))->salvarDoUsuario(7, ['busto' => 90.0]);

        self::assertSame(['begin', 'rollback'], $pdo->transacoes());
        self::assertFalse($pdo->executou('INSERT INTO medidas'));
    }

    public function testSalvarFazRollbackEmFalha(): void
    {
        $pdo = new FakePDO(function (string $sql): FakeResult {
            if (str_contains($sql, 'FROM usuarios')) {
                return FakeResult::coluna(null);
            }

            throw new RuntimeException('falha simulada');
        });

        try {
            (new MedidaRepository($pdo))->salvarDoUsuario(7, ['busto' => 90.0]);
            self::fail('Era esperada RuntimeException.');
        } catch (RuntimeException $excecao) {
            self::assertSame('falha simulada', $excecao->getMessage());
        }

        self::assertSame(['begin', 'rollback'], $pdo->transacoes());
    }
}
