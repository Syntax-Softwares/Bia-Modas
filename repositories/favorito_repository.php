<?php

/**
 * Favoritos do usuário logado.
 *
 * Remover = preencher deletado_em; favoritar de novo reaproveita a mesma
 * linha (a UNIQUE usuario_id + produto_id impede duplicata).
 */
class FavoritoRepository
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    /**
     * Ids dos produtos favoritados, mais recentes primeiro.
     *
     * @return array<int, int>
     */
    public function ids(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT f.produto_id
             FROM favoritos f
             INNER JOIN produtos p ON p.id_produto = f.produto_id
             WHERE f.usuario_id = :usuario
               AND f.deletado_em IS NULL
               AND p.deletado_em IS NULL
               AND p.ativo = TRUE
             ORDER BY f.criado_em DESC, f.id_favorito DESC'
        );
        $stmt->bindValue(':usuario', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function total(int $usuarioId): int
    {
        return count($this->ids($usuarioId));
    }

    /**
     * Alterna o favorito e devolve o novo estado.
     *
     * @return array{ok: bool, favorito?: bool, total?: int, mensagem?: string}
     */
    public function alternar(int $usuarioId, int $produtoId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id_produto FROM produtos
             WHERE id_produto = :id
               AND ativo = TRUE
               AND deletado_em IS NULL'
        );
        $stmt->bindValue(':id', $produtoId, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->fetch() === false) {
            return ['ok' => false, 'mensagem' => 'Produto indisponível.'];
        }

        $stmt = $this->pdo->prepare(
            'SELECT id_favorito, deletado_em
             FROM favoritos
             WHERE usuario_id = :usuario
               AND produto_id = :produto
             LIMIT 1'
        );
        $stmt->bindValue(':usuario', $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(':produto', $produtoId, PDO::PARAM_INT);
        $stmt->execute();

        $favorito = $stmt->fetch();

        if ($favorito !== false && $favorito['deletado_em'] === null) {
            $stmt = $this->pdo->prepare(
                'UPDATE favoritos SET deletado_em = NOW() WHERE id_favorito = :id'
            );
            $stmt->bindValue(':id', (int) $favorito['id_favorito'], PDO::PARAM_INT);
            $stmt->execute();
            $estaFavoritado = false;
        } else {
            $stmt = $this->pdo->prepare(
                'INSERT INTO favoritos (usuario_id, produto_id)
                 VALUES (:usuario, :produto)
                 ON DUPLICATE KEY UPDATE deletado_em = NULL, alterado_em = NOW()'
            );
            $stmt->bindValue(':usuario', $usuarioId, PDO::PARAM_INT);
            $stmt->bindValue(':produto', $produtoId, PDO::PARAM_INT);
            $stmt->execute();
            $estaFavoritado = true;
        }

        return [
            'ok' => true,
            'favorito' => $estaFavoritado,
            'total' => $this->total($usuarioId),
        ];
    }
}
