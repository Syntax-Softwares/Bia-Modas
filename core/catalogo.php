<?php

/**
 * Utilitarios do catalogo: preco, URLs com filtros e cores.
 *
 * Mantido em classe propria para nao colidir com os helpers globais do MVC05
 * (core/helpers.php) na integracao.
 */
final class Catalogo
{
    private const CORES = [
        'Preto' => '#212529',
        'Branco' => '#f8f9fa',
        'Vermelho' => '#dc3545',
        'Azul' => '#0d6efd',
        'Verde' => '#198754',
        'Rosa' => '#d63384',
        'Estampado' => '#fd7e14',
        'Neutro' => '#6c757d',
        'Bege' => '#e8dcc5',
        'Jeans' => '#5b7fa3',
        'Marrom' => '#8B4513',
        'Vinho' => '#6b1f2a',
        'Bordo' => '#6e1b2b',
        'Dourado' => '#b8860b',
        'Prata' => '#c0c0c0',
    ];

    public static function money(float|int|string|null $valor): string
    {
        return 'R$ ' . number_format((float) $valor, 2, ',', '.');
    }

    /**
     * Preco original a partir do valor promocional e do percentual (fracao).
     */
    public static function precoOriginal(float|int|string|null $valor, float|int|null $percentual): ?float
    {
        $valor = (float) $valor;
        $percentual = $percentual === null ? null : (float) $percentual;

        if ($percentual === null || $percentual <= 0 || $percentual >= 1 || $valor <= 0) {
            return null;
        }

        return $valor / (1 - $percentual);
    }

    public static function descontoPercentual(float|int|string|null $percentual): int
    {
        return (int) round((float) $percentual * 100);
    }

    public static function corHex(?string $cor): string
    {
        $cor = trim((string) $cor);

        return self::CORES[$cor] ?? '#cccccc';
    }

    public static function corEhClara(?string $cor): bool
    {
        return in_array(trim((string) $cor), ['Branco', 'Bege', 'Prata'], true);
    }

    /**
     * Monta /categoria preservando os filtros atuais.
     *
     * @param array<string, mixed> $filtros
     * @param array<string, mixed> $overrides
     */
    public static function url(array $filtros = [], array $overrides = []): string
    {
        $params = array_merge($filtros, $overrides);

        foreach ($params as $chave => $valor) {
            if ($valor === null || $valor === '' || $valor === false) {
                unset($params[$chave]);
                continue;
            }

            if (is_array($valor) && $valor === []) {
                unset($params[$chave]);
            }
        }

        $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        return '/categoria' . ($query === '' ? '' : '?' . $query);
    }

    /**
     * URL do produto mantendo a foto selecionada quando necessario.
     */
    public static function urlProduto(int $idProduto, ?int $idFoto = null): string
    {
        $url = '/produto?id=' . $idProduto;

        return $idFoto === null ? $url : $url . '&foto=' . $idFoto;
    }
}
