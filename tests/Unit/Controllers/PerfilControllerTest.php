<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PerfilControllerTest extends TestCase
{
    use InvocaMetodoPrivado;

    protected function setUp(): void
    {
        $_SESSION = [];
        $_POST = [];
        $_SERVER['REMOTE_ADDR'] = '203.0.113.77';
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_POST = [];
    }

    private function controller(): PerfilController
    {
        return new PerfilController(new FakePDO());
    }

    /**
     * @return array<string, mixed>
     */
    private function dadosEnderecoValidos(): array
    {
        return [
            'nome_endereco' => 'Casa',
            'logradouro' => 'Rua A',
            'numero' => '10',
            'complemento' => null,
            'bairro' => 'Centro',
            'cidade' => 'Jaú',
            'uf' => 'SP',
            'cep' => '17200000',
        ];
    }

    public function testDadosEnderecoNormalizaCampos(): void
    {
        $dados = $this->invocarMetodoPrivado($this->controller(), 'dadosEndereco', [[
            'nome_endereco' => '  Casa ',
            'logradouro' => ' Rua A ',
            'numero' => ' 10 ',
            'complemento' => '   ',
            'bairro' => ' Centro ',
            'cidade' => ' Jaú ',
            'uf' => 'sp',
            'cep' => '17200-000',
        ]]);

        self::assertSame('Casa', $dados['nome_endereco']);
        self::assertSame('Rua A', $dados['logradouro']);
        self::assertSame('10', $dados['numero']);
        self::assertNull($dados['complemento']);
        self::assertSame('Centro', $dados['bairro']);
        self::assertSame('Jaú', $dados['cidade']);
        self::assertSame('SP', $dados['uf']);
        self::assertSame('17200000', $dados['cep']);
    }

    public function testValidarEnderecoAceitaDadosCompletos(): void
    {
        self::assertSame([], $this->invocarMetodoPrivado($this->controller(), 'validarEndereco', [$this->dadosEnderecoValidos()]));
    }

    public function testValidarEnderecoRejeitaCamposObrigatorios(): void
    {
        $erros = $this->invocarMetodoPrivado($this->controller(), 'validarEndereco', [[
            'nome_endereco' => '',
            'logradouro' => '',
            'numero' => '',
            'complemento' => null,
            'bairro' => '',
            'cidade' => '',
            'uf' => 'XX',
            'cep' => '123',
        ]]);

        self::assertSame('Informe um nome para o endereco (ate 30 caracteres).', $erros['nome_endereco']);
        self::assertSame('Informe o endereco (ate 100 caracteres).', $erros['logradouro']);
        self::assertSame('Informe o numero (ate 10 caracteres).', $erros['numero']);
        self::assertSame('Informe o bairro (ate 60 caracteres).', $erros['bairro']);
        self::assertSame('Informe a cidade (ate 60 caracteres).', $erros['cidade']);
        self::assertSame('Informe uma UF valida.', $erros['uf']);
        self::assertSame('Informe um CEP com 8 digitos.', $erros['cep']);
    }

    public function testValidarEnderecoRejeitaCamposLongosDemais(): void
    {
        $dados = $this->dadosEnderecoValidos();
        $dados['nome_endereco'] = str_repeat('n', 31);
        $dados['logradouro'] = str_repeat('l', 101);
        $dados['numero'] = str_repeat('9', 11);
        $dados['complemento'] = str_repeat('c', 61);
        $dados['bairro'] = str_repeat('b', 61);
        $dados['cidade'] = str_repeat('c', 61);

        $erros = $this->invocarMetodoPrivado($this->controller(), 'validarEndereco', [$dados]);

        self::assertSame('Informe um nome para o endereco (ate 30 caracteres).', $erros['nome_endereco']);
        self::assertSame('Informe o endereco (ate 100 caracteres).', $erros['logradouro']);
        self::assertSame('Informe o numero (ate 10 caracteres).', $erros['numero']);
        self::assertSame('O complemento deve ter no maximo 60 caracteres.', $erros['complemento']);
        self::assertSame('Informe o bairro (ate 60 caracteres).', $erros['bairro']);
        self::assertSame('Informe a cidade (ate 60 caracteres).', $erros['cidade']);
    }

    public function testIdEnderecoPostAceitaSomenteInteiroPositivo(): void
    {
        $controller = $this->controller();

        $_POST['id_endereco'] = '15';
        self::assertSame(15, $this->invocarMetodoPrivado($controller, 'idEnderecoPost'));

        $_POST['id_endereco'] = '0';
        self::assertNull($this->invocarMetodoPrivado($controller, 'idEnderecoPost'));

        $_POST['id_endereco'] = 'abc';
        self::assertNull($this->invocarMetodoPrivado($controller, 'idEnderecoPost'));

        unset($_POST['id_endereco']);
        self::assertNull($this->invocarMetodoPrivado($controller, 'idEnderecoPost'));
    }

    public function testSenhaConfereValidaHashDoBanco(): void
    {
        $hash = password_hash('BiaModas1!', PASSWORD_DEFAULT);
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::coluna($hash));
        $controller = new PerfilController($pdo);

        self::assertTrue($this->invocarMetodoPrivado($controller, 'senhaConfere', [7, 'BiaModas1!']));
        self::assertFalse($this->invocarMetodoPrivado($controller, 'senhaConfere', [7, 'SenhaErrada1!']));
    }

    public function testSenhaConfereRetornaFalseQuandoUsuarioNaoTemHash(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::coluna(false));

        self::assertFalse(
            $this->invocarMetodoPrivado(new PerfilController($pdo), 'senhaConfere', [7, 'BiaModas1!'])
        );
    }

    public function testBloqueioPorTempoNaSessaoImpedeNovasTentativas(): void
    {
        $_SESSION['_perfil_bloqueio'] = time() + 60;

        self::assertTrue($this->invocarMetodoPrivado($this->controller(), 'bloqueadoPorSenha', [7]));
    }

    public function testRegistrarFalhasBloqueiaAposCincoTentativas(): void
    {
        $usuarioId = 998877;
        $controller = $this->controller();

        for ($i = 1; $i <= 5; $i++) {
            $this->invocarMetodoPrivado($controller, 'registrarFalhaSenha', [$usuarioId]);
        }

        self::assertSame(5, $_SESSION['_perfil_falhas']);
        self::assertGreaterThan(time(), $_SESSION['_perfil_bloqueio']);
        self::assertTrue($this->invocarMetodoPrivado($controller, 'bloqueadoPorSenha', [$usuarioId]));

        $this->invocarMetodoPrivado($controller, 'limparFalhasSenha', [$usuarioId]);

        self::assertArrayNotHasKey('_perfil_falhas', $_SESSION);
        self::assertArrayNotHasKey('_perfil_bloqueio', $_SESSION);

        RateLimit::limpar('senha_falha', 'senha|u:' . $usuarioId . '|ip:' . RateLimit::ip());
        RateLimit::limpar('senha_falha', 'senha|u:' . $usuarioId);
    }
}
