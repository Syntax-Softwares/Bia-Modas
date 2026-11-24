<?php
$resumo = $data['resumo'];
$itens = $resumo['itens'];
$favoritosIds = $data['favoritosIds'] ?? [];
$favoritosTotal = $data['favoritosTotal'] ?? 0;
$quantidadeCarrinho = $data['quantidadeCarrinho'] ?? 0;
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <link rel="icon" href="<?= asset('/favicon.ico') ?>" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('/img/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= asset('/img/favicon-192x192.png') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= asset('/img/apple-touch-icon.png') ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Bia Modas - Meu Carrinho." />
    <meta name="csrf-token" content="<?= e(Sessao::csrfToken()) ?>" />
    <title>Meu Carrinho | Bia Modas</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lucide-static@latest/font/lucide.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= asset('/css/base.css') ?>">
    <link rel="stylesheet" href="<?= asset('/css/layout.css') ?>">
    <link rel="stylesheet" href="<?= asset('/css/components.css') ?>">
    <link rel="stylesheet" href="<?= asset('/css/responsive.css') ?>">
    <link rel="stylesheet" href="<?= asset('/css/categoria.css') ?>">
    <link rel="stylesheet" href="<?= asset('/css/carrinho.css') ?>">
</head>
<body data-page="carrinho">
    <?php require __DIR__ . '/partials/site_header.php'; ?>
    <?php require __DIR__ . '/partials/cart_offcanvas.php'; ?>

    <header class="page-header">
        <div class="container">
            <nav class="breadcrumb" aria-label="Navegação">
                <a href="/">Início</a>
                <span>/</span>
                <span>Carrinho</span>
            </nav>
            <h1>Meu carrinho</h1>
            <p>Revise as peças e finalize seu pedido</p>
        </div>
    </header>

    <section class="products-section">
        <div class="container">
            <?php require __DIR__ . '/partials/alerts.php'; ?>

            <div class="empty-state" id="carrinho-vazio" <?= $itens === [] ? '' : 'hidden' ?>>
                <i class="icon-shopping-bag"></i>
                <h2>Seu carrinho está vazio</h2>
                <p>Explore a coleção e adicione as peças que você gostou.</p>
                <a href="/" class="btn-outline-custom btn-outline-dark mt-3">Ver produtos</a>
            </div>

            <div class="carrinho-layout" id="carrinho-conteudo" <?= $itens === [] ? 'hidden' : '' ?>>
                <div class="carrinho-itens" id="carrinho-itens">
                    <?php foreach ($itens as $item): ?>
                        <article class="carrinho-item" data-item="<?= (int) $item['produto_tamanho_id'] ?>">
                            <div class="carrinho-item-imagem">
                                <img src="<?= e($item['imagem'] ?: '/img/logoBiaModas.png') ?>" alt="<?= e($item['nome']) ?>">
                            </div>
                            <div class="carrinho-item-info">
                                <span class="product-category"><?= e($item['categoria'] ?: 'Bia Modas') ?></span>
                                <h3 class="carrinho-item-nome"><?= e($item['nome']) ?></h3>
                                <small class="cart-item-size d-block">Tamanho: <?= e($item['tamanho']) ?></small>
                                <span class="cart-item-price"><?= money($item['valor_unitario']) ?></span>
                            </div>
                            <div class="qty-control" role="group" aria-label="Quantidade de <?= e($item['nome']) ?>">
                                <button type="button" data-cart-menos data-produto-tamanho-id="<?= (int) $item['produto_tamanho_id'] ?>" aria-label="Diminuir quantidade">&minus;</button>
                                <span data-cart-qtd><?= (int) $item['quantidade'] ?></span>
                                <button type="button" data-cart-mais data-produto-tamanho-id="<?= (int) $item['produto_tamanho_id'] ?>" aria-label="Aumentar quantidade">+</button>
                            </div>
                            <div class="carrinho-item-subtotal" data-cart-subtotal><?= money($item['subtotal']) ?></div>
                            <button type="button" class="cart-item-remove" data-cart-remover data-produto-tamanho-id="<?= (int) $item['produto_tamanho_id'] ?>" aria-label="Remover <?= e($item['nome']) ?> do carrinho">
                                <i class="icon-trash-2"></i>
                            </button>
                        </article>
                    <?php endforeach; ?>
                </div>

                <aside class="carrinho-resumo">
                    <h2>Resumo do pedido</h2>
                    <dl class="carrinho-resumo-linhas">
                        <div>
                            <dt>Subtotal</dt>
                            <dd id="carrinho-subtotal"><?= money($resumo['subtotal']) ?></dd>
                        </div>
                        <div>
                            <dt>Frete</dt>
                            <dd>Combinado no atendimento</dd>
                        </div>
                    </dl>
                    <div class="carrinho-total">
                        <span>Total</span>
                        <span id="carrinho-total"><?= money($resumo['subtotal']) ?></span>
                    </div>
                    <a class="btn-checkout" href="/checkout">
                        <i class="icon-credit-card"></i>
                        Finalizar compra
                    </a>
                    <a href="/" class="btn-outline-custom btn-outline-dark carrinho-continuar">Continuar comprando</a>
                    <a href="/pedidos" class="carrinho-link-pedidos"><i class="icon-receipt me-1"></i>Ver meus pedidos</a>
                </aside>
            </div>
        </div>
    </section>

    <?php require __DIR__ . '/partials/site_footer.php'; ?>
</body>
</html>
