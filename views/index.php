<?php
$estaLogado = $data['estaLogado'];
$usuario = $data['usuario'];
$contaHref = $data['contaHref'];
$busca = '';
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Bia Modas - Moda feminina em Jaú, SP. Tendências, qualidade e preços incríveis para realçar sua beleza." />
    <meta name="csrf-token" content="<?= e(Sessao::csrfToken()) ?>" />
    <title>Bia Modas | Moda Feminina em Jaú</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lucide-static@latest/font/lucide.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/css/base.css">
    <link rel="stylesheet" href="/css/layout.css">
    <link rel="stylesheet" href="/css/components.css">
    <link rel="stylesheet" href="/css/responsive.css">
    <link rel="stylesheet" href="/css/auth.css">
    <link rel="stylesheet" href="/css/catalogo.css">
    <link rel="stylesheet" href="/css/carrinho.css">
</head>
<body>

    <?php require __DIR__ . '/partials/site_header.php'; ?>

    <?php if (!empty($data['flashes'])): ?>
        <div class="container mt-3">
            <?php
            $flashes = $data['flashes'];
            require __DIR__ . '/partials/alerts.php';
            ?>
        </div>
    <?php endif; ?>

    <!-- Toast de notificação (será usado pelo carrinho) -->



    <!-- Hero Section -->
    <section class="hero-section">
        <img src="/img/Banner.png" alt="Coleção Primavera Verão Bia Modas">
        <div class="hero-overlay">
            <div class="container">
                <div class="hero-content">
                    <span class="hero-eyebrow">Coleção 2026 · Primavera Verão</span>
                    <h1>Moda que te veste como você é.</h1>
                    <p>Peças exclusivas, caimento impecável e até 40% OFF na nova estação.</p>
                    <div>
                        <a href="/categoria" class="btn-primary-custom">Comprar agora</a>
                        <a href="/categoria?promocoes=1" class="btn-outline-custom">Ver coleção</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Visual Category Menu -->
    <section class="category-grid-section">
        <div class="container">
            <div class="category-grid">
                <a href="/categoria?categoria=Vestidos" class="category-circle-item">
                    <div class="category-circle">
                        <img src="/img/VestidoVerdePlusSize.png" alt="Vestidos">
                    </div>
                    <span class="category-circle-name">Vestidos</span>
                </a>
                <a href="/categoria?categoria=Blusas" class="category-circle-item">
                    <div class="category-circle">
                        <img src="/img/BlusaPlusSizeVermelho.png" alt="Blusas">
                    </div>
                    <span class="category-circle-name">Blusas</span>
                </a>
                <a href="/categoria?categoria=Calças" class="category-circle-item">
                    <div class="category-circle">
                        <img src="/img/calcas.png" alt="Calças">
                    </div>
                    <span class="category-circle-name">Calças</span>
                </a>
                <a href="/categoria?categoria=Plus%20Size" class="category-circle-item">
                    <div class="category-circle">
                        <img src="/img/BlusaPlusSizePreta.png" alt="Plus Size">
                    </div>
                    <span class="category-circle-name">Plus Size</span>
                </a>
                <a href="/categoria?categoria=Acessórios" class="category-circle-item">
                    <div class="category-circle">
                        <img src="/img/acessorios.png" alt="Acessórios">
                    </div>
                    <span class="category-circle-name">Acessórios</span>
                </a>
                <a href="/categoria?categoria=Saias" class="category-circle-item">
                    <div class="category-circle">
                        <img src="/img/saia.png" alt="Saias">
                    </div>
                    <span class="category-circle-name">Saias</span>
                </a>
                <a href="/categoria?categoria=Shorts" class="category-circle-item">
                    <div class="category-circle">
                        <img src="/img/shorts.png" alt="Shorts">
                    </div>
                    <span class="category-circle-name">Shorts</span>
                </a>
                <a href="/categoria?categoria=Casacos" class="category-circle-item">
                    <div class="category-circle">
                        <img src="/img/casaco.png" alt="Casacos">
                    </div>
                    <span class="category-circle-name">Casacos</span>
                </a>
                <a href="/categoria?categoria=Moda%20Praia" class="category-circle-item">
                    <div class="category-circle">
                        <img src="/img/modapraia.png" alt="Moda Praia">
                    </div>
                    <span class="category-circle-name">Moda Praia</span>
                </a>
                <a href="/categoria?categoria=Fitness" class="category-circle-item">
                    <div class="category-circle">
                        <img src="/img/fitness.png" alt="Fitness">
                    </div>
                    <span class="category-circle-name">Fitness</span>
                </a>
                <a href="/categoria?categoria=Infantil" class="category-circle-item">
                    <div class="category-circle">
                        <img src="/img/infantil.png" alt="Infantil">
                    </div>
                    <span class="category-circle-name">Infantil</span>
                </a>
                <a href="/categoria?categoria=Moda%20Íntima" class="category-circle-item">
                    <div class="category-circle">
                        <img src="/img/modaintima.png" alt="Moda Íntima">
                    </div>
                    <span class="category-circle-name">Moda Íntima</span>
                </a>
                <a href="/categoria?categoria=Sapatos" class="category-circle-item">
                    <div class="category-circle">
                        <img src="/img/sapatos.png" alt="Sapatos">
                    </div>
                    <span class="category-circle-name">Sapatos</span>
                </a>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="features-section">
        <div class="container">
            <div class="row">
                <div class="col-md-3 col-6">
                    <div class="feature-box">
                        <div class="feature-icon">
                            <i class="icon-truck"></i>
                        </div>
                        <h3>Frete Grátis</h3>
                        <p>Para Jaú em compras acima de R$ 199</p>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="feature-box">
                        <div class="feature-icon">
                            <i class="icon-shield-check"></i>
                        </div>
                        <h3>Compra Segura</h3>
                        <p>Ambiente 100% seguro e protegido</p>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="feature-box">
                        <div class="feature-icon">
                            <i class="icon-repeat"></i>
                        </div>
                        <h3>Troca Fácil</h3>
                        <p>7 dias para trocar sem burocracia</p>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="feature-box">
                        <div class="feature-icon">
                            <i class="icon-credit-card"></i>
                        </div>
                        <h3>Até 12x</h3>
                        <p>Parcelamento em todos os cartões</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Recomendados para Você -->
    <section class="products-section" id="section-recomendados" style="display: none;">
        <div class="container">
            <div class="section-header">
                <div class="section-header-meta">
                    <span class="section-number">★</span>
                    <h2>Para Você</h2>
                </div>
                <span class="section-header-side">Selecionados com base no seu perfil</span>
            </div>
            <div class="carousel-container">
                <button class="carousel-btn prev" onclick="moveCarousel('recomendados', -1)" aria-label="Anterior"><i class="icon-chevron-left"></i></button>
                <div class="carousel-track" id="carousel-recomendados"></div>
                <button class="carousel-btn next" onclick="moveCarousel('recomendados', 1)" aria-label="Próximo"><i class="icon-chevron-right"></i></button>
                <div class="carousel-dots" id="dots-recomendados"></div>
            </div>
        </div>
    </section>

    <!-- Products - Novidades -->
    <section class="products-section">
        <div class="container">
            <div class="section-header">
                <div class="section-header-meta">
                    <span class="section-number">01</span>
                    <h2>Novidades</h2>
                </div>
                <a href="/categoria" class="section-link">Ver todas <i class="icon-arrow-right"></i></a>
            </div>

            <?php if ($data['novidades'] === []): ?>
                <div class="catalog-empty">
                    <h2>Nenhum produto cadastrado ainda</h2>
                    <p>Assim que a loja cadastrar produtos, eles aparecerão aqui.</p>
                </div>
            <?php else: ?>
                <div class="carousel-container">
                    <button class="carousel-btn prev" onclick="moveCarousel('novidades', -1)" aria-label="Anterior"><i class="icon-chevron-left"></i></button>
                    <div class="carousel-track" id="carousel-novidades">
                        <?php foreach ($data['novidades'] as $produto): ?>
                            <?php
                            $badge = 'Novo';
                            require __DIR__ . '/partials/product_card.php';
                            ?>
                        <?php endforeach; ?>
                    </div>
                    <button class="carousel-btn next" onclick="moveCarousel('novidades', 1)" aria-label="Próximo"><i class="icon-chevron-right"></i></button>
                    <div class="carousel-dots" id="dots-novidades"></div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Manifesto bar -->
    <section class="manifesto-section">
        <div class="container">
            <p class="manifesto-text">
                <span>Feita em Jaú.</span>
                <span>Pensada para você.</span>
                <span>Vestida com orgulho.</span>
            </p>
        </div>
    </section>

    <!-- Products - Promoções -->
    <section class="products-section">
        <div class="container">
            <div class="section-header">
                <div class="section-header-meta">
                    <span class="section-number">02</span>
                    <h2>Promoções</h2>
                </div>
                <a href="/categoria?promocoes=1" class="section-link">Ver tudo em oferta <i class="icon-arrow-right"></i></a>
            </div>

            <?php if ($data['promocoes'] === []): ?>
                <div class="catalog-empty">
                    <h2>Nenhuma promoção no momento</h2>
                    <p>Volte em breve para conferir novas ofertas.</p>
                </div>
            <?php else: ?>
                <div class="carousel-container">
                    <button class="carousel-btn prev" onclick="moveCarousel('promocoes', -1)" aria-label="Anterior"><i class="icon-chevron-left"></i></button>
                    <div class="carousel-track" id="carousel-promocoes">
                        <?php foreach ($data['promocoes'] as $produto): ?>
                            <?php require __DIR__ . '/partials/product_card.php'; ?>
                        <?php endforeach; ?>
                    </div>
                    <button class="carousel-btn next" onclick="moveCarousel('promocoes', 1)" aria-label="Próximo"><i class="icon-chevron-right"></i></button>
                    <div class="carousel-dots" id="dots-promocoes"></div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Products - Vistos Recentemente -->
    <section class="products-section" id="section-recentes" style="display: none; background-color: var(--color-secondary);">
        <div class="container">
            <div class="section-header">
                <div class="section-header-meta">
                    <span class="section-number">03</span>
                    <h2>Vistos recentemente</h2>
                </div>
                <span class="section-header-side">Continue de onde parou</span>
            </div>

            <div class="carousel-container">
                <button class="carousel-btn prev" onclick="moveCarousel('recentes', -1)" aria-label="Anterior"><i class="icon-chevron-left"></i></button>
                <div class="carousel-track" id="carousel-recentes"></div>
                <button class="carousel-btn next" onclick="moveCarousel('recentes', 1)" aria-label="Próximo"><i class="icon-chevron-right"></i></button>
                <div class="carousel-dots" id="dots-recentes"></div>
            </div>
        </div>
    </section>

    <!-- Newsletter -->
    <section class="newsletter-section">
        <div class="container">
            <div class="newsletter-text">
                <h2>Fique por dentro das novidades.</h2>
                <p>Ofertas exclusivas, lançamentos e dicas de moda direto no seu e-mail.</p>
            </div>
            <form class="newsletter-form" onsubmit="event.preventDefault(); alert('Obrigado por se cadastrar!');">
                <input type="email" placeholder="seu@email.com" required aria-label="E-mail para newsletter">
                <button type="submit">Cadastrar</button>
            </form>
        </div>
    </section>

    <?php require __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
