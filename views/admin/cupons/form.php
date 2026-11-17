<?php
$modoEdicao = isset($cupom);
$action = $modoEdicao
    ? '/admin/cupons/' . (int) $cupom['id_cupom']
    : '/admin/cupons';

$freteMarcado = filter_var($valores['frete_gratis'] ?? false, FILTER_VALIDATE_BOOLEAN);
?>
<div class="admin-page-head">
    <div>
        <p class="admin-eyebrow">Cupons</p>
        <h1><?= $modoEdicao ? 'Editar cupom' : 'Novo cupom' ?></h1>
        <p class="admin-muted">A faixa de valor define o mínimo e o máximo de compra para o cupom valer.</p>
    </div>
    <a href="/admin/cupons" class="admin-btn admin-btn--ghost"><i class="bi bi-arrow-left"></i> Voltar</a>
</div>

<form method="post" action="<?= e($action) ?>" class="admin-form admin-form--narrow">
    <?= Auth::csrfField() ?>
    <?php if ($modoEdicao): ?>
        <input type="hidden" name="_method" value="PUT">
    <?php endif; ?>

    <section class="admin-panel admin-form-section">
        <div class="admin-form-row">
            <div class="admin-field">
                <label for="codigo">Código <span class="admin-required">*</span></label>
                <input type="text" id="codigo" name="codigo" maxlength="50" value="<?= e($valores['codigo']) ?>" required>
                <span class="admin-hint">Letras, números, hífen ou sublinhado.</span>
                <?php if (!empty($errors['codigo'])): ?><span class="admin-field-error"><?= e($errors['codigo']) ?></span><?php endif; ?>
            </div>

            <div class="admin-field">
                <label for="desconto">Desconto (%) <span class="admin-required">*</span></label>
                <input type="number" id="desconto" name="desconto" step="1" min="1" max="99" value="<?= e($valores['desconto']) ?>" required>
                <span class="admin-hint">Número inteiro entre 1 e 99.</span>
                <?php if (!empty($errors['desconto'])): ?><span class="admin-field-error"><?= e($errors['desconto']) ?></span><?php endif; ?>
            </div>
        </div>

        <div class="admin-form-row">
            <div class="admin-field">
                <label for="val_min">Valor mínimo (R$) <span class="admin-required">*</span></label>
                <input type="number" id="val_min" name="val_min" step="0.01" min="0" value="<?= e($valores['val_min']) ?>" required>
                <?php if (!empty($errors['val_min'])): ?><span class="admin-field-error"><?= e($errors['val_min']) ?></span><?php endif; ?>
            </div>

            <div class="admin-field">
                <label for="val_max">Valor máximo (R$) <span class="admin-required">*</span></label>
                <input type="number" id="val_max" name="val_max" step="0.01" min="0.01" value="<?= e($valores['val_max']) ?>" required>
                <?php if (!empty($errors['val_max'])): ?><span class="admin-field-error"><?= e($errors['val_max']) ?></span><?php endif; ?>
            </div>
        </div>

        <label class="admin-check">
            <input type="checkbox" name="frete_gratis" value="1" <?= $freteMarcado ? 'checked' : '' ?>>
            <span>Concede frete grátis</span>
        </label>
    </section>

    <div class="admin-form-actions">
        <a href="/admin/cupons" class="admin-btn admin-btn--ghost">Cancelar</a>
        <button type="submit" class="admin-btn admin-btn--primary">
            <i class="bi bi-check-lg"></i> <?= $modoEdicao ? 'Salvar alterações' : 'Criar cupom' ?>
        </button>
    </div>
</form>
