<?php

/**
 * Consultas administrativas de usuários (clientes e administradores).
 */
class AdminUsuarioRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return array{itens: array<int, array<string, mixed>>, total: int}
     */
    public function listar(array $filtros, int $pagina, int $porPagina = 20): array
    {
        $where = ['u.deletado_em IS NULL'];
        $params = [];

        if (($filtros['q'] ?? '') !== '') {
            $where[] = '(u.nome LIKE :busca_nome OR u.email LIKE :busca_email)';
            $params[':busca_nome'] = '%' . $filtros['q'] . '%';
            $params[':busca_email'] = '%' . $filtros['q'] . '%';
        }

        if (($filtros['tipo'] ?? '') === 'admins') {
            $where[] = 'u.is_admin = TRUE';
        } elseif (($filtros['tipo'] ?? '') === 'clientes') {
            $where[] = 'u.is_admin = FALSE';
        }

        $whereSql = ' WHERE ' . implode(' AND ', $where);

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM usuarios u' . $whereSql);
        $this->bind($stmt, $params);
        $stmt->execute();
        $total = (int) $stmt->fetchColumn();

        $sql = 'SELECT u.id_usuario, u.nome, u.email, u.telefone, u.is_admin, u.criado_em
                FROM usuarios u'
            . $whereSql
            . ' ORDER BY u.criado_em DESC, u.id_usuario DESC
                LIMIT :limite OFFSET :offset';

        $stmt = $this->pdo->prepare($sql);
        $this->bind($stmt, $params);
        $stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', max(0, ($pagina - 1) * $porPagina), PDO::PARAM_INT);
        $stmt->execute();

        return ['itens' => $stmt->fetchAll(), 'total' => $total];
    }

    public function getPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id_usuario, nome, email, telefone, cpf, is_admin, criado_em
             FROM usuarios
             WHERE id_usuario = :id
               AND deletado_em IS NULL
             LIMIT 1'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $usuario = $stmt->fetch();

        return $usuario === false ? null : $usuario;
    }

    public function emailEmUso(string $email, ?int $ignorarId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM usuarios WHERE email = :email AND deletado_em IS NULL';
        $params = [':email' => $email];

        if ($ignorarId !== null) {
            $sql .= ' AND id_usuario <> :ignorar';
            $params[':ignorar'] = $ignorarId;
        }

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $nome => $valor) {
            $stmt->bindValue($nome, $valor, $nome === ':ignorar' ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $stmt->execute();

        return (int) $stmt->fetchColumn() > 0;
    }

    public function cpfEmUso(string $cpf, ?int $ignorarId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM usuarios WHERE cpf = :cpf AND deletado_em IS NULL';
        $params = [':cpf' => $cpf];

        if ($ignorarId !== null) {
            $sql .= ' AND id_usuario <> :ignorar';
            $params[':ignorar'] = $ignorarId;
        }

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $nome => $valor) {
            $stmt->bindValue($nome, $valor, $nome === ':ignorar' ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $stmt->execute();

        return (int) $stmt->fetchColumn() > 0;
    }

    public function criar(array $dados): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO usuarios (nome, email, senha_hash, telefone, cpf, is_admin)
             VALUES (:nome, :email, :senha_hash, :telefone, :cpf, :is_admin)'
        );
        $stmt->execute([
            ':nome' => $dados['nome'],
            ':email' => $dados['email'],
            ':senha_hash' => $dados['senha_hash'],
            ':telefone' => $dados['telefone'],
            ':cpf' => $dados['cpf'],
            ':is_admin' => $dados['is_admin'] ? 1 : 0,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function atualizar(int $id, array $dados): void
    {
        $sql = 'UPDATE usuarios
                SET nome = :nome,
                    email = :email,
                    telefone = :telefone,
                    cpf = :cpf,
                    is_admin = :is_admin';

        $params = [
            ':nome' => $dados['nome'],
            ':email' => $dados['email'],
            ':telefone' => $dados['telefone'],
            ':cpf' => $dados['cpf'],
            ':is_admin' => $dados['is_admin'] ? 1 : 0,
            ':id' => $id,
        ];

        if (($dados['senha_hash'] ?? null) !== null) {
            $sql .= ', senha_hash = :senha_hash';
            $params[':senha_hash'] = $dados['senha_hash'];
        }

        $sql .= ' WHERE id_usuario = :id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    /**
     * Soft delete com anonimização do e-mail para liberar o UNIQUE.
     */
    public function excluir(int $id): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE usuarios
             SET deletado_em = NOW(),
                 email = CONCAT('deleted+', id_usuario, '+', LEFT(email, 200))
             WHERE id_usuario = :id
               AND deletado_em IS NULL"
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function contarAdminsAtivos(): int
    {
        return (int) $this->pdo
            ->query('SELECT COUNT(*) FROM usuarios WHERE is_admin = TRUE AND deletado_em IS NULL')
            ->fetchColumn();
    }

    private function bind(PDOStatement $stmt, array $params): void
    {
        foreach ($params as $nome => $valor) {
            $stmt->bindValue($nome, $valor, PDO::PARAM_STR);
        }
    }
}
