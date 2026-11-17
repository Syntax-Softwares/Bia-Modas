<?php

/**
 * CRUD de tamanhos e medidas base dos produtos (medidas_produtos).
 */
class TamanhoRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function listar(): array
    {
        $sql = "
            SELECT
                mp.id_medida_produto,
                mp.tamanho,
                mp.busto,
                mp.cintura,
                mp.quadril,
                (
                    SELECT COUNT(*)
                    FROM produtos p
                    WHERE p.medida_produto_id = mp.id_medida_produto
                      AND p.deletado_em IS NULL
                ) AS total_produtos
            FROM medidas_produtos mp
            WHERE mp.deletado_em IS NULL
            ORDER BY mp.tamanho ASC
        ";

        return $this->pdo->query($sql)->fetchAll();
    }

    /**
     * @return array<int, array{id_medida_produto: int, tamanho: string}>
     */
    public function listarParaSelect(): array
    {
        return $this->pdo
            ->query(
                'SELECT id_medida_produto, tamanho
                 FROM medidas_produtos
                 WHERE deletado_em IS NULL
                 ORDER BY tamanho ASC'
            )
            ->fetchAll();
    }

    public function getPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id_medida_produto, tamanho, busto, cintura, quadril
             FROM medidas_produtos
             WHERE id_medida_produto = :id
               AND deletado_em IS NULL
             LIMIT 1'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $tamanho = $stmt->fetch();

        return $tamanho === false ? null : $tamanho;
    }

    /**
     * Tamanhos não têm UNIQUE no banco; a duplicidade é validada aqui.
     */
    public function tamanhoEmUso(string $tamanho, ?int $ignorarId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM medidas_produtos WHERE tamanho = :tamanho AND deletado_em IS NULL';
        $params = [':tamanho' => $tamanho];

        if ($ignorarId !== null) {
            $sql .= ' AND id_medida_produto <> :ignorar';
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
            'INSERT INTO medidas_produtos (tamanho, busto, cintura, quadril)
             VALUES (:tamanho, :busto, :cintura, :quadril)'
        );
        $stmt->execute([
            ':tamanho' => $dados['tamanho'],
            ':busto' => $dados['busto'],
            ':cintura' => $dados['cintura'],
            ':quadril' => $dados['quadril'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function atualizar(int $id, array $dados): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE medidas_produtos
             SET tamanho = :tamanho,
                 busto = :busto,
                 cintura = :cintura,
                 quadril = :quadril
             WHERE id_medida_produto = :id'
        );
        $stmt->execute([
            ':tamanho' => $dados['tamanho'],
            ':busto' => $dados['busto'],
            ':cintura' => $dados['cintura'],
            ':quadril' => $dados['quadril'],
            ':id' => $id,
        ]);
    }

    public function excluir(int $id): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE medidas_produtos SET deletado_em = NOW() WHERE id_medida_produto = :id'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function emUso(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM produtos WHERE medida_produto_id = :id AND deletado_em IS NULL'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn() > 0;
    }
}
