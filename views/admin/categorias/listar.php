<?php $total = count($categorias); ?>
<div class="admin-page-head">
    <div>
        <p class="admin-eyebrow">Catálogo</p>
        <h1>Categorias</h1>
        <p class="admin-muted"><?= $total ?> categoria<?= $total === 1 ? '' : 's' ?> cadastrada<?= $total === 1 ? '' : 's' ?></p>
    </div>
    <a href="/admin/categorias/novo" class="admin-btn admin-btn--primary"><i class="bi bi-plus-lg"></i> Nova categoria</a>
</div>

<?php if ($categorias === []): ?>
    <div class="admin-empty-state">
        <i class="bi bi-tags"></i>
        <h2>Nenhuma categoria cadastrada</h2>
        <p>Categorias organizam o catálogo e são obrigatórias nos produtos.</p>
        <a href="/admin/categorias/novo" class="admin-btn admin-btn--primary">Nova categoria</a>
    </div>
<?php else: ?>
    <div class="admin-panel">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Categoria</th>
                        <th>Categoria pai</th>
                        <th class="text-end">Produtos</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categorias as $categoria): ?>
                        <tr>
                            <td>
                                <a href="/admin/categorias/<?= (int) $categoria['id_categoria'] ?>/editar" class="admin-link">
                                    <?= e($categoria['nome']) ?>
                                </a>
                                <?php if (!empty($categoria['descricao'])): ?>
                                    <span class="admin-muted d-block"><?= e($categoria['descricao']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= e($categoria['pai_nome'] ?? '—') ?></td>
                            <td class="text-end"><?= (int) $categoria['total_produtos'] ?></td>
                            <td class="text-end">
                                <div class="admin-actions">
                                    <a href="/admin/categorias/<?= (int) $categoria['id_categoria'] ?>/editar" class="admin-btn admin-btn--sm admin-btn--outline" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="post" action="/admin/categorias/<?= (int) $categoria['id_categoria'] ?>" onsubmit="return confirm('Excluir esta categoria?');">
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
<?php endif; ?>
