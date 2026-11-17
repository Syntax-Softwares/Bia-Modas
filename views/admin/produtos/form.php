<?php
$modoEdicao = isset($produto);
$action = $modoEdicao
    ? '/admin/produtos/' . (int) $produto['id_produto']
    : '/admin/produtos';

$ativoMarcado = filter_var($valores['ativo'] ?? false, FILTER_VALIDATE_BOOLEAN);
?>
<div class="admin-page-head">
    <div>
        <p class="admin-eyebrow">Produtos</p>
        <h1><?= $modoEdicao ? 'Editar produto' : 'Novo produto' ?></h1>
        <p class="admin-muted">
            <?= $modoEdicao ? 'Atualize os dados e a imagem do produto.' : 'Cadastre um novo SKU no catálogo.' ?>
        </p>
    </div>
    <a href="/admin/produtos" class="admin-btn admin-btn--ghost"><i class="bi bi-arrow-left"></i> Voltar</a>
</div>

<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" class="admin-form">
    <?= Auth::csrfField() ?>
    <?php if ($modoEdicao): ?>
        <input type="hidden" name="_method" value="PUT">
    <?php endif; ?>

    <div class="admin-form-grid">
        <section class="admin-panel admin-form-section">
            <h2>Dados do produto</h2>

            <div class="admin-field">
                <label for="nome">Nome <span class="admin-required">*</span></label>
                <input type="text" id="nome" name="nome" maxlength="255" value="<?= e($valores['nome']) ?>" required>
                <?php if (!empty($errors['nome'])): ?><span class="admin-field-error"><?= e($errors['nome']) ?></span><?php endif; ?>
            </div>

            <div class="admin-form-row">
                <div class="admin-field">
                    <label for="sku">SKU <span class="admin-required">*</span></label>
                    <input type="text" id="sku" name="sku" maxlength="50" value="<?= e($valores['sku']) ?>" required>
                    <span class="admin-hint">Maiúsculas, números, ponto, hífen ou sublinhado.</span>
                    <?php if (!empty($errors['sku'])): ?><span class="admin-field-error"><?= e($errors['sku']) ?></span><?php endif; ?>
                </div>

                <div class="admin-field">
                    <label for="valor">Valor (R$) <span class="admin-required">*</span></label>
                    <input type="number" id="valor" name="valor" step="0.01" min="0.01" value="<?= e($valores['valor']) ?>" required>
                    <?php if (!empty($errors['valor'])): ?><span class="admin-field-error"><?= e($errors['valor']) ?></span><?php endif; ?>
                </div>
            </div>

            <div class="admin-form-row">
                <div class="admin-field">
                    <label for="estoque">Estoque <span class="admin-required">*</span></label>
                    <input type="number" id="estoque" name="estoque" step="1" min="0" max="99999" value="<?= e($valores['estoque']) ?>" required>
                    <?php if (!empty($errors['estoque'])): ?><span class="admin-field-error"><?= e($errors['estoque']) ?></span><?php endif; ?>
                </div>

                <div class="admin-field">
                    <label for="medida_produto_id">Tamanho <span class="admin-required">*</span></label>
                    <select id="medida_produto_id" name="medida_produto_id" required>
                        <option value="">Selecione</option>
                        <?php foreach ($tamanhos as $tamanho): ?>
                            <option
                                value="<?= (int) $tamanho['id_medida_produto'] ?>"
                                <?= (int) $valores['medida_produto_id'] === (int) $tamanho['id_medida_produto'] ? 'selected' : '' ?>
                            ><?= e($tamanho['tamanho']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['medida_produto_id'])): ?><span class="admin-field-error"><?= e($errors['medida_produto_id']) ?></span><?php endif; ?>
                </div>
            </div>

            <div class="admin-field">
                <label for="oferta_id">Oferta</label>
                <select id="oferta_id" name="oferta_id">
                    <option value="">Sem oferta</option>
                    <?php foreach ($ofertas as $oferta): ?>
                        <?php
                        $percentualOferta = (int) round((float) $oferta['percentual'] * 100);
                        $vigente = filter_var($oferta['ativo'], FILTER_VALIDATE_BOOLEAN);
                        ?>
                        <option
                            value="<?= (int) $oferta['id_oferta'] ?>"
                            <?= (int) $valores['oferta_id'] === (int) $oferta['id_oferta'] ? 'selected' : '' ?>
                        ><?= $percentualOferta ?>%<?= $vigente ? '' : ' (inativa)' ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="admin-hint">Só ofertas ativas e vigentes aplicam desconto na loja.</span>
                <?php if (!empty($errors['oferta_id'])): ?><span class="admin-field-error"><?= e($errors['oferta_id']) ?></span><?php endif; ?>
            </div>

            <div class="admin-field">
                <label for="descricao">Descrição</label>
                <textarea id="descricao" name="descricao" rows="3" maxlength="255"><?= e($valores['descricao']) ?></textarea>
                <?php if (!empty($errors['descricao'])): ?><span class="admin-field-error"><?= e($errors['descricao']) ?></span><?php endif; ?>
            </div>

            <label class="admin-check">
                <input type="checkbox" name="ativo" value="1" <?= $ativoMarcado ? 'checked' : '' ?>>
                <span>Produto ativo (visível na loja)</span>
            </label>
        </section>

        <aside class="admin-form-aside">
            <section class="admin-panel admin-form-section">
                <h2>Categorias <span class="admin-required">*</span></h2>

                <?php if ($categorias === []): ?>
                    <p class="admin-empty">Cadastre uma categoria antes de criar produtos.</p>
                <?php else: ?>
                    <div class="admin-check-list">
                        <?php foreach ($categorias as $categoria): ?>
                            <?php $selecionada = in_array((int) $categoria['id_categoria'], $categoriasSelecionadas, true); ?>
                            <div class="admin-check-row">
                                <label class="admin-check">
                                    <input
                                        type="checkbox"
                                        name="categorias[]"
                                        value="<?= (int) $categoria['id_categoria'] ?>"
                                        class="js-categoria"
                                        <?= $selecionada ? 'checked' : '' ?>
                                    >
                                    <span><?= e($categoria['nome']) ?></span>
                                </label>

                                <label class="admin-radio" title="Categoria principal">
                                    <input
                                        type="radio"
                                        name="categoria_principal"
                                        value="<?= (int) $categoria['id_categoria'] ?>"
                                        class="js-principal"
                                        <?= (int) $principalSelecionada === (int) $categoria['id_categoria'] ? 'checked' : '' ?>
                                    >
                                    <span>Principal</span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <span class="admin-hint">A categoria principal é a exibida na vitrine.</span>
                <?php endif; ?>

                <?php if (!empty($errors['categorias'])): ?><span class="admin-field-error"><?= e($errors['categorias']) ?></span><?php endif; ?>
                <?php if (!empty($errors['categoria_principal'])): ?><span class="admin-field-error"><?= e($errors['categoria_principal']) ?></span><?php endif; ?>
            </section>

            <section class="admin-panel admin-form-section">
                <h2>Imagem principal</h2>

                <?php if (!empty($imagemAtual)): ?>
                    <img src="<?= e($imagemAtual) ?>" alt="Imagem atual" class="admin-image-preview">
                <?php endif; ?>

                <div class="admin-field">
                    <label for="imagem"><?= $modoEdicao ? 'Substituir imagem' : 'Enviar imagem' ?></label>
                    <input type="file" id="imagem" name="imagem" accept="image/jpeg,image/png,image/webp">
                    <span class="admin-hint">JPG, PNG ou WEBP, até 2 MB.</span>
                    <?php if (!empty($errors['imagem'])): ?><span class="admin-field-error"><?= e($errors['imagem']) ?></span><?php endif; ?>
                </div>
            </section>
        </aside>
    </div>

    <div class="admin-form-actions">
        <a href="/admin/produtos" class="admin-btn admin-btn--ghost">Cancelar</a>
        <button type="submit" class="admin-btn admin-btn--primary">
            <i class="bi bi-check-lg"></i> <?= $modoEdicao ? 'Salvar alterações' : 'Criar produto' ?>
        </button>
    </div>
</form>

<script>
    (function () {
        document.querySelectorAll('.js-categoria').forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                const radio = document.querySelector('.js-principal[value="' + checkbox.value + '"]');

                if (!radio) {
                    return;
                }

                if (checkbox.checked) {
                    const algumMarcado = document.querySelector('.js-principal:checked');

                    if (!algumMarcado) {
                        radio.checked = true;
                    }
                } else if (radio.checked) {
                    radio.checked = false;
                }
            });
        });
    })();
</script>
