<?php
$modoEdicao = isset($usuario);
$action = $modoEdicao
    ? '/admin/usuarios/' . (int) $usuario['id_usuario']
    : '/admin/usuarios';

$ehAdmin = filter_var($valores['is_admin'] ?? false, FILTER_VALIDATE_BOOLEAN);
?>
<div class="admin-page-head">
    <div>
        <p class="admin-eyebrow">Usuários</p>
        <h1><?= $modoEdicao ? 'Editar usuário' : 'Novo usuário' ?></h1>
        <p class="admin-muted">Contas administrativas têm acesso total ao painel.</p>
    </div>
    <a href="/admin/usuarios" class="admin-btn admin-btn--ghost"><i class="bi bi-arrow-left"></i> Voltar</a>
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
            <label for="email">E-mail <span class="admin-required">*</span></label>
            <input type="email" id="email" name="email" maxlength="255" value="<?= e($valores['email']) ?>" required>
            <?php if (!empty($errors['email'])): ?><span class="admin-field-error"><?= e($errors['email']) ?></span><?php endif; ?>
        </div>

        <div class="admin-form-row">
            <div class="admin-field">
                <label for="telefone">Telefone</label>
                <input type="text" id="telefone" name="telefone" maxlength="20" value="<?= e($valores['telefone'] ?? '') ?>">
                <?php if (!empty($errors['telefone'])): ?><span class="admin-field-error"><?= e($errors['telefone']) ?></span><?php endif; ?>
            </div>

            <div class="admin-field">
                <label for="cpf">CPF</label>
                <input type="text" id="cpf" name="cpf" maxlength="11" value="<?= e($valores['cpf'] ?? '') ?>">
                <?php if (!empty($errors['cpf'])): ?><span class="admin-field-error"><?= e($errors['cpf']) ?></span><?php endif; ?>
            </div>
        </div>

        <label class="admin-check">
            <input type="checkbox" name="is_admin" value="1" <?= $ehAdmin ? 'checked' : '' ?>>
            <span>Administrador (acesso ao painel)</span>
        </label>
        <?php if (!empty($errors['is_admin'])): ?><span class="admin-field-error"><?= e($errors['is_admin']) ?></span><?php endif; ?>

        <hr class="admin-divider">

        <h2><?= $modoEdicao ? 'Redefinir senha' : 'Senha de acesso' ?></h2>
        <p class="admin-hint">
            <?= $modoEdicao ? 'Deixe os dois campos em branco para manter a senha atual.' : 'Mínimo de 6 caracteres.' ?>
        </p>

        <div class="admin-form-row">
            <div class="admin-field">
                <label for="senha">Senha <?= $modoEdicao ? '' : '<span class="admin-required">*</span>' ?></label>
                <input type="password" id="senha" name="senha" autocomplete="new-password" <?= $modoEdicao ? '' : 'required' ?>>
                <?php if (!empty($errors['senha'])): ?><span class="admin-field-error"><?= e($errors['senha']) ?></span><?php endif; ?>
            </div>

            <div class="admin-field">
                <label for="senha_confirmar">Confirmar senha <?= $modoEdicao ? '' : '<span class="admin-required">*</span>' ?></label>
                <input type="password" id="senha_confirmar" name="senha_confirmar" autocomplete="new-password" <?= $modoEdicao ? '' : 'required' ?>>
                <?php if (!empty($errors['senha_confirmar'])): ?><span class="admin-field-error"><?= e($errors['senha_confirmar']) ?></span><?php endif; ?>
            </div>
        </div>
    </section>

    <div class="admin-form-actions">
        <a href="/admin/usuarios" class="admin-btn admin-btn--ghost">Cancelar</a>
        <button type="submit" class="admin-btn admin-btn--primary">
            <i class="bi bi-check-lg"></i> <?= $modoEdicao ? 'Salvar alterações' : 'Criar usuário' ?>
        </button>
    </div>
</form>
