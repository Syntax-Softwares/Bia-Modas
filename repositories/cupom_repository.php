<?php

/**
 * CRUD de cupons de desconto.
 */
class CupomRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function listar(): array
    {
        return $this->pdo
            ->query(
                'SELECT id_cupom, codigo, val_min, val_max, desconto, frete_gratis, criado_em
                 FROM cupons
                 WHERE deletado_em IS NULL
                 ORDER BY criado_em DESC, id_cupom DESC'
            )
            ->fetchAll();
    }

    public function getPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id_cupom, codigo, val_min, val_max, desconto, frete_gratis
             FROM cupons
             WHERE id_cupom = :id
               AND deletado_em IS NULL
             LIMIT 1'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $cupom = $stmt->fetch();

        return $cupom === false ? null : $cupom;
    }

    public function codigoEmUso(string $codigo, ?int $ignorarId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM cupons WHERE codigo = :codigo AND deletado_em IS NULL';
        $params = [':codigo' => $codigo];

        if ($ignorarId !== null) {
            $sql .= ' AND id_cupom <> :ignorar';
            $params[':ignorar'] = $ignorarId;
        }

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $nome => $valor) {
            $stmt->bindValue($nome, $valor, $nome === ':ignorar' ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $stmt->execute();

        return (int) $stmt->fetchColumn() > 0;
    }

    public function criar(array $dados): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO cupons (codigo, val_min, val_max, desconto, frete_gratis)
             VALUES (:codigo, :val_min, :val_max, :desconto, :frete_gratis)'
        );
        $stmt->execute([
            ':codigo' => $dados['codigo'],
            ':val_min' => $dados['val_min'],
            ':val_max' => $dados['val_max'],
            ':desconto' => $dados['desconto'],
            ':frete_gratis' => $dados['frete_gratis'] ? 1 : 0,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function atualizar(int $id, array $dados): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE cupons
             SET codigo = :codigo,
                 val_min = :val_min,
                 val_max = :val_max,
                 desconto = :desconto,
                 frete_gratis = :frete_gratis
             WHERE id_cupom = :id'
        );
        $stmt->execute([
            ':codigo' => $dados['codigo'],
            ':val_min' => $dados['val_min'],
            ':val_max' => $dados['val_max'],
            ':desconto' => $dados['desconto'],
            ':frete_gratis' => $dados['frete_gratis'] ? 1 : 0,
            ':id' => $id,
        ]);
    }

    /**
     * Soft delete. O código é renomeado para liberar a restrição UNIQUE.
     */
    public function excluir(int $id): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE cupons
             SET deletado_em = NOW(),
                 codigo = CONCAT(LEFT(codigo, 35), '#del', id_cupom)
             WHERE id_cupom = :id
               AND deletado_em IS NULL"
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }
}
