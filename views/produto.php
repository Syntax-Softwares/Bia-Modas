<?php $produto = $data['produto']; ?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="<?= e($produto['nome']) ?> - Bia Modas." />
    <title><?= e($produto['nome']) ?> | Bia Modas</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/css/base.css">
    <link rel="stylesheet" href="/css/layout.css">
    <link rel="stylesheet" href="/css/components.css">
    <link rel="stylesheet" href="/css/responsive.css">
    <link rel="stylesheet" href="/css/produto.css">
    <link rel="stylesheet" href="/css/auth.css">
</head>
<body>
    <?php require __DIR__ . '/partials/site_header.php'; ?>

    <section class="products-section product-detail-section">
        <div class="container">
            <?php require __DIR__ . '/partials/alerts.php'; ?>

            <nav class="breadcrumb product-breadcrumb" aria-label="Navegação">
                <a href="/">Início</a>
                <span>/</span>
                <a href="/categoria?categoria=<?= rawurlencode($produto['categoria']) ?>"><?= e($produto['categoria']) ?></a>
                <span>/</span>
                <span><?= e($produto['nome']) ?></span>
            </nav>

            <div class="row">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="product-gallery">
                        <div class="product-gallery-main">
                            <img src="<?= e($produto['imagem']) ?>" alt="<?= e($produto['nome']) ?>">
                            <?php if ($produto['oferta_percentual'] !== null): ?>
                                <span class="product-badge is-sale">
                                    -<?= (int) round((float) $produto['oferta_percentual'] * 100) ?>%
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div id="product-category"><?= e($produto['categoria']) ?></div>
                    <h1 id="product-name"><?= e($produto['nome']) ?></h1>

                    <div class="product-price product-detail-price">
                        <?php if ($produto['preco_original'] !== null): ?>
                            <span class="price-original"><?= money($produto['preco_original']) ?></span>
                        <?php endif; ?>
                        <span class="price-current"><?= money($produto['valor']) ?></span>
                    </div>

                    <p class="product-description">
                        <?= e($produto['descricao'] ?: 'Produto exclusivo da coleção Bia Modas, com caimento confortável e acabamento de qualidade.') ?>
                    </p>

                    <div class="product-options">
                        <div class="option-label">
                            <span class="option-label-text">Tamanho:</span>
                            <strong><?= e($produto['tamanho'] ?: 'Consultar') ?></strong>
                        </div>
                        <?php if ($produto['estoque'] > 0): ?>
                            <p class="product-stock"><i class="bi bi-check-circle me-1"></i>Disponível em estoque</p>
                        <?php else: ?>
                            <p class="product-stock out"><i class="bi bi-x-circle me-1"></i>Produto sem estoque</p>
                        <?php endif; ?>
                    </div>

                    <div class="product-buttons">
                        <a
                            class="btn-primary-solid"
                            href="https://api.whatsapp.com/send?phone=5514999999999&text=<?= rawurlencode('Olá! Tenho interesse no produto: ' . $produto['nome']) ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <i class="bi bi-whatsapp me-2"></i>Comprar pelo WhatsApp
                        </a>
                        <a class="btn-outline-custom" href="/categoria?categoria=<?= rawurlencode($produto['categoria']) ?>">
                            <i class="bi bi-arrow-left me-2"></i>Voltar ao catálogo
                        </a>
                    </div>

                    <div class="product-features">
                        <div class="row">
                            <div class="col-6 mb-3">
                                <span class="feature-title"><i class="bi bi-truck me-2"></i>Frete Grátis</span>
                                <p class="feature-desc">Para Jaú acima de R$ 199</p>
                            </div>
                            <div class="col-6 mb-3">
                                <span class="feature-title"><i class="bi bi-arrow-repeat me-2"></i>Troca Fácil</span>
                                <p class="feature-desc">7 dias para trocar</p>
                            </div>
                            <div class="col-6">
                                <span class="feature-title"><i class="bi bi-shield-check me-2"></i>Compra Segura</span>
                                <p class="feature-desc">Ambiente protegido</p>
                            </div>
                            <div class="col-6">
                                <span class="feature-title"><i class="bi bi-credit-card me-2"></i>Até 12x</span>
                                <p class="feature-desc">Parcelamento em cartões</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php require __DIR__ . '/partials/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="/js/site.js"></script>
</body>
</html>
