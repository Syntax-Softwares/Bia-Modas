<?php $total = count($tamanhos); ?>
<div class="admin-page-head">
    <div>
        <p class="admin-eyebrow">Catálogo</p>
        <h1>Tamanhos e medidas</h1>
        <p class="admin-muted"><?= $total ?> tamanho<?= $total === 1 ? '' : 's' ?> base cadastrado<?= $total === 1 ? '' : 's' ?></p>
    </div>
    <a href="/admin/tamanhos/novo" class="admin-btn admin-btn--primary"><i class="bi bi-plus-lg"></i> Novo tamanho</a>
</div>

<?php if ($tamanhos === []): ?>
    <div class="admin-empty-state">
        <i class="bi bi-rulers"></i>
        <h2>Nenhum tamanho cadastrado</h2>
        <p>Tamanhos são obrigatórios no cadastro de produtos.</p>
        <a href="/admin/tamanhos/novo" class="admin-btn admin-btn--primary">Novo tamanho</a>
    </div>
<?php else: ?>
    <div class="admin-panel">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Tamanho</th>
                        <th class="text-end">Busto (cm)</th>
                        <th class="text-end">Cintura (cm)</th>
                        <th class="text-end">Quadril (cm)</th>
                        <th class="text-end">Produtos</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tamanhos as $tamanho): ?>
                        <tr>
                            <td>
                                <a href="/admin/tamanhos/<?= (int) $tamanho['id_medida_produto'] ?>/editar" class="admin-link">
                                    <?= e($tamanho['tamanho']) ?>
                                </a>
                            </td>
                            <td class="text-end"><?= $tamanho['busto'] !== null ? e($tamanho['busto']) : '—' ?></td>
                            <td class="text-end"><?= $tamanho['cintura'] !== null ? e($tamanho['cintura']) : '—' ?></td>
                            <td class="text-end"><?= $tamanho['quadril'] !== null ? e($tamanho['quadril']) : '—' ?></td>
                            <td class="text-end"><?= (int) $tamanho['total_produtos'] ?></td>
                            <td class="text-end">
                                <div class="admin-actions">
                                    <a href="/admin/tamanhos/<?= (int) $tamanho['id_medida_produto'] ?>/editar" class="admin-btn admin-btn--sm admin-btn--outline" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="post" action="/admin/tamanhos/<?= (int) $tamanho['id_medida_produto'] ?>" onsubmit="return confirm('Excluir este tamanho?');">
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
