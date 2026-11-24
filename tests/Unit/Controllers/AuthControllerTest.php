<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AuthControllerTest extends TestCase
{
    use InvocaMetodoPrivado;

    private function controller(): AuthController
    {
        return new AuthController(new FakePDO());
    }

    #[DataProvider('decimais')]
    public function testDecimalOuNullInterpretaMedidas(mixed $entrada, ?float $esperado): void
    {
        $resultado = $this->invocarMetodoPrivado($this->controller(), 'decimalOuNull', [$entrada]);

        if ($esperado === null) {
            self::assertNull($resultado);

            return;
        }

        self::assertSame($esperado, $resultado);
    }

    /**
     * @return array<string, array{mixed, ?float}>
     */
    public static function decimais(): array
    {
        return [
            'vazio' => ['', null],
            'null' => [null, null],
            'inteiro' => ['90', 90.0],
            'decimal com virgula' => ['90,5', 90.5],
            'decimal com ponto' => ['90.5', 90.5],
            'invalido' => ['abc', -1.0],
        ];
    }

    public function testPrimeiroNomeRetornaPrimeiraPalavra(): void
    {
        self::assertSame('Maria', $this->invocarMetodoPrivado($this->controller(), 'primeiroNome', ['Maria Silva']));
        self::assertSame('Ana', $this->invocarMetodoPrivado($this->controller(), 'primeiroNome', ['  Ana   Beatriz ']));
        self::assertSame('', $this->invocarMetodoPrivado($this->controller(), 'primeiroNome', ['   ']));
    }
}
