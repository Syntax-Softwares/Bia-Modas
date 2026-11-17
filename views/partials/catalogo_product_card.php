<?php
/** @var array $produto */
$emOferta = $produto['oferta_percentual'] !== null && (float) $produto['oferta_percentual'] > 0;
$desconto = $emOferta ? Catalogo::descontoPercentual($produto['oferta_percentual']) : 0;
$precoOriginal = $produto['preco_original'] ?? null;
$esgotado = (int) $produto['estoque'] <= 0;
$urlProduto = '/produto?id=' . (int) $produto['id_produto'];
?>
<article class="product-card<?= $emOferta ? ' is-on-sale' : '' ?><?= $esgotado ? ' is-out' : '' ?>">
    <div class="product-image">
        <a href="<?= Html::e($urlProduto) ?>" aria-label="Ver <?= Html::e($produto['nome']) ?>">
            <img src="<?= Html::e($produto['imagem']) ?>" alt="<?= Html::e($produto['nome']) ?>" loading="lazy">
        </a>

        <?php if ($emOferta): ?>
            <span class="product-badge is-sale">-<?= $desconto ?>%</span>
        <?php endif; ?>

        <?php if ($esgotado): ?>
            <span class="product-badge is-out">Esgotado</span>
        <?php endif; ?>

        <div class="product-actions">
            <a href="<?= Html::e($urlProduto) ?>" class="product-view-link" aria-label="Visualizar <?= Html::e($produto['nome']) ?>">
                <i class="bi bi-eye"></i>
            </a>
        </div>
    </div>

    <div class="product-info">
        <div class="product-category"><?= Html::e($produto['categoria']) ?></div>

        <h3 class="product-title">
            <a class="product-title-link" href="<?= Html::e($urlProduto) ?>"><?= Html::e($produto['nome']) ?></a>
        </h3>

        <div class="product-price">
            <?php if ($emOferta && $precoOriginal !== null): ?>
                <span class="price-original"><?= Catalogo::money($precoOriginal) ?></span>
            <?php endif; ?>
            <span class="price-current"><?= Catalogo::money($produto['valor']) ?></span>
        </div>

        <?php if ($emOferta && $precoOriginal !== null): ?>
            <span class="price-savings">Economize <?= Catalogo::money($precoOriginal - (float) $produto['valor']) ?></span>
        <?php endif; ?>

        <?php if ($produto['tamanho'] !== '' || $produto['cor'] !== null): ?>
            <div class="product-meta">
                <?php if ($produto['tamanho'] !== ''): ?>
                    <span><i class="bi bi-rulers"></i> <?= Html::e($produto['tamanho']) ?></span>
                <?php endif; ?>
                <?php if ($produto['cor'] !== null): ?>
                    <span><i class="bi bi-palette"></i> <?= Html::e($produto['cor']) ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</article>
