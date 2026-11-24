<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CatalogoTest extends TestCase
{
    public function testMoneyFormataValores(): void
    {
        self::assertSame('R$ 1.299,90', Catalogo::money(1299.9));
        self::assertSame('R$ 0,00', Catalogo::money(null));
    }

    #[DataProvider('precosOriginais')]
    public function testPrecoOriginalAceitaSomentePercentualFracionarioValido(mixed $valor, mixed $percentual, ?float $esperado): void
    {
        $resultado = Catalogo::precoOriginal($valor, $percentual);

        if ($esperado === null) {
            self::assertNull($resultado);

            return;
        }

        self::assertEqualsWithDelta($esperado, (float) $resultado, 0.0001);
    }

    /**
     * @return array<string, array{mixed, mixed, ?float}>
     */
    public static function precosOriginais(): array
    {
        return [
            'vinte por cento' => [80, 0.2, 100.0],
            'valor como string' => ['80', 0.2, 100.0],
            'sem percentual' => [80, null, null],
            'zero' => [80, 0, null],
            'cem por cento' => [80, 1, null],
            'valor zero' => [0, 0.2, null],
            'valor negativo' => [-10, 0.2, null],
        ];
    }

    #[DataProvider('percentuais')]
    public function testDescontoPercentualConverteFracaoEmInteiro(mixed $fracao, int $esperado): void
    {
        self::assertSame($esperado, Catalogo::descontoPercentual($fracao));
    }

    /**
     * @return array<string, array{mixed, int}>
     */
    public static function percentuais(): array
    {
        return [
            'trinta por cento' => [0.3, 30],
            'arredonda para cima' => [0.305, 31],
            'zero' => [0, 0],
            'inteiro' => [1, 100],
        ];
    }

    #[DataProvider('cores')]
    public function testCorHexMapeiaCoresConhecidas(?string $cor, string $esperado): void
    {
        self::assertSame($esperado, Catalogo::corHex($cor));
    }

    /**
     * @return array<string, array{?string, string}>
     */
    public static function cores(): array
    {
        return [
            'preto' => ['Preto', '#212529'],
            'com espacos' => ['  Rosa ', '#d63384'],
            'desconhecida' => ['Lilas', '#cccccc'],
            'nula' => [null, '#cccccc'],
        ];
    }

    public function testCorEhClaraIdentificaCoresDeFundoClaro(): void
    {
        self::assertTrue(Catalogo::corEhClara('Branco'));
        self::assertTrue(Catalogo::corEhClara('Bege'));
        self::assertTrue(Catalogo::corEhClara(' Prata '));
        self::assertFalse(Catalogo::corEhClara('Preto'));
        self::assertFalse(Catalogo::corEhClara(null));
    }

    public function testUrlSemFiltrosApontaParaCategoria(): void
    {
        self::assertSame('/categoria', Catalogo::url());
    }

    public function testUrlPreservaFiltrosERemoveValoresVazios(): void
    {
        $url = Catalogo::url([
            'busca' => 'vestido azul',
            'tamanhos' => ['M', 'G'],
            'preco_min' => 50.0,
            'preco_max' => null,
            'pagina' => '',
            'promocoes' => false,
            'cores' => [],
        ]);

        self::assertSame(
            '/categoria?busca=vestido%20azul&tamanhos%5B0%5D=M&tamanhos%5B1%5D=G&preco_min=50',
            $url
        );
    }

    public function testUrlAplicaOverridesSobreFiltrosAtuais(): void
    {
        $url = Catalogo::url(
            ['busca' => 'blusa', 'pagina' => 2],
            ['pagina' => 3]
        );

        self::assertSame('/categoria?busca=blusa&pagina=3', $url);
    }

    public function testUrlRemoveFiltroComOverrideNulo(): void
    {
        $url = Catalogo::url(['busca' => 'blusa'], ['busca' => null]);

        self::assertSame('/categoria', $url);
    }

    public function testUrlProdutoIncluiFotoQuandoInformada(): void
    {
        self::assertSame('/produto?id=10', Catalogo::urlProduto(10));
        self::assertSame('/produto?id=10&foto=3', Catalogo::urlProduto(10, 3));
    }
}
