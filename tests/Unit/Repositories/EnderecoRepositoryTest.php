<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class EnderecoRepositoryTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function endereco(array $sobrescrever = []): array
    {
        return array_merge([
            'id_endereco' => 5,
            'nome_endereco' => 'Casa',
            'logradouro' => 'Rua A',
            'numero' => '10',
            'complemento' => null,
            'bairro' => 'Centro',
            'cidade' => 'Jaú',
            'uf' => 'SP',
            'cep' => '17200000',
            'principal' => 0,
        ], $sobrescrever);
    }

    /**
     * @return array<string, mixed>
     */
    private function dados(): array
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

    public function testListarPorUsuarioRetornaLinhas(): void
    {
        $pdo = new FakePDO(fn (): FakeResult => FakeResult::linhas([$this->endereco()]));

        $enderecos = (new EnderecoRepository($pdo))->listarPorUsuario(7);

        self::assertCount(1, $enderecos);
        self::assertSame(7, $pdo->parametrosDaPrimeira('FROM enderecos')[':usuario_id']);
    }

    public function testBuscarDoUsuarioRetornaNullQuandoNaoPertenceAoUsuario(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::vazio());

        self::assertNull((new EnderecoRepository($pdo))->buscarDoUsuario(5, 7));
    }

    public function testContarPorUsuarioConverteParaInteiro(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::coluna('3'));

        self::assertSame(3, (new EnderecoRepository($pdo))->contarPorUsuario(7));
    }

    public function testCriarPrimeiroEnderecoDefineComoPrincipal(): void
    {
        $pdo = new FakePDO();

        $pdo->definirHandler(static function (string $sql) use ($pdo): FakeResult {
            if (str_contains($sql, 'SELECT COUNT(*) FROM enderecos')) {
                return FakeResult::coluna(0);
            }

            if (str_contains($sql, 'INSERT INTO enderecos')) {
                $pdo->definirUltimoId(11);

                return FakeResult::afetadas(1);
            }

            return FakeResult::linha(['id_usuario' => 7]);
        });

        $id = (new EnderecoRepository($pdo))->criar(7, $this->dados());

        self::assertSame(11, $id);
        self::assertSame(1, $pdo->parametrosDaPrimeira('INSERT INTO enderecos')[':principal']);
        self::assertSame(['begin', 'commit'], $pdo->transacoes());
    }

    public function testCriarSegundoEnderecoNaoEhPrincipal(): void
    {
        $pdo = new FakePDO();

        $pdo->definirHandler(static function (string $sql) use ($pdo): FakeResult {
            if (str_contains($sql, 'SELECT COUNT(*) FROM enderecos')) {
                return FakeResult::coluna(2);
            }

            if (str_contains($sql, 'INSERT INTO enderecos')) {
                $pdo->definirUltimoId(12);

                return FakeResult::afetadas(1);
            }

            return FakeResult::linha(['id_usuario' => 7]);
        });

        (new EnderecoRepository($pdo))->criar(7, $this->dados());

        self::assertSame(0, $pdo->parametrosDaPrimeira('INSERT INTO enderecos')[':principal']);
    }

    public function testAtualizarRetornaFalseParaEnderecoDeOutroUsuario(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::vazio());

        self::assertFalse((new EnderecoRepository($pdo))->atualizar(5, 7, $this->dados()));
        self::assertFalse($pdo->executou('UPDATE enderecos'));
    }

    public function testAtualizarGravaNovosDados(): void
    {
        $pdo = new FakePDO(function (string $sql): FakeResult {
            if (str_contains($sql, 'SELECT id_endereco')) {
                return FakeResult::linha($this->enderecoFake());
            }

            return FakeResult::afetadas(1);
        });

        self::assertTrue((new EnderecoRepository($pdo))->atualizar(5, 7, $this->dados()));
        self::assertSame(5, $pdo->parametrosDaPrimeira('SET nome_endereco')[':id_endereco']);
        self::assertSame(7, $pdo->parametrosDaPrimeira('SET nome_endereco')[':usuario_id']);
    }

    public function testDefinirPrincipalDesmarcaOsDemais(): void
    {
        $pdo = new FakePDO(function (string $sql): FakeResult {
            if (str_contains($sql, 'SELECT id_endereco')) {
                return FakeResult::linha($this->enderecoFake());
            }

            return FakeResult::afetadas(1);
        });

        self::assertTrue((new EnderecoRepository($pdo))->definirPrincipal(5, 7));
        self::assertSame(1, $pdo->contarExecucoes('SET principal = FALSE'));
        self::assertSame(1, $pdo->contarExecucoes('SET principal = TRUE'));
        self::assertSame(['begin', 'commit'], $pdo->transacoes());
    }

    public function testDefinirPrincipalRetornaFalseQuandoEnderecoNaoExiste(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::vazio());

        self::assertFalse((new EnderecoRepository($pdo))->definirPrincipal(5, 7));
        self::assertSame([], $pdo->transacoes());
    }

    public function testExcluirEnderecoPrincipalPromoveOutroEndereco(): void
    {
        $pdo = new FakePDO(function (string $sql): FakeResult {
            if (str_contains($sql, 'SELECT id_endereco') && str_contains($sql, 'AND usuario_id')) {
                return FakeResult::linha($this->enderecoFake(['principal' => 1]));
            }

            if (str_contains($sql, 'SELECT id_endereco') && str_contains($sql, 'id_endereco <> :id_endereco')) {
                return FakeResult::coluna(9);
            }

            return FakeResult::afetadas(1);
        });

        self::assertTrue((new EnderecoRepository($pdo))->excluir(5, 7));
        self::assertSame(1, $pdo->contarExecucoes('SET deletado_em = CURRENT_TIMESTAMP'));
        self::assertSame(9, $pdo->parametrosDaPrimeira('SET principal = TRUE')[':id_endereco']);
        self::assertSame(['begin', 'commit'], $pdo->transacoes());
    }

    public function testExcluirEnderecoNaoPrincipalNaoPromoveOutro(): void
    {
        $pdo = new FakePDO(function (string $sql): FakeResult {
            if (str_contains($sql, 'SELECT id_endereco')) {
                return FakeResult::linha($this->enderecoFake(['principal' => 0]));
            }

            return FakeResult::afetadas(1);
        });

        self::assertTrue((new EnderecoRepository($pdo))->excluir(5, 7));
        self::assertSame(0, $pdo->contarExecucoes('SET principal = TRUE'));
        self::assertSame(0, $pdo->contarExecucoes('id_endereco <> :id_endereco'));
    }

    public function testExcluirRetornaFalseQuandoEnderecoNaoPertenceAoUsuario(): void
    {
        $pdo = new FakePDO(static fn (): FakeResult => FakeResult::vazio());

        self::assertFalse((new EnderecoRepository($pdo))->excluir(5, 7));
        self::assertSame([], $pdo->transacoes());
    }

    /**
     * @return array<string, mixed>
     */
    private function enderecoFake(array $sobrescrever = []): array
    {
        return array_merge($this->endereco(), $sobrescrever);
    }
}
