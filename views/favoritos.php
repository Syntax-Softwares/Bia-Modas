<?php
$produtos = $data['produtos'] ?? [];
$favoritosIds = $data['favoritosIds'] ?? [];
$carrinhoIds = $data['carrinhoIds'] ?? [];
$favoritosTotal = $data['favoritosTotal'] ?? 0;
$quantidadeCarrinho = $data['quantidadeCarrinho'] ?? 0;
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Bia Modas - Meus Favoritos." />
    <meta name="csrf-token" content="<?= e(Sessao::csrfToken()) ?>" />
    <title>Meus Favoritos | Bia Modas</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
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
<body data-page="favoritos">
    <?php require __DIR__ . '/partials/site_header.php'; ?>
    <?php require __DIR__ . '/partials/cart_offcanvas.php'; ?>

    <header class="page-header">
        <div class="container">
            <nav class="breadcrumb" aria-label="Navegação">
                <a href="/">Início</a>
                <span>/</span>
                <span>Favoritos</span>
            </nav>
            <h1>Meus favoritos</h1>
            <p>As peças que você salvou para depois</p>
        </div>
    </header>

    <section class="products-section">
        <div class="container">
            <div class="row" id="favoritos-lista">
                <?php foreach ($produtos as $produto): ?>
                    <?php $layout = 'grade'; ?>
                    <?php require __DIR__ . '/partials/product_card.php'; ?>
                <?php endforeach; ?>
            </div>

            <div class="empty-state" id="favoritos-vazio" <?= $produtos === [] ? '' : 'hidden' ?>>
                <i class="bi bi-heart"></i>
                <h2>Você ainda não tem favoritos</h2>
                <p>Toque no coração de um produto para salvá-lo aqui.</p>
                <a href="/" class="btn-outline-custom btn-outline-dark mt-3">Explorar produtos</a>
            </div>
        </div>
    </section>

    <?php require __DIR__ . '/partials/site_footer.php'; ?>
</body>
</html>
