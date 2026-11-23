<?php
require __DIR__ . '/partials/header.php';

$valorCampo = static fn (string $campo): string => (string) ($form[$campo] ?? '');
$erroCampo = static fn (string $campo): string => isset($errors[$campo])
    ? '<div class="admin-field-error">' . e($errors[$campo]) . '</div>'
    : '';
$imagemPreview = $valorCampo('imagem') !== '' ? $valorCampo('imagem') : '/img/logoBiaModas.png';
?>
<section class="admin-panel">
    <form method="post" action="/admin/produtos/salvar" class="admin-form">
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
                <div class="admin-size-grid">
                    <?php foreach (($tamanhos ?? []) as $tamanho): ?>
                        <?php
                        $idTamanho = (int) $tamanho['id_tamanho'];
                        $selecionado = array_key_exists($idTamanho, $form['tamanhos'] ?? []);
                        $estoqueTamanho = $selecionado ? (int) $form['tamanhos'][$idTamanho] : 0;
                        ?>
                        <div class="admin-size-item">
                            <label class="admin-size-check">
                                <input type="checkbox" name="tamanhos[<?= $idTamanho ?>][selecionado]" value="1" <?= $selecionado ? 'checked' : '' ?> data-size-check>
                                <strong><?= e($tamanho['nome']) ?></strong>
                            </label>
                            <input type="number" name="tamanhos[<?= $idTamanho ?>][estoque]" value="<?= $estoqueTamanho ?>" min="0" step="1" aria-label="Estoque do tamanho <?= e($tamanho['nome']) ?>" data-size-stock <?= $selecionado ? '' : 'disabled' ?>>
                        </div>
                    <?php endforeach; ?>
                </div>
                <small>Marque os tamanhos disponíveis e informe o estoque de cada um.</small>
                <?= $erroCampo('tamanhos') ?>
            </div>

            <div class="admin-field admin-field-span-2">
                <label for="produto-descricao">Descrição</label>
                <textarea id="produto-descricao" name="descricao" rows="3" maxlength="255"><?= e($valorCampo('descricao')) ?></textarea>
                <?= $erroCampo('descricao') ?>
            </div>

            <div class="admin-field admin-field-span-2">
                <label for="produto-imagem">Caminho da imagem</label>
                <input type="text" id="produto-imagem" name="imagem" value="<?= e($valorCampo('imagem')) ?>" maxlength="255" placeholder="/img/produtos/foto.jpg" data-image-input>
                <small>Use um caminho já disponível em <code>/public</code>.</small>
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
