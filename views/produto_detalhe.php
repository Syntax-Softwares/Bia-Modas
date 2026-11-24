<?php
/** @var array $produto */
/** @var array $imagens */
/** @var array|null $fotoSelecionada */
/** @var array $sugestoes */
/** @var array $flash */

$buscaAtual = '';
$emOferta = $produto['oferta_percentual'] !== null && (float) $produto['oferta_percentual'] > 0;
$desconto = $emOferta ? Catalogo::descontoPercentual($produto['oferta_percentual']) : 0;
$precoOriginal = $produto['preco_original'] ?? null;
$esgotado = (int) $produto['estoque'] <= 0;
$idProduto = (int) $produto['id_produto'];
$ehFavorito = in_array($idProduto, array_map('intval', $favoritosIds ?? []), true);
$noCarrinho = in_array($idProduto, array_map('intval', $carrinhoIds ?? []), true);
$imagemPrincipal = $fotoSelecionada['caminho_imagem'] ?? $produto['imagem'];
$idFotoSelecionada = $fotoSelecionada['id_imagem'] ?? null;
$urlCategoria = '/categoria?' . http_build_query(['categoria' => $produto['categoria']], '', '&', PHP_QUERY_RFC3986);
$temMedidas = $produto['medida_busto'] !== null
    || $produto['medida_cintura'] !== null
    || $produto['medida_quadril'] !== null;
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= Html::e($produto['nome']) ?> - Bia Modas.">
    <meta name="csrf-token" content="<?= Html::e(Sessao::csrfToken()) ?>">
    <title><?= Html::e($produto['nome']) ?> | Bia Modas</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lucide-static@latest/font/lucide.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/css/base.css">
    <link rel="stylesheet" href="/css/layout.css">
    <link rel="stylesheet" href="/css/components.css">
    <link rel="stylesheet" href="/css/responsive.css">
    <link rel="stylesheet" href="/css/auth.css">
    <link rel="stylesheet" href="/css/produto.css">
    <link rel="stylesheet" href="/css/catalogo.css">
    <link rel="stylesheet" href="/css/carrinho.css">
