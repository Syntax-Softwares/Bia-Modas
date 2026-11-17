<?php

/**
 * CRUD de ofertas (percentual com vigência).
 */
class OfertaRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function listar(): array
    {
        $sql = "
            SELECT
                o.id_oferta,
                o.percentual,
                o.inicio_em,
                o.fim_em,
                o.ativo,
                (
                    SELECT COUNT(*)
                    FROM produtos p
                    WHERE p.oferta_id = o.id_oferta
                      AND p.deletado_em IS NULL
                ) AS total_produtos
            FROM ofertas o
            WHERE o.deletado_em IS NULL
            ORDER BY o.criado_em DESC, o.id_oferta DESC
        ";

        return $this->pdo->query($sql)->fetchAll();
    }

    /**
     * Ofertas ativas e vigentes, usadas no select do formulário de produto.
     */
    public function listarAtivas(): array
    {
        return $this->pdo
            ->query(
                "SELECT id_oferta, percentual, inicio_em, fim_em
                 FROM ofertas
                 WHERE ativo = TRUE
                   AND deletado_em IS NULL
                   AND (inicio_em IS NULL OR inicio_em <= NOW())
                   AND (fim_em IS NULL OR fim_em >= NOW())
                 ORDER BY percentual DESC, id_oferta DESC"
            )
            ->fetchAll();
    }

    public function getPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id_oferta, percentual, inicio_em, fim_em, ativo
             FROM ofertas
             WHERE id_oferta = :id
               AND deletado_em IS NULL
             LIMIT 1'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $oferta = $stmt->fetch();

        return $oferta === false ? null : $oferta;
    }

    public function criar(array $dados): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO ofertas (percentual, inicio_em, fim_em, ativo)
             VALUES (:percentual, :inicio_em, :fim_em, :ativo)'
        );
        $stmt->execute([
            ':percentual' => $dados['percentual'],
            ':inicio_em' => $dados['inicio_em'],
            ':fim_em' => $dados['fim_em'],
            ':ativo' => $dados['ativo'] ? 1 : 0,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function atualizar(int $id, array $dados): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE ofertas
             SET percentual = :percentual,
                 inicio_em = :inicio_em,
                 fim_em = :fim_em,
                 ativo = :ativo
             WHERE id_oferta = :id'
        );
        $stmt->execute([
            ':percentual' => $dados['percentual'],
            ':inicio_em' => $dados['inicio_em'],
            ':fim_em' => $dados['fim_em'],
            ':ativo' => $dados['ativo'] ? 1 : 0,
            ':id' => $id,
        ]);
    }

    /**
     * Soft delete e desvinculação dos produtos para não exibir desconto fantasma.
     */
    public function excluir(int $id): void
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare('UPDATE produtos SET oferta_id = NULL WHERE oferta_id = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $stmt = $this->pdo->prepare(
                'UPDATE ofertas SET deletado_em = NOW(), ativo = FALSE WHERE id_oferta = :id'
            );
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $this->pdo->commit();
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }
}
