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

        <form method="post" action="/admin/categorias/salvar" class="admin-form" enctype="multipart/form-data">
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

            <div class="admin-field">
                <label for="categoria-imagem">Imagem da categoria</label>
                <input type="file" id="categoria-imagem" name="imagem" accept="image/png,image/jpeg,image/webp" data-categoria-input>
                <small>JPG, PNG ou WebP, até 2 MB. Prefira uma foto do produto sem pessoas.</small>
                <input type="hidden" name="imagem_atual" value="<?= e($editando['imagem'] ?? '') ?>">
            </div>

            <div class="admin-category-preview">
                <img src="<?= e($editando['imagem'] ?? '/img/logoBiaModas.png') ?>" alt="Prévia da imagem da categoria" data-categoria-preview>
            </div>

            <div class="admin-form-actions">
                <button type="submit" class="admin-btn admin-btn-primary">
                    <i class="icon-check"></i> <?= $editando ? 'Salvar' : 'Criar categoria' ?>
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
                            <th>Imagem</th>
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
                                <td>
                                    <?php if (!empty($categoria['imagem'])): ?>
                                        <img class="admin-category-thumb" src="<?= e($categoria['imagem']) ?>" alt="" loading="lazy">
                                    <?php else: ?>
                                        <span class="admin-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= e($categoria['nome']) ?></strong></td>
                                <td><?= e($categoria['descricao'] ?? '—') ?></td>
                                <td><?= (int) $categoria['total_produtos'] ?></td>
                                <td class="text-end">
                                    <div class="admin-row-actions">
                                        <a class="admin-btn admin-btn-ghost" href="/admin/categorias?editar=<?= $id ?>">
                                            <i class="icon-pencil"></i> Editar
                                        </a>
                                        <form method="post" action="/admin/categorias/excluir" data-confirm="Excluir esta categoria?">
                                            <?= Sessao::csrfCampo() ?>
                                            <input type="hidden" name="id_categoria" value="<?= $id ?>">
                                            <button type="submit" class="admin-btn admin-btn-danger">
                                                <i class="icon-trash-2"></i>
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
