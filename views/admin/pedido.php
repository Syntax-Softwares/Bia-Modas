<?php
require __DIR__ . '/partials/header.php';

$status = status_pagamento($pedido['pagamento_status']);
$concluido = (bool) $pedido['situacao'];
$subtotal = 0.0;

foreach ($itens as $item) {
    $subtotal += (float) $item['valor_total'];
}
?>
<section class="admin-order-grid">
    <div class="admin-panel">
        <div class="admin-panel-header">
            <h2>Itens do pedido</h2>
            <a href="/admin/pedidos" class="admin-link">Voltar para pedidos</a>
        </div>

        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Qtd.</th>
                        <th>Unitário</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($itens as $item): ?>
                        <tr>
                            <td>
                                <div class="admin-product-cell">
                                    <img src="<?= e($item['imagem'] ?: '/img/logoBiaModas.png') ?>" alt="" loading="lazy">
                                    <div>
                                        <strong><?= e($item['nome']) ?></strong>
                                        <small class="admin-muted d-block"><?= e($item['sku']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?= (int) $item['quantidade'] ?></td>
                            <td><?= money($item['valor_unitario']) ?></td>
                            <td><?= money($item['valor_total']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="admin-totals">
            <div><span>Subtotal</span><strong><?= money($subtotal) ?></strong></div>
            <div><span>Frete</span><strong><?= money($pedido['frete']) ?></strong></div>
            <div><span>Desconto</span><strong>- <?= money($pedido['desconto']) ?></strong></div>
            <div class="admin-totals-final"><span>Total</span><strong><?= money($pedido['valor_total']) ?></strong></div>
        </div>
    </div>

    <div class="admin-order-side">
        <div class="admin-panel">
            <div class="admin-panel-header">
                <h2>Resumo</h2>
            </div>

            <dl class="admin-dl">
                <div><dt>Pedido</dt><dd>#<?= (int) $pedido['numero_pedido'] ?></dd></div>
                <div><dt>Data</dt><dd><?= data_hora($pedido['criado_em']) ?></dd></div>
                <div><dt>Cliente</dt><dd><?= e($pedido['usuario_nome'] ?? $pedido['destinatario'] ?? 'Visitante') ?></dd></div>
                <?php if (!empty($pedido['usuario_email'])): ?>
                    <div><dt>E-mail</dt><dd><?= e($pedido['usuario_email']) ?></dd></div>
                <?php endif; ?>
                <div><dt>Pagamento</dt><dd><?= e(rotulo_pagamento($pedido['pagamento_metodo'])) ?></dd></div>
                <div><dt>Transação</dt><dd><?= e($pedido['pagamento_codigo'] ?? '—') ?></dd></div>
                <div><dt>Status atual</dt><dd><span class="admin-badge admin-badge-<?= e($status['classe']) ?>"><?= e($status['rotulo']) ?></span></dd></div>
            </dl>
        </div>

        <div class="admin-panel">
            <div class="admin-panel-header">
                <h2>Entrega</h2>
            </div>
            <p class="admin-address">
                <strong><?= e($pedido['destinatario']) ?></strong><br>
                <?= e($pedido['endereco_logradouro']) ?>, <?= e($pedido['endereco_numero']) ?>
                <?php if (!empty($pedido['endereco_complemento'])): ?>
                    — <?= e($pedido['endereco_complemento']) ?>
                <?php endif; ?><br>
                <?= e($pedido['endereco_bairro']) ?> · <?= e($pedido['endereco_cidade']) ?>/<?= e($pedido['endereco_uf']) ?><br>
                CEP <?= e($pedido['endereco_cep']) ?><br>
                Telefone: <?= e(formatar_telefone($pedido['telefone'])) ?>
            </p>
        </div>

        <div class="admin-panel">
            <div class="admin-panel-header">
                <h2>Atualizar status</h2>
            </div>

            <form method="post" action="/admin/pedidos/atualizar" class="admin-form">
                <?= Sessao::csrfCampo() ?>
                <input type="hidden" name="id_compra" value="<?= (int) $pedido['id_compra'] ?>">

                <label class="admin-checkbox">
                    <input type="checkbox" name="concluido" value="1" <?= $concluido ? 'checked' : '' ?>>
                    <span>Pedido concluído/entregue</span>
                </label>

                <div class="admin-field">
                    <label for="status-pagamento">Status do pagamento</label>
                    <select id="status-pagamento" name="status_pagamento">
                        <option value="pendente" <?= $pedido['pagamento_status'] === 'pendente' ? 'selected' : '' ?>>Aguardando pagamento</option>
                        <option value="pago" <?= $pedido['pagamento_status'] === 'pago' ? 'selected' : '' ?>>Pagamento aprovado</option>
                        <option value="cancelado" <?= $pedido['pagamento_status'] === 'cancelado' ? 'selected' : '' ?>>Pagamento cancelado</option>
                    </select>
                </div>

                <div class="admin-form-actions">
                    <button type="submit" class="admin-btn admin-btn-primary">
                        <i class="icon-check"></i> Salvar status
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
