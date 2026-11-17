<?php
require __DIR__ . '/partials/header.php';

$editando = $editando ?? null;
$editandoId = (int) ($editando['id_categoria'] ?? 0);
?>
<section class="admin-grid-sidebar">
    <div class="admin-panel admin-panel-sticky">
        <div class="admin-panel-header">
            <h2><?= $editando ? 'Editar categoria' : 'Nova categoria' ?></h2>
            <?php if ($editando): ?>
                <a href="/admin/categorias" class="admin-link">Cancelar edição</a>
            <?php endif; ?>
        </div>

        <form method="post" action="/admin/categorias/salvar" class="admin-form">
            <?= Sessao::csrfCampo() ?>
            <?php if ($editando): ?>
                <input type="hidden" name="id_categoria" value="<?= $editandoId ?>">
            <?php endif; ?>

            <div class="admin-field">
                <label for="categoria-nome">Nome <span>*</span></label>
                <input type="text" id="categoria-nome" name="nome" value="<?= e($editando['nome'] ?? '') ?>" maxlength="255" required>
            </div>

            <div class="admin-field">
                <label for="categoria-descricao">Descrição</label>
                <textarea id="categoria-descricao" name="descricao" rows="3" maxlength="255"><?= e($editando['descricao'] ?? '') ?></textarea>
            </div>

            <div class="admin-form-actions">
                <button type="submit" class="admin-btn admin-btn-primary">
                    <i class="bi bi-check-lg"></i> <?= $editando ? 'Salvar' : 'Criar categoria' ?>
                </button>
            </div>
        </form>
    </div>

    <div class="admin-panel">
        <div class="admin-panel-header">
            <h2>Categorias cadastradas</h2>
            <span class="admin-muted"><?= count($categorias) ?> no total</span>
        </div>

        <?php if ($categorias === []): ?>
            <p class="admin-empty">Nenhuma categoria cadastrada.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Descrição</th>
                            <th>Produtos</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categorias as $categoria): ?>
                            <?php $id = (int) $categoria['id_categoria']; ?>
                            <tr>
                                <td><strong><?= e($categoria['nome']) ?></strong></td>
                                <td><?= e($categoria['descricao'] ?? '—') ?></td>
                                <td><?= (int) $categoria['total_produtos'] ?></td>
                                <td class="text-end">
                                    <div class="admin-row-actions">
                                        <a class="admin-btn admin-btn-ghost" href="/admin/categorias?editar=<?= $id ?>">
                                            <i class="bi bi-pencil"></i> Editar
                                        </a>
                                        <form method="post" action="/admin/categorias/excluir" data-confirm="Excluir esta categoria?">
                                            <?= Sessao::csrfCampo() ?>
                                            <input type="hidden" name="id_categoria" value="<?= $id ?>">
                                            <button type="submit" class="admin-btn admin-btn-danger">
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
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
