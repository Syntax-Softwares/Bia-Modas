<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class RateLimitTest extends TestCase
{
    private string $bucket = '';

    private string $errorLogTeste = '';

    private string|false $errorLogAnterior = false;

    protected function setUp(): void
    {
        $_SESSION = [];
        $_SERVER['REMOTE_ADDR'] = '203.0.113.10';
        // Torna deterministico o log amostrado de bloqueios (mt_rand).
        mt_srand(1);
        $this->bucket = 'test_rl_' . bin2hex(random_bytes(4));
        $this->errorLogTeste = sys_get_temp_dir() . '/biamodas-ratelimit-' . bin2hex(random_bytes(4)) . '.log';
        $this->errorLogAnterior = ini_set('error_log', $this->errorLogTeste);
    }

    protected function tearDown(): void
    {
        RateLimit::limpar($this->bucket, 'chave');

        $arquivo = dirname(__DIR__, 3) . '/storage/ratelimit/' . $this->bucket . '.json';

        if (is_file($arquivo)) {
            @unlink($arquivo);
        }

        ini_set('error_log', (string) $this->errorLogAnterior);
        @unlink($this->errorLogTeste);

        $_SESSION = [];
    }

    public function testIpGeraHashEstavelSemExporEndereco(): void
    {
        $hash = RateLimit::ip();

        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
        self::assertStringNotContainsString('203.0.113.10', $hash);
        self::assertSame($hash, RateLimit::ip());

        $_SERVER['REMOTE_ADDR'] = '198.51.100.7';
        self::assertNotSame($hash, RateLimit::ip());
    }

    public function testIpSemRemoteAddrUsaValorPadrao(): void
    {
        unset($_SERVER['REMOTE_ADDR']);

        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', RateLimit::ip());
    }

    public function testConsumirBloqueiaQuandoAtingeMaximo(): void
    {
        self::assertTrue(RateLimit::consumir($this->bucket, 'chave', 2, 60));
        self::assertTrue(RateLimit::consumir($this->bucket, 'chave', 2, 60));
        self::assertFalse(RateLimit::consumir($this->bucket, 'chave', 2, 60));
        self::assertSame(2, RateLimit::contar($this->bucket, 'chave', 60));
    }

    public function testLimparZeraContagem(): void
    {
        RateLimit::consumir($this->bucket, 'chave', 5, 60);
        RateLimit::consumir($this->bucket, 'chave', 5, 60);

        RateLimit::limpar($this->bucket, 'chave');

        self::assertSame(0, RateLimit::contar($this->bucket, 'chave', 60));
        self::assertTrue(RateLimit::consumir($this->bucket, 'chave', 1, 60));
    }

    public function testRegistrarContaTodasAsOcorrencias(): void
    {
        RateLimit::registrar($this->bucket, 'chave', 60);
        RateLimit::registrar($this->bucket, 'chave', 60);
        RateLimit::registrar($this->bucket, 'chave', 60);

        self::assertSame(3, RateLimit::contar($this->bucket, 'chave', 60));
    }

    public function testMaximoZeroEhNormalizadoParaUm(): void
    {
        self::assertTrue(RateLimit::consumir($this->bucket, 'chave', 0, 60));
        self::assertFalse(RateLimit::consumir($this->bucket, 'chave', 0, 60));
    }

    public function testBucketInvalidoNaoPersisteContagem(): void
    {
        $logAnterior = ini_set('error_log', sys_get_temp_dir() . '/biamodas-rate-limit-test.log');

        try {
            self::assertTrue(RateLimit::consumir('Bucket Invalido!', 'chave', 1, 60));
            self::assertSame(0, RateLimit::contar('Bucket Invalido!', 'chave', 60));
        } finally {
            ini_set('error_log', (string) $logAnterior);
            @unlink(sys_get_temp_dir() . '/biamodas-rate-limit-test.log');
        }
    }

    public function testChavesDiferentesPossuemContadoresIndependentes(): void
    {
        self::assertTrue(RateLimit::consumir($this->bucket, 'a', 1, 60));
        self::assertFalse(RateLimit::consumir($this->bucket, 'a', 1, 60));
        self::assertTrue(RateLimit::consumir($this->bucket, 'b', 1, 60));
    }
}
