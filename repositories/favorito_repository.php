<?php

class FavoritoRepository
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function alternar(string $sessaoId, int $idProduto): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id_produto FROM produtos
             WHERE id_produto = :id AND deletado_em IS NULL AND ativo = 1"
        );
        $stmt->execute([':id' => $idProduto]);

        if ($stmt->fetch() === false) {
            return ['ok' => false, 'mensagem' => 'Produto indisponível.'];
        }

        $stmt = $this->pdo->prepare(
            "SELECT id_favorito, deletado_em
             FROM favoritos
             WHERE sessao_id = :sessao AND produto_id = :produto"
        );
        $stmt->execute([':sessao' => $sessaoId, ':produto' => $idProduto]);
        $favorito = $stmt->fetch();

        if ($favorito !== false && $favorito['deletado_em'] === null) {
            $stmt = $this->pdo->prepare(
                "UPDATE favoritos SET deletado_em = NOW() WHERE id_favorito = :id"
            );
            $stmt->execute([':id' => $favorito['id_favorito']]);
            $estaFavoritado = false;
        } else {
            $stmt = $this->pdo->prepare(
                "INSERT INTO favoritos (sessao_id, produto_id)
                 VALUES (:sessao, :produto)
                 ON DUPLICATE KEY UPDATE deletado_em = NULL, alterado_em = NOW()"
            );
            $stmt->execute([':sessao' => $sessaoId, ':produto' => $idProduto]);
            $estaFavoritado = true;
        }

        return [
            'ok' => true,
            'favorito' => $estaFavoritado,
            'total' => $this->total($sessaoId),
        ];
    }

    public function ids(string $sessaoId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT f.produto_id
             FROM favoritos f
             INNER JOIN produtos p ON p.id_produto = f.produto_id
             WHERE f.sessao_id = :sessao
               AND f.deletado_em IS NULL
               AND p.deletado_em IS NULL
               AND p.ativo = 1
             ORDER BY f.criado_em DESC"
        );
        $stmt->execute([':sessao' => $sessaoId]);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function total(string $sessaoId): int
    {
        return count($this->ids($sessaoId));
    }
}
