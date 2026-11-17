<?php
$pedido = $data['pedido'];
$itens = $data['itens'];
$status = status_pagamento($pedido['pagamento_status']);
$novo = isset($_GET['novo']);
$favoritosIds = $data['favoritosIds'] ?? [];
$favoritosTotal = $data['favoritosTotal'] ?? 0;
$quantidadeCarrinho = $data['quantidadeCarrinho'] ?? 0;

$cep = (string) $pedido['endereco_cep'];
$cepFormatado = strlen($cep) === 8 ? substr($cep, 0, 5) . '-' . substr($cep, 5) : $cep;
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Bia Modas - Detalhes do pedido." />
    <meta name="csrf-token" content="<?= e(Sessao::csrfToken()) ?>" />
    <title>Pedido #<?= (int) $pedido['numero_pedido'] ?> | Bia Modas</title>

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
<body data-page="pedido">
    <?php require __DIR__ . '/partials/site_header.php'; ?>
    <?php require __DIR__ . '/partials/cart_offcanvas.php'; ?>

    <header class="page-header">
        <div class="container">
            <nav class="breadcrumb" aria-label="Navegação">
                <a href="/">Início</a>
                <span>/</span>
                <a href="/pedidos">Meus pedidos</a>
                <span>/</span>
                <span>#<?= (int) $pedido['numero_pedido'] ?></span>
            </nav>
            <h1>Pedido #<?= (int) $pedido['numero_pedido'] ?></h1>
            <p>Realizado em <?= data_hora($pedido['criado_em']) ?></p>
        </div>
    </header>

    <section class="products-section">
        <div class="container">
            <?php if ($novo): ?>
                <div class="alerta alerta-sucesso" role="status">
                    Pedido realizado com sucesso. Guarde o número #<?= (int) $pedido['numero_pedido'] ?> para acompanhar.
                </div>
            <?php endif; ?>

            <div class="pedido-layout">
                <div class="pedido-principal">
                    <div class="pedido-topo">
                        <h2>Itens do pedido</h2>
                        <span class="pedido-badge pedido-badge-<?= e($status['classe']) ?>"><?= e($status['rotulo']) ?></span>
                    </div>

                    <ul class="pedido-itens">
                        <?php foreach ($itens as $item): ?>
                            <li>
                                <img src="<?= e($item['imagem'] ?: '/img/logoBiaModas.png') ?>" alt="<?= e($item['nome']) ?>">
                                <div class="pedido-item-info">
                                    <span><?= e($item['nome']) ?></span>
                                    <small><?= (int) $item['quantidade'] ?> x <?= money($item['valor_unitario']) ?></small>
                                </div>
                                <strong><?= money($item['valor_total']) ?></strong>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="pedido-valores">
                        <div>
                            <span>Subtotal</span>
                            <span><?= money((float) $pedido['valor_total'] - (float) $pedido['frete'] + (float) $pedido['desconto']) ?></span>
                        </div>
                        <div>
                            <span>Frete</span>
                            <span><?= money($pedido['frete']) ?></span>
                        </div>
                        <?php if ((float) $pedido['desconto'] > 0): ?>
                            <div>
                                <span>Desconto</span>
                                <span>-<?= money($pedido['desconto']) ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="pedido-total">
                            <span>Total</span>
                            <span><?= money($pedido['valor_total']) ?></span>
                        </div>
                    </div>
                </div>

                <aside class="pedido-lateral">
                    <h2>Entrega</h2>
                    <p>
                        <strong><?= e($pedido['destinatario']) ?></strong><br>
                        <?= e($pedido['endereco_logradouro']) ?>, <?= e($pedido['endereco_numero']) ?>
                        <?php if (!empty($pedido['endereco_complemento'])): ?>
                            - <?= e($pedido['endereco_complemento']) ?>
                        <?php endif; ?>
                        <br>
                        <?= e($pedido['endereco_bairro']) ?><br>
                        <?= e($pedido['endereco_cidade']) ?> - <?= e($pedido['endereco_uf']) ?><br>
                        CEP <?= e($cepFormatado) ?>
                    </p>
                    <p class="pedido-contato">Telefone: <?= e(formatar_telefone($pedido['telefone'])) ?></p>

                    <h2>Pagamento</h2>
                    <p>
                        <?= e(rotulo_pagamento($pedido['pagamento_metodo'])) ?><br>
                        <span class="pedido-badge pedido-badge-<?= e($status['classe']) ?>"><?= e($status['rotulo']) ?></span>
                    </p>
                    <?php if (!empty($pedido['pagamento_codigo'])): ?>
                        <p class="pedido-codigo">Código: <?= e($pedido['pagamento_codigo']) ?></p>
                    <?php endif; ?>

                    <div class="pedido-acoes">
                        <a href="/pedidos" class="btn-outline-custom btn-outline-dark">Meus pedidos</a>
                        <a href="/" class="btn-primary-custom">Continuar comprando</a>
                    </div>
                </aside>
            </div>
        </div>
    </section>

    <?php require __DIR__ . '/partials/site_footer.php'; ?>
</body>
</html>
