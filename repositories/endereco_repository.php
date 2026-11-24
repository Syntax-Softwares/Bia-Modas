<?php

/**
 * Enderecos do usuario logado.
 *
 * Toda operacao recebe tambem o id do usuario da sessao e filtra por ele,
 * impedindo acesso a enderecos de terceiros (IDOR).
 */
final class EnderecoRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function listarPorUsuario(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare(
            '
            SELECT id_endereco, nome_endereco, logradouro, numero, complemento,
                   bairro, cidade, uf, cep, principal
            FROM enderecos
            WHERE usuario_id = :usuario_id
              AND deletado_em IS NULL
            ORDER BY principal DESC, criado_em DESC, id_endereco DESC
            '
        );
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function buscarDoUsuario(int $idEndereco, int $usuarioId): ?array
    {
        $stmt = $this->pdo->prepare(
            '
            SELECT id_endereco, nome_endereco, logradouro, numero, complemento,
                   bairro, cidade, uf, cep, principal
            FROM enderecos
            WHERE id_endereco = :id_endereco
              AND usuario_id = :usuario_id
              AND deletado_em IS NULL
            LIMIT 1
            '
        );
        $stmt->bindValue(':id_endereco', $idEndereco, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();

        $endereco = $stmt->fetch();

        return $endereco === false ? null : $endereco;
    }

    public function contarPorUsuario(int $usuarioId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM enderecos WHERE usuario_id = :usuario_id AND deletado_em IS NULL'
        );
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function criar(int $usuarioId, array $dados): int
    {
        $this->pdo->beginTransaction();

        try {
            // Serializa criacoes concorrentes do mesmo usuario para nao
            // terminar com dois enderecos principais.
            $this->travarUsuario($usuarioId);

            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*) FROM enderecos WHERE usuario_id = :usuario_id AND deletado_em IS NULL'
            );
            $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $stmt->execute();

            $primeiro = (int) $stmt->fetchColumn() === 0;

            $stmt = $this->pdo->prepare(
                '
                INSERT INTO enderecos
                    (usuario_id, nome_endereco, logradouro, numero, complemento,
                     bairro, cidade, uf, cep, principal)
                VALUES
                    (:usuario_id, :nome_endereco, :logradouro, :numero, :complemento,
                     :bairro, :cidade, :uf, :cep, :principal)
                '
            );
            $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $stmt->bindValue(':nome_endereco', $dados['nome_endereco'], PDO::PARAM_STR);
            $stmt->bindValue(':logradouro', $dados['logradouro'], PDO::PARAM_STR);
            $stmt->bindValue(':numero', $dados['numero'], PDO::PARAM_STR);
            $stmt->bindValue(
                ':complemento',
                $dados['complemento'],
                $dados['complemento'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR
            );
            $stmt->bindValue(':bairro', $dados['bairro'], PDO::PARAM_STR);
            $stmt->bindValue(':cidade', $dados['cidade'], PDO::PARAM_STR);
            $stmt->bindValue(':uf', $dados['uf'], PDO::PARAM_STR);
            $stmt->bindValue(':cep', $dados['cep'], PDO::PARAM_STR);
            $stmt->bindValue(':principal', $primeiro ? 1 : 0, PDO::PARAM_INT);
            $stmt->execute();

            $id = (int) $this->pdo->lastInsertId();

            $this->pdo->commit();

            return $id;
        } catch (Throwable $excecao) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $excecao;
        }
    }

    public function atualizar(int $idEndereco, int $usuarioId, array $dados): bool
    {
        if ($this->buscarDoUsuario($idEndereco, $usuarioId) === null) {
            return false;
        }

        $stmt = $this->pdo->prepare(
            '
            UPDATE enderecos
            SET nome_endereco = :nome_endereco,
                logradouro = :logradouro,
                numero = :numero,
                complemento = :complemento,
                bairro = :bairro,
                cidade = :cidade,
                uf = :uf,
                cep = :cep
            WHERE id_endereco = :id_endereco
              AND usuario_id = :usuario_id
              AND deletado_em IS NULL
            '
        );
        $stmt->bindValue(':nome_endereco', $dados['nome_endereco'], PDO::PARAM_STR);
        $stmt->bindValue(':logradouro', $dados['logradouro'], PDO::PARAM_STR);
        $stmt->bindValue(':numero', $dados['numero'], PDO::PARAM_STR);
        $stmt->bindValue(
            ':complemento',
            $dados['complemento'],
            $dados['complemento'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR
        );
        $stmt->bindValue(':bairro', $dados['bairro'], PDO::PARAM_STR);
        $stmt->bindValue(':cidade', $dados['cidade'], PDO::PARAM_STR);
        $stmt->bindValue(':uf', $dados['uf'], PDO::PARAM_STR);
        $stmt->bindValue(':cep', $dados['cep'], PDO::PARAM_STR);
        $stmt->bindValue(':id_endereco', $idEndereco, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();

        return true;
    }

    public function definirPrincipal(int $idEndereco, int $usuarioId): bool
    {
        $endereco = $this->buscarDoUsuario($idEndereco, $usuarioId);

        if ($endereco === null) {
            return false;
        }

        $this->pdo->beginTransaction();

        try {
            $this->travarUsuario($usuarioId);

            $stmt = $this->pdo->prepare(
                'UPDATE enderecos SET principal = FALSE WHERE usuario_id = :usuario_id AND deletado_em IS NULL'
            );
            $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $stmt->execute();

            $stmt = $this->pdo->prepare(
                '
                UPDATE enderecos
                SET principal = TRUE
                WHERE id_endereco = :id_endereco
                  AND usuario_id = :usuario_id
                  AND deletado_em IS NULL
                '
            );
            $stmt->bindValue(':id_endereco', $idEndereco, PDO::PARAM_INT);
            $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $stmt->execute();

            $this->pdo->commit();

            return true;
        } catch (Throwable $excecao) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $excecao;
        }
    }

    public function excluir(int $idEndereco, int $usuarioId): bool
    {
        $endereco = $this->buscarDoUsuario($idEndereco, $usuarioId);

        if ($endereco === null) {
            return false;
        }

        $this->pdo->beginTransaction();

        try {
            $this->travarUsuario($usuarioId);

            $stmt = $this->pdo->prepare(
                '
                UPDATE enderecos
                SET deletado_em = CURRENT_TIMESTAMP
                WHERE id_endereco = :id_endereco
                  AND usuario_id = :usuario_id
                  AND deletado_em IS NULL
                '
            );
            $stmt->bindValue(':id_endereco', $idEndereco, PDO::PARAM_INT);
            $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $stmt->execute();

            if ((int) $endereco['principal'] === 1) {
                $stmt = $this->pdo->prepare(
                    '
                    SELECT id_endereco
                    FROM enderecos
                    WHERE usuario_id = :usuario_id
                      AND id_endereco <> :id_endereco
                      AND deletado_em IS NULL
                    ORDER BY criado_em DESC, id_endereco DESC
                    LIMIT 1
                    '
                );
                $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
                $stmt->bindValue(':id_endereco', $idEndereco, PDO::PARAM_INT);
                $stmt->execute();

                $novoPrincipal = $stmt->fetchColumn();

                if ($novoPrincipal !== false) {
                    $stmt = $this->pdo->prepare(
                        '
                        UPDATE enderecos
                        SET principal = TRUE
                        WHERE id_endereco = :id_endereco
                          AND usuario_id = :usuario_id
                          AND deletado_em IS NULL
                        '
                    );
                    $stmt->bindValue(':id_endereco', (int) $novoPrincipal, PDO::PARAM_INT);
                    $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
                    $stmt->execute();
                }
            }

            $this->pdo->commit();

            return true;
        } catch (Throwable $excecao) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $excecao;
        }
    }

    /**
     * Bloqueia a linha do usuario ate o fim da transacao, serializando
     * alteracoes concorrentes na lista de enderecos.
     */
    private function travarUsuario(int $usuarioId): void
    {
        $stmt = $this->pdo->prepare(
            'SELECT id_usuario FROM usuarios WHERE id_usuario = :id FOR UPDATE'
        );
        $stmt->bindValue(':id', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();
    }
}
