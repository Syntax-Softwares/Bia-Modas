<?php

/**
 * Consultas e operacoes do painel administrativo.
 */
final class AdminRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function resumo(): array
    {
        return $this->pdo->query(
            "
            SELECT
                (SELECT COUNT(*) FROM produtos WHERE deletado_em IS NULL) AS produtos,
                (SELECT COUNT(*) FROM produtos WHERE deletado_em IS NULL AND ativo = TRUE) AS produtos_ativos,
                (SELECT COUNT(*) FROM usuarios WHERE deletado_em IS NULL) AS usuarios,
                (SELECT COUNT(*) FROM compras WHERE deletado_em IS NULL) AS pedidos,
                (SELECT COUNT(*) FROM compras WHERE deletado_em IS NULL AND situacao = FALSE) AS pedidos_pendentes,
                (SELECT COUNT(*) FROM produtos WHERE deletado_em IS NULL AND ativo = TRUE AND estoque <= 5) AS estoque_baixo,
                (SELECT COALESCE(SUM(valor), 0) FROM pagamentos WHERE deletado_em IS NULL AND status = 'pago') AS receita
            "
        )->fetch() ?: [];
    }

    public function ultimosPedidos(int $limite = 6): array
    {
        $stmt = $this->pdo->prepare(
            "
            SELECT c.id_compra,
                   c.numero_pedido,
                   c.valor_total,
                   c.situacao,
                   c.criado_em,
                   c.destinatario,
                   u.nome AS usuario_nome,
                   (
                       SELECT pg.status
                       FROM pagamentos pg
                       WHERE pg.compra_id = c.id_compra
                       ORDER BY pg.id_pagamento DESC
                       LIMIT 1
                   ) AS pagamento_status
            FROM compras c
            LEFT JOIN usuarios u ON u.id_usuario = c.usuario_id
            WHERE c.deletado_em IS NULL
            ORDER BY c.criado_em DESC, c.id_compra DESC
            LIMIT :limite
            "
        );
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function produtosEstoqueBaixo(int $limite = 6): array
    {
        $stmt = $this->pdo->prepare(
            "
            SELECT id_produto, nome, sku, estoque, valor
            FROM produtos
            WHERE deletado_em IS NULL
              AND ativo = TRUE
              AND estoque <= 5
            ORDER BY estoque ASC, nome ASC
            LIMIT :limite
            "
        );
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function listarProdutos(string $busca = ''): array
    {
        $sql = $this->selectProdutoBase()
            . "
            WHERE p.deletado_em IS NULL
            ";

        $params = [];

        if (trim($busca) !== '') {
            $sql .= ' AND (p.nome LIKE :busca_nome OR p.sku LIKE :busca_sku) ';
            $params[':busca_nome'] = '%' . trim($busca) . '%';
            $params[':busca_sku'] = '%' . trim($busca) . '%';
        }

        $sql .= ' ORDER BY p.id_produto DESC LIMIT 200';

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $nome => $valor) {
            $stmt->bindValue($nome, $valor, PDO::PARAM_STR);
        }

        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function buscarProduto(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            $this->selectProdutoBase()
            . ' WHERE p.id_produto = :id LIMIT 1'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $produto = $stmt->fetch();

        return $produto === false ? null : $produto;
    }

    public function listarCategorias(): array
    {
        return $this->pdo->query(
            "
            SELECT c.id_categoria,
                   c.nome,
                   c.descricao,
                   c.criado_em,
                   (
                       SELECT COUNT(*)
                       FROM categorias_produtos cp
                       INNER JOIN produtos p ON p.id_produto = cp.produto_id
                       WHERE cp.categoria_id = c.id_categoria
                         AND cp.deletado_em IS NULL
                         AND p.deletado_em IS NULL
                   ) AS total_produtos
            FROM categorias c
            WHERE c.deletado_em IS NULL
            ORDER BY c.nome ASC
            "
        )->fetchAll();
    }

    public function categoriasAtivas(): array
    {
        return $this->pdo->query(
            "SELECT id_categoria, nome FROM categorias WHERE deletado_em IS NULL ORDER BY nome ASC"
        )->fetchAll();
    }

    public function buscarCategoria(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id_categoria, nome, descricao FROM categorias WHERE id_categoria = :id AND deletado_em IS NULL LIMIT 1'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $categoria = $stmt->fetch();

        return $categoria === false ? null : $categoria;
    }

    public function criarCategoria(string $nome, ?string $descricao): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO categorias (nome, descricao) VALUES (:nome, :descricao)'
        );
        $stmt->execute([':nome' => $nome, ':descricao' => $descricao]);

        return (int) $this->pdo->lastInsertId();
    }

    public function atualizarCategoria(int $id, string $nome, ?string $descricao): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE categorias SET nome = :nome, descricao = :descricao WHERE id_categoria = :id AND deletado_em IS NULL'
        );
        $stmt->execute([':nome' => $nome, ':descricao' => $descricao, ':id' => $id]);
    }

    public function excluirCategoria(int $id): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE categorias SET deletado_em = NOW() WHERE id_categoria = :id AND deletado_em IS NULL'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function criarProduto(array $dados): int
    {
        $this->pdo->beginTransaction();

        try {
            $medidaId = $this->obterOuCriarMedida((string) $dados['tamanho']);
            $ofertaId = $this->obterOuCriarOferta($dados['oferta_percentual']);

            $stmt = $this->pdo->prepare(
                "
                INSERT INTO produtos
                    (medida_produto_id, oferta_id, nome, sku, descricao, cor, estilo,
                     estoque, valor, ativo)
                VALUES
                    (:medida_id, :oferta_id, :nome, :sku, :descricao, :cor, :estilo,
                     :estoque, :valor, :ativo)
                "
            );
            $stmt->execute([
                ':medida_id' => $medidaId,
                ':oferta_id' => $ofertaId,
                ':nome' => $dados['nome'],
                ':sku' => $dados['sku'],
                ':descricao' => $dados['descricao'] !== '' ? $dados['descricao'] : null,
                ':cor' => $dados['cor'] !== '' ? $dados['cor'] : null,
                ':estilo' => $dados['estilo'] !== '' ? $dados['estilo'] : null,
                ':estoque' => $dados['estoque'],
                ':valor' => $dados['valor'],
                ':ativo' => $dados['ativo'] ? 1 : 0,
            ]);

            $produtoId = (int) $this->pdo->lastInsertId();

            $this->vincularCategoria($produtoId, (int) $dados['categoria_id']);
            $this->salvarImagem($produtoId, (string) $dados['imagem']);

            $this->pdo->commit();

            return $produtoId;
        } catch (Throwable $excecao) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $excecao;
        }
    }

    public function atualizarProduto(int $id, array $dados): void
    {
        $this->pdo->beginTransaction();

        try {
            $medidaId = $this->obterOuCriarMedida((string) $dados['tamanho']);
            $ofertaId = $this->obterOuCriarOferta($dados['oferta_percentual']);

            $stmt = $this->pdo->prepare(
                "
                UPDATE produtos
                SET medida_produto_id = :medida_id,
                    oferta_id = :oferta_id,
                    nome = :nome,
                    sku = :sku,
                    descricao = :descricao,
                    cor = :cor,
                    estilo = :estilo,
                    estoque = :estoque,
                    valor = :valor,
                    ativo = :ativo
                WHERE id_produto = :id
                  AND deletado_em IS NULL
                "
            );
            $stmt->execute([
                ':medida_id' => $medidaId,
                ':oferta_id' => $ofertaId,
                ':nome' => $dados['nome'],
                ':sku' => $dados['sku'],
                ':descricao' => $dados['descricao'] !== '' ? $dados['descricao'] : null,
                ':cor' => $dados['cor'] !== '' ? $dados['cor'] : null,
                ':estilo' => $dados['estilo'] !== '' ? $dados['estilo'] : null,
                ':estoque' => $dados['estoque'],
                ':valor' => $dados['valor'],
                ':ativo' => $dados['ativo'] ? 1 : 0,
                ':id' => $id,
            ]);

            $this->vincularCategoria($id, (int) $dados['categoria_id']);
            $this->salvarImagem($id, (string) $dados['imagem']);

            $this->pdo->commit();
        } catch (Throwable $excecao) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $excecao;
        }
    }

    public function excluirProduto(int $id): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE produtos SET ativo = FALSE, deletado_em = NOW() WHERE id_produto = :id AND deletado_em IS NULL'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function listarPedidos(): array
    {
        return $this->pdo->query(
            "
            SELECT c.id_compra,
                   c.numero_pedido,
                   c.valor_total,
                   c.situacao,
                   c.criado_em,
                   c.destinatario,
                   u.nome AS usuario_nome,
                   u.email AS usuario_email,
                   (
                       SELECT COUNT(*) FROM itens_compra ic WHERE ic.compra_id = c.id_compra
                   ) AS total_itens,
                   (
                       SELECT pg.status
                       FROM pagamentos pg
                       WHERE pg.compra_id = c.id_compra
                       ORDER BY pg.id_pagamento DESC
                       LIMIT 1
                   ) AS pagamento_status,
                   (
                       SELECT pg.metodo
                       FROM pagamentos pg
                       WHERE pg.compra_id = c.id_compra
                       ORDER BY pg.id_pagamento DESC
                       LIMIT 1
                   ) AS pagamento_metodo
            FROM compras c
            LEFT JOIN usuarios u ON u.id_usuario = c.usuario_id
            WHERE c.deletado_em IS NULL
            ORDER BY c.criado_em DESC, c.id_compra DESC
            "
        )->fetchAll();
    }

    public function buscarPedido(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "
            SELECT c.*,
                   u.nome AS usuario_nome,
                   u.email AS usuario_email,
                   (
                       SELECT pg.status
                       FROM pagamentos pg
                       WHERE pg.compra_id = c.id_compra
                       ORDER BY pg.id_pagamento DESC
                       LIMIT 1
                   ) AS pagamento_status,
                   (
                       SELECT pg.metodo
                       FROM pagamentos pg
                       WHERE pg.compra_id = c.id_compra
                       ORDER BY pg.id_pagamento DESC
                       LIMIT 1
                   ) AS pagamento_metodo,
                   (
                       SELECT pg.codigo_transacao
                       FROM pagamentos pg
                       WHERE pg.compra_id = c.id_compra
                       ORDER BY pg.id_pagamento DESC
                       LIMIT 1
                   ) AS pagamento_codigo
            FROM compras c
            LEFT JOIN usuarios u ON u.id_usuario = c.usuario_id
            WHERE c.id_compra = :id
              AND c.deletado_em IS NULL
            LIMIT 1
            "
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $pedido = $stmt->fetch();

        return $pedido === false ? null : $pedido;
    }

    public function itensPedido(int $idCompra): array
    {
        $stmt = $this->pdo->prepare(
            "
            SELECT ic.*,
                   p.nome,
                   p.sku,
                   (
                       SELECT i.caminho_imagem
                       FROM imagens i
                       WHERE i.produto_id = p.id_produto
                         AND i.deletado_em IS NULL
                       ORDER BY i.id_imagem ASC
                       LIMIT 1
                   ) AS imagem
            FROM itens_compra ic
            INNER JOIN produtos p ON p.id_produto = ic.produto_id
            WHERE ic.compra_id = :compra
            ORDER BY ic.id_item_compra ASC
            "
        );
        $stmt->bindValue(':compra', $idCompra, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function atualizarStatusPedido(int $id, bool $concluido, string $statusPagamento): void
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                'UPDATE compras SET situacao = :situacao WHERE id_compra = :id AND deletado_em IS NULL'
            );
            $stmt->execute([':situacao' => $concluido ? 1 : 0, ':id' => $id]);

            if (in_array($statusPagamento, ['pendente', 'pago', 'cancelado'], true)) {
                $stmt = $this->pdo->prepare(
                    'UPDATE pagamentos SET status = :status WHERE compra_id = :compra AND deletado_em IS NULL'
                );
                $stmt->execute([':status' => $statusPagamento, ':compra' => $id]);
            }

            $this->pdo->commit();
        } catch (Throwable $excecao) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $excecao;
        }
    }

    public function listarUsuarios(): array
    {
        return $this->pdo->query(
            "
            SELECT u.id_usuario,
                   u.nome,
                   u.email,
                   u.telefone,
                   u.is_admin,
                   u.criado_em,
                   (
                       SELECT COUNT(*) FROM compras c
                       WHERE c.usuario_id = u.id_usuario AND c.deletado_em IS NULL
                   ) AS total_pedidos
            FROM usuarios u
            WHERE u.deletado_em IS NULL
            ORDER BY u.id_usuario DESC
            "
        )->fetchAll();
    }

    public function buscarUsuario(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "
            SELECT u.id_usuario, u.nome, u.email, u.telefone, u.cpf, u.data_nascimento,
                   u.is_admin, u.criado_em
            FROM usuarios u
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

    public function enderecosUsuario(int $idUsuario): array
    {
        $stmt = $this->pdo->prepare(
            "
            SELECT id_endereco, nome_endereco, logradouro, numero, complemento,
                   bairro, cidade, uf, cep, principal
            FROM enderecos
            WHERE usuario_id = :usuario
              AND deletado_em IS NULL
            ORDER BY principal DESC, id_endereco DESC
            "
        );
        $stmt->bindValue(':usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function pedidosUsuario(int $idUsuario): array
    {
        $stmt = $this->pdo->prepare(
            "
            SELECT id_compra, numero_pedido, valor_total, situacao, criado_em
            FROM compras
            WHERE usuario_id = :usuario
              AND deletado_em IS NULL
            ORDER BY criado_em DESC, id_compra DESC
            "
        );
        $stmt->bindValue(':usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function definirAdmin(int $id, bool $admin): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE usuarios SET is_admin = :admin WHERE id_usuario = :id AND deletado_em IS NULL'
        );
        $stmt->execute([':admin' => $admin ? 1 : 0, ':id' => $id]);
    }

    public function excluirUsuario(int $id): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE usuarios SET is_admin = FALSE, deletado_em = NOW() WHERE id_usuario = :id AND deletado_em IS NULL'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    private function selectProdutoBase(): string
    {
        return "
            SELECT p.*,
                   mp.tamanho,
                   o.percentual AS oferta_percentual,
                   (
                       SELECT i.caminho_imagem
                       FROM imagens i
                       WHERE i.produto_id = p.id_produto AND i.deletado_em IS NULL
                       ORDER BY i.id_imagem ASC
                       LIMIT 1
                   ) AS imagem,
                   (
                       SELECT GROUP_CONCAT(c.nome ORDER BY c.nome SEPARATOR ', ')
                       FROM categorias_produtos cp
                       INNER JOIN categorias c ON c.id_categoria = cp.categoria_id
                       WHERE cp.produto_id = p.id_produto
                         AND cp.deletado_em IS NULL
                         AND c.deletado_em IS NULL
                   ) AS categorias,
                   (
                       SELECT cp.categoria_id
                       FROM categorias_produtos cp
                       INNER JOIN categorias c ON c.id_categoria = cp.categoria_id
                       WHERE cp.produto_id = p.id_produto
                         AND cp.deletado_em IS NULL
                         AND c.deletado_em IS NULL
                       ORDER BY cp.id_categoria_produto ASC
                       LIMIT 1
                   ) AS categoria_id
            FROM produtos p
            LEFT JOIN medidas_produtos mp ON mp.id_medida_produto = p.medida_produto_id
            LEFT JOIN ofertas o ON o.id_oferta = p.oferta_id
        ";
    }

    private function obterOuCriarMedida(string $tamanho): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT id_medida_produto FROM medidas_produtos WHERE tamanho = :tamanho AND deletado_em IS NULL ORDER BY id_medida_produto ASC LIMIT 1'
        );
        $stmt->execute([':tamanho' => $tamanho]);
        $id = $stmt->fetchColumn();

        if ($id !== false) {
            return (int) $id;
        }

        $stmt = $this->pdo->prepare('INSERT INTO medidas_produtos (tamanho) VALUES (:tamanho)');
        $stmt->execute([':tamanho' => $tamanho]);

        return (int) $this->pdo->lastInsertId();
    }

    private function obterOuCriarOferta(?float $percentual): ?int
    {
        if ($percentual === null || $percentual <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            "
            SELECT id_oferta
            FROM ofertas
            WHERE percentual = :percentual
              AND ativo = TRUE
              AND deletado_em IS NULL
              AND (fim_em IS NULL OR fim_em >= NOW())
            ORDER BY id_oferta ASC
            LIMIT 1
            "
        );
        $stmt->execute([':percentual' => $percentual]);
        $id = $stmt->fetchColumn();

        if ($id !== false) {
            return (int) $id;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO ofertas (percentual, inicio_em, ativo) VALUES (:percentual, NOW(), TRUE)'
        );
        $stmt->execute([':percentual' => $percentual]);

        return (int) $this->pdo->lastInsertId();
    }

    private function vincularCategoria(int $produtoId, int $categoriaId): void
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM categorias_produtos WHERE produto_id = :produto'
        );
        $stmt->bindValue(':produto', $produtoId, PDO::PARAM_INT);
        $stmt->execute();

        $stmt = $this->pdo->prepare(
            'INSERT INTO categorias_produtos (produto_id, categoria_id) VALUES (:produto, :categoria)'
        );
        $stmt->execute([':produto' => $produtoId, ':categoria' => $categoriaId]);
    }

    private function salvarImagem(int $produtoId, string $caminho): void
    {
        $caminho = trim($caminho);

        if ($caminho === '') {
            return;
        }

        $stmt = $this->pdo->prepare(
            'SELECT id_imagem FROM imagens WHERE produto_id = :produto AND deletado_em IS NULL ORDER BY id_imagem ASC LIMIT 1'
        );
        $stmt->bindValue(':produto', $produtoId, PDO::PARAM_INT);
        $stmt->execute();
        $idImagem = $stmt->fetchColumn();

        if ($idImagem !== false) {
            $stmt = $this->pdo->prepare(
                'UPDATE imagens SET caminho_imagem = :caminho WHERE id_imagem = :id'
            );
            $stmt->execute([':caminho' => $caminho, ':id' => (int) $idImagem]);

            return;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO imagens (produto_id, caminho_imagem) VALUES (:produto, :caminho)'
        );
        $stmt->execute([':produto' => $produtoId, ':caminho' => $caminho]);
    }
}
