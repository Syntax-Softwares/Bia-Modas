<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class FavoritoRepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
        $_SESSION['visitante_id'] = 'sessao-visitante';
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testAlternarRejeitaProdutoIndisponivel(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::vazio());

        $resultado = (new FavoritoRepository($pdo))->alternar($this->usuario(), 5);

        self::assertFalse($resultado['ok']);
        self::assertSame('Produto indisponível.', $resultado['mensagem']);
        self::assertFalse($pdo->executou('INSERT INTO favoritos'));
    }

    public function testUsuarioLogadoAdicionaFavorito(): void
    {
        $pdo = new FakePDO(static function (string $sql): FakeResult {
            if (str_contains($sql, 'SELECT id_produto FROM produtos')) {
                return FakeResult::linha(['id_produto' => 5]);
            }

            if (str_contains($sql, 'FROM favoritos') && str_contains($sql, 'usuario_id = :usuario')) {
                return FakeResult::vazio();
            }

            if (str_contains($sql, 'SELECT f.produto_id')) {
                return FakeResult::linhas([['produto_id' => 5], ['produto_id' => 6]]);
            }

            return FakeResult::afetadas(1);
        });

        $resultado = (new FavoritoRepository($pdo))->alternar($this->usuario(7), 5);

        self::assertTrue($resultado['ok']);
        self::assertTrue($resultado['favorito']);
        self::assertSame(2, $resultado['total']);
        self::assertSame(7, $pdo->parametrosDaPrimeira('INSERT INTO favoritos')[':dono']);
        self::assertSame(5, $pdo->parametrosDaPrimeira('INSERT INTO favoritos')[':produto']);
    }

    public function testAlternarRemoveFavoritoAtivo(): void
    {
        $pdo = new FakePDO(static function (string $sql): FakeResult {
            if (str_contains($sql, 'SELECT id_produto FROM produtos')) {
                return FakeResult::linha(['id_produto' => 5]);
            }

            if (str_contains($sql, 'FROM favoritos') && str_contains($sql, 'usuario_id = :usuario')) {
                return FakeResult::linha(['id_favorito' => 99, 'deletado_em' => null]);
            }

            if (str_contains($sql, 'SELECT f.produto_id')) {
                return FakeResult::vazio();
            }

            return FakeResult::afetadas(1);
        });

        $resultado = (new FavoritoRepository($pdo))->alternar($this->usuario(), 5);

        self::assertTrue($resultado['ok']);
        self::assertFalse($resultado['favorito']);
        self::assertSame(0, $resultado['total']);
        self::assertSame(99, $pdo->parametrosDaPrimeira('UPDATE favoritos SET deletado_em = NOW()')[':id']);
    }

    public function testAlternarReativaFavoritoRemovido(): void
    {
        $pdo = new FakePDO(static function (string $sql): FakeResult {
            if (str_contains($sql, 'SELECT id_produto FROM produtos')) {
                return FakeResult::linha(['id_produto' => 5]);
            }

            if (str_contains($sql, 'FROM favoritos') && str_contains($sql, 'usuario_id = :usuario')) {
                return FakeResult::linha(['id_favorito' => 99, 'deletado_em' => '2024-01-01 10:00:00']);
            }

            if (str_contains($sql, 'SELECT f.produto_id')) {
                return FakeResult::linhas([['produto_id' => 5]]);
            }

            return FakeResult::afetadas(1);
        });

        $resultado = (new FavoritoRepository($pdo))->alternar($this->usuario(), 5);

        self::assertTrue($resultado['favorito']);
        self::assertTrue($pdo->executou('ON DUPLICATE KEY UPDATE deletado_em = NULL'));
    }

    public function testVisitanteUsaSessaoComoDono(): void
    {
        $pdo = new FakePDO(static function (string $sql): FakeResult {
            if (str_contains($sql, 'SELECT id_produto FROM produtos')) {
                return FakeResult::linha(['id_produto' => 5]);
            }

            if (str_contains($sql, 'FROM favoritos') && str_contains($sql, 'sessao_id = :sessao')) {
                return FakeResult::vazio();
            }

            if (str_contains($sql, 'SELECT f.produto_id')) {
                return FakeResult::linhas([['produto_id' => 5]]);
            }

            return FakeResult::afetadas(1);
        });

        $resultado = (new FavoritoRepository($pdo))->alternar($this->visitante(), 5);

        self::assertTrue($resultado['favorito']);
        self::assertSame('sessao-visitante', $pdo->parametrosDaPrimeira('INSERT INTO favoritos')[':dono']);
    }

    public function testIdsConverteValoresParaInteiro(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::linhas([
            ['produto_id' => '5'],
            ['produto_id' => '9'],
        ]));

        self::assertSame([5, 9], (new FavoritoRepository($pdo))->ids($this->usuario()));
    }

    public function testVincularAoUsuarioMigraFavoritosEDesativaSessao(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::afetadas(1));

        (new FavoritoRepository($pdo))->vincularAoUsuario(7, 'sessao-visitante');

        self::assertSame(['begin', 'commit'], $pdo->transacoes());
        self::assertSame(7, $pdo->parametrosDaPrimeira('INSERT INTO favoritos')[':usuario']);
        self::assertSame('sessao-visitante', $pdo->parametrosDaPrimeira('INSERT INTO favoritos')[':sessao']);
        self::assertSame('sessao-visitante', $pdo->parametrosDaPrimeira('SET deletado_em = NOW()')[':sessao']);
    }

    public function testVincularAoUsuarioFazRollbackEmFalha(): void
    {
        $pdo = new FakePDO(static function (): FakeResult {
            throw new RuntimeException('falha simulada');
        });

        try {
            (new FavoritoRepository($pdo))->vincularAoUsuario(7, 'sessao-visitante');
            self::fail('Era esperada RuntimeException.');
        } catch (RuntimeException $excecao) {
            self::assertSame('falha simulada', $excecao->getMessage());
        }

        self::assertSame(['begin', 'rollback'], $pdo->transacoes());
    }

    private function usuario(int $id = 1): Comprador
    {
        $_SESSION['usuario'] = ['id_usuario' => $id, 'nome' => 'Maria'];

        return Comprador::atual();
    }

    private function visitante(): Comprador
    {
        unset($_SESSION['usuario']);

        return Comprador::atual();
    }
}
