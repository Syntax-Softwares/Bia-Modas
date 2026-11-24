<?php

require_once __DIR__ . '/../core/helpers.php';

class ProdutoRepository
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function get_novidades(int $limite = 15): array
    {
        return $this->getNovidades($limite);
    }

    public function getNovidades(int $limite = 8): array
    {
        $sql = $this->selectBase()
            . "
            WHERE p.ativo = TRUE
              AND p.deletado_em IS NULL
            ORDER BY p.criado_em DESC, p.id_produto DESC
            LIMIT :limite
            ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $this->mapMany($stmt->fetchAll());
    }

    public function getPromocoes(int $limite = 8): array
    {
        $sql = $this->selectBase()
            . "
            WHERE p.ativo = TRUE
              AND p.deletado_em IS NULL
              AND o.id_oferta IS NOT NULL
            ORDER BY o.percentual DESC, p.criado_em DESC, p.id_produto DESC
            LIMIT :limite
            ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $this->mapMany($stmt->fetchAll());
    }

    /**
     * "Para Voce": produtos das categorias das 3 pecas favoritadas mais
     * recentes, excluindo o que ja esta favoritado. Oferta primeiro, depois
     * proximidade de preco com a media dos favoritos.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getParaVoce(int $usuarioId, int $limite = 8): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT f.produto_id, p.valor
             FROM favoritos f
             INNER JOIN produtos p ON p.id_produto = f.produto_id
             WHERE f.usuario_id = :usuario
               AND f.deletado_em IS NULL
               AND p.deletado_em IS NULL
               AND p.ativo = TRUE
             ORDER BY f.criado_em DESC, f.id_favorito DESC
             LIMIT 3'
        );
        $stmt->bindValue(':usuario', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();

        $favoritos = $stmt->fetchAll();

        if ($favoritos === []) {
            return [];
        }

        $ids = array_map(static fn (array $row): int => (int) $row['produto_id'], $favoritos);
        $soma = array_sum(array_map(static fn (array $row): float => (float) $row['valor'], $favoritos));
        $precoMedio = $soma / count($favoritos);
        $listaIds = implode(',', $ids);

        $sql = $this->selectBase()
            . "
            WHERE p.ativo = TRUE
              AND p.deletado_em IS NULL
              AND p.id_produto NOT IN ($listaIds)
              AND EXISTS (
                  SELECT 1
                  FROM categorias_produtos cp_rec
                  INNER JOIN categorias_produtos cp_fav
                      ON cp_fav.categoria_id = cp_rec.categoria_id
                  WHERE cp_rec.produto_id = p.id_produto
                    AND cp_rec.deletado_em IS NULL
                    AND cp_fav.deletado_em IS NULL
                    AND cp_fav.produto_id IN ($listaIds)
              )
            ORDER BY
                (o.id_oferta IS NOT NULL) DESC,
                ABS(p.valor - :preco_medio) ASC,
                p.criado_em DESC
            LIMIT :limite
            ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':preco_medio', $precoMedio);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $this->mapMany($stmt->fetchAll());
    }

    /**
     * Produtos mais favoritados da loja (fallback da secao "Para Voce"
     * quando o visitante nao tem favoritos).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getMaisDesejados(int $limite = 8): array
    {
        $sql = '
            SELECT * FROM (
                SELECT
                    base.*,
                    (
                        SELECT COUNT(*)
                        FROM favoritos f
                        WHERE f.produto_id = base.id_produto
                          AND f.deletado_em IS NULL
                    ) AS total_favoritos
                FROM (' . $this->selectBase() . '
                    WHERE p.ativo = TRUE
                      AND p.deletado_em IS NULL
                ) base
            ) ranking
            WHERE total_favoritos > 0
            ORDER BY total_favoritos DESC, ranking.criado_em DESC, ranking.id_produto DESC
            LIMIT :limite
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $this->mapMany($stmt->fetchAll());
    }

    /**
     * "Voce tambem pode gostar": produtos que compartilham categoria com o
     * produto atual (N:N), oferta primeiro e preco proximo. Se sobrarem
     * menos de 4, completa com produtos na mesma faixa de preco.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRelacionados(int $produtoId, int $limite = 8): array
    {
        $produto = $this->getPorId($produtoId);

        if ($produto === null) {
            return [];
        }

        $preco = (float) $produto['valor'];

        $sql = $this->selectBase()
            . "
            WHERE p.ativo = TRUE
              AND p.deletado_em IS NULL
              AND p.id_produto <> :produto
              AND EXISTS (
                  SELECT 1
                  FROM categorias_produtos cp_rel
                  INNER JOIN categorias_produtos cp_atual
                      ON cp_atual.categoria_id = cp_rel.categoria_id
                  WHERE cp_rel.produto_id = p.id_produto
                    AND cp_rel.deletado_em IS NULL
                    AND cp_atual.deletado_em IS NULL
                    AND cp_atual.produto_id = :produto_categoria
              )
            ORDER BY
                (o.id_oferta IS NOT NULL) DESC,
                ABS(p.valor - :preco_ordem) ASC,
                p.criado_em DESC
            LIMIT :limite
            ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':produto', $produtoId, PDO::PARAM_INT);
        $stmt->bindValue(':produto_categoria', $produtoId, PDO::PARAM_INT);
        $stmt->bindValue(':preco_ordem', $preco);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        $relacionados = $this->mapMany($stmt->fetchAll());

        if (count($relacionados) >= 4 || count($relacionados) >= $limite) {
            return $relacionados;
        }

        $excluir = array_map(
            static fn (array $item): int => (int) $item['id_produto'],
            $relacionados
        );
        $excluir[] = $produtoId;

        $faltam = $limite - count($relacionados);
        $faixa = max(20.0, $preco * 0.3);

        $sql = $this->selectBase()
            . '
            WHERE p.ativo = TRUE
              AND p.deletado_em IS NULL
              AND p.id_produto NOT IN (' . implode(',', $excluir) . ')
              AND ABS(p.valor - :preco_faixa) <= :faixa
            ORDER BY
                (o.id_oferta IS NOT NULL) DESC,
                ABS(p.valor - :preco_ordem) ASC,
                p.criado_em DESC
            LIMIT :limite
            ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':preco_faixa', $preco);
        $stmt->bindValue(':faixa', $faixa);
        $stmt->bindValue(':preco_ordem', $preco);
        $stmt->bindValue(':limite', $faltam, PDO::PARAM_INT);
        $stmt->execute();

        return array_merge($relacionados, $this->mapMany($stmt->fetchAll()));
    }

    public function listar(
        ?string $categoria = null,
        ?string $busca = null,
        bool $somentePromocoes = false,
        int $limite = 60
    ): array {
        $where = [
            'p.ativo = TRUE',
            'p.deletado_em IS NULL',
        ];
        $params = [];

        if ($categoria !== null && trim($categoria) !== '') {
            $where[] = "
                EXISTS (
                    SELECT 1
                    FROM categorias_produtos cp_filtro
                    INNER JOIN categorias c_filtro
                        ON c_filtro.id_categoria = cp_filtro.categoria_id
                    WHERE cp_filtro.produto_id = p.id_produto
                      AND cp_filtro.deletado_em IS NULL
                      AND c_filtro.deletado_em IS NULL
                      AND c_filtro.nome = :categoria
                )
            ";
            $params[':categoria'] = trim($categoria);
        }

        if ($busca !== null && trim($busca) !== '') {
            $where[] = '(p.nome LIKE :busca_nome OR p.descricao LIKE :busca_descricao)';
            $params[':busca_nome'] = '%' . trim($busca) . '%';
            $params[':busca_descricao'] = '%' . trim($busca) . '%';
        }

        if ($somentePromocoes) {
            $where[] = 'o.id_oferta IS NOT NULL';
        }

        $sql = $this->selectBase()
            . ' WHERE ' . implode(' AND ', $where)
            . ' ORDER BY p.criado_em DESC, p.id_produto DESC LIMIT :limite';

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $name => $value) {
            $stmt->bindValue($name, $value, PDO::PARAM_STR);
        }

        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $this->mapMany($stmt->fetchAll());
    }

    public function getPorId(int $id): ?array
    {
        $sql = $this->selectBase()
            . "
            WHERE p.id_produto = :id
              AND p.ativo = TRUE
              AND p.deletado_em IS NULL
            LIMIT 1
            ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $produto = $stmt->fetch();

        if ($produto === false) {
            return null;
        }

        $produto = $this->map($produto);
        $produto['variacoes'] = $this->variacoes($id);

        return $produto;
    }

    public function variacoes(int $idProduto): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT pt.id_produto_tamanho, pt.estoque, t.nome AS tamanho
             FROM produto_tamanhos pt
             INNER JOIN tamanhos t ON t.id_tamanho = pt.tamanho_id
             WHERE pt.produto_id = :id
               AND pt.deletado_em IS NULL
               AND t.deletado_em IS NULL
             ORDER BY t.ordem ASC, t.id_tamanho ASC"
        );
        $stmt->execute([':id' => $idProduto]);

        return array_map(
            static fn (array $linha): array => [
                'id_produto_tamanho' => (int) $linha['id_produto_tamanho'],
                'tamanho' => (string) $linha['tamanho'],
                'estoque' => (int) $linha['estoque'],
            ],
            $stmt->fetchAll()
        );
    }

    public function buscarPorId(int $idProduto): ?array
    {
        return $this->getPorId($idProduto);
    }

    /**
     * Busca varios produtos preservando a ordem dos IDs informados.
     *
     * @param array<int, int|string> $idsProdutos
     * @return array<int, array>
     */
    public function buscarPorIds(array $idsProdutos): array
    {
        $ids = array_values(array_unique(array_map('intval', $idsProdutos)));

        if ($ids === []) {
            return [];
        }

        $marcadores = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare(
            $this->selectBase() . " AND p.id_produto IN ($marcadores)"
        );
        $stmt->execute($ids);

        $encontrados = [];

        foreach ($this->mapMany($stmt->fetchAll()) as $produto) {
            $encontrados[(int) $produto['id_produto']] = $produto;
        }

        $produtos = [];

        foreach ($ids as $id) {
            if (isset($encontrados[$id])) {
                $produtos[$id] = $encontrados[$id];
            }
        }

        return $produtos;
    }

    public function categoriasComImagem(): array
    {
        return $this->pdo->query(
            "SELECT c.id_categoria,
                    c.nome,
                    c.imagem,
                    (
                        SELECT COUNT(*)
                        FROM categorias_produtos cp
                        INNER JOIN produtos p ON p.id_produto = cp.produto_id
                        WHERE cp.categoria_id = c.id_categoria
                          AND cp.deletado_em IS NULL
                          AND p.ativo = TRUE
                          AND p.deletado_em IS NULL
                    ) AS total_produtos
             FROM categorias c
             WHERE c.deletado_em IS NULL
               AND c.imagem IS NOT NULL
               AND c.imagem <> ''
             ORDER BY c.nome ASC"
        )->fetchAll();
    }

    private function selectBase(): string
    {
        return "
            SELECT
                p.id_produto,
                p.nome,
                p.descricao,
                p.sku,
                p.valor,
                p.estoque,
                p.cor,
                p.estilo,
                p.criado_em,
                o.percentual AS oferta_percentual,
                COALESCE((
                    SELECT i.caminho_imagem
                    FROM imagens i
                    WHERE i.produto_id = p.id_produto
                      AND i.deletado_em IS NULL
                    ORDER BY i.id_imagem ASC
                    LIMIT 1
                ), '/img/novidades.png') AS imagem,
                COALESCE((
                    SELECT c.nome
                    FROM categorias_produtos cp
                    INNER JOIN categorias c ON c.id_categoria = cp.categoria_id
                    WHERE cp.produto_id = p.id_produto
                      AND cp.deletado_em IS NULL
                      AND c.deletado_em IS NULL
                    ORDER BY c.id_categoria ASC
                    LIMIT 1
                ), 'Bia Modas') AS categoria,
                COALESCE((
                    SELECT GROUP_CONCAT(t.nome ORDER BY t.ordem SEPARATOR ', ')
                    FROM produto_tamanhos pt
                    INNER JOIN tamanhos t ON t.id_tamanho = pt.tamanho_id
                    WHERE pt.produto_id = p.id_produto
                      AND pt.deletado_em IS NULL
                      AND t.deletado_em IS NULL
                ), '') AS tamanho,
                NULL AS medida_busto,
                NULL AS medida_cintura,
                NULL AS medida_quadril
            FROM produtos p
            LEFT JOIN ofertas o
                ON o.id_oferta = p.oferta_id
               AND o.ativo = TRUE
               AND o.deletado_em IS NULL
               AND (o.inicio_em IS NULL OR o.inicio_em <= NOW())
               AND (o.fim_em IS NULL OR o.fim_em >= NOW())
        ";
    }

    private function mapMany(array $rows): array
    {
        return array_map(fn (array $row): array => $this->map($row), $rows);
    }

    private function map(array $row): array
    {
        $row['id_produto'] = (int) $row['id_produto'];
        $row['valor'] = (float) $row['valor'];
        $row['estoque'] = (int) $row['estoque'];
        $row['oferta_percentual'] = $row['oferta_percentual'] !== null
            ? (float) $row['oferta_percentual']
            : null;
        $row['preco_original'] = preco_original($row['valor'], $row['oferta_percentual']);

        return $row;
    }
}
