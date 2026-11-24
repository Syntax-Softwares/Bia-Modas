<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CheckoutControllerTest extends TestCase
{
    use InvocaMetodoPrivado;

    private function controller(FakePDO $pdo): CheckoutController
    {
        return new CheckoutController($pdo);
    }

    /**
     * @return array<string, mixed>
     */
    private function dadosVazios(): array
    {
        return [
            'destinatario' => '',
            'telefone' => '',
            'endereco_id' => '',
            'endereco_cep' => '',
            'endereco_logradouro' => '',
            'endereco_numero' => '',
            'endereco_complemento' => '',
            'endereco_bairro' => '',
            'endereco_cidade' => '',
            'endereco_uf' => '',
            'forma_pagamento' => '',
        ];
    }

    private function pdoDeUsuario(array $usuario = [], array $endereco = []): FakePDO
    {
        return new FakePDO(static function (string $sql) use ($usuario, $endereco): FakeResult {
            if (str_contains($sql, 'WHERE u.id_usuario = :id')) {
                return $usuario === [] ? FakeResult::vazio() : FakeResult::linha($usuario);
            }

            if (str_contains($sql, 'FROM enderecos') && str_contains($sql, 'id_endereco = :id_endereco')) {
                return $endereco === [] ? FakeResult::vazio() : FakeResult::linha($endereco);
            }

            return FakeResult::vazio();
        });
    }

    public function testDadosDoCadastroUsaNomeTelefoneEEnderecoPrincipal(): void
    {
        $pdo = $this->pdoDeUsuario([
            'id_usuario' => 7,
            'nome' => ' Maria Silva ',
            'telefone' => '(14) 99999-9999',
            'endereco_id' => 5,
        ]);

        $dados = $this->invocarMetodoPrivado($this->controller($pdo), 'dadosDoCadastro', [7]);

        self::assertSame('Maria Silva', $dados['destinatario']);
        self::assertSame('14999999999', $dados['telefone']);
        self::assertSame('5', $dados['endereco_id']);
    }

    public function testDadosDoCadastroSemUsuarioPedeNovoEndereco(): void
    {
        $dados = $this->invocarMetodoPrivado($this->controller($this->pdoDeUsuario()), 'dadosDoCadastro', [7]);

        self::assertSame('', $dados['destinatario']);
        self::assertSame('novo', $dados['endereco_id']);
    }

    public function testDadosDoCadastroSemEnderecoPrincipalPedeNovoEndereco(): void
    {
        $pdo = $this->pdoDeUsuario([
            'id_usuario' => 7,
            'nome' => 'Maria',
            'telefone' => '14999999999',
            'endereco_id' => null,
        ]);

        $dados = $this->invocarMetodoPrivado($this->controller($pdo), 'dadosDoCadastro', [7]);

        self::assertSame('novo', $dados['endereco_id']);
    }

    public function testValidarUsaEnderecoSalvoDoUsuario(): void
    {
        $pdo = $this->pdoDeUsuario(
            [
                'id_usuario' => 7,
                'nome' => 'Maria Silva',
                'telefone' => '14999999999',
                'endereco_id' => 5,
            ],
            [
                'id_endereco' => 5,
                'cep' => '17200000',
                'logradouro' => 'Rua A',
                'numero' => '10',
                'complemento' => null,
                'bairro' => 'Centro',
                'cidade' => 'Jaú',
                'uf' => 'SP',
            ]
        );

        $resultado = $this->invocarMetodoPrivado($this->controller($pdo), 'validar', [[
            'endereco_id' => '5',
            'forma_pagamento' => 'pix',
        ], 7]);

        self::assertSame([], $resultado['erros']);
        self::assertSame('Rua A', $resultado['dados']['endereco_logradouro']);
        self::assertSame('10', $resultado['dados']['endereco_numero']);
        self::assertSame('', $resultado['dados']['endereco_complemento']);
        self::assertSame('Centro', $resultado['dados']['endereco_bairro']);
        self::assertSame('Jaú', $resultado['dados']['endereco_cidade']);
        self::assertSame('SP', $resultado['dados']['endereco_uf']);
        self::assertSame('pix', $resultado['dados']['forma_pagamento']);
    }

    public function testValidarRejeitaEnderecoDeOutroUsuario(): void
    {
        $pdo = $this->pdoDeUsuario([
            'id_usuario' => 7,
            'nome' => 'Maria Silva',
            'telefone' => '14999999999',
            'endereco_id' => null,
        ]);

        $resultado = $this->invocarMetodoPrivado($this->controller($pdo), 'validar', [[
            'endereco_id' => '999',
            'forma_pagamento' => 'pix',
        ], 7]);

        self::assertSame('Escolha um endereço de entrega válido.', $resultado['erros']['endereco_id']);
        self::assertSame('', $resultado['dados']['endereco_id']);
    }

    public function testValidarExigeEscolhaDeEndereco(): void
    {
        $pdo = $this->pdoDeUsuario([
            'id_usuario' => 7,
            'nome' => 'Maria Silva',
            'telefone' => '14999999999',
            'endereco_id' => null,
        ]);

        $resultado = $this->invocarMetodoPrivado($this->controller($pdo), 'validar', [[
            'endereco_id' => '',
            'forma_pagamento' => 'pix',
        ], 7]);

        self::assertSame('Escolha o endereço de entrega.', $resultado['erros']['endereco_id']);
    }

    public function testValidarExigeFormaDePagamentoValida(): void
    {
        $pdo = $this->pdoDeUsuario([
            'id_usuario' => 7,
            'nome' => 'Maria Silva',
            'telefone' => '14999999999',
            'endereco_id' => null,
        ]);

        $resultado = $this->invocarMetodoPrivado($this->controller($pdo), 'validar', [[
            'endereco_id' => '',
            'forma_pagamento' => 'dinheiro',
        ], 7]);

        self::assertSame('Escolha a forma de pagamento.', $resultado['erros']['forma_pagamento']);
    }

    public function testValidarExigeContatoCompletoNoCadastro(): void
    {
        $pdo = $this->pdoDeUsuario([
            'id_usuario' => 7,
            'nome' => 'Jo',
            'telefone' => '',
            'endereco_id' => null,
        ]);

        $resultado = $this->invocarMetodoPrivado($this->controller($pdo), 'validar', [[
            'endereco_id' => 'novo',
            'forma_pagamento' => 'boleto',
            'endereco_cep' => '17200000',
            'endereco_logradouro' => 'Rua A',
            'endereco_numero' => '10',
            'endereco_bairro' => 'Centro',
            'endereco_cidade' => 'Jaú',
            'endereco_uf' => 'SP',
        ], 7]);

        self::assertSame(
            'Complete seu nome e telefone no cadastro para finalizar a compra.',
            $resultado['erros']['contato']
        );
    }

    public function testValidarNovoEnderecoNormalizaCepEUf(): void
    {
        $dados = $this->dadosVazios();
        $erros = [];

        $this->invocarMetodoPrivado($this->controller(new FakePDO()), 'validarNovoEndereco', [[
            'endereco_cep' => '17200-000',
            'endereco_logradouro' => '  Rua das Flores  ',
            'endereco_numero' => ' 123 ',
            'endereco_complemento' => '  Apto 2 ',
            'endereco_bairro' => ' Centro ',
            'endereco_cidade' => ' Jaú ',
            'endereco_uf' => 'sp',
        ], &$dados, &$erros]);

        self::assertSame([], $erros);
        self::assertSame('17200000', $dados['endereco_cep']);
        self::assertSame('Rua das Flores', $dados['endereco_logradouro']);
        self::assertSame('123', $dados['endereco_numero']);
        self::assertSame('Apto 2', $dados['endereco_complemento']);
        self::assertSame('Centro', $dados['endereco_bairro']);
        self::assertSame('Jaú', $dados['endereco_cidade']);
        self::assertSame('SP', $dados['endereco_uf']);
    }

    public function testValidarNovoEnderecoRejeitaCamposInvalidos(): void
    {
        $dados = $this->dadosVazios();
        $erros = [];

        $this->invocarMetodoPrivado($this->controller(new FakePDO()), 'validarNovoEndereco', [[
            'endereco_cep' => '123',
            'endereco_logradouro' => 'ab',
            'endereco_numero' => '',
            'endereco_complemento' => str_repeat('x', 61),
            'endereco_bairro' => 'a',
            'endereco_cidade' => '',
            'endereco_uf' => 'S',
        ], &$dados, &$erros]);

        self::assertSame('Informe um CEP válido (8 dígitos).', $erros['endereco_cep']);
        self::assertSame('Informe a rua ou avenida.', $erros['endereco_logradouro']);
        self::assertSame('Informe o número do endereço.', $erros['endereco_numero']);
        self::assertSame('O complemento deve ter até 60 caracteres.', $erros['endereco_complemento']);
        self::assertSame('Informe o bairro.', $erros['endereco_bairro']);
        self::assertSame('Informe a cidade.', $erros['endereco_cidade']);
        self::assertSame('Informe a UF com 2 letras.', $erros['endereco_uf']);
    }

    public function testValidarNovoEnderecoRejeitaCamposLongosDemais(): void
    {
        $dados = $this->dadosVazios();
        $erros = [];

        $this->invocarMetodoPrivado($this->controller(new FakePDO()), 'validarNovoEndereco', [[
            'endereco_cep' => '17200000',
            'endereco_logradouro' => str_repeat('x', 101),
            'endereco_numero' => str_repeat('9', 11),
            'endereco_bairro' => str_repeat('b', 61),
            'endereco_cidade' => str_repeat('c', 61),
            'endereco_uf' => 'SP',
        ], &$dados, &$erros]);

        self::assertSame('A rua/avenida deve ter até 100 caracteres.', $erros['endereco_logradouro']);
        self::assertSame('Informe o número do endereço.', $erros['endereco_numero']);
        self::assertSame('O bairro deve ter até 60 caracteres.', $erros['endereco_bairro']);
        self::assertSame('A cidade deve ter até 60 caracteres.', $erros['endereco_cidade']);
    }
}
