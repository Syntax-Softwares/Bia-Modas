<?php
require __DIR__ . '/partials/header.php';

$editando = $editando ?? null;
$editandoId = (int) ($editando['id_tamanho'] ?? 0);
$ultimaOrdem = 0;

foreach ($tamanhos as $tamanhoExistente) {
    $ultimaOrdem = max($ultimaOrdem, (int) $tamanhoExistente['ordem']);
}

$ordemPadrao = $editando !== null ? (int) $editando['ordem'] : $ultimaOrdem + 1;
?>
<section class="admin-grid-sidebar">
    <div class="admin-panel admin-panel-sticky">
        <div class="admin-panel-header">
            <h2><?= $editando ? 'Editar tamanho' : 'Novo tamanho' ?></h2>
            <?php if ($editando): ?>
                <a href="/admin/tamanhos" class="admin-link">Cancelar edição</a>
            <?php endif; ?>
        </div>

        <form method="post" action="/admin/tamanhos/salvar" class="admin-form">
            <?= Sessao::csrfCampo() ?>
            <?php if ($editando): ?>
                <input type="hidden" name="id_tamanho" value="<?= $editandoId ?>">
            <?php endif; ?>

            <div class="admin-field">
                <label for="tamanho-nome">Tamanho <span>*</span></label>
                <input type="text" id="tamanho-nome" name="nome" value="<?= e($editando['nome'] ?? '') ?>" maxlength="10" placeholder="P, GG, 42..." required>
                <small>Use o mesmo padrão que aparece na loja: PP, P, M, G, GG, 38, 40...</small>
            </div>

            <div class="admin-field">
                <label for="tamanho-ordem">Ordem de exibição</label>
                <input type="number" id="tamanho-ordem" name="ordem" value="<?= $ordemPadrao ?>" min="0" max="9999" step="1">
                <small>Números menores aparecem primeiro nos filtros e seletores.</small>
            </div>

            <div class="admin-form-actions">
                <button type="submit" class="admin-btn admin-btn-primary">
                    <i class="icon-check"></i> <?= $editando ? 'Salvar' : 'Cadastrar tamanho' ?>
                </button>
            </div>
        </form>
    </div>

    <div class="admin-panel">
        <div class="admin-panel-header">
            <h2>Tamanhos cadastrados</h2>
            <span class="admin-muted"><?= count($tamanhos) ?> no total</span>
        </div>

        <?php if ($tamanhos === []): ?>
            <p class="admin-empty">Nenhum tamanho cadastrado.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Tamanho</th>
                            <th>Ordem</th>
                            <th>Produtos</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tamanhos as $tamanho): ?>
                            <?php
                            $id = (int) $tamanho['id_tamanho'];
                            $emUso = (int) $tamanho['total_produtos'];
                            ?>
                            <tr>
                                <td><strong><?= e($tamanho['nome']) ?></strong></td>
                                <td><?= (int) $tamanho['ordem'] ?></td>
                                <td><?= $emUso ?></td>
                                <td class="text-end">
                                    <div class="admin-row-actions">
                                        <a class="admin-btn admin-btn-ghost" href="/admin/tamanhos?editar=<?= $id ?>">
                                            <i class="icon-pencil"></i> Editar
                                        </a>
                                        <?php if ($emUso > 0): ?>
                                            <button type="button" class="admin-btn admin-btn-danger" disabled title="Em uso por <?= $emUso ?> produto(s)">
                                                <i class="icon-trash-2"></i>
                                            </button>
                                        <?php else: ?>
                                            <form method="post" action="/admin/tamanhos/excluir" data-confirm="Excluir este tamanho?">
                                                <?= Sessao::csrfCampo() ?>
                                                <input type="hidden" name="id_tamanho" value="<?= $id ?>">
                                                <button type="submit" class="admin-btn admin-btn-danger">
                                                    <i class="icon-trash-2"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
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
