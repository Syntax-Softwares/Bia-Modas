<?php
$pedidos = $data['pedidos'];
$favoritosIds = $data['favoritosIds'] ?? [];
$favoritosTotal = $data['favoritosTotal'] ?? 0;
$quantidadeCarrinho = $data['quantidadeCarrinho'] ?? 0;
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Bia Modas - Meus pedidos." />
    <meta name="csrf-token" content="<?= e(Sessao::csrfToken()) ?>" />
    <title>Meus Pedidos | Bia Modas</title>

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
    <link rel="stylesheet" href="<?= asset('/css/checkout.css') ?>">
</head>
<body data-page="pedidos">
    <?php require __DIR__ . '/partials/site_header.php'; ?>
    <?php require __DIR__ . '/partials/cart_offcanvas.php'; ?>

    <header class="page-header">
        <div class="container">
            <nav class="breadcrumb" aria-label="Navegação">
                <a href="/">Início</a>
                <span>/</span>
                <span>Meus pedidos</span>
            </nav>
            <h1>Meus pedidos</h1>
            <p>Acompanhe suas compras anteriores</p>
        </div>
    </header>

    <section class="products-section">
        <div class="container">
            <?php require __DIR__ . '/partials/alerts.php'; ?>

            <?php if ($pedidos === []): ?>
                <div class="empty-state">
                    <i class="icon-receipt"></i>
                    <h2>Você ainda não fez pedidos</h2>
                    <p>Quando finalizar uma compra, ela aparece aqui.</p>
                    <a href="/" class="btn-outline-custom btn-outline-dark mt-3">Ver produtos</a>
                </div>
            <?php else: ?>
                <div class="pedidos-lista">
                    <?php foreach ($pedidos as $pedido): ?>
                        <?php $status = status_pagamento($pedido['pagamento_status']); ?>
                        <article class="pedido-card">
                            <div class="pedido-card-info">
                                <div class="pedido-card-topo">
                                    <h2>Pedido #<?= (int) $pedido['numero_pedido'] ?></h2>
                                    <span class="pedido-badge pedido-badge-<?= e($status['classe']) ?>"><?= e($status['rotulo']) ?></span>
                                </div>
                                <p>
                                    <?= data_hora($pedido['criado_em']) ?> ·
                                    <?= (int) $pedido['total_itens'] ?> <?= (int) $pedido['total_itens'] === 1 ? 'item' : 'itens' ?> ·
                                    <?= e(rotulo_pagamento($pedido['pagamento_metodo'])) ?>
                                </p>
                            </div>
                            <div class="pedido-card-valor">
                                <strong><?= money($pedido['valor_total']) ?></strong>
                                <a href="/pedido?numero=<?= (int) $pedido['numero_pedido'] ?>">Ver detalhes</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php require __DIR__ . '/partials/site_footer.php'; ?>
</body>
</html>
