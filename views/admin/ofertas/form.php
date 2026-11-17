<?php
$modoEdicao = isset($oferta);
$action = $modoEdicao
    ? '/admin/ofertas/' . (int) $oferta['id_oferta']
    : '/admin/ofertas';

$ativoMarcado = filter_var($valores['ativo'] ?? false, FILTER_VALIDATE_BOOLEAN);
?>
<div class="admin-page-head">
    <div>
        <p class="admin-eyebrow">Ofertas</p>
        <h1><?= $modoEdicao ? 'Editar oferta' : 'Nova oferta' ?></h1>
        <p class="admin-muted">O percentual é aplicado sobre o valor dos produtos vinculados.</p>
    </div>
    <a href="/admin/ofertas" class="admin-btn admin-btn--ghost"><i class="bi bi-arrow-left"></i> Voltar</a>
</div>

<form method="post" action="<?= e($action) ?>" class="admin-form admin-form--narrow">
    <?= Auth::csrfField() ?>
    <?php if ($modoEdicao): ?>
        <input type="hidden" name="_method" value="PUT">
    <?php endif; ?>

    <section class="admin-panel admin-form-section">
        <div class="admin-field">
            <label for="percentual">Percentual de desconto (%) <span class="admin-required">*</span></label>
            <input type="number" id="percentual" name="percentual" step="1" min="1" max="99" value="<?= e($valores['percentual']) ?>" required>
            <span class="admin-hint">Número inteiro entre 1 e 99.</span>
            <?php if (!empty($errors['percentual'])): ?><span class="admin-field-error"><?= e($errors['percentual']) ?></span><?php endif; ?>
        </div>

        <div class="admin-form-row">
            <div class="admin-field">
                <label for="inicio_em">Início <span class="admin-required">*</span></label>
                <input type="datetime-local" id="inicio_em" name="inicio_em" value="<?= e($valores['inicio_em']) ?>" required>
                <?php if (!empty($errors['inicio_em'])): ?><span class="admin-field-error"><?= e($errors['inicio_em']) ?></span><?php endif; ?>
            </div>

            <div class="admin-field">
                <label for="fim_em">Fim</label>
                <input type="datetime-local" id="fim_em" name="fim_em" value="<?= e($valores['fim_em']) ?>">
                <span class="admin-hint">Deixe em branco para não expirar.</span>
                <?php if (!empty($errors['fim_em'])): ?><span class="admin-field-error"><?= e($errors['fim_em']) ?></span><?php endif; ?>
            </div>
        </div>

        <label class="admin-check">
            <input type="checkbox" name="ativo" value="1" <?= $ativoMarcado ? 'checked' : '' ?>>
            <span>Oferta ativa</span>
        </label>
    </section>

    <div class="admin-form-actions">
        <a href="/admin/ofertas" class="admin-btn admin-btn--ghost">Cancelar</a>
        <button type="submit" class="admin-btn admin-btn--primary">
            <i class="bi bi-check-lg"></i> <?= $modoEdicao ? 'Salvar alterações' : 'Criar oferta' ?>
        </button>
    </div>
</form>
