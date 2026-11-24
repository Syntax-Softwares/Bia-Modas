<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class MailerFakeRecuperacao extends Mailer
{
    /** @var list<array{para: string, nome: string, codigo: string}> */
    public array $enviados = [];

    public function __construct(private ?Throwable $erro = null)
    {
    }

    public function enviarCodigo(string $para, string $nome, string $codigo): void
    {
        if ($this->erro !== null) {
            throw $this->erro;
        }

        $this->enviados[] = ['para' => $para, 'nome' => $nome, 'codigo' => $codigo];
    }
}

final class RecuperacaoSenhaControllerTest extends TestCase
{
    use InvocaMetodoPrivado;

    protected function setUp(): void
    {
        $_SESSION = [];
        $_SERVER['REMOTE_ADDR'] = '203.0.113.99';
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    private function controller(FakePDO $pdo, ?MailerFakeRecuperacao $mailer = null): RecuperacaoSenhaController
    {
        return new RecuperacaoSenhaController($pdo, $mailer ?? new MailerFakeRecuperacao());
    }

    private function pdoDeRecuperacao(bool $podeSolicitar = true): FakePDO
    {
        $pdo = new FakePDO();

        $pdo->definirHandler(static function (string $sql) use ($pdo, $podeSolicitar): FakeResult {
            if (str_contains($sql, 'FROM codigos_recuperacao_senha') && str_contains($sql, 'COUNT(*)')) {
                return FakeResult::linha([
                    'total_hora' => $podeSolicitar ? 0 : 3,
                    'ultimo_minuto' => 0,
                ]);
            }

            if (str_contains($sql, 'INSERT INTO codigos_recuperacao_senha')) {
                $pdo->definirUltimoId(5);

                return FakeResult::afetadas(1);
            }

            return FakeResult::afetadas(1);
        });

        return $pdo;
    }

    public function testMascararEmailPreservaDominio(): void
    {
        $controller = $this->controller(new FakePDO());

        self::assertSame('m***@exemplo.com', $this->invocarMetodoPrivado($controller, 'mascararEmail', ['maria@exemplo.com']));
        self::assertSame('a***@b', $this->invocarMetodoPrivado($controller, 'mascararEmail', ['a@b']));
        self::assertSame('sem-arroba', $this->invocarMetodoPrivado($controller, 'mascararEmail', ['sem-arroba']));
        self::assertSame('@exemplo.com', $this->invocarMetodoPrivado($controller, 'mascararEmail', ['@exemplo.com']));
    }

    public function testSessaoAtivaDescartaSolicitacaoExpirada(): void
    {
        $_SESSION['recuperacao'] = [
            'usuario_id' => 7,
            'email' => 'maria@exemplo.com',
            'expira_em' => time() - 1,
        ];

        $resultado = $this->invocarMetodoPrivado($this->controller(new FakePDO()), 'sessaoAtiva');

        self::assertNull($resultado);
        self::assertArrayNotHasKey('recuperacao', $_SESSION);
    }

    public function testSessaoAtivaRetornaSolicitacaoValida(): void
    {
        $_SESSION['recuperacao'] = [
            'usuario_id' => 7,
            'email' => 'maria@exemplo.com',
            'expira_em' => time() + 60,
        ];

        $resultado = $this->invocarMetodoPrivado($this->controller(new FakePDO()), 'sessaoAtiva');

        self::assertSame(7, $resultado['usuario_id']);
    }

    public function testSessaoAtivaSemSolicitacaoRetornaNull(): void
    {
        self::assertNull($this->invocarMetodoPrivado($this->controller(new FakePDO()), 'sessaoAtiva'));
    }

    public function testEnviarCodigoGravaHashEEnviaEmail(): void
    {
        $pdo = $this->pdoDeRecuperacao();
        $mailer = new MailerFakeRecuperacao();
        $controller = $this->controller($pdo, $mailer);

        $resultado = $this->invocarMetodoPrivado($controller, 'enviarCodigo', [[
            'id_usuario' => 7,
            'email' => 'maria@exemplo.com',
            'nome' => 'Maria',
        ]]);

        self::assertTrue($resultado);
        self::assertCount(1, $mailer->enviados);
        self::assertSame('maria@exemplo.com', $mailer->enviados[0]['para']);
        self::assertSame('Maria', $mailer->enviados[0]['nome']);
        self::assertMatchesRegularExpression('/^\d{6}$/', $mailer->enviados[0]['codigo']);

        $hash = $pdo->parametrosDaPrimeira('INSERT INTO codigos_recuperacao_senha')[':codigo_hash'];

        self::assertNotSame($mailer->enviados[0]['codigo'], $hash);
        self::assertTrue(password_verify($mailer->enviados[0]['codigo'], $hash));
    }

    public function testEnviarCodigoRespeitaLimiteDeSolicitacoes(): void
    {
        $pdo = $this->pdoDeRecuperacao(podeSolicitar: false);
        $mailer = new MailerFakeRecuperacao();
        $controller = $this->controller($pdo, $mailer);

        $resultado = $this->invocarMetodoPrivado($controller, 'enviarCodigo', [[
            'id_usuario' => 7,
            'email' => 'maria@exemplo.com',
            'nome' => 'Maria',
        ]]);

        self::assertFalse($resultado);
        self::assertSame([], $mailer->enviados);
        self::assertFalse($pdo->executou('INSERT INTO codigos_recuperacao_senha'));
    }

    public function testFalhaDeSmtpNaoPropagaExcecao(): void
    {
        $pdo = $this->pdoDeRecuperacao();
        $mailer = new MailerFakeRecuperacao(new RuntimeException('smtp fora do ar'));
        $controller = $this->controller($pdo, $mailer);

        $logAnterior = ini_set('error_log', sys_get_temp_dir() . '/biamodas-mailer-test.log');

        try {
            self::assertFalse($this->invocarMetodoPrivado($controller, 'enviarCodigo', [[
                'id_usuario' => 7,
                'email' => 'maria@exemplo.com',
                'nome' => 'Maria',
            ]]));
        } finally {
            ini_set('error_log', (string) $logAnterior);
            @unlink(sys_get_temp_dir() . '/biamodas-mailer-test.log');
        }
    }

    public function testRegistrarFalhaInvalidaCodigoAoAtingirCincoTentativas(): void
    {
        $pdo = $this->pdoDeRecuperacao();
        $controller = $this->controller($pdo);

        $this->invocarMetodoPrivado($controller, 'registrarFalha', [[
            'id_codigo' => 5,
            'tentativas' => 4,
        ]]);

        self::assertSame(1, $pdo->contarExecucoes('tentativas = tentativas + 1'));
        self::assertSame(1, $pdo->contarExecucoes('SET usado_em = NOW()'));
    }

    public function testRegistrarFalhaNaoInvalidaAntesDoLimite(): void
    {
        $pdo = $this->pdoDeRecuperacao();
        $controller = $this->controller($pdo);

        $this->invocarMetodoPrivado($controller, 'registrarFalha', [[
            'id_codigo' => 5,
            'tentativas' => 1,
        ]]);

        self::assertSame(1, $pdo->contarExecucoes('tentativas = tentativas + 1'));
        self::assertSame(0, $pdo->contarExecucoes('SET usado_em = NOW()'));
    }

    public function testRegistrarFalhaSemCodigoNaoTocaNoBanco(): void
    {
        $pdo = $this->pdoDeRecuperacao();

        $this->invocarMetodoPrivado($this->controller($pdo), 'registrarFalha', [null]);

        self::assertSame([], $pdo->execucoes());
    }
}
