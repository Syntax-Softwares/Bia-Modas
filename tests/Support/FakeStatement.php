<?php

declare(strict_types=1);

/**
 * PDOStatement falso que devolve o FakeResult definido pelo handler do teste.
 */
final class FakeStatement extends PDOStatement
{
    /** @var array<string|int, mixed> */
    private array $parametros = [];

    private ?FakeResult $resultado = null;

    /** @var list<array<string, mixed>> */
    private array $restantes = [];

    /**
     * @param callable(string, array<string|int, mixed>): FakeResult $handler
     */
    public function __construct(
        private readonly FakePDO $pdo,
        private readonly string $sql,
        private readonly mixed $handler
    ) {
    }

    public function execute(?array $params = null): bool
    {
        if ($params !== null) {
            $this->parametros = $params;
        }

        $this->pdo->registrarExecucao($this->sql, $this->parametros);
        $this->resultado = ($this->handler)($this->sql, $this->parametros);
        $this->restantes = $this->resultado->linhas;

        return true;
    }

    public function fetch(int $mode = PDO::FETCH_DEFAULT, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed
    {
        if ($this->restantes === []) {
            return false;
        }

        return array_shift($this->restantes);
    }

    public function fetchAll(int $mode = PDO::FETCH_DEFAULT, mixed ...$args): array
    {
        $linhas = $this->restantes;
        $this->restantes = [];

        if ($mode === PDO::FETCH_COLUMN) {
            $coluna = (int) ($args[0] ?? 0);

            return array_map(
                static fn (array $linha): mixed => array_values($linha)[$coluna] ?? null,
                $linhas
            );
        }

        return $linhas;
    }

    public function fetchColumn(int $column = 0): mixed
    {
        if ($this->resultado !== null && $this->resultado->temColuna) {
            return $this->resultado->coluna;
        }

        if ($this->restantes === []) {
            return false;
        }

        $linha = array_shift($this->restantes);
        $valores = array_values($linha);

        return $valores[$column] ?? false;
    }

    public function rowCount(): int
    {
        if ($this->resultado === null) {
            return 0;
        }

        return $this->resultado->linhasAfetadas ?? count($this->resultado->linhas);
    }

    public function bindValue(string|int $param, mixed $value, int $type = PDO::PARAM_STR): bool
    {
        $this->parametros[$param] = $value;

        return true;
    }
}
