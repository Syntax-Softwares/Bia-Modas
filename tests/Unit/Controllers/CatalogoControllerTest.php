<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CatalogoControllerTest extends TestCase
{
    use InvocaMetodoPrivado;

    private function controller(): CatalogoController
    {
        return new CatalogoController(new FakePDO());
    }

    public function testTextoRemoveControleAplicaTrimELimitaTamanho(): void
    {
        $texto = $this->invocarMetodoPrivado($this->controller(), 'texto', ["  ves\x00tido  ", 100]);
        $longo = $this->invocarMetodoPrivado($this->controller(), 'texto', [str_repeat('a', 120), 100]);
        $unicode = $this->invocarMetodoPrivado($this->controller(), 'texto', [str_repeat('ç', 120), 100]);

        self::assertSame('vestido', $texto);
        self::assertSame(100, strlen($longo));
        self::assertSame(100, mb_strlen($unicode, 'UTF-8'));
    }

    public function testListaSelecionadaAceitaSomenteValoresPermitidos(): void
    {
        $selecionados = $this->invocarMetodoPrivado($this->controller(), 'listaSelecionada', [
            ['M', 'G', 'M', 'X', 34],
            ['M', 'G', '34'],
        ]);

        self::assertSame(['M', 'G', '34'], $selecionados);
    }

    public function testListaSelecionadaAceitaValorUnicoEIgnoraTiposInvalidos(): void
    {
        self::assertSame(['M'], $this->invocarMetodoPrivado($this->controller(), 'listaSelecionada', [' M ', ['M']]));
        self::assertSame([], $this->invocarMetodoPrivado($this->controller(), 'listaSelecionada', [null, ['M']]));
    }

    public function testPrecosInterpretamVirgulaEInvertemFaixa(): void
    {
        self::assertSame(
            [50.0, 100.0],
            $this->invocarMetodoPrivado($this->controller(), 'precos', ['100', '50,00'])
        );
    }

    public function testPrecosRejeitamValoresInvalidos(): void
    {
        self::assertSame(
            [null, null],
            $this->invocarMetodoPrivado($this->controller(), 'precos', ['abc', '-1'])
        );
        self::assertSame(
            [null, null],
            $this->invocarMetodoPrivado($this->controller(), 'precos', ['1000001', null])
        );
    }

    public function testDecimalArredondaParaDuasCasas(): void
    {
        self::assertSame(10.56, $this->invocarMetodoPrivado($this->controller(), 'decimal', ['10,555']));
        self::assertNull($this->invocarMetodoPrivado($this->controller(), 'decimal', ['']));
        self::assertNull($this->invocarMetodoPrivado($this->controller(), 'decimal', [null]));
    }
}
