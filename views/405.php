<?php
$quantidadeCarrinho = 0;
$favoritosTotal = 0;
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Método não permitido | Bia Modas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="<?= asset('/css/base.css') ?>">
    <link rel="stylesheet" href="<?= asset('/css/layout.css') ?>">
    <link rel="stylesheet" href="<?= asset('/css/components.css') ?>">
    <link rel="stylesheet" href="<?= asset('/css/responsive.css') ?>">
    <link rel="stylesheet" href="<?= asset('/css/categoria.css') ?>">
</head>
<body>
    <?php require __DIR__ . '/partials/site_header.php'; ?>

    <section class="products-section">
        <div class="container">
            <div class="empty-state">
                <i class="bi bi-slash-circle"></i>
                <h1>405</h1>
                <h2>Método não permitido</h2>
                <p>Essa ação não é aceita por este endereço.</p>
                <a href="/" class="btn-outline-custom btn-outline-dark mt-3">Voltar para a home</a>
            </div>
        </div>
    </section>
</body>
</html>
