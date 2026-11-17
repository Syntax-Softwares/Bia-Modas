<?php

/**
 * Consultas do catalogo.
 *
 * Cor e estilo usam as colunas novas de `produtos`, com fallback para o
 * padrao do seed ("Cor: Marrom. Estilo: Elegante.") em descricao. Assim a
 * branch funciona tanto com o seed novo quanto com o seed antigo do MVC04.
 */
final class CatalogoRepository
{
    private const COR_EXPR = "
        COALESCE(
            NULLIF(TRIM(p.cor), ''),
            CASE WHEN p.descricao LIKE '%Cor: %'
                THEN NULLIF(TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(p.descricao, 'Cor: ', -1), '.', 1)), '')
                ELSE NULL
            END
        )
    ";

    private const ESTILO_EXPR = "
        COALESCE(
            NULLIF(TRIM(p.estilo), ''),
            CASE WHEN p.descricao LIKE '%Estilo: %'
                THEN NULLIF(TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(p.descricao, 'Estilo: ', -1), '.', 1)), '')
                ELSE NULL
            END
        )
    ";

    private const ORDEM_TAMANHOS = [
        'PP', 'P', 'M', 'G', 'GG', 'XG', 'XXG',
        '34', '36', '38', '40', '42', '44', '46', '48', '50', '52', '54', '56',
    ];

    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @param array<string, mixed> $filtros
     * @return array{itens: array, total: int, pagina: int, paginas: int, por_pagina: int}
     */
    public function listar(array $filtros, int $pagina = 1, int $porPagina = 12): array
    {
        $porPagina = max(1, min(48, $porPagina));

        $where = ['p.ativo = TRUE', 'p.deletado_em IS NULL'];
        $paramsWhere = [];
        $score = '0 AS relevancia';
        $paramsScore = [];

        $busca = trim((string) ($filtros['busca'] ?? ''));

        if ($busca !== '') {
            $like = '%' . addcslashes($busca, '\\%_') . '%';
            $prefixo = addcslashes($busca, '\\%_') . '%';

            $score = "
                CASE
                    WHEN p.nome = :score_exato THEN 100
                    WHEN p.nome LIKE :score_prefixo THEN 80
                    WHEN p.nome LIKE :score_contem THEN 60
                    WHEN EXISTS (
                        SELECT 1
                        FROM categorias_produtos cp_score
                        INNER JOIN categorias c_score
                            ON c_score.id_categoria = cp_score.categoria_id
                        WHERE cp_score.produto_id = p.id_produto
                          AND cp_score.deletado_em IS NULL
                          AND c_score.deletado_em IS NULL
                          AND c_score.nome LIKE :score_categoria
                    ) THEN 40
                    WHEN " . self::COR_EXPR . " LIKE :score_cor
                      OR " . self::ESTILO_EXPR . " LIKE :score_estilo THEN 30
                    WHEN p.descricao LIKE :score_descricao THEN 20
                    ELSE 0
                END AS relevancia
            ";

            $paramsScore = [
                ':score_exato' => $busca,
                ':score_prefixo' => $prefixo,
                ':score_contem' => $like,
                ':score_categoria' => $like,
                ':score_cor' => $like,
                ':score_estilo' => $like,
                ':score_descricao' => $like,
            ];

            $where[] = "(
                p.nome LIKE :busca_nome
                OR p.descricao LIKE :busca_descricao
                OR " . self::COR_EXPR . " LIKE :busca_cor
                OR " . self::ESTILO_EXPR . " LIKE :busca_estilo
                OR EXISTS (
                    SELECT 1
                    FROM categorias_produtos cp_busca
                    INNER JOIN categorias c_busca
                        ON c_busca.id_categoria = cp_busca.categoria_id
                    WHERE cp_busca.produto_id = p.id_produto
                      AND cp_busca.deletado_em IS NULL
                      AND c_busca.deletado_em IS NULL
                      AND c_busca.nome LIKE :busca_categoria
                )
            )";

            $paramsWhere[':busca_nome'] = $like;
            $paramsWhere[':busca_descricao'] = $like;
            $paramsWhere[':busca_cor'] = $like;
            $paramsWhere[':busca_estilo'] = $like;
            $paramsWhere[':busca_categoria'] = $like;
        }

        $categoria = trim((string) ($filtros['categoria'] ?? ''));

        if ($categoria !== '') {
            $where[] = "
                EXISTS (
                    SELECT 1
                    FROM categorias_produtos cp_cat
                    INNER JOIN categorias c_cat
                        ON c_cat.id_categoria = cp_cat.categoria_id
                    WHERE cp_cat.produto_id = p.id_produto
                      AND cp_cat.deletado_em IS NULL
                      AND c_cat.deletado_em IS NULL
                      AND c_cat.nome = :categoria
                )
            ";
            $paramsWhere[':categoria'] = $categoria;
        }

        $tamanhos = $this->limparLista($filtros['tamanhos'] ?? []);

        if ($tamanhos !== []) {
            $marcadores = $this->placeholders('tamanho', $tamanhos, $paramsWhere);
            $where[] = "
                EXISTS (
                    SELECT 1
                    FROM medidas_produtos mp_filtro
                    WHERE mp_filtro.id_medida_produto = p.medida_produto_id
                      AND mp_filtro.deletado_em IS NULL
                      AND mp_filtro.tamanho IN ({$marcadores})
                )
            ";
        }

        $cores = $this->limparLista($filtros['cores'] ?? []);

        if ($cores !== []) {
            $marcadores = $this->placeholders('cor', $cores, $paramsWhere);
            $where[] = self::COR_EXPR . " IN ({$marcadores})";
        }

        $estilos = $this->limparLista($filtros['estilos'] ?? []);

        if ($estilos !== []) {
            $marcadores = $this->placeholders('estilo', $estilos, $paramsWhere);
            $where[] = self::ESTILO_EXPR . " IN ({$marcadores})";
        }

        if (isset($filtros['preco_min']) && $filtros['preco_min'] !== null) {
            $where[] = 'p.valor >= :preco_min';
            $paramsWhere[':preco_min'] = (float) $filtros['preco_min'];
        }

        if (isset($filtros['preco_max']) && $filtros['preco_max'] !== null) {
            $where[] = 'p.valor <= :preco_max';
            $paramsWhere[':preco_max'] = (float) $filtros['preco_max'];
        }

        if (!empty($filtros['promocoes'])) {
            $where[] = 'o.id_oferta IS NOT NULL';
        }

        $whereSql = ' WHERE ' . implode(' AND ', $where);

        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM produtos p ' . $this->joinOfertas() . $whereSql
        );
        $this->bind($stmt, $paramsWhere);
        $stmt->execute();
        $total = (int) $stmt->fetchColumn();

        $paginas = max(1, (int) ceil($total / $porPagina));
        $pagina = max(1, min($pagina, $paginas));
        $offset = ($pagina - 1) * $porPagina;

        $ordem = match ((string) ($filtros['ordenacao'] ?? 'relevancia')) {
            'menor-preco' => 'p.valor ASC, p.nome ASC, p.id_produto ASC',
            'maior-preco' => 'p.valor DESC, p.nome ASC, p.id_produto DESC',
            'nome' => 'p.nome ASC, p.id_produto ASC',
            default => 'relevancia DESC, p.criado_em DESC, p.id_produto DESC',
        };

        $sql = $this->selectBase($score)
            . $whereSql
            . ' ORDER BY ' . $ordem
            . ' LIMIT :limite OFFSET :offset';

        $stmt = $this->pdo->prepare($sql);
        $this->bind($stmt, array_merge($paramsScore, $paramsWhere));
        $stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'itens' => array_map([$this, 'map'], $stmt->fetchAll()),
            'total' => $total,
            'pagina' => $pagina,
            'paginas' => $paginas,
            'por_pagina' => $porPagina,
        ];
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            $this->selectBase('0 AS relevancia')
            . '
            WHERE p.id_produto = :id
              AND p.ativo = TRUE
              AND p.deletado_em IS NULL
            LIMIT 1
            '
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $produto = $stmt->fetch();

        return $produto === false ? null : $this->map($produto);
    }

    public function imagensDoProduto(int $id): array
    {
        $stmt = $this->pdo->prepare(
            '
            SELECT id_imagem, caminho_imagem
            FROM imagens
            WHERE produto_id = :id
              AND deletado_em IS NULL
            ORDER BY id_imagem ASC
            '
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function contarAtivos(): int
    {
        return (int) $this->pdo->query(
            'SELECT COUNT(*) FROM produtos WHERE ativo = TRUE AND deletado_em IS NULL'
        )->fetchColumn();
    }

    public function categoriasComContagem(): array
    {
        $stmt = $this->pdo->query(
            '
            SELECT c.id_categoria, c.nome, COUNT(DISTINCT cp.produto_id) AS total
            FROM categorias c
            INNER JOIN categorias_produtos cp
                ON cp.categoria_id = c.id_categoria
               AND cp.deletado_em IS NULL
            INNER JOIN produtos p
                ON p.id_produto = cp.produto_id
               AND p.ativo = TRUE
               AND p.deletado_em IS NULL
            WHERE c.deletado_em IS NULL
            GROUP BY c.id_categoria, c.nome
            HAVING total > 0
            ORDER BY c.nome ASC
            '
        );

        return array_map(
            static fn (array $linha): array => [
                'id_categoria' => (int) $linha['id_categoria'],
                'nome' => (string) $linha['nome'],
                'total' => (int) $linha['total'],
            ],
            $stmt->fetchAll()
        );
    }

    public function tamanhosDisponiveis(): array
    {
        $stmt = $this->pdo->query(
            '
            SELECT DISTINCT mp.tamanho
            FROM medidas_produtos mp
            INNER JOIN produtos p
                ON p.medida_produto_id = mp.id_medida_produto
               AND p.ativo = TRUE
               AND p.deletado_em IS NULL
            WHERE mp.deletado_em IS NULL
              AND mp.tamanho <> \'\'
            '
        );

        $tamanhos = array_map(static fn (array $linha): string => (string) $linha['tamanho'], $stmt->fetchAll());

        usort($tamanhos, static function (string $a, string $b): int {
            $posA = array_search($a, self::ORDEM_TAMANHOS, true);
            $posB = array_search($b, self::ORDEM_TAMANHOS, true);
            $posA = $posA === false ? 999 : $posA;
            $posB = $posB === false ? 999 : $posB;

            return $posA <=> $posB ?: strcmp($a, $b);
        });

        return $tamanhos;
    }

    public function coresDisponiveis(): array
    {
        $stmt = $this->pdo->query(
            '
            SELECT ' . self::COR_EXPR . ' AS cor, COUNT(*) AS total
            FROM produtos p
            WHERE p.ativo = TRUE
              AND p.deletado_em IS NULL
            GROUP BY cor
            HAVING cor IS NOT NULL AND cor <> \'\'
            ORDER BY cor ASC
            '
        );

        return array_map(
            static fn (array $linha): array => [
                'cor' => (string) $linha['cor'],
                'total' => (int) $linha['total'],
            ],
            $stmt->fetchAll()
        );
    }

    public function estilosDisponiveis(): array
    {
        $stmt = $this->pdo->query(
            '
            SELECT ' . self::ESTILO_EXPR . ' AS estilo, COUNT(*) AS total
            FROM produtos p
            WHERE p.ativo = TRUE
              AND p.deletado_em IS NULL
            GROUP BY estilo
            HAVING estilo IS NOT NULL AND estilo <> \'\'
            ORDER BY estilo ASC
            '
        );

        return array_map(
            static fn (array $linha): array => [
                'estilo' => (string) $linha['estilo'],
                'total' => (int) $linha['total'],
            ],
            $stmt->fetchAll()
        );
    }

    /**
     * Nomes de produtos e categorias para o <datalist> do header.
     */
    public function sugestoes(int $limite = 30): array
    {
        $stmt = $this->pdo->prepare(
            '
            SELECT nome
            FROM produtos
            WHERE ativo = TRUE
              AND deletado_em IS NULL
            ORDER BY criado_em DESC, nome ASC
            LIMIT :limite
            '
        );
        $stmt->bindValue(':limite', max(1, min(50, $limite)), PDO::PARAM_INT);
        $stmt->execute();

        $produtos = array_map(static fn (array $linha): string => (string) $linha['nome'], $stmt->fetchAll());

        $categorias = $this->pdo->query(
            '
            SELECT nome
            FROM categorias
            WHERE deletado_em IS NULL
            ORDER BY nome ASC
            '
        )->fetchAll(PDO::FETCH_COLUMN);

        $sugestoes = array_values(array_unique(array_merge($produtos, array_map('strval', $categorias))));
        sort($sugestoes, SORT_NATURAL | SORT_FLAG_CASE);

        return array_slice($sugestoes, 0, 40);
    }

    private function selectBase(string $score): string
    {
        return "
            SELECT
                p.id_produto,
                p.nome,
                p.descricao,
                p.sku,
                p.valor,
                p.estoque,
                p.criado_em,
                " . self::COR_EXPR . " AS cor,
                " . self::ESTILO_EXPR . " AS estilo,
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
                    SELECT c2.id_categoria
                    FROM categorias_produtos cp2
                    INNER JOIN categorias c2 ON c2.id_categoria = cp2.categoria_id
                    WHERE cp2.produto_id = p.id_produto
                      AND cp2.deletado_em IS NULL
                      AND c2.deletado_em IS NULL
                    ORDER BY c2.id_categoria ASC
                    LIMIT 1
                ), 0) AS id_categoria,
                COALESCE(mp.tamanho, '') AS tamanho,
                mp.busto AS medida_busto,
                mp.cintura AS medida_cintura,
                mp.quadril AS medida_quadril,
                {$score}
            FROM produtos p
            LEFT JOIN medidas_produtos mp
                ON mp.id_medida_produto = p.medida_produto_id
               AND mp.deletado_em IS NULL
            " . $this->joinOfertas();
    }

    private function joinOfertas(): string
    {
        return "
            LEFT JOIN ofertas o
                ON o.id_oferta = p.oferta_id
               AND o.ativo = TRUE
               AND o.deletado_em IS NULL
               AND (o.inicio_em IS NULL OR o.inicio_em <= NOW())
               AND (o.fim_em IS NULL OR o.fim_em >= NOW())
        ";
    }

    private function map(array $linha): array
    {
        $linha['id_produto'] = (int) $linha['id_produto'];
        $linha['valor'] = (float) $linha['valor'];
        $linha['estoque'] = (int) $linha['estoque'];
        $linha['id_categoria'] = (int) $linha['id_categoria'];
        $linha['oferta_percentual'] = $linha['oferta_percentual'] !== null
            ? (float) $linha['oferta_percentual']
            : null;
        $linha['preco_original'] = Catalogo::precoOriginal($linha['valor'], $linha['oferta_percentual']);
        $linha['cor'] = $linha['cor'] !== null ? (string) $linha['cor'] : null;
        $linha['estilo'] = $linha['estilo'] !== null ? (string) $linha['estilo'] : null;
        $linha['tamanho'] = (string) $linha['tamanho'];
        $linha['descricao'] = $linha['descricao'] !== null ? (string) $linha['descricao'] : null;

        return $linha;
    }

    /**
     * @param array<int, mixed> $valores
     */
    private function limparLista(mixed $valores): array
    {
        if (!is_array($valores)) {
            $valores = [$valores];
        }

        $limpos = [];

        foreach ($valores as $valor) {
            $valor = trim((string) $valor);

            if ($valor !== '') {
                $limpos[] = $valor;
            }
        }

        return array_values(array_unique($limpos));
    }

    /**
     * @param array<int, string> $valores
     * @param array<string, mixed> $params
     */
    private function placeholders(string $prefixo, array $valores, array &$params): string
    {
        $marcadores = [];

        foreach ($valores as $indice => $valor) {
            $nome = ':' . $prefixo . '_' . $indice;
            $marcadores[] = $nome;
            $params[$nome] = $valor;
        }

        return implode(', ', $marcadores);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function bind(PDOStatement $stmt, array $params): void
    {
        foreach ($params as $nome => $valor) {
            $tipo = match (true) {
                is_int($valor) => PDO::PARAM_INT,
                is_bool($valor) => PDO::PARAM_BOOL,
                $valor === null => PDO::PARAM_NULL,
                default => PDO::PARAM_STR,
            };

            $stmt->bindValue($nome, $valor, $tipo);
        }
    }
}
