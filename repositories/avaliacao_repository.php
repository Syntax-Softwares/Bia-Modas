<?php

/**
 * Acesso as avaliacoes de produto.
 *
 * Regras: uma avaliacao por usuario por produto; remocao e soft delete
 * (deletado_em); a media ignora as linhas removidas.
 */
class AvaliacaoRepository
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    /**
     * Avaliacoes visiveis de um produto, mais recentes primeiro.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listarPorProduto(int $produtoId, int $limite = 50): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.id_avaliacao,
                    a.nota,
                    a.comentario,
                    a.criado_em,
                    u.nome AS autor
             FROM avaliacoes a
             INNER JOIN usuarios u ON u.id_usuario = a.usuario_id
             WHERE a.produto_id = :produto
               AND a.deletado_em IS NULL
               AND u.deletado_em IS NULL
             ORDER BY a.criado_em DESC, a.id_avaliacao DESC
             LIMIT :limite'
        );
        $stmt->bindValue(':produto', $produtoId, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(static function (array $row): array {
            $row['id_avaliacao'] = (int) $row['id_avaliacao'];
            $row['nota'] = (int) $row['nota'];
            $row['comentario'] = $row['comentario'] !== null ? (string) $row['comentario'] : null;

            return $row;
        }, $stmt->fetchAll());
    }

    /**
     * Media e quantidade de avaliacoes ativas.
     *
     * @return array{media: float|null, total: int}
     */
    public function resumo(int $produtoId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(AVG(nota), 0) AS media, COUNT(*) AS total
             FROM avaliacoes
             WHERE produto_id = :produto
               AND deletado_em IS NULL'
        );
        $stmt->bindValue(':produto', $produtoId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();
        $total = (int) ($row['total'] ?? 0);

        return [
            'media' => $total > 0 ? round((float) $row['media'], 1) : null,
            'total' => $total,
        ];
    }

    /**
     * Avaliacao ativa do usuario para o produto (para pre-preencher o formulario).
     *
     * @return array<string, mixed>|null
     */
    public function doUsuario(int $produtoId, int $usuarioId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id_avaliacao, nota, comentario
             FROM avaliacoes
             WHERE produto_id = :produto
               AND usuario_id = :usuario
               AND deletado_em IS NULL
             LIMIT 1'
        );
        $stmt->bindValue(':produto', $produtoId, PDO::PARAM_INT);
        $stmt->bindValue(':usuario', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();

        $avaliacao = $stmt->fetch();

        if ($avaliacao === false) {
            return null;
        }

        $avaliacao['nota'] = (int) $avaliacao['nota'];

        return $avaliacao;
    }

    /**
     * Cria ou atualiza a avaliacao do usuario.
     *
     * A UNIQUE (usuario_id, produto_id) impede uma segunda linha, entao uma
     * avaliacao removida e reativada em vez de inserida de novo.
     */
    public function salvar(int $usuarioId, int $produtoId, int $nota, ?string $comentario): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO avaliacoes (usuario_id, produto_id, nota, comentario)
             VALUES (:usuario, :produto, :nota, :comentario)
             ON DUPLICATE KEY UPDATE
                 nota = VALUES(nota),
                 comentario = VALUES(comentario),
                 deletado_em = NULL,
                 alterado_em = NOW()'
        );

        $stmt->bindValue(':usuario', $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(':produto', $produtoId, PDO::PARAM_INT);
        $stmt->bindValue(':nota', $nota, PDO::PARAM_INT);
        $stmt->bindValue(':comentario', $comentario, $comentario === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->execute();
    }

    /**
     * Remove (soft delete) a avaliacao do usuario.
     */
    public function remover(int $usuarioId, int $produtoId): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE avaliacoes
             SET deletado_em = NOW()
             WHERE usuario_id = :usuario
               AND produto_id = :produto
               AND deletado_em IS NULL'
        );

        $stmt->bindValue(':usuario', $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(':produto', $produtoId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
