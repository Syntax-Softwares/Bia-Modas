<?php $total = count($ofertas); ?>
<div class="admin-page-head">
    <div>
        <p class="admin-eyebrow">Catálogo</p>
        <h1>Ofertas</h1>
        <p class="admin-muted"><?= $total ?> oferta<?= $total === 1 ? '' : 's' ?> cadastrada<?= $total === 1 ? '' : 's' ?></p>
    </div>
    <a href="/admin/ofertas/novo" class="admin-btn admin-btn--primary"><i class="bi bi-plus-lg"></i> Nova oferta</a>
</div>

<?php if ($ofertas === []): ?>
    <div class="admin-empty-state">
        <i class="bi bi-percent"></i>
        <h2>Nenhuma oferta cadastrada</h2>
        <p>Ofertas aplicam desconto percentual aos produtos e podem ter vigência.</p>
        <a href="/admin/ofertas/novo" class="admin-btn admin-btn--primary">Nova oferta</a>
    </div>
<?php else: ?>
    <div class="admin-panel">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Desconto</th>
                        <th>Início</th>
                        <th>Fim</th>
                        <th>Status</th>
                        <th class="text-end">Produtos</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ofertas as $oferta): ?>
                        <?php
                        $percentual = (int) round((float) $oferta['percentual'] * 100);
                        $ativo = filter_var($oferta['ativo'], FILTER_VALIDATE_BOOLEAN);
                        $inicio = $oferta['inicio_em'] !== null ? strtotime($oferta['inicio_em']) : null;
                        $fim = $oferta['fim_em'] !== null ? strtotime($oferta['fim_em']) : null;
                        $agora = time();
                        $vigente = $inicio !== null && $inicio <= $agora && ($fim === null || $fim >= $agora);
                        ?>
                        <tr>
                            <td>
                                <a href="/admin/ofertas/<?= (int) $oferta['id_oferta'] ?>/editar" class="admin-link">
                                    <?= $percentual ?>%
                                </a>
                            </td>
                            <td><?= $oferta['inicio_em'] !== null ? e(date('d/m/Y H:i', strtotime($oferta['inicio_em']))) : '—' ?></td>
                            <td><?= $oferta['fim_em'] !== null ? e(date('d/m/Y H:i', strtotime($oferta['fim_em']))) : 'Sem fim' ?></td>
                            <td>
                                <?php if (!$ativo): ?>
                                    <span class="admin-badge">Inativa</span>
                                <?php elseif ($vigente): ?>
                                    <span class="admin-badge admin-badge--success">Vigente</span>
                                <?php elseif ($inicio !== null && $inicio > $agora): ?>
                                    <span class="admin-badge admin-badge--warning">Agendada</span>
                                <?php else: ?>
                                    <span class="admin-badge admin-badge--warning">Expirada</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end"><?= (int) $oferta['total_produtos'] ?></td>
                            <td class="text-end">
                                <div class="admin-actions">
                                    <a href="/admin/ofertas/<?= (int) $oferta['id_oferta'] ?>/editar" class="admin-btn admin-btn--sm admin-btn--outline" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="post" action="/admin/ofertas/<?= (int) $oferta['id_oferta'] ?>" onsubmit="return confirm('Excluir esta oferta? Os produtos serão desvinculados.');">
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
