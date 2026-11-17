<?php
$modoEdicao = isset($tamanho);
$action = $modoEdicao
    ? '/admin/tamanhos/' . (int) $tamanho['id_medida_produto']
    : '/admin/tamanhos';
?>
<div class="admin-page-head">
    <div>
        <p class="admin-eyebrow">Tamanhos e medidas</p>
        <h1><?= $modoEdicao ? 'Editar tamanho' : 'Novo tamanho' ?></h1>
        <p class="admin-muted">As medidas são opcionais e ficam disponíveis para consulta.</p>
    </div>
    <a href="/admin/tamanhos" class="admin-btn admin-btn--ghost"><i class="bi bi-arrow-left"></i> Voltar</a>
</div>

<form method="post" action="<?= e($action) ?>" class="admin-form admin-form--narrow">
    <?= Auth::csrfField() ?>
    <?php if ($modoEdicao): ?>
        <input type="hidden" name="_method" value="PUT">
    <?php endif; ?>

    <section class="admin-panel admin-form-section">
        <div class="admin-field">
            <label for="tamanho">Tamanho <span class="admin-required">*</span></label>
            <input type="text" id="tamanho" name="tamanho" maxlength="5" value="<?= e($valores['tamanho']) ?>" required>
            <span class="admin-hint">Ex.: P, M, GG, 38, 42.</span>
            <?php if (!empty($errors['tamanho'])): ?><span class="admin-field-error"><?= e($errors['tamanho']) ?></span><?php endif; ?>
        </div>

        <div class="admin-form-row">
            <div class="admin-field">
                <label for="busto">Busto (cm)</label>
                <input type="number" id="busto" name="busto" step="0.1" min="0.1" max="300" value="<?= e($valores['busto']) ?>">
                <?php if (!empty($errors['busto'])): ?><span class="admin-field-error"><?= e($errors['busto']) ?></span><?php endif; ?>
            </div>

            <div class="admin-field">
                <label for="cintura">Cintura (cm)</label>
                <input type="number" id="cintura" name="cintura" step="0.1" min="0.1" max="300" value="<?= e($valores['cintura']) ?>">
                <?php if (!empty($errors['cintura'])): ?><span class="admin-field-error"><?= e($errors['cintura']) ?></span><?php endif; ?>
            </div>

            <div class="admin-field">
                <label for="quadril">Quadril (cm)</label>
                <input type="number" id="quadril" name="quadril" step="0.1" min="0.1" max="300" value="<?= e($valores['quadril']) ?>">
                <?php if (!empty($errors['quadril'])): ?><span class="admin-field-error"><?= e($errors['quadril']) ?></span><?php endif; ?>
            </div>
        </div>
    </section>

    <div class="admin-form-actions">
        <a href="/admin/tamanhos" class="admin-btn admin-btn--ghost">Cancelar</a>
        <button type="submit" class="admin-btn admin-btn--primary">
            <i class="bi bi-check-lg"></i> <?= $modoEdicao ? 'Salvar alterações' : 'Criar tamanho' ?>
        </button>
    </div>
</form>
