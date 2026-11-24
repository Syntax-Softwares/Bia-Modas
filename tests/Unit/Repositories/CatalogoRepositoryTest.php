<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CatalogoRepositoryTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function linhaDeProduto(array $sobrescrever = []): array
    {
        return array_merge([
            'id_produto' => '10',
            'nome' => 'Vestido Midi',
            'descricao' => 'Vestido leve para o verÃ£o.',
            'sku' => 'VEST-10',
            'valor' => '80.00',
            'estoque' => '7',
            'criado_em' => '2024-01-01 10:00:00',
            'cor' => 'Rosa',
            'estilo' => 'Elegante',
            'oferta_percentual' => '0.2',
            'imagem' => '/img/vestido.jpg',
            'categoria' => 'Vestidos',
            'id_categoria' => '3',
            'tamanho' => 'M, G',
            'medida_busto' => null,
            'medida_cintura' => null,
            'medida_quadril' => null,
            'relevancia' => '60',
        ], $sobrescrever);
    }

    private function pdoDeListagem(?array $linha = null, int $total = 25): FakePDO
    {
        $linha ??= $this->linhaDeProduto();

        return new FakePDO(static function (string $sql) use ($linha, $total): FakeResult {
            if (str_contains($sql, 'SELECT COUNT(*) FROM produtos p')) {
                return FakeResult::coluna($total);
            }

            if (str_contains($sql, 'LIMIT :limite OFFSET :offset')) {
                return FakeResult::linhas([$linha]);
            }

            return FakeResult::vazio();
        });
    }

    public function testListarMapeiaProdutosECalculaPaginacao(): void
    {
        $pdo = $this->pdoDeListagem();
        $resultado = (new CatalogoRepository($pdo))->listar([]);

        self::assertSame(25, $resultado['total']);
        self::assertSame(1, $resultado['pagina']);
        self::assertSame(3, $resultado['paginas']);
        self::assertSame(12, $resultado['por_pagina']);
        self::assertCount(1, $resultado['itens']);

        $produto = $resultado['itens'][0];

        self::assertSame(10, $produto['id_produto']);
        self::assertSame(80.0, $produto['valor']);
        self::assertSame(7, $produto['estoque']);
        self::assertSame(3, $produto['id_categoria']);
        self::assertSame(0.2, $produto['oferta_percentual']);
        self::assertEqualsWithDelta(100.0, $produto['preco_original'], 0.0001);
        self::assertSame('Rosa', $produto['cor']);
        self::assertSame('Elegante', $produto['estilo']);
        self::assertSame('M, G', $produto['tamanho']);

        $parametros = $pdo->parametrosDaPrimeira('ORDER BY relevancia DESC');

        self::assertSame(12, $parametros[':limite']);
        self::assertSame(0, $parametros[':offset']);
    }

    public function testListarAjustaPaginaParaIntervaloValido(): void
    {
        $pdo = $this->pdoDeListagem(null, 25);
        $repositorio = new CatalogoRepository($pdo);

        $resultado = $repositorio->listar([], 99, 12);

        self::assertSame(3, $resultado['pagina']);
        self::assertSame(24, $pdo->parametrosDaPrimeira('ORDER BY relevancia DESC')[':offset']);

        $resultado = $repositorio->listar([], 0, 12);

        self::assertSame(1, $resultado['pagina']);
    }

    public function testListarNormalizaTamanhoDaPagina(): void
    {
        $pdo = $this->pdoDeListagem(null, 0);
        $repositorio = new CatalogoRepository($pdo);

        self::assertSame(1, $repositorio->listar([], 1, 0)['por_pagina']);
        self::assertSame(48, $repositorio->listar([], 1, 500)['por_pagina']);
    }

    public function testListarSemResultadosMantemUmaPagina(): void
    {
        $pdo = new FakePDO(static function (string $sql): FakeResult {
            if (str_contains($sql, 'SELECT COUNT(*)')) {
                return FakeResult::coluna(0);
            }

            return FakeResult::linhas([]);
        });

        $resultado = (new CatalogoRepository($pdo))->listar([], 5);

        self::assertSame(0, $resultado['total']);
        self::assertSame(1, $resultado['pagina']);
        self::assertSame(1, $resultado['paginas']);
        self::assertSame([], $resultado['itens']);
    }

    public function testBuscaEscapaCuringasEUsaScoreDeRelevancia(): void
    {
        $pdo = $this->pdoDeListagem();
        (new CatalogoRepository($pdo))->listar(['busca' => '  vestido 50%_  ']);

        $sqlBusca = '';
        foreach ($pdo->execucoes() as $execucao) {
            if (str_contains($execucao['sql'], 'LIMIT :limite OFFSET :offset')) {
                $sqlBusca = $execucao['sql'];
            }
        }

        self::assertStringContainsString('CASE', $sqlBusca);
        self::assertStringContainsString('AS relevancia', $sqlBusca);

        $parametros = $pdo->parametrosDaPrimeira('LIMIT :limite OFFSET :offset');

        self::assertSame('vestido 50%_', $parametros[':score_exato']);
        self::assertSame('vestido 50\\%\\_%', $parametros[':score_prefixo']);
        self::assertSame('%vestido 50\\%\\_%', $parametros[':busca_nome']);
        self::assertSame('%vestido 50\\%\\_%', $parametros[':busca_descricao']);
    }

    public function testFiltroDeCategoriaUsaNomeExato(): void
    {
        $pdo = $this->pdoDeListagem();
        (new CatalogoRepository($pdo))->listar(['categoria' => ' Vestidos ']);

        self::assertSame('Vestidos', $pdo->parametrosDaPrimeira('c_cat.nome = :categoria')[':categoria']);
    }

    public function testFiltrosDeTamanhoCorEEstiloGeramPlaceholders(): void
    {
        $pdo = $this->pdoDeListagem();
        (new CatalogoRepository($pdo))->listar([
            'tamanhos' => ['M', 'G', 'M', ''],
            'cores' => ['Preto', 'Rosa', 'Preto'],
            'estilos' => ['Elegante'],
        ]);

        $parametros = $pdo->parametrosDaPrimeira('LIMIT :limite OFFSET :offset');

        self::assertSame('M', $parametros[':tamanho_0']);
        self::assertSame('G', $parametros[':tamanho_1']);
        self::assertArrayNotHasKey(':tamanho_2', $parametros);
        self::assertSame('Preto', $parametros[':cor_0']);
        self::assertSame('Rosa', $parametros[':cor_1']);
        self::assertArrayNotHasKey(':cor_2', $parametros);
        self::assertSame('Elegante', $parametros[':estilo_0']);
    }

    public function testFiltrosDePrecoConveremParaFloat(): void
    {
        $pdo = $this->pdoDeListagem();
        (new CatalogoRepository($pdo))->listar(['preco_min' => '50.5', 'preco_max' => 200]);

        $parametros = $pdo->parametrosDaPrimeira('LIMIT :limite OFFSET :offset');

        self::assertSame(50.5, $parametros[':preco_min']);
        self::assertSame(200.0, $parametros[':preco_max']);
    }

    public function testFiltroDePromocoesExigeOfertaAtiva(): void
    {
        $pdo = $this->pdoDeListagem();
        (new CatalogoRepository($pdo))->listar(['promocoes' => 1]);

        $sql = $pdo->execucoes()[1]['sql'] ?? '';

        self::assertStringContainsString('o.id_oferta IS NOT NULL', $sql);
    }

    public function testOrdenacaoSelecionadaEhAplicada(): void
    {
        $casos = [
            'menor-preco' => 'ORDER BY p.valor ASC, p.nome ASC, p.id_produto ASC',
            'maior-preco' => 'ORDER BY p.valor DESC, p.nome ASC, p.id_produto DESC',
            'nome' => 'ORDER BY p.nome ASC, p.id_produto ASC',
            'relevancia' => 'ORDER BY relevancia DESC, p.criado_em DESC, p.id_produto DESC',
            'invalida' => 'ORDER BY relevancia DESC, p.criado_em DESC, p.id_produto DESC',
        ];

        foreach ($casos as $ordenacao => $trecho) {
            $pdo = $this->pdoDeListagem();
            (new CatalogoRepository($pdo))->listar(['ordenacao' => $ordenacao]);

            $sql = '';

            foreach ($pdo->execucoes() as $execucao) {
                if (str_contains($execucao['sql'], 'LIMIT :limite OFFSET :offset')) {
                    $sql = $execucao['sql'];
                }
            }

            self::assertStringContainsString($trecho, $sql, 'Ordenacao: ' . $ordenacao);
        }
    }

    public function testBuscarPorIdCarregaVariacoesEMapeiaCampos(): void
    {
        $pdo = new FakePDO(static function (string $sql): FakeResult {
            if (str_contains($sql, 'WHERE p.id_produto = :id')) {
                return FakeResult::linha([
                    'id_produto' => '10',
                    'nome' => 'Vestido Midi',
                    'descricao' => null,
                    'sku' => 'VEST-10',
                    'valor' => '80.00',
                    'estoque' => '7',
                    'criado_em' => '2024-01-01',
                    'cor' => null,
                    'estilo' => null,
                    'oferta_percentual' => null,
                    'imagem' => '/img/vestido.jpg',
                    'categoria' => 'Vestidos',
                    'id_categoria' => '3',
                    'tamanho' => '',
                    'medida_busto' => null,
                    'medida_cintura' => null,
                    'medida_quadril' => null,
                    'relevancia' => '0',
                ]);
            }

            if (str_contains($sql, 'SELECT pt.id_produto_tamanho, pt.estoque')) {
                return FakeResult::linhas([
                    ['id_produto_tamanho' => '30', 'estoque' => '4', 'tamanho' => 'M'],
                ]);
            }

            return FakeResult::vazio();
        });

        $produto = (new CatalogoRepository($pdo))->buscarPorId(10);

        self::assertSame(10, $produto['id_produto']);
        self::assertNull($produto['descricao']);
        self::assertNull($produto['cor']);
        self::assertNull($produto['preco_original']);
        self::assertSame([[
            'id_produto_tamanho' => 30,
            'tamanho' => 'M',
            'estoque' => 4,
        ]], $produto['variacoes']);
    }

    public function testBuscarPorIdRetornaNullQuandoNaoExiste(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::vazio());

        self::assertNull((new CatalogoRepository($pdo))->buscarPorId(999));
    }

    public function testVariacoesConvertemEstoqueEIdParaInteiro(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::linhas([
            ['id_produto_tamanho' => '7', 'estoque' => '2', 'tamanho' => 'P'],
            ['id_produto_tamanho' => '8', 'estoque' => '0', 'tamanho' => 'M'],
        ]));

        self::assertSame([
            ['id_produto_tamanho' => 7, 'tamanho' => 'P', 'estoque' => 2],
            ['id_produto_tamanho' => 8, 'tamanho' => 'M', 'estoque' => 0],
        ], (new CatalogoRepository($pdo))->variacoes(10));
    }

    public function testContarAtivosRetornaInteiro(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::coluna('15'));

        self::assertSame(15, (new CatalogoRepository($pdo))->contarAtivos());
    }

    public function testImagensDoProdutoRetornaLinhas(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::linhas([
            ['id_imagem' => 1, 'caminho_imagem' => '/img/a.jpg'],
        ]));

        self::assertSame([['id_imagem' => 1, 'caminho_imagem' => '/img/a.jpg']], (new CatalogoRepository($pdo))->imagensDoProduto(10));
        self::assertSame(10, $pdo->parametrosDaPrimeira('FROM imagens')[':id']);
    }

    public function testCategoriasComContagemConverteTotal(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::linhas([
            ['id_categoria' => '1', 'nome' => 'Blusas', 'total' => '4'],
            ['id_categoria' => '2', 'nome' => 'Vestidos', 'total' => '9'],
        ]));

        self::assertSame([
            ['id_categoria' => 1, 'nome' => 'Blusas', 'total' => 4],
            ['id_categoria' => 2, 'nome' => 'Vestidos', 'total' => 9],
        ], (new CatalogoRepository($pdo))->categoriasComContagem());
    }

    public function testTamanhosDisponiveisRespeitamOrdemDoCatalogo(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::linhas([
            ['tamanho' => 'M'],
            ['tamanho' => 'GG'],
            ['tamanho' => 'P'],
            ['tamanho' => '44'],
            ['tamanho' => '36'],
            ['tamanho' => 'Personalizado'],
        ]));

        self::assertSame(
            ['P', 'M', 'GG', '36', '44', 'Personalizado'],
            (new CatalogoRepository($pdo))->tamanhosDisponiveis()
        );
    }

    public function testCoresEEstilosDisponiveisConveremTotal(): void
    {
        $pdo = new FakePDO(static function (string $sql): FakeResult {
            if (str_contains($sql, 'GROUP BY cor')) {
                return FakeResult::linhas([['cor' => 'Preto', 'total' => '3']]);
            }

            return FakeResult::linhas([['estilo' => 'Casual', 'total' => '5']]);
        });

        $repositorio = new CatalogoRepository($pdo);

        self::assertSame([['cor' => 'Preto', 'total' => 3]], $repositorio->coresDisponiveis());
        self::assertSame([['estilo' => 'Casual', 'total' => 5]], $repositorio->estilosDisponiveis());
    }

    public function testSugestoesUnemProdutosECategoriasSemDuplicar(): void
    {
        $pdo = new FakePDO(static function (string $sql): FakeResult {
            if (str_contains($sql, 'FROM produtos')) {
                return FakeResult::linhas([['nome' => 'Vestido'], ['nome' => 'Blusa']]);
            }

            return FakeResult::linhas([['nome' => 'AcessÃ³rios'], ['nome' => 'Vestido']]);
        });

        self::assertSame(
            ['AcessÃ³rios', 'Blusa', 'Vestido'],
            (new CatalogoRepository($pdo))->sugestoes()
        );
    }

    public function testSugestoesLimitamQuantidadeConsultada(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::linhas([]));

        (new CatalogoRepository($pdo))->sugestoes(500);

        self::assertSame(50, $pdo->parametrosDaPrimeira('FROM produtos')[':limite']);
    }
}
