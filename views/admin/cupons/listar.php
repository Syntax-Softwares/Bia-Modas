<?php $total = count($cupons); ?>
<div class="admin-page-head">
    <div>
        <p class="admin-eyebrow">Catálogo</p>
        <h1>Cupons</h1>
        <p class="admin-muted"><?= $total ?> cupom<?= $total === 1 ? '' : 's' ?> cadastrado<?= $total === 1 ? '' : 's' ?></p>
    </div>
    <a href="/admin/cupons/novo" class="admin-btn admin-btn--primary"><i class="bi bi-plus-lg"></i> Novo cupom</a>
</div>

<?php if ($cupons === []): ?>
    <div class="admin-empty-state">
        <i class="bi bi-ticket-perforated"></i>
        <h2>Nenhum cupom cadastrado</h2>
        <p>Cupons definem desconto percentual e faixa de valor de compra.</p>
        <a href="/admin/cupons/novo" class="admin-btn admin-btn--primary">Novo cupom</a>
    </div>
<?php else: ?>
    <div class="admin-panel">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th class="text-end">Desconto</th>
                        <th class="text-end">Faixa de valor</th>
                        <th>Frete grátis</th>
                        <th>Criado em</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cupons as $cupom): ?>
                        <tr>
                            <td>
                                <a href="/admin/cupons/<?= (int) $cupom['id_cupom'] ?>/editar" class="admin-link">
                                    <?= e($cupom['codigo']) ?>
                                </a>
                            </td>
                            <td class="text-end"><?= (int) round((float) $cupom['desconto'] * 100) ?>%</td>
                            <td class="text-end"><?= money($cupom['val_min']) ?> – <?= money($cupom['val_max']) ?></td>
                            <td>
                                <?php if (filter_var($cupom['frete_gratis'], FILTER_VALIDATE_BOOLEAN)): ?>
                                    <span class="admin-badge admin-badge--success">Sim</span>
                                <?php else: ?>
                                    <span class="admin-badge">Não</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $cupom['criado_em'] ? e(date('d/m/Y', strtotime($cupom['criado_em']))) : '—' ?></td>
                            <td class="text-end">
                                <div class="admin-actions">
                                    <a href="/admin/cupons/<?= (int) $cupom['id_cupom'] ?>/editar" class="admin-btn admin-btn--sm admin-btn--outline" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="post" action="/admin/cupons/<?= (int) $cupom['id_cupom'] ?>" onsubmit="return confirm('Excluir este cupom?');">
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
