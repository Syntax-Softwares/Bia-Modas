<?php

/**
 * Consultas e escrita de produtos na área administrativa.
 */
class AdminProdutoRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return array{itens: array<int, array<string, mixed>>, total: int}
     */
    public function listar(array $filtros, int $pagina, int $porPagina = 20): array
    {
        $where = ['p.deletado_em IS NULL'];
        $params = [];

        if (($filtros['q'] ?? '') !== '') {
            $where[] = '(p.nome LIKE :busca_nome OR p.sku LIKE :busca_sku)';
            $params[':busca_nome'] = '%' . $filtros['q'] . '%';
            $params[':busca_sku'] = '%' . $filtros['q'] . '%';
        }

        if (($filtros['status'] ?? '') === 'ativos') {
            $where[] = 'p.ativo = TRUE';
        } elseif (($filtros['status'] ?? '') === 'inativos') {
            $where[] = 'p.ativo = FALSE';
        }

        if (($filtros['categoria_id'] ?? 0) > 0) {
            $where[] = 'EXISTS (
                SELECT 1
                FROM categorias_produtos cp_filtro
                WHERE cp_filtro.produto_id = p.id_produto
                  AND cp_filtro.categoria_id = :categoria_id
                  AND cp_filtro.deletado_em IS NULL
            )';
            $params[':categoria_id'] = (int) $filtros['categoria_id'];
        }

        $whereSql = ' WHERE ' . implode(' AND ', $where);

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM produtos p' . $whereSql);
        $this->bind($stmt, $params);
        $stmt->execute();
        $total = (int) $stmt->fetchColumn();

        $sql = $this->selectLista()
            . $whereSql
            . ' ORDER BY p.criado_em DESC, p.id_produto DESC
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
            'SELECT
                p.id_produto, p.medida_produto_id, p.oferta_id, p.nome, p.sku,
                p.descricao, p.estoque, p.valor, p.ativo, p.criado_em
             FROM produtos p
             WHERE p.id_produto = :id
               AND p.deletado_em IS NULL
             LIMIT 1'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $produto = $stmt->fetch();

        if ($produto === false) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT categoria_id, principal
             FROM categorias_produtos
             WHERE produto_id = :id
               AND deletado_em IS NULL
             ORDER BY principal DESC, categoria_id ASC'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $produto['categorias'] = $stmt->fetchAll();

        $stmt = $this->pdo->prepare(
            'SELECT caminho_imagem
             FROM imagens
             WHERE produto_id = :id
               AND deletado_em IS NULL
             ORDER BY id_imagem ASC
             LIMIT 1'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $imagem = $stmt->fetchColumn();
        $produto['imagem'] = $imagem === false ? null : (string) $imagem;

        return $produto;
    }

    public function skuEmUso(string $sku, ?int $ignorarId = null): bool
    {
        // Inclui produtos excluídos: o UNIQUE do banco também os considera.
        $sql = 'SELECT COUNT(*) FROM produtos WHERE sku = :sku';
        $params = [':sku' => $sku];

        if ($ignorarId !== null) {
            $sql .= ' AND id_produto <> :ignorar';
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
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO produtos
                    (medida_produto_id, oferta_id, nome, sku, descricao, estoque, valor, ativo)
                 VALUES
                    (:medida_produto_id, :oferta_id, :nome, :sku, :descricao, :estoque, :valor, :ativo)'
            );
            $stmt->execute([
                ':medida_produto_id' => $dados['medida_produto_id'],
                ':oferta_id' => $dados['oferta_id'],
                ':nome' => $dados['nome'],
                ':sku' => $dados['sku'],
                ':descricao' => $dados['descricao'],
                ':estoque' => $dados['estoque'],
                ':valor' => $dados['valor'],
                ':ativo' => $dados['ativo'] ? 1 : 0,
            ]);

            $produtoId = (int) $this->pdo->lastInsertId();

            $this->sincronizarCategorias($produtoId, $dados['categorias'], $dados['categoria_principal']);

            if (($dados['imagem'] ?? null) !== null) {
                $this->inserirImagem($produtoId, $dados['imagem']);
            }

            $this->pdo->commit();

            return $produtoId;
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function atualizar(int $id, array $dados): void
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                'UPDATE produtos
                 SET medida_produto_id = :medida_produto_id,
                     oferta_id = :oferta_id,
                     nome = :nome,
                     sku = :sku,
                     descricao = :descricao,
                     estoque = :estoque,
                     valor = :valor,
                     ativo = :ativo
                 WHERE id_produto = :id'
            );
            $stmt->execute([
                ':medida_produto_id' => $dados['medida_produto_id'],
                ':oferta_id' => $dados['oferta_id'],
                ':nome' => $dados['nome'],
                ':sku' => $dados['sku'],
                ':descricao' => $dados['descricao'],
                ':estoque' => $dados['estoque'],
                ':valor' => $dados['valor'],
                ':ativo' => $dados['ativo'] ? 1 : 0,
                ':id' => $id,
            ]);

            $this->sincronizarCategorias($id, $dados['categorias'], $dados['categoria_principal']);

            if (($dados['imagem'] ?? null) !== null) {
                $stmt = $this->pdo->prepare('UPDATE imagens SET deletado_em = NOW() WHERE produto_id = :id');
                $stmt->bindValue(':id', $id, PDO::PARAM_INT);
                $stmt->execute();

                $this->inserirImagem($id, $dados['imagem']);
            }

            $this->pdo->commit();
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function excluir(int $id): void
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                'UPDATE produtos SET deletado_em = NOW(), ativo = FALSE WHERE id_produto = :id'
            );
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $stmt = $this->pdo->prepare(
                'UPDATE categorias_produtos SET deletado_em = NOW() WHERE produto_id = :id AND deletado_em IS NULL'
            );
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $this->pdo->commit();
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    /**
     * @return array{itens: array<int, array<string, mixed>>, total: int}
     */
    public function listarParaEstoque(
        ?string $busca,
        bool $somenteBaixo,
        int $pagina,
        int $porPagina = 20,
        int $limiteBaixo = 5
    ): array {
        $where = ['p.deletado_em IS NULL'];
        $params = [];

        if ($busca !== null && trim($busca) !== '') {
            $where[] = '(p.nome LIKE :busca_nome OR p.sku LIKE :busca_sku)';
            $params[':busca_nome'] = '%' . trim($busca) . '%';
            $params[':busca_sku'] = '%' . trim($busca) . '%';
        }

        if ($somenteBaixo) {
            $where[] = 'p.estoque <= :limite_baixo';
            $params[':limite_baixo'] = $limiteBaixo;
        }

        $whereSql = ' WHERE ' . implode(' AND ', $where);

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM produtos p' . $whereSql);
        $this->bind($stmt, $params);
        $stmt->execute();
        $total = (int) $stmt->fetchColumn();

        $sql = 'SELECT
                    p.id_produto,
                    p.nome,
                    p.sku,
                    p.estoque,
                    p.ativo,
                    COALESCE(mp.tamanho, \'\') AS tamanho,
                    COALESCE((
                        SELECT c.nome
                        FROM categorias_produtos cp
                        INNER JOIN categorias c
                            ON c.id_categoria = cp.categoria_id
                           AND c.deletado_em IS NULL
                        WHERE cp.produto_id = p.id_produto
                          AND cp.deletado_em IS NULL
                        ORDER BY cp.principal DESC, c.id_categoria ASC
                        LIMIT 1
                    ), \'—\') AS categoria
                FROM produtos p
                LEFT JOIN medidas_produtos mp
                    ON mp.id_medida_produto = p.medida_produto_id
                   AND mp.deletado_em IS NULL'
            . $whereSql
            . ' ORDER BY p.estoque ASC, p.nome ASC
                LIMIT :limite OFFSET :offset';

        $stmt = $this->pdo->prepare($sql);
        $this->bind($stmt, $params);
        $stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', max(0, ($pagina - 1) * $porPagina), PDO::PARAM_INT);
        $stmt->execute();

        return ['itens' => $stmt->fetchAll(), 'total' => $total];
    }

    public function atualizarEstoque(int $id, int $estoque): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE produtos SET estoque = :estoque WHERE id_produto = :id AND deletado_em IS NULL'
        );
        $stmt->bindValue(':estoque', $estoque, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    private function inserirImagem(int $produtoId, string $caminho): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO imagens (produto_id, caminho_imagem) VALUES (:produto_id, :caminho)'
        );
        $stmt->execute([
            ':produto_id' => $produtoId,
            ':caminho' => $caminho,
        ]);
    }

    private function sincronizarCategorias(int $produtoId, array $categorias, int $principalId): void
    {
        $stmt = $this->pdo->prepare(
            'SELECT categoria_id, deletado_em
             FROM categorias_produtos
             WHERE produto_id = :id'
        );
        $stmt->bindValue(':id', $produtoId, PDO::PARAM_INT);
        $stmt->execute();

        $existentes = [];

        foreach ($stmt->fetchAll() as $linha) {
            $existentes[(int) $linha['categoria_id']] = $linha['deletado_em'];
        }

        foreach ($categorias as $categoriaId) {
            $categoriaId = (int) $categoriaId;
            $principal = $categoriaId === $principalId ? 1 : 0;

            if (array_key_exists($categoriaId, $existentes)) {
                $stmt = $this->pdo->prepare(
                    'UPDATE categorias_produtos
                     SET deletado_em = NULL,
                         principal = :principal
                     WHERE produto_id = :produto_id
                       AND categoria_id = :categoria_id'
                );
                $stmt->execute([
                    ':principal' => $principal,
                    ':produto_id' => $produtoId,
                    ':categoria_id' => $categoriaId,
                ]);
                continue;
            }

            $stmt = $this->pdo->prepare(
                'INSERT INTO categorias_produtos (produto_id, categoria_id, principal)
                 VALUES (:produto_id, :categoria_id, :principal)'
            );
            $stmt->execute([
                ':produto_id' => $produtoId,
                ':categoria_id' => $categoriaId,
                ':principal' => $principal,
            ]);
        }

        foreach ($existentes as $categoriaId => $deletadoEm) {
            if (in_array($categoriaId, array_map('intval', $categorias), true)) {
                continue;
            }

            if ($deletadoEm !== null) {
                continue;
            }

            $stmt = $this->pdo->prepare(
                'UPDATE categorias_produtos
                 SET deletado_em = NOW()
                 WHERE produto_id = :produto_id
                   AND categoria_id = :categoria_id'
            );
            $stmt->execute([
                ':produto_id' => $produtoId,
                ':categoria_id' => $categoriaId,
            ]);
        }
    }

    private function selectLista(): string
    {
        return "SELECT
                    p.id_produto,
                    p.nome,
                    p.sku,
                    p.valor,
                    p.estoque,
                    p.ativo,
                    p.criado_em,
                    COALESCE((
                        SELECT c.nome
                        FROM categorias_produtos cp
                        INNER JOIN categorias c
                            ON c.id_categoria = cp.categoria_id
                           AND c.deletado_em IS NULL
                        WHERE cp.produto_id = p.id_produto
                          AND cp.deletado_em IS NULL
                        ORDER BY cp.principal DESC, c.id_categoria ASC
                        LIMIT 1
                    ), '—') AS categoria,
                    (
                        SELECT i.caminho_imagem
                        FROM imagens i
                        WHERE i.produto_id = p.id_produto
                          AND i.deletado_em IS NULL
                        ORDER BY i.id_imagem ASC
                        LIMIT 1
                    ) AS imagem
                FROM produtos p";
    }

    private function bind(PDOStatement $stmt, array $params): void
    {
        foreach ($params as $nome => $valor) {
            $stmt->bindValue(
                $nome,
                $valor,
                is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR
            );
        }
    }
}
