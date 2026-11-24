<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ProdutoRepositoryTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function linha(array $sobrescrever = []): array
    {
        return array_merge([
            'id_produto' => '10',
            'nome' => 'Vestido Midi',
            'descricao' => 'Vestido leve.',
            'sku' => 'VEST-10',
            'valor' => '80.00',
            'estoque' => '7',
            'cor' => 'Rosa',
            'estilo' => 'Elegante',
            'criado_em' => '2024-01-01 10:00:00',
            'oferta_percentual' => '0.2',
            'imagem' => '/img/vestido.jpg',
            'categoria' => 'Vestidos',
            'tamanho' => 'M, G',
            'medida_busto' => null,
            'medida_cintura' => null,
            'medida_quadril' => null,
        ], $sobrescrever);
    }

    public function testGetNovidadesMapeiaProdutosECalculaPrecoOriginal(): void
    {
        $linha = $this->linha();
        $pdo = new FakePDO(static fn (string $sql): FakeResult => str_contains($sql, 'FROM produtos p')
            ? FakeResult::linhas([$linha])
            : FakeResult::vazio());

        $produtos = (new ProdutoRepository($pdo))->getNovidades(5);

        self::assertCount(1, $produtos);
        self::assertSame(10, $produtos[0]['id_produto']);
        self::assertSame(80.0, $produtos[0]['valor']);
        self::assertSame(7, $produtos[0]['estoque']);
        self::assertEqualsWithDelta(100.0, $produtos[0]['preco_original'], 0.0001);
        self::assertSame(5, $pdo->parametrosDaPrimeira('LIMIT :limite')[':limite']);
    }

    public function testGetPromocoesFiltraOfertas(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::linhas([
            [
                'id_produto' => '1',
                'nome' => 'Blusa',
                'descricao' => null,
                'sku' => 'BLU-1',
                'valor' => '50.00',
                'estoque' => '3',
                'cor' => null,
                'estilo' => null,
                'criado_em' => '2024-01-01',
                'oferta_percentual' => '0.1',
                'imagem' => '/img/blusa.jpg',
                'categoria' => 'Blusas',
                'tamanho' => 'P',
                'medida_busto' => null,
                'medida_cintura' => null,
                'medida_quadril' => null,
            ],
        ]));

        $produtos = (new ProdutoRepository($pdo))->getPromocoes();

        self::assertSame('Blusa', $produtos[0]['nome']);
        self::assertStringContainsString('o.id_oferta IS NOT NULL', $pdo->execucoes()[0]['sql']);
    }

    public function testListarAplicaFiltrosDeCategoriaEBusca(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::linhas([]));

        (new ProdutoRepository($pdo))->listar(' Vestidos ', ' midi ', true, 30);

        $sql = $pdo->execucoes()[0]['sql'];
        $parametros = $pdo->execucoes()[0]['parametros'];

        self::assertStringContainsString('c_filtro.nome = :categoria', $sql);
        self::assertStringContainsString('p.nome LIKE :busca_nome', $sql);
        self::assertStringContainsString('o.id_oferta IS NOT NULL', $sql);
        self::assertSame('Vestidos', $parametros[':categoria']);
        self::assertSame('%midi%', $parametros[':busca_nome']);
        self::assertSame('%midi%', $parametros[':busca_descricao']);
        self::assertSame(30, $parametros[':limite']);
    }

    public function testListarSemFiltrosNaoAdicionaCondicoesExtras(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::linhas([]));

        (new ProdutoRepository($pdo))->listar();

        $sql = $pdo->execucoes()[0]['sql'];

        self::assertStringNotContainsString('c_filtro.nome', $sql);
        self::assertStringNotContainsString('busca_nome', $sql);
        self::assertStringNotContainsString('o.id_oferta IS NOT NULL', $sql);
    }

    public function testGetPorIdRetornaNullQuandoNaoEncontra(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::vazio());

        self::assertNull((new ProdutoRepository($pdo))->getPorId(999));
    }

    public function testGetPorIdCarregaVariacoesMapeadas(): void
    {
        $pdo = new FakePDO(static function (string $sql): FakeResult {
            if (str_contains($sql, 'SELECT pt.id_produto_tamanho, pt.estoque')) {
                return FakeResult::linhas([
                    ['id_produto_tamanho' => '30', 'estoque' => '4', 'tamanho' => 'M'],
                ]);
            }

            return FakeResult::linha([
                'id_produto' => '10',
                'nome' => 'Vestido',
                'descricao' => null,
                'sku' => 'VEST-10',
                'valor' => '100.00',
                'estoque' => '4',
                'cor' => null,
                'estilo' => null,
                'criado_em' => '2024-01-01',
                'oferta_percentual' => null,
                'imagem' => '/img/vestido.jpg',
                'categoria' => 'Vestidos',
                'tamanho' => 'M',
                'medida_busto' => null,
                'medida_cintura' => null,
                'medida_quadril' => null,
            ]);
        });

        $produto = (new ProdutoRepository($pdo))->getPorId(10);

        self::assertSame(10, $produto['id_produto']);
        self::assertNull($produto['oferta_percentual']);
        self::assertNull($produto['preco_original']);
        self::assertSame([[
            'id_produto_tamanho' => 30,
            'tamanho' => 'M',
            'estoque' => 4,
        ]], $produto['variacoes']);
    }

    public function testBuscarPorIdsPreservaOrdemEDeduplicaIds(): void
    {
        $linhas = [
            $this->linha(['id_produto' => '3', 'nome' => 'Terceiro', 'valor' => '30.00']),
            $this->linha(['id_produto' => '1', 'nome' => 'Primeiro', 'valor' => '10.00']),
            $this->linha(['id_produto' => '2', 'nome' => 'Segundo', 'valor' => '20.00']),
        ];
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::linhas($linhas));

        $produtos = (new ProdutoRepository($pdo))->buscarPorIds([2, 1, 2, 3, 999]);

        self::assertSame([2, 1, 3], array_keys($produtos));
        self::assertSame('Segundo', $produtos[2]['nome']);
        self::assertSame([2, 1, 3, 999], $pdo->execucoes()[0]['parametros']);
    }

    public function testBuscarPorIdsVazioNaoConsultaBanco(): void
    {
        $pdo = new FakePDO();

        self::assertSame([], (new ProdutoRepository($pdo))->buscarPorIds([]));
        self::assertSame([], $pdo->execucoes());
    }

    public function testCategoriasComImagemRetornaLinhasDoBanco(): void
    {
        $linhas = [['id_categoria' => 1, 'nome' => 'Blusas', 'imagem' => '/img/blusas.jpg', 'total_produtos' => 4]];
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::linhas($linhas));

        self::assertSame($linhas, (new ProdutoRepository($pdo))->categoriasComImagem());
    }


}
