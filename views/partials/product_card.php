<?php

$layout = $layout ?? 'carousel';
$idProduto = (int) $produto['id_produto'];
$ehFavorito = in_array($idProduto, $favoritosIds ?? [], true);
$noCarrinho = in_array($idProduto, $carrinhoIds ?? [], true);

$percentual = $produto['oferta_percentual'] ?? null;
$emOferta = $percentual !== null && (float) $percentual > 0;
$desconto = $emOferta ? (int) round((float) $percentual * 100) : 0;

$precoAtual = (float) $produto['valor'];
$precoOriginal = $emOferta ? round($precoAtual / (1 - (float) $percentual), 2) : null;

$imagem = $produto['imagem'] ?: '/img/logoBiaModas.png';
$categoria = $produto['categoria'] ?: 'Bia Modas';
?>
<?php if ($layout === 'grade'): ?>
<div class="col-12 col-sm-6 col-md-4 col-lg-3 mb-4" data-produto-card="<?= $idProduto ?>">
<?php else: ?>
<div class="carousel-slide">
<?php endif; ?>
    <article class="product-card<?= $emOferta ? ' is-on-sale' : '' ?>">
        <div class="product-image">
            <img src="<?= e($imagem) ?>" alt="<?= e($produto['nome']) ?>" loading="lazy">

            <?php if ($emOferta): ?>
                <span class="product-badge is-sale"><i class="bi bi-tag-fill"></i>-<?= $desconto ?>%</span>
            <?php endif; ?>

            <div class="product-actions">
                <button type="button"
                        class="favorito-btn<?= $ehFavorito ? ' active' : '' ?>"
                        data-favorito
                        data-produto-id="<?= $idProduto ?>"
                        data-produto-nome="<?= e($produto['nome']) ?>"
                        aria-pressed="<?= $ehFavorito ? 'true' : 'false' ?>"
                        aria-label="<?= $ehFavorito ? 'Remover dos favoritos' : 'Adicionar aos favoritos' ?>">
                    <i class="bi bi-heart<?= $ehFavorito ? '-fill' : '' ?>"></i>
                </button>
                <button type="button"
                        class="botao-carrinho<?= $noCarrinho ? ' active' : '' ?>"
                        data-adicionar
                        data-produto-id="<?= $idProduto ?>"
                        data-produto-nome="<?= e($produto['nome']) ?>"
                        aria-pressed="<?= $noCarrinho ? 'true' : 'false' ?>"
                        aria-label="<?= $noCarrinho ? 'Remover ' . e($produto['nome']) . ' do carrinho' : 'Adicionar ' . e($produto['nome']) . ' ao carrinho' ?>">
                    <i class="bi bi-bag-<?= $noCarrinho ? 'check-fill' : 'plus' ?>"></i>
                </button>
            </div>
        </div>

        <div class="product-info">
            <div class="product-category"><?= e($categoria) ?></div>
            <h3 class="product-title"><?= e($produto['nome']) ?></h3>
            <div class="product-price">
                <?php if ($precoOriginal !== null): ?>
                    <span class="price-original"><?= money($precoOriginal) ?></span>
                <?php endif; ?>
                <span class="price-current"><?= money($precoAtual) ?></span>
            </div>
            <?php if ($precoOriginal !== null): ?>
                <span class="price-savings">Economize <?= money($precoOriginal - $precoAtual) ?></span>
            <?php endif; ?>
        </div>
    </article>
</div>
