<?php

/**
 * CRUD de categorias para a área administrativa.
 */
class CategoriaRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function listar(): array
    {
        $sql = "
            SELECT
                c.id_categoria,
                c.nome,
                c.descricao,
                c.categoria_pai_id,
                pai.nome AS pai_nome,
                (
                    SELECT COUNT(*)
                    FROM categorias_produtos cp
                    INNER JOIN produtos p
                        ON p.id_produto = cp.produto_id
                       AND p.deletado_em IS NULL
                    WHERE cp.categoria_id = c.id_categoria
                      AND cp.deletado_em IS NULL
                ) AS total_produtos
            FROM categorias c
            LEFT JOIN categorias pai
                ON pai.id_categoria = c.categoria_pai_id
               AND pai.deletado_em IS NULL
            WHERE c.deletado_em IS NULL
            ORDER BY c.nome ASC
        ";

        return $this->pdo->query($sql)->fetchAll();
    }

    /**
     * @return array<int, array{id_categoria: int, nome: string}>
     */
    public function listarParaSelect(?int $ignorarId = null): array
    {
        $sql = 'SELECT id_categoria, nome FROM categorias WHERE deletado_em IS NULL';
        $params = [];

        if ($ignorarId !== null) {
            $sql .= ' AND id_categoria <> :ignorar';
            $params[':ignorar'] = $ignorarId;
        }

        $sql .= ' ORDER BY nome ASC';

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $nome => $valor) {
            $stmt->bindValue($nome, $valor, PDO::PARAM_INT);
        }

        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id_categoria, nome, descricao, categoria_pai_id
             FROM categorias
             WHERE id_categoria = :id
               AND deletado_em IS NULL
             LIMIT 1'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $categoria = $stmt->fetch();

        return $categoria === false ? null : $categoria;
    }

    public function nomeEmUso(string $nome, ?int $ignorarId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM categorias WHERE nome = :nome AND deletado_em IS NULL';
        $params = [':nome' => $nome];

        if ($ignorarId !== null) {
            $sql .= ' AND id_categoria <> :ignorar';
            $params[':ignorar'] = $ignorarId;
        }

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $nomeParam => $valor) {
            $stmt->bindValue($nomeParam, $valor, $nomeParam === ':ignorar' ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $stmt->execute();

        return (int) $stmt->fetchColumn() > 0;
    }

    public function criar(array $dados): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO categorias (nome, descricao, categoria_pai_id)
             VALUES (:nome, :descricao, :categoria_pai_id)'
        );
        $stmt->execute([
            ':nome' => $dados['nome'],
            ':descricao' => $dados['descricao'],
            ':categoria_pai_id' => $dados['categoria_pai_id'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function atualizar(int $id, array $dados): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE categorias
             SET nome = :nome,
                 descricao = :descricao,
                 categoria_pai_id = :categoria_pai_id
             WHERE id_categoria = :id'
        );
        $stmt->execute([
            ':nome' => $dados['nome'],
            ':descricao' => $dados['descricao'],
            ':categoria_pai_id' => $dados['categoria_pai_id'],
            ':id' => $id,
        ]);
    }

    /**
     * Soft delete. O nome é renomeado para liberar a restrição UNIQUE.
     */
    public function excluir(int $id): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE categorias
             SET deletado_em = NOW(),
                 nome = CONCAT(LEFT(nome, 180), ' (excluída #', id_categoria, ')')
             WHERE id_categoria = :id
               AND deletado_em IS NULL"
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function temProdutosVinculados(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM categorias_produtos cp
             INNER JOIN produtos p
                 ON p.id_produto = cp.produto_id
                AND p.deletado_em IS NULL
             WHERE cp.categoria_id = :id
               AND cp.deletado_em IS NULL'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn() > 0;
    }

    public function temSubcategorias(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM categorias WHERE categoria_pai_id = :id AND deletado_em IS NULL'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Evita ciclos na hierarquia (A pai de B, B pai de A).
     */
    public function formariaCiclo(int $id, int $paiId): bool
    {
        $atual = $paiId;
        $visitados = [];

        while ($atual !== null && !isset($visitados[$atual]) && count($visitados) < 50) {
            if ($atual === $id) {
                return true;
            }

            $visitados[$atual] = true;

            $stmt = $this->pdo->prepare(
                'SELECT categoria_pai_id FROM categorias WHERE id_categoria = :id LIMIT 1'
            );
            $stmt->bindValue(':id', $atual, PDO::PARAM_INT);
            $stmt->execute();

            $pai = $stmt->fetchColumn();
            $atual = $pai === false || $pai === null ? null : (int) $pai;
        }

        return false;
    }
}
