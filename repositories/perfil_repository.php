<?php

/**
 * Dados cadastrais do usuario logado.
 *
 * Todas as consultas sao preparadas e restritas ao proprio usuario.
 */
final class PerfilRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            '
            SELECT id_usuario, nome, email, telefone, cpf, data_nascimento, criado_em
            FROM usuarios
            WHERE id_usuario = :id
              AND deletado_em IS NULL
            LIMIT 1
            '
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $usuario = $stmt->fetch();

        return $usuario === false ? null : $usuario;
    }

    public function buscarSenhaHash(int $id): ?string
    {
        $stmt = $this->pdo->prepare(
            '
            SELECT senha_hash
            FROM usuarios
            WHERE id_usuario = :id
              AND deletado_em IS NULL
            LIMIT 1
            '
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $hash = $stmt->fetchColumn();

        return is_string($hash) ? $hash : null;
    }

    public function emailEmUsoPorOutro(string $email, int $id): bool
    {
        $stmt = $this->pdo->prepare(
            '
            SELECT COUNT(*)
            FROM usuarios
            WHERE email = :email
              AND id_usuario <> :id
              AND deletado_em IS NULL
            '
        );
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn() > 0;
    }

    public function cpfEmUsoPorOutro(string $cpf, int $id): bool
    {
        $stmt = $this->pdo->prepare(
            '
            SELECT COUNT(*)
            FROM usuarios
            WHERE cpf = :cpf
              AND id_usuario <> :id
              AND deletado_em IS NULL
            '
        );
        $stmt->bindValue(':cpf', $cpf, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn() > 0;
    }

    public function atualizarDados(int $id, array $dados): void
    {
        $stmt = $this->pdo->prepare(
            '
            UPDATE usuarios
            SET nome = :nome,
                email = :email,
                telefone = :telefone,
                cpf = :cpf,
                data_nascimento = :data_nascimento
            WHERE id_usuario = :id
              AND deletado_em IS NULL
            '
        );
        $stmt->bindValue(':nome', $dados['nome'], PDO::PARAM_STR);
        $stmt->bindValue(':email', $dados['email'], PDO::PARAM_STR);
        $stmt->bindValue(':telefone', $dados['telefone'], PDO::PARAM_STR);
        $stmt->bindValue(':cpf', $dados['cpf'], $dados['cpf'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(
            ':data_nascimento',
            $dados['data_nascimento'],
            $dados['data_nascimento'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function atualizarSenha(int $id, string $senhaHash): void
    {
        $stmt = $this->pdo->prepare(
            '
            UPDATE usuarios
            SET senha_hash = :senha_hash
            WHERE id_usuario = :id
              AND deletado_em IS NULL
            '
        );
        $stmt->bindValue(':senha_hash', $senhaHash, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }
}
