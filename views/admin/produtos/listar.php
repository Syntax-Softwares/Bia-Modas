<?php
$queryFiltros = [
    'q' => $filtros['q'],
    'status' => $filtros['status'],
    'categoria_id' => $filtros['categoria_id'] > 0 ? $filtros['categoria_id'] : '',
];
?>
<div class="admin-page-head">
    <div>
        <p class="admin-eyebrow">Catálogo</p>
        <h1>Produtos</h1>
        <p class="admin-muted"><?= (int) $resultado['total'] ?> produto<?= (int) $resultado['total'] === 1 ? '' : 's' ?> encontrado<?= (int) $resultado['total'] === 1 ? '' : 's' ?></p>
    </div>
    <a href="/admin/produtos/novo" class="admin-btn admin-btn--primary">
        <i class="bi bi-plus-lg"></i> Novo produto
    </a>
</div>

<form method="get" action="/admin/produtos" class="admin-toolbar">
    <div class="admin-field admin-field--grow">
        <label for="filtro-q">Buscar</label>
        <input type="text" id="filtro-q" name="q" value="<?= e($filtros['q']) ?>" placeholder="Nome ou SKU">
    </div>

    <div class="admin-field">
        <label for="filtro-status">Status</label>
        <select id="filtro-status" name="status">
            <option value=""<?= $filtros['status'] === '' ? ' selected' : '' ?>>Todos</option>
            <option value="ativos"<?= $filtros['status'] === 'ativos' ? ' selected' : '' ?>>Ativos</option>
            <option value="inativos"<?= $filtros['status'] === 'inativos' ? ' selected' : '' ?>>Inativos</option>
        </select>
    </div>

    <div class="admin-field">
        <label for="filtro-categoria">Categoria</label>
        <select id="filtro-categoria" name="categoria_id">
            <option value="">Todas</option>
            <?php foreach ($categoriasFiltro as $categoria): ?>
                <option value="<?= (int) $categoria['id_categoria'] ?>"<?= (int) $filtros['categoria_id'] === (int) $categoria['id_categoria'] ? ' selected' : '' ?>>
                    <?= e($categoria['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="admin-toolbar-actions">
        <button type="submit" class="admin-btn admin-btn--outline"><i class="bi bi-search"></i> Filtrar</button>
        <a href="/admin/produtos" class="admin-btn admin-btn--ghost">Limpar</a>
    </div>
</form>

<?php if ($resultado['itens'] === []): ?>
    <div class="admin-empty-state">
        <i class="bi bi-bag"></i>
        <h2>Nenhum produto encontrado</h2>
        <p>Ajuste os filtros ou cadastre um novo produto.</p>
        <a href="/admin/produtos/novo" class="admin-btn admin-btn--primary">Novo produto</a>
    </div>
<?php else: ?>
    <div class="admin-panel">
        <div class="table-responsive">
            <table class="admin-table admin-table--wide">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Categoria</th>
                        <th class="text-end">Valor</th>
                        <th class="text-end">Estoque</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resultado['itens'] as $produto): ?>
                        <tr>
                            <td>
                                <div class="admin-product-cell">
                                    <?php if (!empty($produto['imagem'])): ?>
                                        <img src="<?= e($produto['imagem']) ?>" alt="" class="admin-thumb">
                                    <?php else: ?>
                                        <span class="admin-thumb admin-thumb--empty"><i class="bi bi-image"></i></span>
                                    <?php endif; ?>
                                    <div>
                                        <a href="/admin/produtos/<?= (int) $produto['id_produto'] ?>/editar" class="admin-link">
                                            <?= e($produto['nome']) ?>
                                        </a>
                                        <span class="admin-muted d-block"><?= e($produto['sku']) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td><?= e($produto['categoria']) ?></td>
                            <td class="text-end"><?= money($produto['valor']) ?></td>
                            <td class="text-end">
                                <span class="admin-badge<?= (int) $produto['estoque'] <= 5 ? ' admin-badge--warning' : '' ?>">
                                    <?= (int) $produto['estoque'] ?>
                                </span>
                            </td>
                            <td>
                                <?php if (filter_var($produto['ativo'], FILTER_VALIDATE_BOOLEAN)): ?>
                                    <span class="admin-badge admin-badge--success">Ativo</span>
                                <?php else: ?>
                                    <span class="admin-badge">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="admin-actions">
                                    <a href="/admin/produtos/<?= (int) $produto['id_produto'] ?>/editar" class="admin-btn admin-btn--sm admin-btn--outline" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="post" action="/admin/produtos/<?= (int) $produto['id_produto'] ?>" onsubmit="return confirm('Excluir este produto?');">
                                        <?= Auth::csrfField() ?>
                                        <input type="hidden" name="_method" value="DELETE">
                                        <button type="submit" class="admin-btn admin-btn--sm admin-btn--danger" title="Excluir">
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
    </div>

    <?php if ($totalPaginas > 1): ?>
        <nav class="admin-pagination" aria-label="Paginação de produtos">
            <a
                href="<?= e(url_com_query('/admin/produtos', $queryFiltros + ['pagina' => max(1, $pagina - 1)])) ?>"
                class="admin-btn admin-btn--sm admin-btn--outline<?= $pagina <= 1 ? ' is-disabled' : '' ?>"
                <?= $pagina <= 1 ? 'aria-disabled="true" tabindex="-1"' : '' ?>
            >
                <i class="bi bi-chevron-left"></i>
            </a>

            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                <a
                    href="<?= e(url_com_query('/admin/produtos', $queryFiltros + ['pagina' => $i])) ?>"
                    class="admin-btn admin-btn--sm<?= $i === $pagina ? ' admin-btn--primary' : ' admin-btn--outline' ?>"
                ><?= $i ?></a>
            <?php endfor; ?>

            <a
                href="<?= e(url_com_query('/admin/produtos', $queryFiltros + ['pagina' => min($totalPaginas, $pagina + 1)])) ?>"
                class="admin-btn admin-btn--sm admin-btn--outline<?= $pagina >= $totalPaginas ? ' is-disabled' : '' ?>"
                <?= $pagina >= $totalPaginas ? 'aria-disabled="true" tabindex="-1"' : '' ?>
            >
                <i class="bi bi-chevron-right"></i>
            </a>
        </nav>
    <?php endif; ?>
<?php endif; ?>
