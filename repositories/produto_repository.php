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
                    ORDER BY cp.principal DESC, c.id_categoria ASC
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
