<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AdminRepositoryTest extends TestCase
{
    public function testResumoRetornaIndicadores(): void
    {
        $linha = [
            'produtos' => '10',
            'produtos_ativos' => '8',
            'usuarios' => '20',
            'pedidos' => '5',
            'pedidos_pendentes' => '2',
            'estoque_baixo' => '1',
            'receita' => '1500.00',
        ];
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::linha($linha));

        self::assertSame($linha, (new AdminRepository($pdo))->resumo());
    }

    public function testUltimosPedidosRespeitaLimite(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::linhas([
            ['id_compra' => 1, 'numero_pedido' => 1001],
        ]));

        $pedidos = (new AdminRepository($pdo))->ultimosPedidos(3);

        self::assertCount(1, $pedidos);
        self::assertSame(3, $pdo->parametrosDaPrimeira('ORDER BY c.criado_em DESC')[':limite']);
    }

    public function testProdutosEstoqueBaixoRespeitaLimite(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::linhas([]));

        (new AdminRepository($pdo))->produtosEstoqueBaixo(4);

        self::assertSame(4, $pdo->parametrosDaPrimeira('estoque <= 5')[':limite']);
    }

    public function testListarProdutosAplicaBuscaPorNomeOuSku(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::linhas([]));

        (new AdminRepository($pdo))->listarProdutos('  vestido  ');

        $sql = $pdo->execucoes()[0]['sql'];
        $parametros = $pdo->execucoes()[0]['parametros'];

        self::assertStringContainsString('p.nome LIKE :busca_nome', $sql);
        self::assertStringContainsString('p.sku LIKE :busca_sku', $sql);
        self::assertSame('%vestido%', $parametros[':busca_nome']);
        self::assertSame('%vestido%', $parametros[':busca_sku']);
    }

    public function testListarProdutosSemBuscaNaoFiltra(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::linhas([]));

        (new AdminRepository($pdo))->listarProdutos('   ');

        self::assertStringNotContainsString('busca_nome', $pdo->execucoes()[0]['sql']);
    }

    public function testBuscarProdutoRetornaNullQuandoNaoExiste(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::vazio());

        self::assertNull((new AdminRepository($pdo))->buscarProduto(999));
    }

    public function testCriarCategoriaRetornaIdGerado(): void
    {
        $pdo = new FakePDO();

        $pdo->definirHandler(static function (string $sql) use ($pdo): FakeResult {
            if (str_contains($sql, 'INSERT INTO categorias')) {
                $pdo->definirUltimoId(8);

                return FakeResult::afetadas(1);
            }

            return FakeResult::vazio();
        });

        $id = (new AdminRepository($pdo))->criarCategoria('Vestidos', 'Vestidos longos', '/img/vestidos.jpg');

        self::assertSame(8, $id);
        self::assertSame('Vestidos', $pdo->parametrosDaPrimeira('INSERT INTO categorias')[':nome']);
    }

    public function testCriarTamanhoRetornaIdGerado(): void
    {
        $pdo = new FakePDO();

        $pdo->definirHandler(static function (string $sql) use ($pdo): FakeResult {
            if (str_contains($sql, 'INSERT INTO tamanhos')) {
                $pdo->definirUltimoId(4);

                return FakeResult::afetadas(1);
            }

            return FakeResult::vazio();
        });

        $id = (new AdminRepository($pdo))->criarTamanho('GG', 7);

        self::assertSame(4, $id);
        self::assertSame(7, $pdo->parametrosDaPrimeira('INSERT INTO tamanhos')[':ordem']);
    }

    public function testCriarProdutoGravaOfertaCategoriaImagemETamanhos(): void
    {
        $pdo = $this->pdoDeCriacaoProduto();

        $id = (new AdminRepository($pdo))->criarProduto([
            'nome' => 'Vestido Midi',
            'sku' => 'VEST-21',
            'descricao' => '',
            'valor' => 100.0,
            'tamanhos' => [1 => 5, 2 => 0],
            'categoria_id' => 3,
            'cor' => '',
            'estilo' => '',
            'oferta_percentual' => 0.2,
            'imagem' => '/img/vestido.jpg',
            'ativo' => true,
        ]);

        self::assertSame(21, $id);
        self::assertSame(['begin', 'commit'], $pdo->transacoes());

        self::assertSame(1, $pdo->contarExecucoes('INSERT INTO ofertas'));
        self::assertSame(6, $pdo->parametrosDaPrimeira('INSERT INTO produtos')[':oferta_id']);

        $produto = $pdo->parametrosDaPrimeira('INSERT INTO produtos');

        self::assertNull($produto[':descricao']);
        self::assertNull($produto[':cor']);
        self::assertNull($produto[':estilo']);
        self::assertSame(1, $produto[':ativo']);

        self::assertSame(3, $pdo->parametrosDaPrimeira('INSERT INTO categorias_produtos')[':categoria']);
        self::assertSame('/img/vestido.jpg', $pdo->parametrosDaPrimeira('INSERT INTO imagens')[':caminho']);

        $tamanhos = $pdo->parametrosDe('INSERT INTO produto_tamanhos');

        self::assertCount(2, $tamanhos);
        self::assertSame(1, $tamanhos[0][':tamanho']);
        self::assertSame(5, $tamanhos[0][':estoque']);
        self::assertSame(2, $tamanhos[1][':tamanho']);
        self::assertSame(0, $tamanhos[1][':estoque']);
        self::assertSame(1, $pdo->contarExecucoes('SET p.estoque = ('));
    }

    public function testCriarProdutoSemOfertaNaoCriaOferta(): void
    {
        $pdo = $this->pdoDeCriacaoProduto();

        (new AdminRepository($pdo))->criarProduto([
            'nome' => 'Blusa',
            'sku' => 'BLU-1',
            'descricao' => 'Blusa leve',
            'valor' => 50.0,
            'tamanhos' => [1 => 2],
            'categoria_id' => 3,
            'cor' => 'Rosa',
            'estilo' => 'Casual',
            'oferta_percentual' => null,
            'imagem' => '',
            'ativo' => false,
        ]);

        self::assertFalse($pdo->executou('INSERT INTO ofertas'));
        self::assertNull($pdo->parametrosDaPrimeira('INSERT INTO produtos')[':oferta_id']);
        self::assertSame(0, $pdo->parametrosDaPrimeira('INSERT INTO produtos')[':ativo']);
        self::assertFalse($pdo->executou('INSERT INTO imagens'));
    }

    public function testCriarProdutoReutilizaOfertaExistente(): void
    {
        $pdo = $this->pdoDeCriacaoProduto(ofertaExistente: 5);

        (new AdminRepository($pdo))->criarProduto([
            'nome' => 'Vestido',
            'sku' => 'VEST-1',
            'descricao' => '',
            'valor' => 100.0,
            'tamanhos' => [1 => 1],
            'categoria_id' => 3,
            'cor' => '',
            'estilo' => '',
            'oferta_percentual' => 0.2,
            'imagem' => '',
            'ativo' => true,
        ]);

        self::assertFalse($pdo->executou('INSERT INTO ofertas'));
        self::assertSame(5, $pdo->parametrosDaPrimeira('INSERT INTO produtos')[':oferta_id']);
    }

    public function testCriarProdutoFazRollbackEmFalha(): void
    {
        $pdo = new FakePDO();

        $pdo->definirHandler(static function (string $sql): FakeResult {
            if (str_contains($sql, 'INSERT INTO produtos')) {
                throw new RuntimeException('falha simulada');
            }

            return FakeResult::coluna(false);
        });

        try {
            (new AdminRepository($pdo))->criarProduto([
                'nome' => 'Vestido',
                'sku' => 'VEST-1',
                'descricao' => '',
                'valor' => 100.0,
                'tamanhos' => [1 => 1],
                'categoria_id' => 3,
                'cor' => '',
                'estilo' => '',
                'oferta_percentual' => null,
                'imagem' => '',
                'ativo' => true,
            ]);
            self::fail('Era esperada RuntimeException.');
        } catch (RuntimeException $excecao) {
            self::assertSame('falha simulada', $excecao->getMessage());
        }

        self::assertSame(['begin', 'rollback'], $pdo->transacoes());
    }

    public function testAtualizarProdutoSincronizaDadosRelacionados(): void
    {
        $pdo = new FakePDO(function (string $sql): FakeResult {
            if (str_contains($sql, 'SELECT id_oferta')) {
                return FakeResult::coluna(5);
            }

            if (str_contains($sql, 'SELECT id_imagem FROM imagens')) {
                return FakeResult::coluna(9);
            }

            return FakeResult::afetadas(1);
        });

        (new AdminRepository($pdo))->atualizarProduto(12, [
            'nome' => 'Vestido Novo',
            'sku' => 'VEST-12',
            'descricao' => 'Nova descrição',
            'valor' => 120.0,
            'tamanhos' => [1 => 3],
            'categoria_id' => 4,
            'cor' => 'Preto',
            'estilo' => 'Elegante',
            'oferta_percentual' => 0.2,
            'imagem' => '/img/novo.jpg',
            'ativo' => true,
        ]);

        self::assertSame(12, $pdo->parametrosDaPrimeira('UPDATE produtos')[':id']);
        self::assertSame(4, $pdo->parametrosDaPrimeira('INSERT INTO categorias_produtos')[':categoria']);
        self::assertSame('/img/novo.jpg', $pdo->parametrosDaPrimeira('UPDATE imagens')[':caminho']);
        self::assertSame(['begin', 'commit'], $pdo->transacoes());
    }

    public function testAtualizarStatusPedidoConcluidoComoPago(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::afetadas(1));

        (new AdminRepository($pdo))->atualizarStatusPedido(42, true, 'pago');

        self::assertSame(1, $pdo->parametrosDaPrimeira('UPDATE compras SET situacao')[':situacao']);
        self::assertSame(42, $pdo->parametrosDaPrimeira('UPDATE compras SET situacao')[':id']);
        self::assertSame('pago', $pdo->parametrosDaPrimeira('UPDATE pagamentos SET status')[':status']);
        self::assertSame(['begin', 'commit'], $pdo->transacoes());
    }

    public function testAtualizarStatusPedidoIgnoraPagamentoInvalido(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::afetadas(1));

        (new AdminRepository($pdo))->atualizarStatusPedido(42, false, 'invalido');

        self::assertSame(0, $pdo->parametrosDaPrimeira('UPDATE compras SET situacao')[':situacao']);
        self::assertFalse($pdo->executou('UPDATE pagamentos SET status'));
        self::assertSame(['begin', 'commit'], $pdo->transacoes());
    }

    public function testAtualizarStatusPedidoFazRollbackEmFalha(): void
    {
        $pdo = new FakePDO(static function (string $sql): FakeResult {
            if (str_contains($sql, 'UPDATE pagamentos')) {
                throw new RuntimeException('falha simulada');
            }

            return FakeResult::afetadas(1);
        });

        try {
            (new AdminRepository($pdo))->atualizarStatusPedido(42, true, 'pago');
            self::fail('Era esperada RuntimeException.');
        } catch (RuntimeException $excecao) {
            self::assertSame('falha simulada', $excecao->getMessage());
        }

        self::assertSame(['begin', 'rollback'], $pdo->transacoes());
    }

    public function testDefinirAdminEExcluirUsuario(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::afetadas(1));
        $repositorio = new AdminRepository($pdo);

        $repositorio->definirAdmin(7, true);
        $repositorio->excluirUsuario(8);

        self::assertSame(1, $pdo->parametrosDaPrimeira('UPDATE usuarios SET is_admin = :admin')[':admin']);
        self::assertSame(7, $pdo->parametrosDaPrimeira('UPDATE usuarios SET is_admin = :admin')[':id']);
        self::assertSame(8, $pdo->parametrosDaPrimeira('SET is_admin = FALSE, deletado_em = NOW()')[':id']);
    }

    public function testExcluirProdutoCategoriaETamanho(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::afetadas(1));
        $repositorio = new AdminRepository($pdo);

        $repositorio->excluirProduto(1);
        $repositorio->excluirCategoria(2);
        $repositorio->excluirTamanho(3);

        self::assertSame(1, $pdo->parametrosDaPrimeira('UPDATE produtos SET ativo = FALSE')[':id']);
        self::assertSame(2, $pdo->parametrosDaPrimeira('UPDATE categorias SET deletado_em')[':id']);
        self::assertSame(3, $pdo->parametrosDaPrimeira('DELETE FROM tamanhos')[':id']);
    }

    public function testListarPedidosBuscarPedidoEItens(): void
    {
        $pdo = new FakePDO(static function (string $sql): FakeResult {
            if (str_contains($sql, 'FROM itens_compra ic')) {
                return FakeResult::linhas([['id_item_compra' => 1, 'nome' => 'Vestido']]);
            }

            if (str_contains($sql, 'WHERE c.id_compra = :id')) {
                return FakeResult::linha(['id_compra' => 5, 'numero_pedido' => 1005]);
            }

            return FakeResult::linhas([['id_compra' => 5, 'numero_pedido' => 1005]]);
        });

        $repositorio = new AdminRepository($pdo);

        self::assertCount(1, $repositorio->listarPedidos());
        self::assertSame(5, $repositorio->buscarPedido(5)['id_compra']);
        self::assertCount(1, $repositorio->itensPedido(5));
        self::assertSame(5, $pdo->parametrosDaPrimeira('WHERE ic.compra_id = :compra')[':compra']);
    }

    public function testListarUsuariosEBuscarUsuario(): void
    {
        $pdo = new FakePDO(static function (string $sql): FakeResult {
            if (str_contains($sql, 'WHERE u.id_usuario = :id')) {
                return FakeResult::linha(['id_usuario' => 7, 'nome' => 'Maria']);
            }

            return FakeResult::linhas([['id_usuario' => 7, 'nome' => 'Maria']]);
        });

        $repositorio = new AdminRepository($pdo);

        self::assertCount(1, $repositorio->listarUsuarios());
        self::assertSame(7, $repositorio->buscarUsuario(7)['id_usuario']);
    }

    public function testEnderecosEPedidosDoUsuario(): void
    {
        $pdo = new FakePDO(static function (string $sql): FakeResult {
            if (str_contains($sql, 'FROM enderecos')) {
                return FakeResult::linhas([['id_endereco' => 1]]);
            }

            return FakeResult::linhas([['id_compra' => 5]]);
        });

        $repositorio = new AdminRepository($pdo);

        self::assertCount(1, $repositorio->enderecosUsuario(7));
        self::assertCount(1, $repositorio->pedidosUsuario(7));
        self::assertSame(7, $pdo->parametrosDaPrimeira('FROM enderecos')[':usuario']);
    }

    private function pdoDeCriacaoProduto(int|false $ofertaExistente = false): FakePDO
    {
        $pdo = new FakePDO();

        $pdo->definirHandler(static function (string $sql) use ($pdo, $ofertaExistente): FakeResult {
            if (str_contains($sql, 'SELECT id_oferta')) {
                return FakeResult::coluna($ofertaExistente);
            }

            if (str_contains($sql, 'INSERT INTO ofertas')) {
                $pdo->definirUltimoId(6);

                return FakeResult::afetadas(1);
            }

            if (str_contains($sql, 'INSERT INTO produtos')) {
                $pdo->definirUltimoId(21);

                return FakeResult::afetadas(1);
            }

            if (str_contains($sql, 'SELECT id_imagem FROM imagens')) {
                return FakeResult::coluna(false);
            }

            return FakeResult::afetadas(1);
        });

        return $pdo;
    }
}
