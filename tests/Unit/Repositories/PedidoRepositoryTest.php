<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PedidoRepositoryTest extends TestCase
{
    private function dadosDoCheckout(array $sobrescrever = []): array
    {
        return array_merge([
            'endereco_id' => '5',
            'destinatario' => 'Maria Silva',
            'telefone' => '14999999999',
            'endereco_cep' => '17200000',
            'endereco_logradouro' => 'Rua das Flores',
            'endereco_numero' => '123',
            'endereco_complemento' => '',
            'endereco_bairro' => 'Centro',
            'endereco_cidade' => 'Jaú',
            'endereco_uf' => 'SP',
            'forma_pagamento' => 'pix',
        ], $sobrescrever);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function itens(): array
    {
        return [
            [
                'produto_id' => 1,
                'produto_tamanho_id' => 10,
                'quantidade' => 2,
                'valor_unitario' => 50.0,
                'nome' => 'Vestido',
                'sku' => 'VEST',
                'tamanho' => 'M',
                'estoque' => 10,
            ],
            [
                'produto_id' => 2,
                'produto_tamanho_id' => 20,
                'quantidade' => 1,
                'valor_unitario' => 30.0,
                'nome' => 'Blusa',
                'sku' => 'BLU',
                'tamanho' => 'P',
                'estoque' => 5,
            ],
        ];
    }

    private function pdoDePedido(array $itens, int $numeroPedido = 1001, int $idCompra = 42): FakePDO
    {
        $pdo = new FakePDO();

        $pdo->definirHandler(static function (string $sql) use ($pdo, $itens, $numeroPedido, $idCompra): FakeResult {
            if (str_contains($sql, 'FROM itens_carrinho ic')) {
                return FakeResult::linhas($itens);
            }

            if (str_contains($sql, 'SELECT COALESCE(MAX(numero_pedido)')) {
                return FakeResult::coluna($numeroPedido);
            }

            if (str_contains($sql, 'INSERT INTO compras')) {
                $pdo->definirUltimoId($idCompra);

                return FakeResult::afetadas(1);
            }

            if (str_contains($sql, 'SET estoque = estoque - :quantidade')) {
                return FakeResult::afetadas(1);
            }

            if (str_contains($sql, 'SELECT produto_id FROM produto_tamanhos')) {
                return FakeResult::coluna(1);
            }

            return FakeResult::afetadas(1);
        });

        return $pdo;
    }

    public function testCriarPedidoCalculaTotalBaixaEstoqueERegistraPagamento(): void
    {
        $pdo = $this->pdoDePedido($this->itens());

        $resultado = (new PedidoRepository($pdo))->criar(7, 3, $this->dadosDoCheckout());

        self::assertSame([
            'ok' => true,
            'id_compra' => 42,
            'numero_pedido' => 1001,
            'total' => 130.0,
        ], $resultado);

        self::assertSame(['begin', 'commit'], $pdo->transacoes());

        $compra = $pdo->parametrosDaPrimeira('INSERT INTO compras');

        self::assertSame(7, $compra[':usuario']);
        self::assertSame(5, $compra[':endereco']);
        self::assertSame(1001, $compra[':numero']);
        self::assertSame(130.0, $compra[':total']);
        self::assertSame(0.0, $compra[':frete']);
        self::assertSame(0.0, $compra[':desconto']);
        self::assertSame('Maria Silva', $compra[':destinatario']);
        self::assertSame('17200000', $compra[':cep']);
        self::assertSame('SP', $compra[':uf']);
        self::assertNull($compra[':complemento']);

        $itens = $pdo->parametrosDe('INSERT INTO itens_compra');

        self::assertCount(2, $itens);
        self::assertSame('VEST-M', $itens[0][':sku']);
        self::assertSame(100.0, $itens[0][':total']);
        self::assertSame('BLU-P', $itens[1][':sku']);
        self::assertSame(30.0, $itens[1][':total']);

        self::assertSame(2, $pdo->contarExecucoes('SET estoque = estoque - :quantidade'));
        self::assertSame(2, $pdo->contarExecucoes('UPDATE produtos p'));

        $pagamento = $pdo->parametrosDaPrimeira('INSERT INTO pagamentos');

        self::assertSame(42, $pagamento[':compra']);
        self::assertSame('pix', $pagamento[':metodo']);
        self::assertSame(130.0, $pagamento[':valor']);
        self::assertSame(1, $pdo->contarExecucoes("VALUES (:compra, :metodo, 'pendente', :valor, :codigo)"));

        self::assertSame(1, $pdo->contarExecucoes('UPDATE itens_carrinho'));
    }

    public function testCriarPedidoComCarrinhoVazioFalhaSemInsercoes(): void
    {
        $pdo = $this->pdoDePedido([]);

        $resultado = (new PedidoRepository($pdo))->criar(7, 3, $this->dadosDoCheckout());

        self::assertFalse($resultado['ok']);
        self::assertSame('Seu carrinho está vazio.', $resultado['mensagem']);
        self::assertSame(['begin', 'rollback'], $pdo->transacoes());
        self::assertFalse($pdo->executou('INSERT INTO compras'));
    }

    public function testCriarPedidoRejeitaQuantidadeAcimaDoEstoque(): void
    {
        $itens = $this->itens();
        $itens[0]['quantidade'] = 11;
        $pdo = $this->pdoDePedido($itens);

        $resultado = (new PedidoRepository($pdo))->criar(7, 3, $this->dadosDoCheckout());

        self::assertFalse($resultado['ok']);
        self::assertSame('Estoque insuficiente para Vestido.', $resultado['mensagem']);
        self::assertSame(['begin', 'rollback'], $pdo->transacoes());
        self::assertFalse($pdo->executou('INSERT INTO compras'));
    }

    public function testCriarPedidoFalhaQuandoEstoqueMudaDuranteABaixa(): void
    {
        $pdo = $this->pdoDePedido($this->itens());
        $pdo->definirHandler(static function (string $sql) use ($pdo): FakeResult {
            if (str_contains($sql, 'SET estoque = estoque - :quantidade')) {
                return FakeResult::afetadas(0);
            }

            if (str_contains($sql, 'FROM itens_carrinho ic')) {
                return FakeResult::linhas([
                    [
                        'produto_id' => 1,
                        'produto_tamanho_id' => 10,
                        'quantidade' => 1,
                        'valor_unitario' => 50.0,
                        'nome' => 'Vestido',
                        'sku' => 'VEST',
                        'tamanho' => 'M',
                        'estoque' => 10,
                    ],
                ]);
            }

            if (str_contains($sql, 'SELECT COALESCE(MAX(numero_pedido)')) {
                return FakeResult::coluna(1001);
            }

            if (str_contains($sql, 'INSERT INTO compras')) {
                $pdo->definirUltimoId(42);

                return FakeResult::afetadas(1);
            }

            return FakeResult::afetadas(1);
        });

        $resultado = (new PedidoRepository($pdo))->criar(7, 3, $this->dadosDoCheckout());

        self::assertFalse($resultado['ok']);
        self::assertSame('Estoque insuficiente para concluir o pedido.', $resultado['mensagem']);
        self::assertSame(['begin', 'rollback'], $pdo->transacoes());
    }

    public function testCriarPedidoAceitaEnderecoAvulsoSemId(): void
    {
        $pdo = $this->pdoDePedido($this->itens());

        $resultado = (new PedidoRepository($pdo))->criar(
            7,
            3,
            $this->dadosDoCheckout(['endereco_id' => 'novo'])
        );

        self::assertTrue($resultado['ok']);
        self::assertNull($pdo->parametrosDaPrimeira('INSERT INTO compras')[':endereco']);
    }

    public function testCriarPedidoTentaNovamenteQuandoNumeroPedidoColide(): void
    {
        $tentativas = 0;
        $pdo = new FakePDO();

        $pdo->definirHandler(static function (string $sql) use (&$tentativas, $pdo): FakeResult {
            if (str_contains($sql, 'FROM itens_carrinho ic')) {
                return FakeResult::linhas([[
                    'produto_id' => 1,
                    'produto_tamanho_id' => 10,
                    'quantidade' => 1,
                    'valor_unitario' => 50.0,
                    'nome' => 'Vestido',
                    'sku' => 'VEST',
                    'tamanho' => 'M',
                    'estoque' => 10,
                ]]);
            }

            if (str_contains($sql, 'SELECT COALESCE(MAX(numero_pedido)')) {
                return FakeResult::coluna(1001);
            }

            if (str_contains($sql, 'INSERT INTO compras')) {
                $tentativas++;

                if ($tentativas === 1) {
                    $excecao = new PDOException('Duplicate entry');
                    $codigo = new ReflectionProperty(Exception::class, 'code');
                    $codigo->setValue($excecao, '23000');

                    throw $excecao;
                }

                $pdo->definirUltimoId(43);

                return FakeResult::afetadas(1);
            }

            if (str_contains($sql, 'SELECT produto_id FROM produto_tamanhos')) {
                return FakeResult::coluna(1);
            }

            return FakeResult::afetadas(1);
        });

        $resultado = (new PedidoRepository($pdo))->criar(7, 3, $this->dadosDoCheckout());

        self::assertTrue($resultado['ok']);
        self::assertSame(2, $tentativas);
        self::assertSame(43, $resultado['id_compra']);
    }

    public function testCriarPedidoFazRollbackEmErroInesperado(): void
    {
        $pdo = new FakePDO(static function (string $sql): FakeResult {
            if (str_contains($sql, 'FROM itens_carrinho ic')) {
                return FakeResult::linhas([[
                    'produto_id' => 1,
                    'produto_tamanho_id' => 10,
                    'quantidade' => 1,
                    'valor_unitario' => 50.0,
                    'nome' => 'Vestido',
                    'sku' => 'VEST',
                    'tamanho' => 'M',
                    'estoque' => 10,
                ]]);
            }

            if (str_contains($sql, 'INSERT INTO compras')) {
                throw new RuntimeException('falha inesperada');
            }

            return FakeResult::coluna(1001);
        });

        try {
            (new PedidoRepository($pdo))->criar(7, 3, $this->dadosDoCheckout());
            self::fail('Era esperada RuntimeException.');
        } catch (RuntimeException $excecao) {
            self::assertSame('falha inesperada', $excecao->getMessage());
        }

        self::assertSame(['begin', 'rollback'], $pdo->transacoes());
    }

    public function testListarDoUsuarioRetornaPedidos(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::linhas([
            ['id_compra' => 1, 'numero_pedido' => 1001, 'valor_total' => 130.0],
        ]));

        $pedidos = (new PedidoRepository($pdo))->listarDoUsuario(7);

        self::assertCount(1, $pedidos);
        self::assertSame(1001, $pedidos[0]['numero_pedido']);
        self::assertSame(7, $pdo->parametrosDaPrimeira('FROM compras c')[':usuario']);
    }

    public function testBuscarPorNumeroDoUsuarioRetornaNullQuandoNaoEncontra(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::vazio());

        self::assertNull((new PedidoRepository($pdo))->buscarPorNumeroDoUsuario(7, 9999));
    }

    public function testBuscarPorNumeroDoUsuarioRetornaPedidoDoDono(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::linha([
            'id_compra' => 5,
            'numero_pedido' => 1005,
            'usuario_id' => 7,
        ]));

        $pedido = (new PedidoRepository($pdo))->buscarPorNumeroDoUsuario(7, 1005);

        self::assertSame(5, $pedido['id_compra']);
        self::assertSame(7, $pdo->parametrosDaPrimeira('WHERE c.usuario_id = :usuario')[':usuario']);
        self::assertSame(1005, $pdo->parametrosDaPrimeira('c.numero_pedido = :numero')[':numero']);
    }

    public function testItensDaCompraRetornaItens(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::linhas([
            ['produto_id' => 1, 'nome' => 'Vestido', 'quantidade' => 2],
        ]));

        $itens = (new PedidoRepository($pdo))->itensDaCompra(42);

        self::assertCount(1, $itens);
        self::assertSame(42, $pdo->parametrosDaPrimeira('WHERE ic.compra_id = :compra')[':compra']);
    }
}
