<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CodigoRecuperacaoRepositoryTest extends TestCase
{
    public function testPodeSolicitarQuandoNaoHaRegistrosRecentes(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::vazio());

        self::assertTrue((new CodigoRecuperacaoRepository($pdo))->podeSolicitar(7));
    }

    public function testPodeSolicitarDentroDosLimites(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::linha([
            'total_hora' => 2,
            'ultimo_minuto' => 0,
        ]));

        self::assertTrue((new CodigoRecuperacaoRepository($pdo))->podeSolicitar(7));
    }

    public function testNaoPodeSolicitarComTresCodigosNaHora(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::linha([
            'total_hora' => 3,
            'ultimo_minuto' => 0,
        ]));

        self::assertFalse((new CodigoRecuperacaoRepository($pdo))->podeSolicitar(7));
    }

    public function testNaoPodeSolicitarDoisCodigosNoMesmoMinuto(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::linha([
            'total_hora' => 1,
            'ultimo_minuto' => 1,
        ]));

        self::assertFalse((new CodigoRecuperacaoRepository($pdo))->podeSolicitar(7));
    }

    public function testCriarInvalidaCodigosAnterioresEGravaNovoHash(): void
    {
        $pdo = new FakePDO();

        $pdo->definirHandler(static function (string $sql) use ($pdo): FakeResult {
            if (str_contains($sql, 'INSERT INTO codigos_recuperacao_senha')) {
                $pdo->definirUltimoId(5);

                return FakeResult::afetadas(1);
            }

            return FakeResult::afetadas(1);
        });

        $id = (new CodigoRecuperacaoRepository($pdo))->criar(7, 'hash-do-codigo');

        self::assertSame(5, $id);
        self::assertSame(['begin', 'commit'], $pdo->transacoes());
        self::assertSame(1, $pdo->contarExecucoes('SET usado_em = NOW()'));

        $parametros = $pdo->parametrosDaPrimeira('INSERT INTO codigos_recuperacao_senha');

        self::assertSame(7, $parametros[':usuario_id']);
        self::assertSame('hash-do-codigo', $parametros[':codigo_hash']);
        self::assertStringContainsString('INTERVAL 10 MINUTE', $pdo->execucoes()[1]['sql']);
    }

    public function testCriarFazRollbackEmFalha(): void
    {
        $pdo = new FakePDO(static function (string $sql): FakeResult {
            if (str_contains($sql, 'INSERT INTO codigos_recuperacao_senha')) {
                throw new RuntimeException('falha simulada');
            }

            return FakeResult::afetadas(1);
        });

        try {
            (new CodigoRecuperacaoRepository($pdo))->criar(7, 'hash');
            self::fail('Era esperada RuntimeException.');
        } catch (RuntimeException $excecao) {
            self::assertSame('falha simulada', $excecao->getMessage());
        }

        self::assertSame(['begin', 'rollback'], $pdo->transacoes());
    }

    public function testBuscarAtivoPorUsuarioRetornaRegistroOuNull(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::linha([
            'id_codigo' => 5,
            'tentativas' => 0,
            'codigo_hash' => 'hash',
        ]));

        $registro = (new CodigoRecuperacaoRepository($pdo))->buscarAtivoPorUsuario(7);

        self::assertSame(5, $registro['id_codigo']);
        self::assertSame(7, $pdo->parametrosDaPrimeira('FROM codigos_recuperacao_senha')[':usuario_id']);

        $pdoVazio = new FakePDO(static fn (): FakeResult => FakeResult::vazio());
        self::assertNull((new CodigoRecuperacaoRepository($pdoVazio))->buscarAtivoPorUsuario(7));
    }

    public function testRegistrarTentativaIncrementaContador(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::afetadas(1));

        (new CodigoRecuperacaoRepository($pdo))->registrarTentativa(5);

        self::assertStringContainsString('tentativas = tentativas + 1', $pdo->execucoes()[0]['sql']);
        self::assertSame(5, $pdo->parametrosDaPrimeira('tentativas = tentativas + 1')[':id_codigo']);
    }

    public function testInvalidarMarcaCodigoComoUsado(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::afetadas(1));

        (new CodigoRecuperacaoRepository($pdo))->invalidar(5);

        self::assertStringContainsString('SET usado_em = NOW()', $pdo->execucoes()[0]['sql']);
        self::assertSame(5, $pdo->parametrosDaPrimeira('SET usado_em = NOW()')[':id_codigo']);
    }

    public function testInvalidarTodosDoUsuarioLimpaPendentes(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::afetadas(2));

        (new CodigoRecuperacaoRepository($pdo))->invalidarTodosDoUsuario(7);

        self::assertSame(7, $pdo->parametrosDaPrimeira('SET usado_em = NOW()')[':usuario_id']);
    }
}
