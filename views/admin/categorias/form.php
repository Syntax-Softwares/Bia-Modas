<?php
$modoEdicao = isset($categoria);
$action = $modoEdicao
    ? '/admin/categorias/' . (int) $categoria['id_categoria']
    : '/admin/categorias';
?>
<div class="admin-page-head">
    <div>
        <p class="admin-eyebrow">Categorias</p>
        <h1><?= $modoEdicao ? 'Editar categoria' : 'Nova categoria' ?></h1>
        <p class="admin-muted">A categoria pai é opcional e serve para organizar o catálogo.</p>
    </div>
    <a href="/admin/categorias" class="admin-btn admin-btn--ghost"><i class="bi bi-arrow-left"></i> Voltar</a>
</div>

<form method="post" action="<?= e($action) ?>" class="admin-form admin-form--narrow">
    <?= Auth::csrfField() ?>
    <?php if ($modoEdicao): ?>
        <input type="hidden" name="_method" value="PUT">
    <?php endif; ?>

    <section class="admin-panel admin-form-section">
        <div class="admin-field">
            <label for="nome">Nome <span class="admin-required">*</span></label>
            <input type="text" id="nome" name="nome" maxlength="255" value="<?= e($valores['nome']) ?>" required>
            <?php if (!empty($errors['nome'])): ?><span class="admin-field-error"><?= e($errors['nome']) ?></span><?php endif; ?>
        </div>

        <div class="admin-field">
            <label for="categoria_pai_id">Categoria pai</label>
            <select id="categoria_pai_id" name="categoria_pai_id">
                <option value="">Nenhuma (categoria raiz)</option>
                <?php foreach ($pais as $pai): ?>
                    <option
                        value="<?= (int) $pai['id_categoria'] ?>"
                        <?= (string) ($valores['categoria_pai_id'] ?? '') === (string) $pai['id_categoria'] ? 'selected' : '' ?>
                    ><?= e($pai['nome']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['categoria_pai_id'])): ?><span class="admin-field-error"><?= e($errors['categoria_pai_id']) ?></span><?php endif; ?>
        </div>

        <div class="admin-field">
            <label for="descricao">Descrição</label>
            <textarea id="descricao" name="descricao" rows="3" maxlength="255"><?= e($valores['descricao']) ?></textarea>
            <?php if (!empty($errors['descricao'])): ?><span class="admin-field-error"><?= e($errors['descricao']) ?></span><?php endif; ?>
        </div>
    </section>

    <div class="admin-form-actions">
        <a href="/admin/categorias" class="admin-btn admin-btn--ghost">Cancelar</a>
        <button type="submit" class="admin-btn admin-btn--primary">
            <i class="bi bi-check-lg"></i> <?= $modoEdicao ? 'Salvar alterações' : 'Criar categoria' ?>
        </button>
    </div>
</form>
