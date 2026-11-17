<?php
require __DIR__ . '/partials/header.php';
?>
<section class="admin-panel">
    <div class="admin-toolbar">
        <form class="admin-search" method="get" action="/admin/produtos" role="search">
            <i class="bi bi-search"></i>
            <input type="search" name="busca" value="<?= e($busca ?? '') ?>" placeholder="Buscar por nome ou SKU" aria-label="Buscar produtos">
            <button type="submit">Buscar</button>
        </form>
        <a href="/admin/produtos/novo" class="admin-btn admin-btn-primary">
            <i class="bi bi-plus-lg"></i> Novo produto
        </a>
    </div>

    <?php if ($produtos === []): ?>
        <div class="admin-empty-state">
            <i class="bi bi-bag-x"></i>
            <h2>Nenhum produto encontrado</h2>
            <p>Cadastre o primeiro produto ou ajuste a busca.</p>
            <a href="/admin/produtos/novo" class="admin-btn admin-btn-primary">Cadastrar produto</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="admin-table admin-table-products">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Categoria</th>
                        <th>Tamanho</th>
                        <th>Estoque</th>
                        <th>Preço</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produtos as $produto): ?>
                        <?php
                        $id = (int) $produto['id_produto'];
                        $imagem = (string) ($produto['imagem'] ?? '');
                        $imagem = $imagem !== '' ? $imagem : '/img/logoBiaModas.png';
                        $emOferta = $produto['oferta_percentual'] !== null && (float) $produto['oferta_percentual'] > 0;
                        ?>
                        <tr>
                            <td>
                                <div class="admin-product-cell">
                                    <img src="<?= e($imagem) ?>" alt="" loading="lazy">
                                    <div>
                                        <strong><?= e($produto['nome']) ?></strong>
                                        <small class="admin-muted d-block"><?= e($produto['sku']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?= e($produto['categorias'] ?? '—') ?></td>
                            <td><?= e($produto['tamanho'] ?? '—') ?></td>
                            <td>
                                <span class="<?= (int) $produto['estoque'] <= 5 ? 'admin-text-danger' : '' ?>">
                                    <?= (int) $produto['estoque'] ?>
                                </span>
                            </td>
                            <td>
                                <?= money($produto['valor']) ?>
                                <?php if ($emOferta): ?>
                                    <small class="admin-muted d-block">-<?= (int) round((float) $produto['oferta_percentual'] * 100) ?>%</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="admin-badge admin-badge-<?= $produto['ativo'] ? 'pago' : 'cancelado' ?>">
                                    <?= $produto['ativo'] ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="admin-row-actions">
                                    <a class="admin-btn admin-btn-ghost" href="/admin/produtos/editar?id=<?= $id ?>">
                                        <i class="bi bi-pencil"></i> Editar
                                    </a>
                                    <form method="post" action="/admin/produtos/excluir" data-confirm="Remover este produto do catálogo?">
                                        <?= Sessao::csrfCampo() ?>
                                        <input type="hidden" name="id_produto" value="<?= $id ?>">
                                        <button type="submit" class="admin-btn admin-btn-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
