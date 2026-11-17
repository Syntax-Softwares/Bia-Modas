<?php

require_once __DIR__ . '/../core/helpers.php';

class ProdutoRepository
{
    public function __construct(
        private PDO $pdo
    ) {
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

        return $produto === false ? null : $this->map($produto);
    }

    /**
     * Busca vários produtos por id. A ordem devolvida segue a do banco;
     * quem precisa de uma ordem específica reordena em PHP.
     *
     * @param array<int, int> $ids
     * @return array<int, array<string, mixed>>
     */
    public function getPorIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $ids),
            static fn (int $id): bool => $id > 0
        )));

        if ($ids === []) {
            return [];
        }

        $sql = $this->selectBase()
            . ' WHERE p.id_produto IN (' . implode(',', $ids) . ')
                AND p.ativo = TRUE
                AND p.deletado_em IS NULL';

        return $this->mapMany($this->pdo->query($sql)->fetchAll());
    }

    /**
     * "Para Você": produtos das categorias das 3 peças favoritadas mais
     * recentes, excluindo o que já está favoritado. Oferta primeiro, depois
     * proximidade de preço com a média dos favoritos.
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
     * Produtos mais favoritados da loja (fallback da seção "Para Você"
     * quando o visitante não tem favoritos).
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
     * "Você também pode gostar": produtos que compartilham categoria com o
     * produto atual (N:N), oferta primeiro e preço próximo. Se sobrarem
     * menos de 4, completa com produtos na mesma faixa de preço.
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

    private function selectBase(): string
    {
        return "
            SELECT
                p.id_produto,
                p.nome,
                p.descricao,
                p.valor,
                p.estoque,
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
                    SELECT mp.tamanho
                    FROM medidas_produtos mp
                    WHERE mp.id_medida_produto = p.medida_produto_id
                      AND mp.deletado_em IS NULL
                    LIMIT 1
                ), '') AS tamanho
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
