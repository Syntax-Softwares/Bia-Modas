<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CarrinhoRepositoryTest extends TestCase
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

    public function testResumoCalculaQuantidadeESubtotal(): void
    {
        $repositorio = new class (new FakePDO()) extends CarrinhoRepository {
            public function listarItens(int $idCarrinho): array
            {
                return [
                    ['quantidade' => 2, 'subtotal' => 79.9],
                    ['quantidade' => 1, 'subtotal' => 20.05],
                ];
            }
        };

        $resumo = $repositorio->resumo(1);

        self::assertSame(3, $resumo['quantidade']);
        self::assertEqualsWithDelta(99.95, $resumo['subtotal'], 0.0001);
        self::assertCount(2, $resumo['itens']);
    }

    public function testResumoDeCarrinhoVazioRetornaZeros(): void
    {
        $repositorio = new class (new FakePDO()) extends CarrinhoRepository {
            public function listarItens(int $idCarrinho): array
            {
                return [];
            }
        };

        $resumo = $repositorio->resumo(1);

        self::assertSame([], $resumo['itens']);
        self::assertSame(0, $resumo['quantidade']);
        self::assertSame(0.0, $resumo['subtotal']);
    }

    public function testResumoDeVisitanteSemCarrinhoNaoConsultaItens(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::coluna(false));
        $repositorio = new CarrinhoRepository($pdo);

        $resumo = $repositorio->resumoDe($this->visitante());

        self::assertSame(0, $resumo['quantidade']);
        self::assertSame(0.0, $resumo['subtotal']);
        self::assertSame([], $resumo['itens']);
    }

    public function testResumoDeVisitanteUsaCarrinhoDaSessao(): void
    {
        $pdo = new FakePDO(static function (string $sql): FakeResult {
            if (str_contains($sql, 'FROM carrinhos') && str_contains($sql, 'sessao_id = :sessao')) {
                return FakeResult::coluna(7);
            }

            return FakeResult::vazio();
        });

        $repositorio = new class ($pdo) extends CarrinhoRepository {
            public function listarItens(int $idCarrinho): array
            {
                return [['quantidade' => 2, 'subtotal' => 50.0]];
            }
        };

        $resumo = $repositorio->resumoDe($this->visitante());

        self::assertSame(2, $resumo['quantidade']);
        self::assertSame(50.0, $resumo['subtotal']);
    }

    public function testAdicionarCriaItemDentroDoEstoque(): void
    {
        $pdo = $this->pdoComVariante(estoque: 5, valor: 49.9);
        $repositorio = new CarrinhoRepository($pdo);

        $resultado = $repositorio->adicionar(10, 100, 2);

        self::assertTrue($resultado['ok']);
        self::assertSame(2, $resultado['quantidade']);
        self::assertSame(['begin', 'commit'], $pdo->transacoes());

        $parametros = $pdo->parametrosDaPrimeira('INSERT INTO itens_carrinho');

        self::assertSame(10, $parametros[':carrinho']);
        self::assertSame(3, $parametros[':produto']);
        self::assertSame(100, $parametros[':produto_tamanho']);
        self::assertSame(2, $parametros[':quantidade']);
        self::assertSame(49.9, $parametros[':valor']);
    }

    public function testAdicionarSomaQuantidadeDeItemExistente(): void
    {
        $pdo = $this->pdoComVariante(
            estoque: 10,
            itemExistente: ['quantidade' => 1, 'deletado_em' => null]
        );

        $resultado = (new CarrinhoRepository($pdo))->adicionar(10, 100, 2);

        self::assertTrue($resultado['ok']);
        self::assertSame(3, $resultado['quantidade']);
        self::assertSame(3, $pdo->parametrosDaPrimeira('INSERT INTO itens_carrinho')[':quantidade']);
    }

    public function testAdicionarReativaItemQueEstavaRemovido(): void
    {
        $pdo = $this->pdoComVariante(
            estoque: 10,
            itemExistente: ['quantidade' => 4, 'deletado_em' => '2024-01-01 10:00:00']
        );

        $resultado = (new CarrinhoRepository($pdo))->adicionar(10, 100, 2);

        self::assertTrue($resultado['ok']);
        self::assertSame(2, $resultado['quantidade']);
    }

    public function testAdicionarRespeitaLimiteDeNoventaENoveUnidades(): void
    {
        $pdo = $this->pdoComVariante(
            estoque: 200,
            itemExistente: ['quantidade' => 98, 'deletado_em' => null]
        );

        $resultado = (new CarrinhoRepository($pdo))->adicionar(10, 100, 10);

        self::assertTrue($resultado['ok']);
        self::assertSame(99, $resultado['quantidade']);
    }

    public function testAdicionarRejeitaQuantidadeAcimaDoEstoque(): void
    {
        $pdo = $this->pdoComVariante(estoque: 1);
        $repositorio = new CarrinhoRepository($pdo);

        $resultado = $repositorio->adicionar(10, 100, 2);

        self::assertFalse($resultado['ok']);
        self::assertSame('Estoque insuficiente para a quantidade desejada.', $resultado['mensagem']);
        self::assertSame(['begin', 'rollback'], $pdo->transacoes());
        self::assertFalse($pdo->executou('INSERT INTO itens_carrinho'));
    }

    public function testAdicionarRejeitaProdutoInativo(): void
    {
        $pdo = $this->pdoComVariante(estoque: 10, ativo: 0);

        $resultado = (new CarrinhoRepository($pdo))->adicionar(10, 100, 1);

        self::assertFalse($resultado['ok']);
        self::assertSame('Produto indisponível.', $resultado['mensagem']);
        self::assertSame(['begin', 'rollback'], $pdo->transacoes());
    }

    public function testAdicionarRejeitaVarianteInexistente(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::vazio());

        $resultado = (new CarrinhoRepository($pdo))->adicionar(10, 100, 1);

        self::assertFalse($resultado['ok']);
        self::assertSame('Produto indisponível.', $resultado['mensagem']);
    }

    public function testDefinirQuantidadeZeroRemoveOItemSemTransacao(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::afetadas(1));

        $resultado = (new CarrinhoRepository($pdo))->definirQuantidade(10, 100, 0);

        self::assertTrue($resultado['ok']);
        self::assertSame(0, $resultado['quantidade']);
        self::assertSame([], $pdo->transacoes());
        self::assertStringContainsString('SET deletado_em = NOW()', $pdo->execucoes()[0]['sql']);
    }

    public function testDefinirQuantidadeRejeitaEstoqueInsuficiente(): void
    {
        $pdo = $this->pdoComVariante(estoque: 2);

        $resultado = (new CarrinhoRepository($pdo))->definirQuantidade(10, 100, 3);

        self::assertFalse($resultado['ok']);
        self::assertSame('Estoque insuficiente para a quantidade desejada.', $resultado['mensagem']);
        self::assertSame(['begin', 'rollback'], $pdo->transacoes());
    }

    public function testDefinirQuantidadeAtualizaEPermiteReativarItem(): void
    {
        $pdo = $this->pdoComVariante(estoque: 10);

        $resultado = (new CarrinhoRepository($pdo))->definirQuantidade(10, 100, 4);

        self::assertTrue($resultado['ok']);
        self::assertSame(4, $resultado['quantidade']);
        self::assertSame(['begin', 'commit'], $pdo->transacoes());

        $sql = $pdo->execucoes()[1]['sql'];

        self::assertStringContainsString('SET quantidade = :quantidade, deletado_em = NULL', $sql);
    }

    public function testDefinirQuantidadeLimitaAoMaximoPermitido(): void
    {
        $pdo = $this->pdoComVariante(estoque: 500);

        $resultado = (new CarrinhoRepository($pdo))->definirQuantidade(10, 100, 500);

        self::assertTrue($resultado['ok']);
        self::assertSame(99, $resultado['quantidade']);
    }

    public function testRemoverFazSoftDeleteDoItem(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::afetadas(1));

        (new CarrinhoRepository($pdo))->remover(10, 100);

        $parametros = $pdo->parametrosDaPrimeira('UPDATE itens_carrinho');

        self::assertSame(10, $parametros[':carrinho']);
        self::assertSame(100, $parametros[':produto_tamanho']);
    }

    public function testAlternarRemoveQuandoItemJaExiste(): void
    {
        $pdo = new FakePDO(static function (string $sql): FakeResult {
            if (str_contains($sql, 'SELECT quantidade') && str_contains($sql, 'FROM itens_carrinho')) {
                return FakeResult::linha(['quantidade' => 1]);
            }

            return FakeResult::afetadas(1);
        });

        $resultado = (new CarrinhoRepository($pdo))->alternar(10, 100);

        self::assertTrue($resultado['ok']);
        self::assertSame('removido', $resultado['acao']);
        self::assertFalse($pdo->executou('INSERT INTO itens_carrinho'));
    }

    public function testAlternarAdicionaQuandoItemNaoExiste(): void
    {
        $repositorio = new class (new FakePDO(static fn (): FakeResult => FakeResult::vazio())) extends CarrinhoRepository {
            public array $chamadas = [];

            public function adicionar(int $idCarrinho, int $idProdutoTamanho, int $quantidade): array
            {
                $this->chamadas[] = ['adicionar', $idCarrinho, $idProdutoTamanho, $quantidade];

                return ['ok' => true, 'quantidade' => $quantidade];
            }

            public function remover(int $idCarrinho, int $idProdutoTamanho): void
            {
                $this->chamadas[] = ['remover', $idCarrinho, $idProdutoTamanho];
            }
        };

        $resultado = $repositorio->alternar(10, 100);

        self::assertSame(['ok' => true, 'acao' => 'adicionado'], $resultado);
        self::assertSame([['adicionar', 10, 100, 1]], $repositorio->chamadas);
    }

    public function testAlternarPropagaFalhaAoAdicionar(): void
    {
        $repositorio = new class (new FakePDO(static fn (): FakeResult => FakeResult::vazio())) extends CarrinhoRepository {
            public function adicionar(int $idCarrinho, int $idProdutoTamanho, int $quantidade): array
            {
                return ['ok' => false, 'mensagem' => 'Estoque insuficiente.'];
            }
        };

        $resultado = $repositorio->alternar(10, 100);

        self::assertSame(['ok' => false, 'mensagem' => 'Estoque insuficiente.'], $resultado);
    }

    public function testVincularAoUsuarioSemCarrinhoDeVisitanteApenasFinaliza(): void
    {
        $pdo = new FakePDO(static function (string $sql): FakeResult {
            if (str_contains($sql, 'SELECT carrinho_id FROM usuarios')) {
                return FakeResult::linha(['carrinho_id' => null]);
            }

            if (str_contains($sql, 'FROM carrinhos') && str_contains($sql, 'sessao_id = :sessao')) {
                return FakeResult::coluna(false);
            }

            return FakeResult::vazio();
        });

        (new CarrinhoRepository($pdo))->vincularAoUsuario(1, 'sessao-visitante');

        self::assertSame(['begin', 'commit'], $pdo->transacoes());
        self::assertFalse($pdo->executou('UPDATE usuarios SET carrinho_id'));
    }

    public function testVincularAoUsuarioAdotaCarrinhoDoVisitanteQuandoUsuarioNaoTemCarrinho(): void
    {
        $pdo = new FakePDO(static function (string $sql): FakeResult {
            if (str_contains($sql, 'SELECT carrinho_id FROM usuarios')) {
                return FakeResult::linha(['carrinho_id' => null]);
            }

            if (str_contains($sql, 'FROM carrinhos') && str_contains($sql, 'sessao_id = :sessao')) {
                return FakeResult::coluna(9);
            }

            return FakeResult::afetadas(1);
        });

        (new CarrinhoRepository($pdo))->vincularAoUsuario(1, 'sessao-visitante');

        self::assertSame(9, $pdo->parametrosDaPrimeira('UPDATE usuarios SET carrinho_id')[':carrinho']);
        self::assertSame(9, $pdo->parametrosDaPrimeira('UPDATE carrinhos SET sessao_id = NULL')[':carrinho']);
        self::assertSame(['begin', 'commit'], $pdo->transacoes());
        self::assertFalse($pdo->executou('FROM itens_carrinho ic'));
    }

    public function testVincularAoUsuarioNaoFazNadaQuandoCarrinhoJaEhOMesmo(): void
    {
        $pdo = new FakePDO(static function (string $sql): FakeResult {
            if (str_contains($sql, 'SELECT carrinho_id FROM usuarios')) {
                return FakeResult::linha(['carrinho_id' => 7]);
            }

            if (str_contains($sql, 'sessao_id = :sessao')) {
                return FakeResult::coluna(7);
            }

            if (str_contains($sql, 'FROM carrinhos')) {
                return FakeResult::coluna(7);
            }

            return FakeResult::vazio();
        });

        (new CarrinhoRepository($pdo))->vincularAoUsuario(1, 'sessao-visitante');

        self::assertSame(['begin', 'commit'], $pdo->transacoes());
        self::assertFalse($pdo->executou('FROM itens_carrinho ic'));
        self::assertFalse($pdo->executou('UPDATE usuarios SET carrinho_id'));
    }

    public function testVincularAoUsuarioMesclaItensQuandoUsuarioJaTemCarrinho(): void
    {
        $pdo = new FakePDO(static function (string $sql): FakeResult {
            if (str_contains($sql, 'SELECT carrinho_id FROM usuarios')) {
                return FakeResult::linha(['carrinho_id' => 5]);
            }

            if (str_contains($sql, 'FROM carrinhos') && str_contains($sql, 'sessao_id = :sessao')) {
                return FakeResult::coluna(7);
            }

            if (str_contains($sql, 'FROM carrinhos')) {
                return FakeResult::coluna(5);
            }

            if (str_contains($sql, 'FROM itens_carrinho ic')) {
                return FakeResult::linhas([[
                    'produto_id' => 3,
                    'produto_tamanho_id' => 30,
                    'quantidade' => 2,
                    'valor_atual' => 59.9,
                    'estoque' => 10,
                ]]);
            }

            if (str_contains($sql, 'SELECT quantidade, deletado_em')) {
                return FakeResult::vazio();
            }

            return FakeResult::afetadas(1);
        });

        (new CarrinhoRepository($pdo))->vincularAoUsuario(1, 'sessao-visitante');

        $parametros = $pdo->parametrosDaPrimeira('INSERT INTO itens_carrinho');

        self::assertSame(5, $parametros[':carrinho']);
        self::assertSame(3, $parametros[':produto']);
        self::assertSame(30, $parametros[':produto_tamanho']);
        self::assertSame(2, $parametros[':quantidade']);
        self::assertSame(59.9, $parametros[':valor']);
        self::assertSame(7, $pdo->parametrosDaPrimeira('SET sessao_id = NULL, deletado_em = NOW()')[':carrinho']);
        self::assertSame(['begin', 'commit'], $pdo->transacoes());
    }

    public function testVincularAoUsuarioFalhaQuandoUsuarioNaoExiste(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::vazio());

        try {
            (new CarrinhoRepository($pdo))->vincularAoUsuario(1, 'sessao-visitante');
            self::fail('Era esperada RuntimeException.');
        } catch (RuntimeException $excecao) {
            self::assertSame('Usuario nao encontrado ao vincular carrinho.', $excecao->getMessage());
        }

        self::assertSame(['begin', 'rollback'], $pdo->transacoes());
    }

    private function visitante(): Comprador
    {
        return Comprador::atual();
    }

    /**
     * @param array{quantidade: int, deletado_em: ?string}|null $itemExistente
     */
    private function pdoComVariante(
        int $estoque,
        float $valor = 49.9,
        int $ativo = 1,
        ?array $itemExistente = null
    ): FakePDO {
        return new FakePDO(static function (string $sql) use ($estoque, $valor, $ativo, $itemExistente): FakeResult {
            if (str_contains($sql, 'SELECT pt.id_produto_tamanho')) {
                return FakeResult::linha([
                    'id_produto_tamanho' => 100,
                    'produto_id' => 3,
                    'estoque' => $estoque,
                    'valor' => $valor,
                    'ativo' => $ativo,
                    'deletado_em' => null,
                ]);
            }

            if (str_contains($sql, 'SELECT quantidade, deletado_em')) {
                return $itemExistente === null ? FakeResult::vazio() : FakeResult::linha($itemExistente);
            }

            return FakeResult::afetadas(1);
        });
    }
}
