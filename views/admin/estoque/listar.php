<?php
$queryFiltros = [
    'q' => $busca,
    'baixo' => $somenteBaixo ? '1' : '',
];
?>
<div class="admin-page-head">
    <div>
        <p class="admin-eyebrow">Catálogo</p>
        <h1>Estoque</h1>
        <p class="admin-muted">
            <?= (int) $resultado['total'] ?> produto<?= (int) $resultado['total'] === 1 ? '' : 's' ?>
            <?= $somenteBaixo ? ' com estoque baixo (≤ 5)' : ' no catálogo' ?>
        </p>
    </div>
    <a href="/admin/produtos/novo" class="admin-btn admin-btn--outline"><i class="bi bi-plus-lg"></i> Novo produto</a>
</div>

<form method="get" action="/admin/estoque" class="admin-toolbar">
    <div class="admin-field admin-field--grow">
        <label for="filtro-q">Buscar</label>
        <input type="text" id="filtro-q" name="q" value="<?= e($busca) ?>" placeholder="Nome ou SKU">
    </div>

    <label class="admin-check admin-check--inline">
        <input type="checkbox" name="baixo" value="1" <?= $somenteBaixo ? 'checked' : '' ?>>
        <span>Somente estoque baixo (≤ 5)</span>
    </label>

    <div class="admin-toolbar-actions">
        <button type="submit" class="admin-btn admin-btn--outline"><i class="bi bi-search"></i> Filtrar</button>
        <a href="/admin/estoque" class="admin-btn admin-btn--ghost">Limpar</a>
    </div>
</form>

<?php if ($resultado['itens'] === []): ?>
    <div class="admin-empty-state">
        <i class="bi bi-box-seam"></i>
        <h2>Nenhum produto encontrado</h2>
        <p>Ajuste os filtros para ver outros produtos.</p>
    </div>
<?php else: ?>
    <div class="admin-panel">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Categoria</th>
                        <th>Tamanho</th>
                        <th>Status</th>
                        <th class="text-end">Estoque atual</th>
                        <th class="text-end">Ajustar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resultado['itens'] as $produto): ?>
                        <?php $baixo = (int) $produto['estoque'] <= 5; ?>
                        <tr class="<?= $baixo ? 'admin-row--warning' : '' ?>">
                            <td>
                                <a href="/admin/produtos/<?= (int) $produto['id_produto'] ?>/editar" class="admin-link">
                                    <?= e($produto['nome']) ?>
                                </a>
                                <span class="admin-muted d-block"><?= e($produto['sku']) ?></span>
                            </td>
                            <td><?= e($produto['categoria']) ?></td>
                            <td><?= e($produto['tamanho'] ?: '—') ?></td>
                            <td>
                                <?php if (filter_var($produto['ativo'], FILTER_VALIDATE_BOOLEAN)): ?>
                                    <span class="admin-badge admin-badge--success">Ativo</span>
                                <?php else: ?>
                                    <span class="admin-badge">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <span class="admin-badge<?= $baixo ? ' admin-badge--warning' : '' ?>">
                                    <?= (int) $produto['estoque'] ?>
                                </span>
                            </td>
                            <td>
                                <form method="post" action="/admin/estoque/<?= (int) $produto['id_produto'] ?>" class="admin-stock-form">
                                    <?= Auth::csrfField() ?>
                                    <input type="hidden" name="_method" value="PUT">
                                    <input type="hidden" name="q" value="<?= e($busca) ?>">
                                    <input type="hidden" name="baixo" value="<?= $somenteBaixo ? '1' : '' ?>">
                                    <input
                                        type="number"
                                        name="estoque"
                                        min="0"
                                        max="99999"
                                        step="1"
                                        value="<?= (int) $produto['estoque'] ?>"
                                        class="admin-input-inline"
                                        aria-label="Estoque de <?= e($produto['nome']) ?>"
                                        required
                                    >
                                    <button type="submit" class="admin-btn admin-btn--sm admin-btn--primary" title="Salvar estoque">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($totalPaginas > 1): ?>
        <nav class="admin-pagination" aria-label="Paginação de estoque">
            <a
                href="<?= e(url_com_query('/admin/estoque', $queryFiltros + ['pagina' => max(1, $pagina - 1)])) ?>"
                class="admin-btn admin-btn--sm admin-btn--outline<?= $pagina <= 1 ? ' is-disabled' : '' ?>"
                <?= $pagina <= 1 ? 'aria-disabled="true" tabindex="-1"' : '' ?>
            ><i class="bi bi-chevron-left"></i></a>

            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                <a
                    href="<?= e(url_com_query('/admin/estoque', $queryFiltros + ['pagina' => $i])) ?>"
                    class="admin-btn admin-btn--sm<?= $i === $pagina ? ' admin-btn--primary' : ' admin-btn--outline' ?>"
                ><?= $i ?></a>
            <?php endfor; ?>

            <a
                href="<?= e(url_com_query('/admin/estoque', $queryFiltros + ['pagina' => min($totalPaginas, $pagina + 1)])) ?>"
                class="admin-btn admin-btn--sm admin-btn--outline<?= $pagina >= $totalPaginas ? ' is-disabled' : '' ?>"
                <?= $pagina >= $totalPaginas ? 'aria-disabled="true" tabindex="-1"' : '' ?>
            ><i class="bi bi-chevron-right"></i></a>
        </nav>
    <?php endif; ?>
<?php endif; ?>
