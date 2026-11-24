<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class HtmlTest extends TestCase
{
    public function testEEscapaAspasParaUsoEmAtributos(): void
    {
        self::assertSame('&lt;b&gt;x&lt;/b&gt;', Html::e('<b>x</b>'));
        self::assertSame('&#039;aspas&#039;', Html::e("'aspas'"));
        self::assertSame('', Html::e(null));
    }

    public function testInicialRetornaPrimeiraLetraOuInterrogacao(): void
    {
        self::assertSame('M', Html::inicial('Maria'));
        self::assertSame('É', Html::inicial('Érica'));
        self::assertSame('?', Html::inicial(''));
    }

    public function testDataFormataISOComoBrasileiro(): void
    {
        self::assertSame('10/05/1990', Html::data('1990-05-10'));
        self::assertSame('—', Html::data(null));
        self::assertSame('—', Html::data(''));
    }

    #[DataProvider('ceps')]
    public function testCepFormataQuandoPossuiOitoDigitos(?string $cep, string $esperado): void
    {
        self::assertSame($esperado, Html::cep($cep));
    }

    /**
     * @return array<string, array{?string, string}>
     */
    public static function ceps(): array
    {
        return [
            'digitos' => ['17200000', '17200-000'],
            'mascara' => ['17200-000', '17200-000'],
            'incompleto' => ['1720000', '1720000'],
            'nulo' => [null, ''],
        ];
    }

    #[DataProvider('telefones')]
    public function testTelefoneFormataFixoECelular(?string $telefone, string $esperado): void
    {
        self::assertSame($esperado, Html::telefone($telefone));
    }

    /**
     * @return array<string, array{?string, string}>
     */
    public static function telefones(): array
    {
        return [
            'celular' => ['14999999999', '(14) 99999-9999'],
            'fixo' => ['1433334444', '(14) 3333-4444'],
            'invalido' => ['123', '123'],
            'nulo' => [null, ''],
        ];
    }

    #[DataProvider('cpfs')]
    public function testCpfFormataQuandoPossuiOnzeDigitos(?string $cpf, string $esperado): void
    {
        self::assertSame($esperado, Html::cpf($cpf));
    }

    /**
     * @return array<string, array{?string, string}>
     */
    public static function cpfs(): array
    {
        return [
            'digitos' => ['52998224725', '529.982.247-25'],
            'mascara' => ['529.982.247-25', '529.982.247-25'],
            'incompleto' => ['123', '123'],
            'nulo' => [null, ''],
        ];
    }
}
