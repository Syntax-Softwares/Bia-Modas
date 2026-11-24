<?php
require __DIR__ . '/partials/header.php';

$valorCampo = static fn (string $campo): string => (string) ($form[$campo] ?? '');
$erroCampo = static fn (string $campo): string => isset($errors[$campo])
    ? '<div class="admin-field-error">' . e($errors[$campo]) . '</div>'
    : '';
$imagemPreview = $valorCampo('imagem') !== '' ? $valorCampo('imagem') : '/img/logoBiaModas.png';
?>
<section class="admin-panel">
    <form method="post" action="/admin/produtos/salvar" class="admin-form" enctype="multipart/form-data" novalidate>
        <?= Sessao::csrfCampo() ?>
        <?php if (!empty($form['id_produto'])): ?>
            <input type="hidden" name="id_produto" value="<?= (int) $form['id_produto'] ?>">
        <?php endif; ?>

        <div class="admin-form-grid">
            <div class="admin-field admin-field-span-2">
                <label for="produto-nome">Nome do produto <span>*</span></label>
                <input type="text" id="produto-nome" name="nome" value="<?= e($valorCampo('nome')) ?>" maxlength="255" required>
                <?= $erroCampo('nome') ?>
            </div>

            <div class="admin-field">
                <label for="produto-sku">SKU <span>*</span></label>
                <input type="text" id="produto-sku" name="sku" value="<?= e($valorCampo('sku')) ?>" maxlength="50" required>
                <?= $erroCampo('sku') ?>
            </div>

            <div class="admin-field">
                <label for="produto-categoria">Categoria <span>*</span></label>
                <select id="produto-categoria" name="categoria_id" required>
                    <option value="">Selecione</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?= (int) $categoria['id_categoria'] ?>" <?= (string) $categoria['id_categoria'] === $valorCampo('categoria_id') ? 'selected' : '' ?>>
                            <?= e($categoria['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?= $erroCampo('categoria_id') ?>
            </div>

            <div class="admin-field">
                <label for="produto-valor">Preço (R$) <span>*</span></label>
                <input type="text" id="produto-valor" name="valor" value="<?= e($valorCampo('valor')) ?>" inputmode="decimal" placeholder="199,90" required>
                <?= $erroCampo('valor') ?>
            </div>

            <div class="admin-field">
                <label for="produto-oferta">Desconto (%)</label>
                <input type="number" id="produto-oferta" name="oferta_percentual" value="<?= e($valorCampo('oferta_percentual')) ?>" min="1" max="99" step="1" placeholder="0">
                <small>Deixe vazio para não colocar em promoção.</small>
                <?= $erroCampo('oferta_percentual') ?>
            </div>

            <div class="admin-field">
                <label for="produto-cor">Cor</label>
                <input type="text" id="produto-cor" name="cor" value="<?= e($valorCampo('cor')) ?>" maxlength="30" placeholder="Preto, Bege...">
                <?= $erroCampo('cor') ?>
            </div>

            <div class="admin-field">
                <label for="produto-estilo">Estilo</label>
                <input type="text" id="produto-estilo" name="estilo" value="<?= e($valorCampo('estilo')) ?>" maxlength="30" placeholder="Casual, Elegante...">
                <?= $erroCampo('estilo') ?>
            </div>

            <div class="admin-field admin-field-span-2">
                <label>Tamanhos e estoque <span>*</span></label>
                <?php
                $selecionados = [];
                foreach (($tamanhos ?? []) as $tamanho) {
                    $idTamanho = (int) $tamanho['id_tamanho'];
                    if (array_key_exists($idTamanho, $form['tamanhos'] ?? [])) {
                        $selecionados[$idTamanho] = [
                            'nome' => (string) $tamanho['nome'],
                            'estoque' => (int) $form['tamanhos'][$idTamanho],
                        ];
                    }
                }
                ?>
                <div class="admin-size-selector" data-size-selector>
                    <div class="admin-size-tags" data-size-tags>
                        <?php foreach ($selecionados as $idTamanho => $tamanhoSelecionado): ?>
                            <span class="admin-size-tag" data-size-tag="<?= $idTamanho ?>">
                                <?= e($tamanhoSelecionado['nome']) ?>
                                <button type="button" class="admin-size-tag-remove" data-size-remove aria-label="Remover tamanho <?= e($tamanhoSelecionado['nome']) ?>">&times;</button>
                                <input type="hidden" name="tamanhos[<?= $idTamanho ?>][selecionado]" value="1" data-size-hidden>
                            </span>
                        <?php endforeach; ?>
                        <select class="admin-size-picker" data-size-picker aria-label="Adicionar tamanho">
                            <option value="">Adicionar tamanho...</option>
                            <?php foreach (($tamanhos ?? []) as $tamanho): ?>
                                <?php $idTamanho = (int) $tamanho['id_tamanho']; ?>
                                <option value="<?= $idTamanho ?>" data-nome="<?= e($tamanho['nome']) ?>" <?= array_key_exists($idTamanho, $selecionados) ? 'disabled' : '' ?>>
                                    <?= e($tamanho['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="admin-size-stocks" data-size-stocks>
                    <?php foreach ($selecionados as $idTamanho => $tamanhoSelecionado): ?>
                        <label class="admin-size-stock" data-size-stock-row="<?= $idTamanho ?>">
                            <span><?= e($tamanhoSelecionado['nome']) ?></span>
                            <input type="number" name="tamanhos[<?= $idTamanho ?>][estoque]" value="<?= $tamanhoSelecionado['estoque'] ?>" min="0" step="1" aria-label="Estoque do tamanho <?= e($tamanhoSelecionado['nome']) ?>">
                        </label>
                    <?php endforeach; ?>
                </div>
                <p class="admin-size-empty" data-size-empty <?= $selecionados !== [] ? 'hidden' : '' ?>>Nenhum tamanho selecionado.</p>
                <small>Selecione os tamanhos disponíveis e informe o estoque de cada um. Precisa de outro? <a href="/admin/tamanhos" target="_blank" rel="noopener">Cadastre em Tamanhos</a>.</small>
                <?= $erroCampo('tamanhos') ?>
            </div>

            <div class="admin-field admin-field-span-2">
                <label for="produto-descricao">Descrição</label>
                <textarea id="produto-descricao" name="descricao" rows="3" maxlength="255"><?= e($valorCampo('descricao')) ?></textarea>
                <?= $erroCampo('descricao') ?>
            </div>

            <div class="admin-field admin-field-span-2">
                <label for="produto-imagem">Imagem do produto</label>
                <input type="file" id="produto-imagem" name="imagem_arquivo" accept="image/png,image/jpeg,image/webp" data-image-input>
                <small>JPG, PNG ou WebP, até 2 MB.<?= $isEdicao ? ' Deixe vazio para manter a imagem atual.' : '' ?></small>
                <input type="hidden" name="imagem_atual" value="<?= e($valorCampo('imagem')) ?>">
                <?= $erroCampo('imagem') ?>
            </div>

            <div class="admin-field admin-field-span-2">
                <label class="admin-checkbox">
                    <input type="checkbox" name="ativo" value="1" <?= !empty($form['ativo']) ? 'checked' : '' ?>>
                    <span>Produto ativo no catálogo</span>
                </label>
            </div>
        </div>

        <div class="admin-form-preview">
            <img src="<?= e($imagemPreview) ?>" alt="Prévia da imagem" data-image-preview>
        </div>

        <div class="admin-form-actions">
            <a href="/admin/produtos" class="admin-btn admin-btn-ghost">Cancelar</a>
            <button type="submit" class="admin-btn admin-btn-primary">
                <i class="icon-check"></i> <?= $isEdicao ? 'Salvar alterações' : 'Cadastrar produto' ?>
            </button>
        </div>
    </form>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
