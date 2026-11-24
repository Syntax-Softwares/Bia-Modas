<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProvadorControllerTest extends TestCase
{
    use InvocaMetodoPrivado;

    private function controller(): ProvadorController
    {
        return new ProvadorController(new FakePDO());
    }

    #[DataProvider('medidas')]
    public function testDecimalOuNullConverteEArredondaMedidas(mixed $entrada, ?float $esperado): void
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
    public static function medidas(): array
    {
        return [
            'vazia' => ['', null],
            'inteira' => ['90', 90.0],
            'decimal com virgula' => ['90,55', 90.6],
            'decimal com ponto' => ['90.54', 90.5],
            'invalida' => ['abc', -1.0],
        ];
    }

    public function testTextoMedidaRemoveControleELimitaDozeCaracteres(): void
    {
        $resultado = $this->invocarMetodoPrivado($this->controller(), 'textoMedida', ["  90\x00,5  "]);

        self::assertSame('90,5', $resultado);
        self::assertSame(12, strlen($this->invocarMetodoPrivado($this->controller(), 'textoMedida', [str_repeat('9', 20)])));
    }
}