</head>
<body data-page="produto">
    <?php require __DIR__ . '/partials/catalogo_header.php'; ?>

    <section class="products-section product-detail-section">
        <div class="container">
            <?php require __DIR__ . '/partials/catalogo_alerts.php'; ?>

            <nav class="breadcrumb product-breadcrumb" aria-label="Navegação">
                <a href="/">Início</a>
                <i class="icon-chevron-right" aria-hidden="true"></i>
                <a href="<?= Html::e($urlCategoria) ?>"><?= Html::e($produto['categoria']) ?></a>
                <i class="icon-chevron-right" aria-hidden="true"></i>
                <span><?= Html::e($produto['nome']) ?></span>
            </nav>

            <div class="row">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="product-gallery">
                        <div class="product-gallery-main">
                            <img src="<?= Html::e($imagemPrincipal) ?>" alt="<?= Html::e($produto['nome']) ?>">

                            <?php if ($emOferta): ?>
                                <span class="product-badge is-sale">-<?= $desconto ?>%</span>
                            <?php endif; ?>
                        </div>

                        <?php if (count($imagens) > 1): ?>
                            <div class="product-gallery-thumbs">
                                <?php foreach ($imagens as $imagem): ?>
                                    <?php $ativa = (int) $imagem['id_imagem'] === (int) $idFotoSelecionada; ?>
                                    <a
                                        href="/produto?id=<?= (int) $produto['id_produto'] ?>&foto=<?= (int) $imagem['id_imagem'] ?>"
                                        class="gallery-thumb<?= $ativa ? ' active' : '' ?>"
                                        aria-label="Ver foto <?= (int) $imagem['id_imagem'] ?>"
                                    >
                                        <img src="<?= Html::e($imagem['caminho_imagem']) ?>" alt="<?= Html::e($produto['nome']) ?> - foto" loading="lazy">
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-lg-6">
                    <a href="<?= Html::e($urlCategoria) ?>" class="product-detail-category">
                        <?= Html::e($produto['categoria']) ?>
                    </a>

                    <h1 id="product-name"><?= Html::e($produto['nome']) ?></h1>
                    <p class="product-sku">SKU: <?= Html::e($produto['sku']) ?></p>

                    <div class="product-price product-detail-price">
                        <?php if ($emOferta && $precoOriginal !== null): ?>
                            <span class="price-original"><?= Catalogo::money($precoOriginal) ?></span>
                        <?php endif; ?>
                        <span class="price-current"><?= Catalogo::money($produto['valor']) ?></span>
                    </div>

                    <?php if ($emOferta && $precoOriginal !== null): ?>
                        <p class="product-savings">
                            Economize <?= Catalogo::money($precoOriginal - (float) $produto['valor']) ?>
                        </p>
                    <?php endif; ?>

                    <p class="product-description">
                        <?= Html::e($produto['descricao'] ?: 'Produto exclusivo da coleção Bia Modas, com caimento confortável e acabamento de qualidade.') ?>
                    </p>

                    <?php if ($produto['cor'] !== null): ?>
                        <div class="product-options">
                            <div class="option-label">
                                <span class="option-label-text">Cor:</span>
                                <strong><?= Html::e($produto['cor']) ?></strong>
                            </div>
                            <div class="color-options">
                                <span
                                    class="color-option active"
                                    style="background: <?= Html::e(Catalogo::corHex($produto['cor'])) ?>;<?= Catalogo::corEhClara($produto['cor']) ? 'border:1px solid #ddd;' : '' ?>"
                                    title="<?= Html::e($produto['cor']) ?>"
                                ></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($produto['variacoes'])): ?>
                        <div class="product-options">
                            <div class="option-label">
                                <span class="option-label-text">Tamanho:</span>
                                <strong data-size-selected-label>Selecione</strong>
                            </div>
                            <div class="size-options" data-size-options>
                                <?php foreach ($produto['variacoes'] as $variacao): ?>
                                    <?php $semEstoque = (int) $variacao['estoque'] <= 0; ?>
                                    <button
                                        type="button"
                                        class="size-option<?= $semEstoque ? ' is-out' : '' ?>"
                                        data-size-id="<?= (int) $variacao['id_produto_tamanho'] ?>"
                                        data-size-nome="<?= Html::e($variacao['tamanho']) ?>"
                                        data-size-estoque="<?= (int) $variacao['estoque'] ?>"
                                        <?= $semEstoque ? 'disabled' : '' ?>
                                    ><?= Html::e($variacao['tamanho']) ?></button>
                                <?php endforeach; ?>
                            </div>
                            <p class="product-stock" data-size-stock></p>
                        </div>
                    <?php endif; ?>

                    <?php if ($temMedidas): ?>
                        <div class="product-measures">
                            <h2>Medidas da peça</h2>
                            <ul>
                                <?php if ($produto['medida_busto'] !== null): ?>
                                    <li><span>Busto</span><strong><?= Html::e($produto['medida_busto']) ?> cm</strong></li>
                                <?php endif; ?>
                                <?php if ($produto['medida_cintura'] !== null): ?>
                                    <li><span>Cintura</span><strong><?= Html::e($produto['medida_cintura']) ?> cm</strong></li>
                                <?php endif; ?>
                                <?php if ($produto['medida_quadril'] !== null): ?>
                                    <li><span>Quadril</span><strong><?= Html::e($produto['medida_quadril']) ?> cm</strong></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="product-buttons">
                        <?php if ($esgotado): ?>
                            <button type="button" class="btn-primary-solid" disabled>
                                <i class="icon-circle-x"></i>
                                <span>Indisponível</span>
                            </button>
                        <?php else: ?>
                            <button
                                type="button"
                                class="btn-primary-solid"
                                data-adicionar
                                data-produto-id="<?= $idProduto ?>"
                                data-produto-tamanho-id=""
                                data-produto-nome="<?= Html::e($produto['nome']) ?>"
                                data-requer-tamanho="1"
                            >
                                <i class="icon-shopping-bag"></i>
                                <span data-cart-label>Adicionar ao carrinho</span>
                            </button>
                        <?php endif; ?>

                        <button
                            type="button"
                            class="btn-outline-catalogo favorito-btn<?= $ehFavorito ? ' active' : '' ?>"
                            data-favorito
                            data-produto-id="<?= $idProduto ?>"
                            data-produto-nome="<?= Html::e($produto['nome']) ?>"
                            aria-pressed="<?= $ehFavorito ? 'true' : 'false' ?>"
                        >
                            <i class="icon-heart"></i>
                            <span data-favorito-label><?= $ehFavorito ? 'Nos favoritos' : 'Favoritar' ?></span>
                        </button>
                    </div>

                    <div class="product-features">
                        <div class="row">
                            <div class="col-6 mb-3">
                                <span class="feature-title"><i class="icon-truck me-2"></i>Frete Grátis</span>
                                <p class="feature-desc">Para Jaú acima de R$ 199</p>
                            </div>
                            <div class="col-6 mb-3">
                                <span class="feature-title"><i class="icon-repeat me-2"></i>Troca Fácil</span>
                                <p class="feature-desc">7 dias para trocar</p>
                            </div>
                            <div class="col-6">
                                <span class="feature-title"><i class="icon-shield-check me-2"></i>Compra Segura</span>
                                <p class="feature-desc">Ambiente protegido</p>
                            </div>
                            <div class="col-6">
                                <span class="feature-title"><i class="icon-credit-card me-2"></i>Até 12x</span>
                                <p class="feature-desc">Parcelamento em cartões</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php require __DIR__ . '/partials/catalogo_footer.php'; ?>
</body>
</html>
