<?php

declare(strict_types=1);

/**
 * PDO em memoria usado nos testes de repositorio.
 *
 * O teste registra um handler que recebe o SQL e os parametros e devolve o
 * FakeResult correspondente. Assim e possivel exercitar as regras de negocio
 * (transacoes, validacoes, calculos) sem banco de dados.
 */
final class FakePDO extends PDO
{
    /** @var list<array{sql: string, parametros: array<string|int, mixed>}> */
    private array $execucoes = [];

    /** @var list<string> */
    private array $transacoes = [];

    private bool $emTransacao = false;

    private int $ultimoId = 0;

    /** @var callable(string, array<string|int, mixed>): FakeResult */
    private mixed $handler;

    /**
     * @param null|callable(string, array<string|int, mixed>): FakeResult $handler
     */
    public function __construct(?callable $handler = null)
    {
        $this->handler = $handler ?? static fn (): FakeResult => FakeResult::vazio();
    }

    /**
     * @param callable(string, array<string|int, mixed>): FakeResult $handler
     */
    public function definirHandler(callable $handler): void
    {
        $this->handler = $handler;
    }

    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        return new FakeStatement($this, $query, $this->handler);
    }

    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false
    {
        $stmt = new FakeStatement($this, $query, $this->handler);
        $stmt->execute();

        return $stmt;
    }

    public function beginTransaction(): bool
    {
        $this->emTransacao = true;
        $this->transacoes[] = 'begin';

        return true;
    }

    public function commit(): bool
    {
        $this->emTransacao = false;
        $this->transacoes[] = 'commit';

        return true;
    }

    public function rollBack(): bool
    {
        $this->emTransacao = false;
        $this->transacoes[] = 'rollback';

        return true;
    }

    public function inTransaction(): bool
    {
        return $this->emTransacao;
    }

    public function lastInsertId(?string $name = null): string|false
    {
        return (string) $this->ultimoId;
    }

    public function definirUltimoId(int $id): void
    {
        $this->ultimoId = $id;
    }

    /**
     * @param array<string|int, mixed> $parametros
     */
    public function registrarExecucao(string $sql, array $parametros): void
    {
        $this->execucoes[] = [
            'sql' => $sql,
            'parametros' => $parametros,
        ];
    }

    /**
     * @return list<array{sql: string, parametros: array<string|int, mixed>}>
     */
    public function execucoes(): array
    {
        return $this->execucoes;
    }

    /**
     * @return list<string>
     */
    public function transacoes(): array
    {
        return $this->transacoes;
    }

    public function executou(string $trecho): bool
    {
        return $this->contarExecucoes($trecho) > 0;
    }

    public function contarExecucoes(string $trecho): int
    {
        $total = 0;

        foreach ($this->execucoes as $execucao) {
            if (str_contains($execucao['sql'], $trecho)) {
                $total++;
            }
        }

        return $total;
    }

    /**
     * @return array<string|int, mixed>
     */
    public function parametrosDaPrimeira(string $trecho): array
    {
        foreach ($this->execucoes as $execucao) {
            if (str_contains($execucao['sql'], $trecho)) {
                return $execucao['parametros'];
            }
        }

        return [];
    }

    /**
     * @return list<array<string|int, mixed>>
     */
    public function parametrosDe(string $trecho): array
    {
        $parametros = [];

        foreach ($this->execucoes as $execucao) {
            if (str_contains($execucao['sql'], $trecho)) {
                $parametros[] = $execucao['parametros'];
            }
        }

        return $parametros;
    }
}
