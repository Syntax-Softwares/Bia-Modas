<?php

declare(strict_types=1);

/**
 * Resultado previsto para uma consulta executada no FakePDO.
 */
final class FakeResult
{
    /**
     * @param list<array<string, mixed>> $linhas
     */
    private function __construct(
        public readonly array $linhas = [],
        public readonly bool $temColuna = false,
        public readonly mixed $coluna = null,
        public readonly ?int $linhasAfetadas = null
    ) {
    }

    public static function vazio(): self
    {
        return new self();
    }

    /**
     * @param array<string, mixed> $linha
     */
    public static function linha(array $linha): self
    {
        return new self([$linha]);
    }

    /**
     * @param list<array<string, mixed>> $linhas
     */
    public static function linhas(array $linhas): self
    {
        return new self($linhas);
    }

    public static function coluna(mixed $valor, ?int $linhasAfetadas = null): self
    {
        return new self([], true, $valor, $linhasAfetadas);
    }

    public static function afetadas(int $linhas): self
    {
        return new self([], false, null, $linhas);
    }
}
