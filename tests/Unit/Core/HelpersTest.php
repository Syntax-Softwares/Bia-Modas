<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class HelpersTest extends TestCase
{
    public function testEEscapaHtmlEmTextoEAtributos(): void
    {
        self::assertSame(
            '&lt;script&gt;alert(&#039;xss&#039;)&lt;/script&gt;',
            e("<script>alert('xss')</script>")
        );
        self::assertSame('&quot;aspas&quot;', e('"aspas"'));
        self::assertSame('', e(null));
    }

    #[DataProvider('valoresMonetarios')]
    public function testMoneyFormataNoPadraoBrasileiro(mixed $valor, string $esperado): void
    {
        self::assertSame($esperado, money($valor));
    }

    /**
     * @return array<string, array{mixed, string}>
     */
    public static function valoresMonetarios(): array
    {
        return [
            'inteiro' => [199, 'R$ 199,00'],
            'decimal' => [199.9, 'R$ 199,90'],
            'string' => ['1234.5', 'R$ 1.234,50'],
            'zero' => [0, 'R$ 0,00'],
            'nulo' => [null, 'R$ 0,00'],
            'negativo' => [-10.5, 'R$ -10,50'],
        ];
    }

    public function testInicialUsaPrimeiraLetraOuInterrogacao(): void
    {
        self::assertSame('A', inicial('Ana'));
        self::assertSame('É', inicial('Érica'));
        self::assertSame('?', inicial('   '));
        self::assertSame('?', inicial(''));
    }

    #[DataProvider('precosOriginais')]
    public function testPrecoOriginalCalculaValorAntesDoDesconto(float $valor, ?float $percentual, ?float $esperado): void
    {
        $resultado = preco_original($valor, $percentual);

        if ($esperado === null) {
            self::assertNull($resultado);

            return;
        }

        self::assertEqualsWithDelta($esperado, (float) $resultado, 0.0001);
    }

    /**
     * @return array<string, array{float, ?float, ?float}>
     */
    public static function precosOriginais(): array
    {
        return [
            'vinte por cento' => [80.0, 0.2, 100.0],
            'trinta por cento' => [69.93, 0.3, 99.9],
            'sem oferta' => [80.0, null, null],
            'percentual zero' => [80.0, 0.0, null],
            'percentual total' => [80.0, 1.0, null],
            'percentual negativo' => [80.0, -0.1, null],
        ];
    }

    public function testSomenteDigitosRemoveTudoQueNaoEDigito(): void
    {
        self::assertSame('17200000', somente_digitos('17200-000'));
        self::assertSame('', somente_digitos('abc'));
    }

    public function testAlgumPreenchidoConsideraStringsComEspacoComoVazias(): void
    {
        self::assertFalse(algum_preenchido(['', '   ', null]));
        self::assertTrue(algum_preenchido(['', 'x']));
        self::assertTrue(algum_preenchido([0]));
        self::assertFalse(algum_preenchido([]));
    }

    public function testAssetAdicionaVersaoSomenteParaArquivoExistente(): void
    {
        $existente = asset('/css/base.css');
        $inexistente = asset('/arquivo-que-nao-existe.css');

        self::assertMatchesRegularExpression('#^/css/base\.css\?v=\d+$#', $existente);
        self::assertSame('/arquivo-que-nao-existe.css', $inexistente);
    }

    public function testPostIntLeEValidaInteirosDoPost(): void
    {
        $anterior = $_POST;

        try {
            $_POST['id'] = '42';
            $_POST['invalido'] = 'abc';

            self::assertSame(42, post_int('id'));
            self::assertNull(post_int('invalido'));
            self::assertNull(post_int('ausente'));
        } finally {
            $_POST = $anterior;
        }
    }

    #[DataProvider('datasHora')]
    public function testDataHoraFormataDatasValidas(?string $entrada, string $esperado): void
    {
        self::assertSame($esperado, data_hora($entrada));
    }

    /**
     * @return array<string, array{?string, string}>
     */
    public static function datasHora(): array
    {
        return [
            'data completa' => ['2024-01-02 15:30:00', '02/01/2024 15:30'],
            'somente data' => ['2024-01-02', '02/01/2024 00:00'],
            'nula' => [null, ''],
            'vazia' => ['', ''],
            'invalida' => ['nao-e-data', ''],
        ];
    }

    #[DataProvider('statusDePagamento')]
    public function testStatusPagamentoTraduzSituacao(?string $status, string $rotulo, string $classe): void
    {
        self::assertSame(['rotulo' => $rotulo, 'classe' => $classe], status_pagamento($status));
    }

    /**
     * @return array<string, array{?string, string, string}>
     */
    public static function statusDePagamento(): array
    {
        return [
            'pago' => ['pago', 'Pagamento aprovado', 'pago'],
            'cancelado' => ['cancelado', 'Pagamento cancelado', 'cancelado'],
            'pendente' => ['pendente', 'Aguardando pagamento', 'pendente'],
            'desconhecido' => ['outro', 'Aguardando pagamento', 'pendente'],
            'nulo' => [null, 'Aguardando pagamento', 'pendente'],
        ];
    }

    #[DataProvider('metodosDePagamento')]
    public function testRotuloPagamentoTraduzMetodo(?string $metodo, string $esperado): void
    {
        self::assertSame($esperado, rotulo_pagamento($metodo));
    }

    /**
     * @return array<string, array{?string, string}>
     */
    public static function metodosDePagamento(): array
    {
        return [
            'pix' => ['pix', 'PIX'],
            'cartao' => ['cartao', 'Cartão de crédito'],
            'boleto' => ['boleto', 'Boleto'],
            'invalido' => ['dinheiro', 'Não informado'],
            'nulo' => [null, 'Não informado'],
        ];
    }

    #[DataProvider('telefonesFormatados')]
    public function testFormatarTelefoneAplicaMascaraQuandoPossivel(?string $telefone, string $esperado): void
    {
        self::assertSame($esperado, formatar_telefone($telefone));
    }

    /**
     * @return array<string, array{?string, string}>
     */
    public static function telefonesFormatados(): array
    {
        return [
            'celular' => ['14999999999', '(14) 99999-9999'],
            'celular com mascara' => ['(14) 99999-9999', '(14) 99999-9999'],
            'fixo' => ['1433334444', '(14) 3333-4444'],
            'incompleto' => ['149999', '149999'],
            'nulo' => [null, ''],
        ];
    }

    public function testTamanhoTextoContaCaracteresUnicode(): void
    {
        self::assertSame(4, tamanho_texto('José'));
        self::assertSame(3, tamanho_texto('abc'));
        self::assertSame(0, tamanho_texto(''));
    }
}
