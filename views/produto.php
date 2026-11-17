<?php
$produto = $data['produto'];
$avaliacoes = $data['avaliacoes'];
$resumo = $data['resumoAvaliacoes'];
$minhaAvaliacao = $data['avaliacaoDoUsuario'];
$relacionados = $data['relacionados'];
$favoritosIds = $data['favoritosIds'];
$estaLogado = $data['estaLogado'];
$errors = $data['errors'] ?? [];
$old = $data['old'] ?? [];

$favoritado = in_array((int) $produto['id_produto'], $favoritosIds, true);
$media = $resumo['media'];
$totalAvaliacoes = (int) $resumo['total'];

$notaSelecionada = (int) ($old['nota'] ?? ($minhaAvaliacao['nota'] ?? 0));
$comentarioValor = (string) ($old['comentario'] ?? ($minhaAvaliacao['comentario'] ?? ''));
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="<?= e($produto['nome']) ?> - Bia Modas." />
    <meta name="csrf-token" content="<?= e(Auth::csrfToken()) ?>" />
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

                    <?php if ($media !== null): ?>
                        <a class="product-rating" href="#avaliacoes">
                            <?= estrelas((float) $media) ?>
                            <strong><?= number_format((float) $media, 1, ',', '.') ?></strong>
                            <span>(<?= $totalAvaliacoes ?> avaliaç<?= $totalAvaliacoes === 1 ? 'ão' : 'ões' ?>)</span>
                        </a>
                    <?php else: ?>
                        <a class="product-rating is-empty" href="#avaliacoes">
                            <i class="bi bi-star"></i> Seja a primeira a avaliar
                        </a>
                    <?php endif; ?>

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
                        <button
                            type="button"
                            class="btn-outline-custom product-favorite-detail<?= $favoritado ? ' active' : '' ?>"
                            id="btn-favorite"
                            data-favorito="<?= (int) $produto['id_produto'] ?>"
                            aria-pressed="<?= $favoritado ? 'true' : 'false' ?>"
                        >
                            <i class="bi <?= $favoritado ? 'bi-heart-fill' : 'bi-heart' ?> me-2"></i>
                            <span data-favorito-texto><?= $favoritado ? 'Salvo nos favoritos' : 'Salvar nos favoritos' ?></span>
                        </button>
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

    <!-- Avaliações -->
    <section class="products-section avaliacoes-section" id="avaliacoes">
        <div class="container">
            <div class="section-header">
                <div class="section-header-meta">
                    <span class="section-number">★</span>
                    <h2>Avaliações</h2>
                </div>
                <span class="section-header-side">
                    <?php if ($media !== null): ?>
                        Média <?= number_format((float) $media, 1, ',', '.') ?> em <?= $totalAvaliacoes ?> avaliaç<?= $totalAvaliacoes === 1 ? 'ão' : 'ões' ?>
                    <?php else: ?>
                        Nenhuma avaliação ainda
                    <?php endif; ?>
                </span>
            </div>

            <div class="row g-4">
                <div class="col-lg-5">
                    <?php if (!$estaLogado): ?>
                        <div class="avaliacao-cta">
                            <i class="bi bi-lock"></i>
                            <h3>Entre para avaliar</h3>
                            <p>Só quem tem conta na Bia Modas pode deixar uma avaliação.</p>
                            <a class="btn-primary-solid" href="/login">Entrar na conta</a>
                        </div>
                    <?php else: ?>
                        <form class="avaliacao-form" method="post" action="/avaliacoes" novalidate>
                            <?= Auth::csrfField() ?>
                            <input type="hidden" name="produto_id" value="<?= (int) $produto['id_produto'] ?>">

                            <h3><?= $minhaAvaliacao !== null ? 'Sua avaliação' : 'Avaliar esta peça' ?></h3>

                            <fieldset class="avaliacao-nota">
                                <legend>Sua nota</legend>
                                <div class="avaliacao-estrelas">
                                    <?php for ($nota = 5; $nota >= 1; $nota--): ?>
                                        <input
                                            type="radio"
                                            name="nota"
                                            id="nota-<?= $nota ?>"
                                            value="<?= $nota ?>"
                                            <?= $notaSelecionada === $nota ? 'checked' : '' ?>
                                            required
                                        >
                                        <label for="nota-<?= $nota ?>" aria-label="<?= $nota ?> estrela<?= $nota > 1 ? 's' : '' ?>">
                                            <i class="bi bi-star-fill"></i>
                                        </label>
                                    <?php endfor; ?>
                                </div>
                                <small class="avaliacao-nota-texto" data-nota-texto>
                                    <?= $notaSelecionada > 0 ? 'Sua nota: ' . $notaSelecionada . ' de 5' : 'Clique nas estrelas' ?>
                                </small>
                                <?php if (isset($errors['nota'])): ?>
                                    <div class="invalid-feedback show"><?= e($errors['nota']) ?></div>
                                <?php endif; ?>
                            </fieldset>

                            <div class="form-group">
                                <label for="avaliacao-comentario">Comentário <span class="opcional">(opcional)</span></label>
                                <textarea
                                    class="form-control<?= isset($errors['comentario']) ? ' is-invalid' : '' ?>"
                                    id="avaliacao-comentario"
                                    name="comentario"
                                    rows="4"
                                    maxlength="500"
                                    placeholder="Conte como foi o caimento, o tecido, o tamanho..."
                                ><?= e($comentarioValor) ?></textarea>
                                <?php if (isset($errors['comentario'])): ?>
                                    <div class="invalid-feedback show"><?= e($errors['comentario']) ?></div>
                                <?php endif; ?>
                                <small class="avaliacao-contador" data-contador>0/500</small>
                            </div>

                            <button type="submit" class="btn-primary-solid btn-block">
                                <i class="bi bi-send me-2"></i><?= $minhaAvaliacao !== null ? 'Atualizar avaliação' : 'Publicar avaliação' ?>
                            </button>
                        </form>

                        <?php if ($minhaAvaliacao !== null): ?>
                            <form class="avaliacao-remover" method="post" action="/avaliacoes/remover">
                                <?= Auth::csrfField() ?>
                                <input type="hidden" name="produto_id" value="<?= (int) $produto['id_produto'] ?>">
                                <button type="submit" class="link-danger">
                                    <i class="bi bi-trash me-1"></i>Remover minha avaliação
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="col-lg-7">
                    <?php if ($avaliacoes === []): ?>
                        <div class="avaliacoes-vazio">
                            <i class="bi bi-chat-square-heart"></i>
                            <h3>Nenhuma avaliação ainda</h3>
                            <p>Seja a primeira a contar como foi vestir esta peça.</p>
                        </div>
                    <?php else: ?>
                        <div class="avaliacoes-lista">
                            <?php foreach ($avaliacoes as $avaliacao): ?>
                                <article class="avaliacao-item">
                                    <div class="avaliacao-topo">
                                        <span class="avaliacao-avatar"><?= e(inicial($avaliacao['autor'])) ?></span>
                                        <div class="avaliacao-identidade">
                                            <strong><?= e($avaliacao['autor']) ?></strong>
                                            <div class="avaliacao-meta">
                                                <?= estrelas((float) $avaliacao['nota']) ?>
                                                <time datetime="<?= e(date('Y-m-d', strtotime((string) $avaliacao['criado_em']))) ?>">
                                                    <?= e(date('d/m/Y', strtotime((string) $avaliacao['criado_em']))) ?>
                                                </time>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if ($avaliacao['comentario'] !== null): ?>
                                        <p class="avaliacao-texto"><?= e($avaliacao['comentario']) ?></p>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Você também pode gostar -->
    <?php if ($relacionados !== []): ?>
        <section class="products-section">
            <div class="container">
                <div class="section-header">
                    <div class="section-header-meta">
                        <span class="section-number">02</span>
                        <h2>Você também pode gostar</h2>
                    </div>
                    <a href="/categoria?categoria=<?= rawurlencode($produto['categoria']) ?>" class="section-link">
                        Ver mais <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                <div class="row g-4">
                    <?php foreach ($relacionados as $produto): ?>
                        <div class="col-6 col-md-4 col-lg-3">
                            <?php
                            $semCarousel = true;
                            $badge = null;
                            require __DIR__ . '/partials/product_card.php';
                            ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php require __DIR__ . '/partials/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="/js/site.js"></script>
    <script src="/js/api.js"></script>
    <script src="/js/favoritos.js"></script>
    <script src="/js/avaliacoes.js"></script>
</body>
</html>
