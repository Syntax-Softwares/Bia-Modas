<?php

class CarrinhoRepository
{
    private const QUANTIDADE_MAXIMA = 99;

    public function __construct(
        private PDO $pdo
    ) {
    }

    public function obterPorVisitante(string $sessaoId): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO carrinhos (sessao_id) VALUES (:sessao)
             ON DUPLICATE KEY UPDATE id_carrinho = LAST_INSERT_ID(id_carrinho)"
        );
        $stmt->execute([':sessao' => $sessaoId]);

        return (int) $this->pdo->lastInsertId();
    }

    public function listarItens(int $idCarrinho): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT ic.id_item_carrinho,
                    ic.produto_id,
                    ic.produto_tamanho_id,
                    ic.quantidade,
                    ic.valor_unitario,
                    (ic.valor_unitario * ic.quantidade) AS subtotal,
                    p.nome,
                    p.valor AS valor_atual,
                    t.nome AS tamanho,
                    pt.estoque,
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
             FROM itens_carrinho ic
             INNER JOIN produtos p ON p.id_produto = ic.produto_id
             INNER JOIN produto_tamanhos pt ON pt.id_produto_tamanho = ic.produto_tamanho_id
             INNER JOIN tamanhos t ON t.id_tamanho = pt.tamanho_id
             WHERE ic.carrinho_id = :carrinho
               AND ic.deletado_em IS NULL
               AND p.deletado_em IS NULL
               AND pt.deletado_em IS NULL
               AND t.deletado_em IS NULL
             ORDER BY ic.criado_em ASC, ic.id_item_carrinho ASC"
        );
        $stmt->execute([':carrinho' => $idCarrinho]);

        $itens = [];
        foreach ($stmt->fetchAll() as $item) {
            $item['id_item_carrinho'] = (int) $item['id_item_carrinho'];
            $item['produto_id'] = (int) $item['produto_id'];
            $item['produto_tamanho_id'] = (int) $item['produto_tamanho_id'];
            $item['quantidade'] = (int) $item['quantidade'];
            $item['estoque'] = (int) $item['estoque'];
            $item['valor_unitario'] = (float) $item['valor_unitario'];
            $item['valor_atual'] = (float) $item['valor_atual'];
            $item['subtotal'] = (float) $item['subtotal'];
            $itens[] = $item;
        }

        return $itens;
    }

    public function resumo(int $idCarrinho): array
    {
        $itens = $this->listarItens($idCarrinho);

        $quantidade = 0;
        $subtotal = 0.0;

        foreach ($itens as $item) {
            $quantidade += $item['quantidade'];
            $subtotal += $item['subtotal'];
        }

        return [
            'itens' => $itens,
            'quantidade' => $quantidade,
            'subtotal' => round($subtotal, 2),
        ];
    }

    public function resumoPorVisitante(string $sessaoId): array
    {
        $stmt = $this->pdo->prepare("SELECT id_carrinho FROM carrinhos WHERE sessao_id = :sessao LIMIT 1");
        $stmt->execute([':sessao' => $sessaoId]);
        $idCarrinho = $stmt->fetchColumn();

        if ($idCarrinho === false) {
            return ['itens' => [], 'quantidade' => 0, 'subtotal' => 0.0];
        }

        return $this->resumo((int) $idCarrinho);
    }

    public function alternar(int $idCarrinho, int $idProdutoTamanho): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT quantidade
             FROM itens_carrinho
             WHERE carrinho_id = :carrinho AND produto_tamanho_id = :produto_tamanho AND deletado_em IS NULL
             LIMIT 1"
        );
        $stmt->execute([':carrinho' => $idCarrinho, ':produto_tamanho' => $idProdutoTamanho]);

        if ($stmt->fetch() !== false) {
            $this->remover($idCarrinho, $idProdutoTamanho);

            return ['ok' => true, 'acao' => 'removido'];
        }

        $resultado = $this->adicionar($idCarrinho, $idProdutoTamanho, 1);

        if (!$resultado['ok']) {
            return $resultado;
        }

        return ['ok' => true, 'acao' => 'adicionado'];
    }

    public function adicionar(int $idCarrinho, int $idProdutoTamanho, int $quantidade): array
    {
        $quantidade = max(1, min(self::QUANTIDADE_MAXIMA, $quantidade));

        $this->pdo->beginTransaction();

        try {
            $variante = $this->bloquearVariante($idProdutoTamanho);

            $stmt = $this->pdo->prepare(
                "SELECT quantidade, deletado_em
                 FROM itens_carrinho
                 WHERE carrinho_id = :carrinho AND produto_tamanho_id = :produto_tamanho
                 FOR UPDATE"
            );
            $stmt->execute([':carrinho' => $idCarrinho, ':produto_tamanho' => $idProdutoTamanho]);
            $atual = $stmt->fetch();

            $quantidadeAtual = ($atual !== false && $atual['deletado_em'] === null)
                ? (int) $atual['quantidade']
                : 0;

            $novaQuantidade = min(self::QUANTIDADE_MAXIMA, $quantidadeAtual + $quantidade);

            if ($novaQuantidade > (int) $variante['estoque']) {
                throw new DomainException('Estoque insuficiente para a quantidade desejada.');
            }

            $stmt = $this->pdo->prepare(
                "INSERT INTO itens_carrinho
                    (carrinho_id, produto_id, produto_tamanho_id, quantidade, valor_unitario)
                 VALUES (:carrinho, :produto, :produto_tamanho, :quantidade, :valor)
                 ON DUPLICATE KEY UPDATE
                    quantidade = VALUES(quantidade),
                    valor_unitario = VALUES(valor_unitario),
                    deletado_em = NULL"
            );
            $stmt->execute([
                ':carrinho' => $idCarrinho,
                ':produto' => $variante['produto_id'],
                ':produto_tamanho' => $idProdutoTamanho,
                ':quantidade' => $novaQuantidade,
                ':valor' => $variante['valor'],
            ]);

            $this->pdo->commit();

            return ['ok' => true, 'quantidade' => $novaQuantidade];
        } catch (DomainException $e) {
            $this->pdo->rollBack();

            return ['ok' => false, 'mensagem' => $e->getMessage()];
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function definirQuantidade(int $idCarrinho, int $idProdutoTamanho, int $quantidade): array
    {
        if ($quantidade <= 0) {
            $this->remover($idCarrinho, $idProdutoTamanho);

            return ['ok' => true, 'quantidade' => 0];
        }

        $quantidade = min(self::QUANTIDADE_MAXIMA, $quantidade);

        $this->pdo->beginTransaction();

        try {
            $variante = $this->bloquearVariante($idProdutoTamanho);

            if ($quantidade > (int) $variante['estoque']) {
                throw new DomainException('Estoque insuficiente para a quantidade desejada.');
            }

            $stmt = $this->pdo->prepare(
                "UPDATE itens_carrinho
                 SET quantidade = :quantidade, deletado_em = NULL
                 WHERE carrinho_id = :carrinho AND produto_tamanho_id = :produto_tamanho"
            );
            $stmt->execute([
                ':quantidade' => $quantidade,
                ':carrinho' => $idCarrinho,
                ':produto_tamanho' => $idProdutoTamanho,
            ]);

            $this->pdo->commit();

            return ['ok' => true, 'quantidade' => $quantidade];
        } catch (DomainException $e) {
            $this->pdo->rollBack();

            return ['ok' => false, 'mensagem' => $e->getMessage()];
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function remover(int $idCarrinho, int $idProdutoTamanho): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE itens_carrinho
             SET deletado_em = NOW()
             WHERE carrinho_id = :carrinho AND produto_tamanho_id = :produto_tamanho AND deletado_em IS NULL"
        );
        $stmt->execute([':carrinho' => $idCarrinho, ':produto_tamanho' => $idProdutoTamanho]);
    }

    private function bloquearVariante(int $idProdutoTamanho): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT pt.id_produto_tamanho,
                    pt.produto_id,
                    pt.estoque,
                    p.valor,
                    p.ativo,
                    p.deletado_em
             FROM produto_tamanhos pt
             INNER JOIN produtos p ON p.id_produto = pt.produto_id
             WHERE pt.id_produto_tamanho = :id
               AND pt.deletado_em IS NULL
             FOR UPDATE"
        );
        $stmt->execute([':id' => $idProdutoTamanho]);
        $variante = $stmt->fetch();

        if ($variante === false || !(int) $variante['ativo'] || $variante['deletado_em'] !== null) {
            throw new DomainException('Produto indisponível.');
        }

        return $variante;
    }
}
