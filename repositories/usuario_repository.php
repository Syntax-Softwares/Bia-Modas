<?php

class UsuarioRepository
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function buscarPorEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare(
            "
            SELECT id_usuario, nome, email, senha_hash, telefone, is_admin, criado_em
            FROM usuarios
            WHERE email = :email
              AND deletado_em IS NULL
            LIMIT 1
            "
        );
        $stmt->execute([':email' => $email]);

        $usuario = $stmt->fetch();

        return $usuario === false ? null : $usuario;
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "
            SELECT
                u.id_usuario,
                u.nome,
                u.email,
                u.telefone,
                u.is_admin,
                u.criado_em,
                m.busto AS medida_busto,
                m.cintura AS medida_cintura,
                m.quadril AS medida_quadril,
                e.nome_endereco AS endereco_nome,
                e.logradouro AS endereco_logradouro,
                e.numero AS endereco_numero,
                e.complemento AS endereco_complemento,
                e.bairro AS endereco_bairro,
                e.cidade AS endereco_cidade,
                e.uf AS endereco_uf,
                e.cep AS endereco_cep
            FROM usuarios u
            LEFT JOIN medidas m
                ON m.id_medida = u.medida_id
               AND m.deletado_em IS NULL
            LEFT JOIN enderecos e
                ON e.usuario_id = u.id_usuario
               AND e.principal = TRUE
               AND e.deletado_em IS NULL
            WHERE u.id_usuario = :id
              AND u.deletado_em IS NULL
            LIMIT 1
            "
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $usuario = $stmt->fetch();

        return $usuario === false ? null : $usuario;
    }

    public function emailEmUso(string $email): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM usuarios WHERE email = :email AND deletado_em IS NULL'
        );
        $stmt->execute([':email' => $email]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Cria o usuário e, opcionalmente, suas medidas e endereço principal.
     *
     * @return int ID do usuário criado.
     */
    public function criar(array $dados): int
    {
        $this->pdo->beginTransaction();

        try {
            $medidaId = null;

            if (algum_preenchido($dados['medidas'] ?? [])) {
                $stmt = $this->pdo->prepare(
                    'INSERT INTO medidas (busto, cintura, quadril) VALUES (:busto, :cintura, :quadril)'
                );
                $stmt->execute([
                    ':busto' => $dados['medidas']['busto'] ?? null,
                    ':cintura' => $dados['medidas']['cintura'] ?? null,
                    ':quadril' => $dados['medidas']['quadril'] ?? null,
                ]);

                $medidaId = (int) $this->pdo->lastInsertId();
            }

            $stmt = $this->pdo->prepare(
                "
                INSERT INTO usuarios
                    (medida_id, nome, email, senha_hash, cpf, telefone, data_nascimento, is_admin)
                VALUES
                    (:medida_id, :nome, :email, :senha_hash, :cpf, :telefone, :data_nascimento, FALSE)
                "
            );
            $stmt->execute([
                ':medida_id' => $medidaId,
                ':nome' => $dados['nome'],
                ':email' => $dados['email'],
                ':senha_hash' => $dados['senha_hash'],
                ':cpf' => $dados['cpf'] ?? null,
                ':telefone' => $dados['telefone'] ?? null,
                ':data_nascimento' => $dados['data_nascimento'] ?? null,
            ]);

            $usuarioId = (int) $this->pdo->lastInsertId();

            if (algum_preenchido($dados['endereco'] ?? [])) {
                $stmt = $this->pdo->prepare(
                    "
                    INSERT INTO enderecos
                        (usuario_id, nome_endereco, logradouro, numero, complemento,
                         bairro, cidade, uf, cep, principal)
                    VALUES
                        (:usuario_id, :nome_endereco, :logradouro, :numero, :complemento,
                         :bairro, :cidade, :uf, :cep, TRUE)
                    "
                );
                $stmt->execute([
                    ':usuario_id' => $usuarioId,
                    ':nome_endereco' => $dados['endereco']['nome_endereco'] ?: 'Principal',
                    ':logradouro' => $dados['endereco']['logradouro'],
                    ':numero' => $dados['endereco']['numero'],
                    ':complemento' => $dados['endereco']['complemento'] ?: null,
                    ':bairro' => $dados['endereco']['bairro'],
                    ':cidade' => $dados['endereco']['cidade'],
                    ':uf' => $dados['endereco']['uf'],
                    ':cep' => $dados['endereco']['cep'],
                ]);
            }

            $this->pdo->commit();

            return $usuarioId;
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function atualizarSenhaHash(int $id, string $senhaHash): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE usuarios SET senha_hash = :senha_hash WHERE id_usuario = :id'
        );
        $stmt->execute([
            ':senha_hash' => $senhaHash,
            ':id' => $id,
        ]);
    }
}
