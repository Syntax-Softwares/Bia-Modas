<?php

require_once __DIR__ . '/../core/comprador.php';

class FavoritoRepository
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function alternar(Comprador $comprador, int $idProduto): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id_produto FROM produtos
             WHERE id_produto = :id AND deletado_em IS NULL AND ativo = 1"
        );
        $stmt->execute([':id' => $idProduto]);

        if ($stmt->fetch() === false) {
            return ['ok' => false, 'mensagem' => 'Produto indisponível.'];
        }

        $usuarioId = $comprador->idUsuario();

        if ($usuarioId !== null) {
            $favorito = $this->buscarDoUsuario($usuarioId, $idProduto);
            $estaFavoritado = $this->alternarRegistro(
                $favorito,
                'usuario_id',
                $usuarioId,
                $idProduto
            );
        } else {
            $sessaoId = $comprador->idSessao();
            $favorito = $this->buscarDoVisitante($sessaoId, $idProduto);
            $estaFavoritado = $this->alternarRegistro(
                $favorito,
                'sessao_id',
                $sessaoId,
                $idProduto
            );
        }

        return [
            'ok' => true,
            'favorito' => $estaFavoritado,
            'total' => $this->total($comprador),
        ];
    }

    public function ids(Comprador $comprador): array
    {
        $usuarioId = $comprador->idUsuario();

        if ($usuarioId !== null) {
            $sql = "SELECT f.produto_id
                    FROM favoritos f
                    INNER JOIN produtos p ON p.id_produto = f.produto_id
                    WHERE f.usuario_id = :dono
                      AND f.deletado_em IS NULL
                      AND p.deletado_em IS NULL
                      AND p.ativo = 1
                    ORDER BY f.criado_em DESC";
            $parametro = [':dono' => $usuarioId];
        } else {
            $sql = "SELECT f.produto_id
                    FROM favoritos f
                    INNER JOIN produtos p ON p.id_produto = f.produto_id
                    WHERE f.sessao_id = :dono
                      AND f.deletado_em IS NULL
                      AND p.deletado_em IS NULL
                      AND p.ativo = 1
                    ORDER BY f.criado_em DESC";
            $parametro = [':dono' => $comprador->idSessao()];
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parametro);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function total(Comprador $comprador): int
    {
        return count($this->ids($comprador));
    }

    /**
     * Migra os favoritos anonimos para a conta no login/cadastro.
     *
     * A chave unica (usuario_id, produto_id) garante que produtos repetidos
     * nao dupliquem; se o usuario ja tinha o favorito removido, ele volta.
     */
    public function vincularAoUsuario(int $usuarioId, string $sessaoId): void
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO favoritos (usuario_id, produto_id)
                 SELECT :usuario, f.produto_id
                 FROM favoritos f
                 INNER JOIN produtos p ON p.id_produto = f.produto_id
                 WHERE f.sessao_id = :sessao
                   AND f.deletado_em IS NULL
                   AND p.deletado_em IS NULL
                   AND p.ativo = 1
                 ON DUPLICATE KEY UPDATE
                    deletado_em = NULL,
                    alterado_em = NOW()"
            );
            $stmt->execute([':usuario' => $usuarioId, ':sessao' => $sessaoId]);

            $stmt = $this->pdo->prepare(
                'UPDATE favoritos
                 SET deletado_em = NOW()
                 WHERE sessao_id = :sessao AND deletado_em IS NULL'
            );
            $stmt->execute([':sessao' => $sessaoId]);

            $this->pdo->commit();
        } catch (Throwable $excecao) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $excecao;
        }
    }

    private function buscarDoUsuario(int $usuarioId, int $idProduto): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT id_favorito, deletado_em
             FROM favoritos
             WHERE usuario_id = :usuario AND produto_id = :produto
             LIMIT 1'
        );
        $stmt->execute([':usuario' => $usuarioId, ':produto' => $idProduto]);

        return $stmt->fetch();
    }

    private function buscarDoVisitante(string $sessaoId, int $idProduto): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT id_favorito, deletado_em
             FROM favoritos
             WHERE sessao_id = :sessao AND produto_id = :produto
             LIMIT 1'
        );
        $stmt->execute([':sessao' => $sessaoId, ':produto' => $idProduto]);

        return $stmt->fetch();
    }

    /**
     * Alterna um favorito ja resolvido para o dono correto (usuario ou sessao).
     *
     * A coluna de vinculo vem de um valor controlado pelo repositorio, nunca
     * de entrada do usuario, e o valor e sempre parametrizado.
     */
    private function alternarRegistro(
        array|false $favorito,
        string $colunaDono,
        int|string $valorDono,
        int $idProduto
    ): bool {
        if ($favorito !== false && $favorito['deletado_em'] === null) {
            $stmt = $this->pdo->prepare(
                'UPDATE favoritos SET deletado_em = NOW() WHERE id_favorito = :id'
            );
            $stmt->execute([':id' => $favorito['id_favorito']]);

            return false;
        }

        $stmt = $this->pdo->prepare(
            "INSERT INTO favoritos ({$colunaDono}, produto_id)
             VALUES (:dono, :produto)
             ON DUPLICATE KEY UPDATE deletado_em = NULL, alterado_em = NOW()"
        );
        $stmt->execute([':dono' => $valorDono, ':produto' => $idProduto]);

        return true;
    }
}
