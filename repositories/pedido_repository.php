<?php

class PedidoRepository
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function criar(string $sessaoId, int $idCarrinho, array $dados): array
    {
        $this->pdo->beginTransaction();

        try {
            $itens = $this->itensDoCarrinho($idCarrinho);

            if ($itens === []) {
                throw new DomainException('Seu carrinho está vazio.');
            }

            $subtotal = 0.0;

            foreach ($itens as $item) {
                if ($item['quantidade'] > $item['estoque']) {
                    throw new DomainException('Estoque insuficiente para ' . $item['nome'] . '.');
                }

                $subtotal += $item['valor_unitario'] * $item['quantidade'];
            }

            $subtotal = round($subtotal, 2);
            $frete = 0.0;
            $desconto = 0.0;
            $total = round($subtotal + $frete - $desconto, 2);

            $compra = $this->inserirCompra($sessaoId, $dados, $frete, $desconto, $total);

            foreach ($itens as $item) {
                $this->inserirItem($compra['id_compra'], $item);
                $this->baixarEstoque($item['produto_id'], $item['quantidade']);
            }

            $this->registrarPagamento($compra['id_compra'], $dados['forma_pagamento'], $total);
            $this->limparCarrinho($idCarrinho);

            $this->pdo->commit();

            return [
                'ok' => true,
                'id_compra' => $compra['id_compra'],
                'numero_pedido' => $compra['numero_pedido'],
                'total' => $total,
            ];
        } catch (DomainException $e) {
            $this->pdo->rollBack();

            return ['ok' => false, 'mensagem' => $e->getMessage()];
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function listarPorVisitante(string $sessaoId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT c.id_compra,
                    c.numero_pedido,
                    c.valor_total,
                    c.situacao,
                    c.criado_em,
                    (
                        SELECT COALESCE(SUM(ic.quantidade), 0)
                        FROM itens_compra ic
                        WHERE ic.compra_id = c.id_compra
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
             WHERE c.sessao_id = :sessao
               AND c.deletado_em IS NULL
             ORDER BY c.criado_em DESC, c.id_compra DESC"
        );
        $stmt->execute([':sessao' => $sessaoId]);

        return $stmt->fetchAll();
    }

    public function buscarPorNumero(string $sessaoId, int $numeroPedido): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT c.*,
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
             WHERE c.sessao_id = :sessao
               AND c.numero_pedido = :numero
               AND c.deletado_em IS NULL
             LIMIT 1"
        );
        $stmt->execute([':sessao' => $sessaoId, ':numero' => $numeroPedido]);
        $pedido = $stmt->fetch();

        return $pedido === false ? null : $pedido;
    }

    public function itensDaCompra(int $idCompra): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT ic.produto_id,
                    ic.quantidade,
                    ic.valor_unitario,
                    ic.valor_total,
                    p.nome,
                    p.valor AS valor_atual,
                    (
                        SELECT i.caminho_imagem
                        FROM imagens i
                        WHERE i.produto_id = p.id_produto AND i.deletado_em IS NULL
                        ORDER BY i.id_imagem ASC
                        LIMIT 1
                    ) AS imagem
             FROM itens_compra ic
             INNER JOIN produtos p ON p.id_produto = ic.produto_id
             WHERE ic.compra_id = :compra
             ORDER BY ic.id_item_compra ASC"
        );
        $stmt->execute([':compra' => $idCompra]);

        return $stmt->fetchAll();
    }

    private function itensDoCarrinho(int $idCarrinho): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT ic.produto_id,
                    ic.quantidade,
                    ic.valor_unitario,
                    p.nome,
                    p.estoque
             FROM itens_carrinho ic
             INNER JOIN produtos p ON p.id_produto = ic.produto_id
             WHERE ic.carrinho_id = :carrinho
               AND ic.deletado_em IS NULL
               AND p.deletado_em IS NULL
             FOR UPDATE"
        );
        $stmt->execute([':carrinho' => $idCarrinho]);

        $itens = [];

        foreach ($stmt->fetchAll() as $item) {
            $item['produto_id'] = (int) $item['produto_id'];
            $item['quantidade'] = (int) $item['quantidade'];
            $item['estoque'] = (int) $item['estoque'];
            $item['valor_unitario'] = (float) $item['valor_unitario'];
            $itens[] = $item;
        }

        return $itens;
    }

    private function inserirCompra(
        string $sessaoId,
        array $dados,
        float $frete,
        float $desconto,
        float $total
    ): array {
        for ($tentativa = 1; ; $tentativa++) {
            try {
                $numeroPedido = $this->proximoNumeroPedido();

                $stmt = $this->pdo->prepare(
                    "INSERT INTO compras (
                        usuario_id, sessao_id, endereco_id, cupom_id, numero_pedido,
                        valor_total, situacao, frete, desconto,
                        destinatario, telefone, endereco_cep, endereco_logradouro,
                        endereco_numero, endereco_complemento, endereco_bairro,
                        endereco_cidade, endereco_uf
                     ) VALUES (
                        NULL, :sessao, NULL, NULL, :numero,
                        :total, FALSE, :frete, :desconto,
                        :destinatario, :telefone, :cep, :logradouro,
                        :numero_endereco, :complemento, :bairro,
                        :cidade, :uf
                     )"
                );
                $stmt->execute([
                    ':sessao' => $sessaoId,
                    ':numero' => $numeroPedido,
                    ':total' => $total,
                    ':frete' => $frete,
                    ':desconto' => $desconto,
                    ':destinatario' => $dados['destinatario'],
                    ':telefone' => $dados['telefone'],
                    ':cep' => $dados['endereco_cep'],
                    ':logradouro' => $dados['endereco_logradouro'],
                    ':numero_endereco' => $dados['endereco_numero'],
                    ':complemento' => $dados['endereco_complemento'] !== '' ? $dados['endereco_complemento'] : null,
                    ':bairro' => $dados['endereco_bairro'],
                    ':cidade' => $dados['endereco_cidade'],
                    ':uf' => $dados['endereco_uf'],
                ]);

                return [
                    'id_compra' => (int) $this->pdo->lastInsertId(),
                    'numero_pedido' => $numeroPedido,
                ];
            } catch (PDOException $e) {
                if ($e->getCode() !== '23000' || $tentativa >= 3) {
                    throw $e;
                }
            }
        }
    }

    private function proximoNumeroPedido(): int
    {
        $stmt = $this->pdo->query('SELECT COALESCE(MAX(numero_pedido), 1000) + 1 FROM compras');

        return (int) $stmt->fetchColumn();
    }

    private function inserirItem(int $idCompra, array $item): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO itens_compra (compra_id, produto_id, quantidade, valor_unitario, valor_total)
             VALUES (:compra, :produto, :quantidade, :unitario, :total)"
        );
        $stmt->execute([
            ':compra' => $idCompra,
            ':produto' => $item['produto_id'],
            ':quantidade' => $item['quantidade'],
            ':unitario' => $item['valor_unitario'],
            ':total' => round($item['valor_unitario'] * $item['quantidade'], 2),
        ]);
    }

    private function baixarEstoque(int $idProduto, int $quantidade): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE produtos
             SET estoque = estoque - :quantidade
             WHERE id_produto = :produto AND estoque >= :quantidade"
        );
        $stmt->execute([':quantidade' => $quantidade, ':produto' => $idProduto]);

        if ($stmt->rowCount() === 0) {
            throw new DomainException('Estoque insuficiente para concluir o pedido.');
        }
    }

    private function registrarPagamento(int $idCompra, string $metodo, float $valor): void
    {
        $codigo = strtoupper($metodo) . '-' . strtoupper(bin2hex(random_bytes(4)));

        $stmt = $this->pdo->prepare(
            "INSERT INTO pagamentos (compra_id, metodo, status, valor, codigo_transacao)
             VALUES (:compra, :metodo, 'pendente', :valor, :codigo)"
        );
        $stmt->execute([
            ':compra' => $idCompra,
            ':metodo' => $metodo,
            ':valor' => $valor,
            ':codigo' => $codigo,
        ]);
    }

    private function limparCarrinho(int $idCarrinho): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE itens_carrinho
             SET deletado_em = NOW()
             WHERE carrinho_id = :carrinho AND deletado_em IS NULL"
        );
        $stmt->execute([':carrinho' => $idCarrinho]);
    }
}
