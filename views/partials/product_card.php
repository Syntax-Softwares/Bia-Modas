<?php
$emOferta = ($produto['oferta_percentual'] ?? null) !== null && (float) $produto['oferta_percentual'] > 0;
$desconto = $emOferta ? (int) round((float) $produto['oferta_percentual'] * 100) : 0;
$precoOriginal = $produto['preco_original'] ?? null;
$urlProduto = '/produto?id=' . (int) $produto['id_produto'];
$textoBadge = $emOferta ? '-' . $desconto . '%' : ($badge ?? null);
$favoritado = in_array((int) $produto['id_produto'], $favoritosIds ?? [], true);
?>
<?php if (empty($semCarousel)): ?>
<div class="carousel-slide">
<?php endif; ?>
    <article class="product-card<?= $emOferta ? ' is-on-sale' : '' ?>">
        <div class="product-image">
            <a href="<?= e($urlProduto) ?>" style="display:block;width:100%;height:100%;">
                <img src="<?= e($produto['imagem']) ?>" alt="<?= e($produto['nome']) ?>" loading="lazy">
            </a>
            <?php if ($textoBadge !== null && $textoBadge !== ''): ?>
                <span class="product-badge<?= $emOferta ? ' is-sale' : '' ?>"><?= e($textoBadge) ?></span>
            <?php endif; ?>
            <div class="product-actions">
                <button
                    type="button"
                    class="product-favorite<?= $favoritado ? ' active' : '' ?>"
                    data-favorito="<?= (int) $produto['id_produto'] ?>"
                    aria-pressed="<?= $favoritado ? 'true' : 'false' ?>"
                    aria-label="<?= $favoritado ? 'Remover dos favoritos' : 'Salvar nos favoritos' ?>"
                    title="<?= $favoritado ? 'Remover dos favoritos' : 'Salvar nos favoritos' ?>"
                >
                    <i class="bi <?= $favoritado ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
                </button>
                <a href="<?= e($urlProduto) ?>" class="product-view-link" aria-label="Visualizar <?= e($produto['nome']) ?>">
                    <i class="bi bi-eye"></i>
                </a>
            </div>
        </div>
        <div class="product-info">
            <div class="product-category"><?= e($produto['categoria']) ?></div>
            <h3 class="product-title">
                <a href="<?= e($urlProduto) ?>" style="color:inherit;text-decoration:none;"><?= e($produto['nome']) ?></a>
            </h3>
            <div class="product-price">
                <?php if ($emOferta && $precoOriginal !== null): ?>
                    <span class="price-original"><?= money($precoOriginal) ?></span>
                <?php endif; ?>
                <span class="price-current"><?= money($produto['valor']) ?></span>
            </div>
            <?php if ($emOferta && $precoOriginal !== null): ?>
                <span class="price-savings">Economize <?= money($precoOriginal - (float) $produto['valor']) ?></span>
            <?php endif; ?>
        </div>
    </article>
<?php if (empty($semCarousel)): ?>
</div>
<?php endif; ?>
