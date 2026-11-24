<?php
require __DIR__ . '/partials/header.php';

$totalProdutos = (int) ($resumo['produtos'] ?? 0);
$produtosAtivos = (int) ($resumo['produtos_ativos'] ?? 0);
$totalUsuarios = (int) ($resumo['usuarios'] ?? 0);
$totalPedidos = (int) ($resumo['pedidos'] ?? 0);
$pedidosPendentes = (int) ($resumo['pedidos_pendentes'] ?? 0);
$estoqueBaixoTotal = (int) ($resumo['estoque_baixo'] ?? 0);
$receita = (float) ($resumo['receita'] ?? 0);
?>
<section class="admin-cards">
    <article class="admin-stat-card">
        <span class="admin-stat-icon"><i class="icon-shopping-bag"></i></span>
        <div>
            <span class="admin-stat-label">Produtos</span>
            <strong><?= $totalProdutos ?></strong>
            <small><?= $produtosAtivos ?> ativos</small>
        </div>
    </article>

    <article class="admin-stat-card">
        <span class="admin-stat-icon"><i class="icon-users"></i></span>
        <div>
            <span class="admin-stat-label">Usuários</span>
            <strong><?= $totalUsuarios ?></strong>
            <small>cadastrados</small>
        </div>
    </article>

    <article class="admin-stat-card">
        <span class="admin-stat-icon"><i class="icon-receipt"></i></span>
        <div>
            <span class="admin-stat-label">Pedidos</span>
            <strong><?= $totalPedidos ?></strong>
            <small><?= $pedidosPendentes ?> em andamento</small>
        </div>
    </article>

    <article class="admin-stat-card">
        <span class="admin-stat-icon"><i class="icon-banknote"></i></span>
        <div>
            <span class="admin-stat-label">Receita paga</span>
            <strong class="admin-stat-money"><?= money($receita) ?></strong>
            <small>pagamentos aprovados</small>
        </div>
    </article>
</section>

<section class="admin-grid-2">
    <div class="admin-panel">
        <div class="admin-panel-header">
            <h2>Últimos pedidos</h2>
            <a href="/admin/pedidos" class="admin-link">Ver todos <i class="icon-arrow-right"></i></a>
        </div>

        <?php if ($ultimosPedidos === []): ?>
            <p class="admin-empty">Nenhum pedido recebido ainda.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Pedido</th>
                            <th>Cliente</th>
                            <th>Total</th>
                            <th>Pagamento</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimosPedidos as $pedido): ?>
                            <?php $status = status_pagamento($pedido['pagamento_status']); ?>
                            <tr>
                                <td>
                                    <strong>#<?= (int) $pedido['numero_pedido'] ?></strong>
                                    <small class="admin-muted d-block"><?= data_hora($pedido['criado_em']) ?></small>
                                </td>
                                <td><?= e($pedido['usuario_nome'] ?? $pedido['destinatario'] ?? 'Visitante') ?></td>
                                <td><?= money($pedido['valor_total']) ?></td>
                                <td><span class="admin-badge admin-badge-<?= e($status['classe']) ?>"><?= e($status['rotulo']) ?></span></td>
                                <td class="text-end">
                                    <a class="admin-btn admin-btn-ghost" href="/admin/pedidos/ver?id=<?= (int) $pedido['id_compra'] ?>">
                                        Abrir
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="admin-panel">
        <div class="admin-panel-header">
            <h2>Estoque baixo</h2>
            <a href="/admin/produtos" class="admin-link">Gerenciar <i class="icon-arrow-right"></i></a>
        </div>

        <?php if ($estoqueBaixo === []): ?>
            <p class="admin-empty">Nenhum produto com estoque baixo.</p>
        <?php else: ?>
            <ul class="admin-stock-list">
                <?php foreach ($estoqueBaixo as $produto): ?>
                    <li>
                        <div>
                            <strong><?= e($produto['nome']) ?></strong>
                            <small class="admin-muted d-block"><?= e($produto['sku']) ?></small>
                        </div>
                        <span class="admin-badge admin-badge-pendente"><?= (int) $produto['estoque'] ?> un.</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>

<section class="admin-panel">
    <div class="admin-panel-header">
        <h2>Ações rápidas</h2>
    </div>
    <div class="admin-quick-actions">
        <a class="admin-btn admin-btn-primary" href="/admin/produtos/novo">
            <i class="icon-plus"></i> Novo produto
        </a>
        <a class="admin-btn admin-btn-ghost" href="/admin/categorias">
            <i class="icon-tag"></i> Nova categoria
        </a>
        <a class="admin-btn admin-btn-ghost" href="/admin/tamanhos">
            <i class="icon-ruler"></i> Novo tamanho
        </a>
        <a class="admin-btn admin-btn-ghost" href="/admin/pedidos">
            <i class="icon-receipt"></i> Ver pedidos
        </a>
        <a class="admin-btn admin-btn-ghost" href="/admin/usuarios">
            <i class="icon-users"></i> Ver usuários
        </a>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
