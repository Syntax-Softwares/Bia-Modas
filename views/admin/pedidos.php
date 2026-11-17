<?php
require __DIR__ . '/partials/header.php';
?>
<section class="admin-panel">
    <div class="admin-panel-header">
        <h2>Todos os pedidos</h2>
        <span class="admin-muted"><?= count($pedidos) ?> pedido(s)</span>
    </div>

    <?php if ($pedidos === []): ?>
        <div class="admin-empty-state">
            <i class="bi bi-receipt"></i>
            <h2>Nenhum pedido recebido</h2>
            <p>Os pedidos do checkout aparecerão aqui.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>Cliente</th>
                        <th>Itens</th>
                        <th>Total</th>
                        <th>Situação</th>
                        <th>Pagamento</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidos as $pedido): ?>
                        <?php
                        $status = status_pagamento($pedido['pagamento_status']);
                        $concluido = (bool) $pedido['situacao'];
                        ?>
                        <tr>
                            <td>
                                <strong>#<?= (int) $pedido['numero_pedido'] ?></strong>
                                <small class="admin-muted d-block"><?= data_hora($pedido['criado_em']) ?></small>
                            </td>
                            <td>
                                <?= e($pedido['usuario_nome'] ?? $pedido['destinatario'] ?? 'Visitante') ?>
                                <?php if (!empty($pedido['usuario_email'])): ?>
                                    <small class="admin-muted d-block"><?= e($pedido['usuario_email']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= (int) $pedido['total_itens'] ?></td>
                            <td><?= money($pedido['valor_total']) ?></td>
                            <td>
                                <span class="admin-badge admin-badge-<?= $concluido ? 'pago' : 'pendente' ?>">
                                    <?= $concluido ? 'Concluído' : 'Em andamento' ?>
                                </span>
                            </td>
                            <td>
                                <span class="admin-badge admin-badge-<?= e($status['classe']) ?>"><?= e($status['rotulo']) ?></span>
                                <small class="admin-muted d-block"><?= e(rotulo_pagamento($pedido['pagamento_metodo'])) ?></small>
                            </td>
                            <td class="text-end">
                                <a class="admin-btn admin-btn-ghost" href="/admin/pedidos/ver?id=<?= (int) $pedido['id_compra'] ?>">
                                    <i class="bi bi-eye"></i> Detalhes
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
