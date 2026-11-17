<?php

class ProdutoRepository
{
    
    private const SELECT_PRODUTO = "
        SELECT p.*,
            o.percentual AS oferta_percentual,
            (
                SELECT i.caminho_imagem
                FROM imagens i
                WHERE i.produto_id = p.id_produto AND i.deletado_em IS NULL
                ORDER BY i.id_imagem ASC
                LIMIT 1
            ) AS imagem,
            (
                SELECT c.nome
                FROM categorias_produtos cp
                INNER JOIN categorias c ON c.id_categoria = cp.categoria_id
                WHERE cp.produto_id = p.id_produto
                  AND cp.deletado_em IS NULL
                  AND c.deletado_em IS NULL
                ORDER BY cp.id_categoria_produto ASC
                LIMIT 1
            ) AS categoria
        FROM produtos p
        LEFT JOIN ofertas o ON o.id_oferta = p.oferta_id
            AND o.ativo = 1
            AND o.deletado_em IS NULL
            AND (o.fim_em IS NULL OR o.fim_em >= NOW())
        WHERE p.deletado_em IS NULL
          AND p.ativo = 1
    ";

    public function __construct(
        private PDO $pdo
    ) {
    }

    public function get_novidades(int $limite = 15): array
    {
        $stmt = $this->pdo->prepare(
            self::SELECT_PRODUTO . " ORDER BY p.criado_em DESC, p.id_produto DESC LIMIT :limite"
        );
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function buscarPorId(int $idProduto): ?array
    {
        $stmt = $this->pdo->prepare(self::SELECT_PRODUTO . " AND p.id_produto = :id LIMIT 1");
        $stmt->execute([':id' => $idProduto]);
        $produto = $stmt->fetch();

        return $produto === false ? null : $produto;
    }

    public function buscarPorIds(array $idsProdutos): array
    {
        $ids = array_values(array_unique(array_map('intval', $idsProdutos)));
        if ($ids === []) {
            return [];
        }

        $marcadores = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare(self::SELECT_PRODUTO . " AND p.id_produto IN ($marcadores)");
        $stmt->execute($ids);

        $encontrados = [];
        foreach ($stmt->fetchAll() as $produto) {
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
}
