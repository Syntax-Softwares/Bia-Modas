<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class MailerTest extends TestCase
{
    /** @var array<string, string|false> */
    private array $ambiente = [];

    protected function setUp(): void
    {
        foreach (['MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_SECURE', 'MAIL_FROM', 'MAIL_FROM_NAME'] as $variavel) {
            $this->ambiente[$variavel] = getenv($variavel);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->ambiente as $variavel => $valor) {
            if ($valor === false) {
                putenv($variavel);
            } else {
                putenv($variavel . '=' . $valor);
            }
        }
    }

    public function testFromEnvLeConfiguracaoDoAmbiente(): void
    {
        putenv('MAIL_HOST=smtp.exemplo.com');
        putenv('MAIL_PORT=587');
        putenv('MAIL_USERNAME=usuario');
        putenv('MAIL_PASSWORD=segredo');
        putenv('MAIL_SECURE=tls');
        putenv('MAIL_FROM=contato@biamodas.com');
        putenv('MAIL_FROM_NAME=Bia Modas');

        $mailer = Mailer::fromEnv();

        self::assertSame('smtp.exemplo.com', $this->propriedade($mailer, 'host'));
        self::assertSame(587, $this->propriedade($mailer, 'port'));
        self::assertSame('usuario', $this->propriedade($mailer, 'username'));
        self::assertSame('segredo', $this->propriedade($mailer, 'password'));
        self::assertSame('tls', $this->propriedade($mailer, 'secure'));
        self::assertSame('contato@biamodas.com', $this->propriedade($mailer, 'from'));
        self::assertSame('Bia Modas', $this->propriedade($mailer, 'fromName'));
    }

    public function testFromEnvUsaPadroesDeDesenvolvimento(): void
    {
        foreach (['MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_SECURE', 'MAIL_FROM', 'MAIL_FROM_NAME'] as $variavel) {
            putenv($variavel);
        }

        $mailer = Mailer::fromEnv();

        self::assertSame('mailpit', $this->propriedade($mailer, 'host'));
        self::assertSame(1025, $this->propriedade($mailer, 'port'));
        self::assertSame('', $this->propriedade($mailer, 'username'));
        self::assertSame('nao-responda@biamodas.local', $this->propriedade($mailer, 'from'));
    }

    public function testTemplateDeCodigoEscapaNomeEIncluiCodigo(): void
    {
        $mailer = new Mailer('host', 1025, '', '', '', 'from@x.com', 'Bia Modas');

        $html = $this->invocarRenderizar($mailer, [
            'nome' => 'Maria <script>alert(1)</script>',
            'codigo' => '123456',
        ]);

        self::assertStringContainsString('123456', $html);
        self::assertStringContainsString('Maria &lt;script&gt;alert(1)&lt;/script&gt;', $html);
        self::assertStringNotContainsString('<script>alert(1)</script>', $html);
    }

    private function propriedade(Mailer $mailer, string $nome): mixed
    {
        $propriedade = new ReflectionProperty(Mailer::class, $nome);

        return $propriedade->getValue($mailer);
    }

    /**
     * @param array{nome: string, codigo: string} $dados
     */
    private function invocarRenderizar(Mailer $mailer, array $dados): string
    {
        $metodo = new ReflectionMethod($mailer, 'renderizar');

        return (string) $metodo->invoke($mailer, 'codigo_recuperacao', $dados);
    }
}
