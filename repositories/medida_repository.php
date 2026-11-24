<?php

require_once __DIR__ . '/../core/helpers.php';

/**
 * Medidas corporais do usuario (MVC10).
 *
 * A tabela `medidas` e 1:1 opcional com `usuarios` (usuarios.medida_id). As
 * consultas sao sempre restritas ao proprio usuario autenticado; nenhum id
 * de medida vindo do formulario e aceito.
 */
final class MedidaRepository
{
    private const CAMPOS = ['busto', 'cintura', 'quadril'];

    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Medidas do usuario no formato usado pela view.
     *
     * Retorna `null` quando o usuario nao existe; quando nao ha medidas
     * cadastradas, retorna os campos nulos.
     *
     * @return array{busto: ?float, cintura: ?float, quadril: ?float}|null
     */
    public function buscarDoUsuario(int $usuarioId): ?array
    {
        $stmt = $this->pdo->prepare(
            '
            SELECT u.id_usuario, m.busto, m.cintura, m.quadril
            FROM usuarios u
            LEFT JOIN medidas m
                ON m.id_medida = u.medida_id
               AND m.deletado_em IS NULL
            WHERE u.id_usuario = :id
              AND u.deletado_em IS NULL
            LIMIT 1
            '
        );
        $stmt->bindValue(':id', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();

        $linha = $stmt->fetch();

        if ($linha === false) {
            return null;
        }

        return [
            'busto' => $linha['busto'] !== null ? (float) $linha['busto'] : null,
            'cintura' => $linha['cintura'] !== null ? (float) $linha['cintura'] : null,
            'quadril' => $linha['quadril'] !== null ? (float) $linha['quadril'] : null,
        ];
    }

    /**
     * Grava as medidas do usuario, criando ou reaproveitando o registro 1:1.
     *
     * @param array{busto: ?float, cintura: ?float, quadril: ?float} $medidas
     */
    public function salvarDoUsuario(int $usuarioId, array $medidas): void
    {
        $valores = [];

        foreach (self::CAMPOS as $campo) {
            $valor = $medidas[$campo] ?? null;
            $valores[$campo] = $valor === null ? null : round((float) $valor, 1);
        }

        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                '
                SELECT medida_id
                FROM usuarios
                WHERE id_usuario = :id
                  AND deletado_em IS NULL
                FOR UPDATE
                '
            );
            $stmt->bindValue(':id', $usuarioId, PDO::PARAM_INT);
            $stmt->execute();
            $medidaId = $stmt->fetchColumn();

            if ($medidaId === false) {
                // Usuario removido entre a leitura e a gravacao.
                $this->pdo->rollBack();

                return;
            }

            $medidaId = $medidaId === null ? null : (int) $medidaId;

            if ($medidaId === null) {
                // Sem nada preenchido nao ha motivo para criar um registro vazio.
                if (!algum_preenchido($valores)) {
                    $this->pdo->commit();

                    return;
                }

                $stmt = $this->pdo->prepare(
                    'INSERT INTO medidas (busto, cintura, quadril)
                     VALUES (:busto, :cintura, :quadril)'
                );
                $stmt->execute($this->parametros($valores));
                $medidaId = (int) $this->pdo->lastInsertId();

                $stmt = $this->pdo->prepare(
                    'UPDATE usuarios SET medida_id = :medida_id WHERE id_usuario = :id'
                );
                $stmt->execute([':medida_id' => $medidaId, ':id' => $usuarioId]);
            } else {
                // Reaproveita o registro existente (inclusive se estava
                // marcado como removido) para nao deixar linhas orfas.
                $stmt = $this->pdo->prepare(
                    'UPDATE medidas
                     SET busto = :busto,
                         cintura = :cintura,
                         quadril = :quadril,
                         deletado_em = NULL
                     WHERE id_medida = :id'
                );
                $stmt->execute($this->parametros($valores) + [':id' => $medidaId]);
            }

            $this->pdo->commit();
        } catch (Throwable $excecao) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $excecao;
        }
    }

    /**
     * @param array{busto: ?float, cintura: ?float, quadril: ?float} $valores
     * @return array<string, float|null>
     */
    private function parametros(array $valores): array
    {
        return [
            ':busto' => $valores['busto'],
            ':cintura' => $valores['cintura'],
            ':quadril' => $valores['quadril'],
        ];
    }
}
