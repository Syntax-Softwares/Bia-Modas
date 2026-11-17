<?php
/**
 * Cartao de produto compartilhado por home, favoritos e catalogo.
 *
 * Variaveis opcionais: $layout ('carousel'|'grade'), $favoritosIds,
 * $carrinhoIds, $badge e $semCarousel.
 */
$layout = $layout ?? (empty($semCarousel) ? 'carousel' : 'grade');
$idProduto = (int) $produto['id_produto'];
$favoritosIds = is_array($favoritosIds ?? null) ? $favoritosIds : [];
$carrinhoIds = is_array($carrinhoIds ?? null) ? $carrinhoIds : [];
$ehFavorito = in_array($idProduto, array_map('intval', $favoritosIds), true);
$noCarrinho = in_array($idProduto, array_map('intval', $carrinhoIds), true);

$percentual = $produto['oferta_percentual'] ?? null;
$emOferta = $percentual !== null && (float) $percentual > 0;
$desconto = $emOferta ? (int) round((float) $percentual * 100) : 0;
$esgotado = (int) ($produto['estoque'] ?? 0) <= 0;

$precoAtual = (float) $produto['valor'];
$precoOriginal = $produto['preco_original'] ?? preco_original($precoAtual, $percentual === null ? null : (float) $percentual);

$imagem = (string) ($produto['imagem'] ?? '');
$imagem = $imagem !== '' ? $imagem : '/img/logoBiaModas.png';
$categoria = (string) ($produto['categoria'] ?? 'Bia Modas');
$categoria = $categoria !== '' ? $categoria : 'Bia Modas';
$urlProduto = '/produto?id=' . $idProduto;

$textoBadge = $emOferta ? '-' . $desconto . '%' : ($badge ?? null);
?>
<?php if ($layout === 'grade'): ?>
<div class="col-12 col-sm-6 col-md-4 col-lg-3 mb-4" data-produto-card="<?= $idProduto ?>">
<?php else: ?>
<div class="carousel-slide">
<?php endif; ?>
    <article class="product-card<?= $emOferta ? ' is-on-sale' : '' ?><?= $esgotado ? ' is-out' : '' ?>">
        <div class="product-image">
            <a href="<?= e($urlProduto) ?>" style="display:block;width:100%;height:100%;" aria-label="Ver <?= e($produto['nome']) ?>">
                <img src="<?= e($imagem) ?>" alt="<?= e($produto['nome']) ?>" loading="lazy">
            </a>

            <?php if ($textoBadge !== null && $textoBadge !== ''): ?>
                <span class="product-badge<?= $emOferta ? ' is-sale' : '' ?>"><?= e($textoBadge) ?></span>
            <?php endif; ?>

            <?php if ($esgotado): ?>
                <span class="product-badge is-out">Esgotado</span>
            <?php endif; ?>

            <div class="product-actions">
                <a href="<?= e($urlProduto) ?>" class="product-view-link" aria-label="Visualizar <?= e($produto['nome']) ?>">
                    <i class="bi bi-eye"></i>
                </a>
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
                        <?= $esgotado ? 'disabled' : '' ?>
                        aria-label="<?= $esgotado ? 'Produto esgotado' : ($noCarrinho ? 'Remover ' . e($produto['nome']) . ' do carrinho' : 'Adicionar ' . e($produto['nome']) . ' ao carrinho') ?>">
                    <i class="bi bi-bag-<?= $noCarrinho ? 'check-fill' : 'plus' ?>"></i>
                </button>
            </div>
        </div>

        <div class="product-info">
            <div class="product-category"><?= e($categoria) ?></div>
            <h3 class="product-title">
                <a href="<?= e($urlProduto) ?>" style="color:inherit;text-decoration:none;"><?= e($produto['nome']) ?></a>
            </h3>
            <div class="product-price">
                <?php if ($emOferta && $precoOriginal !== null): ?>
                    <span class="price-original"><?= money($precoOriginal) ?></span>
                <?php endif; ?>
                <span class="price-current"><?= money($precoAtual) ?></span>
            </div>
            <?php if ($emOferta && $precoOriginal !== null): ?>
                <span class="price-savings">Economize <?= money($precoOriginal - $precoAtual) ?></span>
            <?php endif; ?>

            <?php if (($produto['tamanho'] ?? '') !== '' || ($produto['cor'] ?? null) !== null): ?>
                <div class="product-meta">
                    <?php if (($produto['tamanho'] ?? '') !== ''): ?>
                        <span><i class="bi bi-rulers"></i> <?= e($produto['tamanho']) ?></span>
                    <?php endif; ?>
                    <?php if (($produto['cor'] ?? null) !== null): ?>
                        <span><i class="bi bi-palette"></i> <?= e($produto['cor']) ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </article>
</div>
