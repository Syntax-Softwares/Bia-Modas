<?php

class CodigoRecuperacaoRepository
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    /**
     * Limite de solicitações por usuário:
     * - 1 código por minuto;
     * - no máximo 3 códigos por hora.
     */
    public function podeSolicitar(int $usuarioId): bool
    {
        $stmt = $this->pdo->prepare(
            "
            SELECT
                COUNT(*) AS total_hora,
                COALESCE(SUM(criado_em > DATE_SUB(NOW(), INTERVAL 60 SECOND)), 0) AS ultimo_minuto
            FROM codigos_recuperacao_senha
            WHERE usuario_id = :usuario_id
              AND criado_em > DATE_SUB(NOW(), INTERVAL 60 MINUTE)
            "
        );
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();

        $linha = $stmt->fetch();

        if ($linha === false) {
            return true;
        }

        return (int) $linha['total_hora'] < 3
            && (int) $linha['ultimo_minuto'] === 0;
    }

    /**
     * Invalida os códigos anteriores e grava o novo (expira em 10 minutos).
     *
     * @return int ID do código criado.
     */
    public function criar(int $usuarioId, string $codigoHash): int
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                '
                UPDATE codigos_recuperacao_senha
                   SET usado_em = NOW()
                 WHERE usuario_id = :usuario_id
                   AND usado_em IS NULL
                '
            );
            $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $stmt->execute();

            $stmt = $this->pdo->prepare(
                '
                INSERT INTO codigos_recuperacao_senha (usuario_id, codigo_hash, expira_em)
                VALUES (:usuario_id, :codigo_hash, DATE_ADD(NOW(), INTERVAL 10 MINUTE))
                '
            );
            $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $stmt->bindValue(':codigo_hash', $codigoHash);
            $stmt->execute();

            $idCodigo = (int) $this->pdo->lastInsertId();

            $this->pdo->commit();

            return $idCodigo;
        } catch (Throwable $erro) {
            $this->pdo->rollBack();
            throw $erro;
        }
    }

    /**
     * Retorna o código ativo (não usado e não expirado) mais recente.
     */
    public function buscarAtivoPorUsuario(int $usuarioId): ?array
    {
        $stmt = $this->pdo->prepare(
            '
            SELECT id_codigo, usuario_id, codigo_hash, tentativas, expira_em, criado_em
              FROM codigos_recuperacao_senha
             WHERE usuario_id = :usuario_id
               AND usado_em IS NULL
               AND expira_em > NOW()
             ORDER BY criado_em DESC
             LIMIT 1
            '
        );
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();

        $registro = $stmt->fetch();

        return $registro === false ? null : $registro;
    }

    public function registrarTentativa(int $idCodigo): void
    {
        $stmt = $this->pdo->prepare(
            '
            UPDATE codigos_recuperacao_senha
               SET tentativas = tentativas + 1
             WHERE id_codigo = :id_codigo
            '
        );
        $stmt->bindValue(':id_codigo', $idCodigo, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function invalidar(int $idCodigo): void
    {
        $stmt = $this->pdo->prepare(
            '
            UPDATE codigos_recuperacao_senha
               SET usado_em = NOW()
             WHERE id_codigo = :id_codigo
               AND usado_em IS NULL
            '
        );
        $stmt->bindValue(':id_codigo', $idCodigo, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function invalidarTodosDoUsuario(int $usuarioId): void
    {
        $stmt = $this->pdo->prepare(
            '
            UPDATE codigos_recuperacao_senha
               SET usado_em = NOW()
             WHERE usuario_id = :usuario_id
               AND usado_em IS NULL
            '
        );
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();
    }
}
