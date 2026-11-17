<?php
$estaLogado = $data['estaLogado'];
$usuario = $data['usuario'];
$contaHref = $data['contaHref'];
$produtos = $data['produtos'];
$favoritosIds = $data['favoritosIds'];
$busca = '';
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Seus favoritos na Bia Modas." />
    <meta name="csrf-token" content="<?= e(Auth::csrfToken()) ?>" />
    <title>Favoritos | Bia Modas</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/css/base.css">
    <link rel="stylesheet" href="/css/layout.css">
    <link rel="stylesheet" href="/css/components.css">
    <link rel="stylesheet" href="/css/responsive.css">
    <link rel="stylesheet" href="/css/categoria.css">
    <link rel="stylesheet" href="/css/auth.css">
</head>
<body>
    <?php require __DIR__ . '/partials/site_header.php'; ?>

    <header class="page-header">
        <div class="container">
            <nav class="breadcrumb" aria-label="Navegação">
                <a href="/">Início</a>
                <span>/</span>
                <span>Favoritos</span>
            </nav>
            <h1>Meus favoritos</h1>
            <p>
                <?php if ($produtos === []): ?>
                    As peças que você salvar para depois
                <?php else: ?>
                    <?= count($produtos) ?> peça<?= count($produtos) === 1 ? '' : 's' ?> salva<?= count($produtos) === 1 ? '' : 's' ?>
                <?php endif; ?>
            </p>
        </div>
    </header>

    <section class="products-section">
        <div class="container">
            <?php require __DIR__ . '/partials/alerts.php'; ?>

            <?php if ($produtos === []): ?>
                <div class="catalog-empty">
                    <i class="bi bi-heart"></i>
                    <h2>Você ainda não tem favoritos</h2>
                    <p>Explore o catálogo e salve as peças que você mais gostou.</p>
                    <a href="/categoria" class="btn-primary-solid">Ver catálogo</a>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($produtos as $produto): ?>
                        <div class="col-6 col-md-4 col-lg-3">
                            <?php
                            $semCarousel = true;
                            $badge = null;
                            require __DIR__ . '/partials/product_card.php';
                            ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php require __DIR__ . '/partials/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="/js/site.js"></script>
    <script src="/js/api.js"></script>
    <script src="/js/favoritos.js"></script>
</body>
</html>
