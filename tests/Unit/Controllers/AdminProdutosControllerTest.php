<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AdminProdutosControllerTest extends TestCase
{
    use InvocaMetodoPrivado;

    protected function setUp(): void
    {
        $_SESSION = [
            'usuario' => [
                'id_usuario' => 1,
                'nome' => 'Admin',
                'email' => 'admin@biamodas.com',
                'is_admin' => true,
            ],
        ];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    private function controller(?FakePDO $pdo = null): AdminProdutosController
    {
        $pdo ??= new FakePDO(static function (string $sql, array $parametros): FakeResult {
            if (str_contains($sql, 'FROM tamanhos')) {
                return FakeResult::linhas([
                    ['id_tamanho' => 1, 'nome' => 'P'],
                    ['id_tamanho' => 2, 'nome' => 'M'],
                ]);
            }

            if (
                str_contains($sql, 'FROM categorias')
                && str_contains($sql, 'id_categoria = :id')
                && ($parametros[':id'] ?? null) === 3
            ) {
                return FakeResult::linha(['id_categoria' => 3, 'nome' => 'Vestidos']);
            }

            return FakeResult::vazio();
        });

        return new AdminProdutosController($pdo);
    }

    /**
     * @return array<string, mixed>
     */
    private function formularioValido(): array
    {
        return [
            'nome' => 'Vestido Midi',
            'sku' => 'VEST-1',
            'descricao' => 'Vestido leve.',
            'valor' => '100.00',
            'tamanhos' => [1 => 5],
            'categoria_id' => '3',
            'cor' => 'Rosa',
            'estilo' => 'Elegante',
            'oferta_percentual' => '20',
            'imagem' => '/img/vestido.jpg',
            'ativo' => true,
        ];
    }

    public function testNormalizarTamanhosMantemSomenteSelecionados(): void
    {
        $tamanhos = $this->invocarMetodoPrivado($this->controller(), 'normalizarTamanhos', [[
            '1' => ['selecionado' => '1', 'estoque' => '5'],
            '2' => ['selecionado' => ''],
            '3' => ['selecionado' => '1', 'estoque' => 'abc'],
            'abc' => ['selecionado' => '1', 'estoque' => '2'],
            '4' => 'invalido',
        ]]);

        self::assertSame([1 => 5, 3 => -1], $tamanhos);
    }

    public function testNormalizarTamanhosRejeitaEntradaNaoArray(): void
    {
        self::assertSame([], $this->invocarMetodoPrivado($this->controller(), 'normalizarTamanhos', ['abc']));
    }

    public function testValidarAceitaFormularioCompleto(): void
    {
        self::assertSame(
            [],
            $this->invocarMetodoPrivado($this->controller(), 'validar', [$this->formularioValido(), null])
        );
    }

    public function testValidarRejeitaCamposObrigatoriosInvalidos(): void
    {
        $erros = $this->invocarMetodoPrivado($this->controller(), 'validar', [[
            'nome' => 'ab',
            'sku' => '',
            'descricao' => str_repeat('d', 256),
            'valor' => 'abc',
            'tamanhos' => [],
            'categoria_id' => '999',
            'cor' => str_repeat('c', 31),
            'estilo' => str_repeat('e', 31),
            'oferta_percentual' => '100',
            'imagem' => str_repeat('i', 256),
            'ativo' => true,
        ], null]);

        self::assertSame('Informe um nome entre 3 e 255 caracteres.', $erros['nome']);
        self::assertSame('Informe um SKU de até 50 caracteres.', $erros['sku']);
        self::assertSame('A descrição deve ter até 255 caracteres.', $erros['descricao']);
        self::assertSame('Informe um valor maior que zero.', $erros['valor']);
        self::assertSame('Selecione pelo menos um tamanho e informe o estoque.', $erros['tamanhos']);
        self::assertSame('Selecione uma categoria válida.', $erros['categoria_id']);
        self::assertSame('A cor deve ter até 30 caracteres.', $erros['cor']);
        self::assertSame('O estilo deve ter até 30 caracteres.', $erros['estilo']);
        self::assertSame('Informe um percentual entre 1 e 99.', $erros['oferta_percentual']);
        self::assertSame('O caminho da imagem deve ter até 255 caracteres.', $erros['imagem']);
    }

    public function testValidarRejeitaTamanhoInexistenteNoCatalogo(): void
    {
        $form = $this->formularioValido();
        $form['tamanhos'] = [99 => 5];

        $erros = $this->invocarMetodoPrivado($this->controller(), 'validar', [$form, null]);

        self::assertSame('Um dos tamanhos selecionados é inválido.', $erros['tamanhos']);
    }

    public function testValidarRejeitaEstoqueNegativo(): void
    {
        $form = $this->formularioValido();
        $form['tamanhos'] = [1 => -1];

        $erros = $this->invocarMetodoPrivado($this->controller(), 'validar', [$form, null]);

        self::assertSame('Informe um estoque válido para cada tamanho.', $erros['tamanhos']);
    }

    public function testValidarRejeitaOfertaForaDoIntervalo(): void
    {
        foreach (['0', '100', '-1', 'abc'] as $percentual) {
            $form = $this->formularioValido();
            $form['oferta_percentual'] = $percentual;

            $erros = $this->invocarMetodoPrivado($this->controller(), 'validar', [$form, null]);

            self::assertSame('Informe um percentual entre 1 e 99.', $erros['oferta_percentual'], 'Percentual: ' . $percentual);
        }
    }

    public function testFormularioVazioTemValoresPadrao(): void
    {
        $form = $this->invocarMetodoPrivado($this->controller(), 'formularioVazio');

        self::assertNull($form['id_produto']);
        self::assertSame('', $form['nome']);
        self::assertSame([], $form['tamanhos']);
        self::assertTrue($form['ativo']);
    }

    public function testFormularioDoBancoFormataValoresParaEdicao(): void
    {
        $form = $this->invocarMetodoPrivado($this->controller(), 'formularioDoBanco', [
            [
                'id_produto' => 12,
                'nome' => 'Vestido',
                'sku' => 'VEST-12',
                'descricao' => 'Descrição',
                'valor' => 100.5,
                'categoria_id' => 3,
                'cor' => 'Rosa',
                'estilo' => 'Elegante',
                'oferta_percentual' => 0.2,
                'imagem' => '/img/vestido.jpg',
                'ativo' => 1,
            ],
            [1 => 5],
        ]);

        self::assertSame(12, $form['id_produto']);
        self::assertSame('100,50', $form['valor']);
        self::assertSame('3', $form['categoria_id']);
        self::assertSame('20', $form['oferta_percentual']);
        self::assertSame([1 => 5], $form['tamanhos']);
        self::assertTrue($form['ativo']);
    }
}
