<?php
/** @var array $produto */
/** @var array $imagens */
/** @var array|null $tamanhoSelecionado */
/** @var array|null $usuario */
/** @var array|null $medidas */
/** @var array $flash */
/** @var array $erros */
/** @var array $old */
/** @var array $sugestoes */

$idProduto = (int) $produto['id_produto'];
$produtoUrl = Catalogo::urlProduto($idProduto);
$imagem = $imagens[0]['caminho_imagem'] ?? $produto['imagem'];
$flash = is_array($flash ?? null) ? $flash : [];
$erros = is_array($erros ?? null) ? $erros : [];
$old = is_array($old ?? null) ? $old : [];
$medidas = is_array($medidas ?? null) ? $medidas : null;

$valorMedida = static function (string $campo) use ($old, $medidas): string {
    if (array_key_exists($campo, $old)) {
        return (string) $old[$campo];
    }

    if ($medidas === null) {
        return '';
    }

    $valor = $medidas[$campo] ?? null;

    if ($valor === null) {
        return '';
    }

    $formatado = rtrim(rtrim(number_format((float) $valor, 1, '.', ''), '0'), '.');

    return $formatado === '' ? '0' : $formatado;
};

$dadosProvador = [
    'produtoId' => $idProduto,
    'nome' => (string) $produto['nome'],
    'preco' => Catalogo::money($produto['valor']),
    'imagem' => (string) $imagem,
    'categoria' => (string) $produto['categoria'],
    'tamanhoId' => $tamanhoSelecionado['id_produto_tamanho'] ?? null,
    'tamanhoNome' => $tamanhoSelecionado['tamanho'] ?? null,
    'produtoUrl' => $produtoUrl,
    'carrinhoUrl' => '/carrinho',
    'modelo' => '/model.glb',
];
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="<?= asset('/favicon.ico') ?>" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('/img/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= asset('/img/favicon-192x192.png') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= asset('/img/apple-touch-icon.png') ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Provador Virtual Bia Modas - veja a peça em um avatar 3D antes de comprar.">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="<?= Html::e(Sessao::csrfToken()) ?>">
    <title>Provador Virtual | Bia Modas</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lucide-static@latest/font/lucide.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= asset('/css/base.css') ?>">
    <link rel="stylesheet" href="<?= asset('/css/layout.css') ?>">
    <link rel="stylesheet" href="<?= asset('/css/components.css') ?>">
    <link rel="stylesheet" href="<?= asset('/css/responsive.css') ?>">
    <link rel="stylesheet" href="<?= asset('/css/auth.css') ?>">
    <link rel="stylesheet" href="<?= asset('/css/catalogo.css') ?>">
    <link rel="stylesheet" href="<?= asset('/css/carrinho.css') ?>">
    <link rel="stylesheet" href="<?= asset('/css/provador-virtual.css') ?>">
</head>
<body data-page="provador">
    <?php require __DIR__ . '/partials/site_header.php'; ?>

    <main class="provador-page">
        <!-- Barra fina superior: voltar + contador -->
        <div class="provador-topbar">
            <div class="container">
                <a href="<?= Html::e($produtoUrl) ?>" class="provador-back">
                    <i class="icon-arrow-left"></i>
                    <span>Voltar ao produto</span>
                </a>
                <div class="provador-meta">
                    <span class="provador-eyebrow">Provador Virtual</span>
                    <span class="provador-step-counter"><strong id="step-counter-current">01</strong> &nbsp;/&nbsp; 04</span>
                </div>
            </div>
        </div>

        <!-- Título grande + barra de progresso -->
        <div class="provador-title-bar">
            <div class="container">
                <div class="provador-title-row">
                    <div class="provador-title-text">
                        <span class="provador-title-eyebrow" id="step-eyebrow">Bem-vinda</span>
                        <h1 id="step-title">Experimente antes de levar.</h1>
                    </div>
                </div>
                <div class="provador-progress-track">
                    <div class="provador-progress-fill" id="progress-fill"></div>
                </div>
            </div>
        </div>

        <!-- Conteúdo dos steps -->
        <div class="container provador-content">

            <?php if ($flash !== []): ?>
                <div class="provador-alerts">
                    <?php foreach ($flash as $mensagem): ?>
                        <?php
                        $tipoOriginal = (string) ($mensagem['type'] ?? $mensagem['tipo'] ?? 'info');
                        $tipo = in_array($tipoOriginal, ['success', 'danger', 'info', 'warning'], true)
                            ? $tipoOriginal
                            : 'info';
                        $texto = (string) ($mensagem['message'] ?? $mensagem['mensagem'] ?? '');
                        ?>
                        <?php if ($texto !== ''): ?>
                            <div class="alert alert-<?= Html::e($tipo) ?>" role="alert"><?= Html::e($texto) ?></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- STEP 1: Boas-vindas -->
            <section class="wizard-step active" data-step="1">
                <div class="step-grid">
                    <div class="step-media step-media-product">
                        <img id="provador-product-img" src="<?= Html::e($imagem) ?>" alt="<?= Html::e($produto['nome']) ?>">
                    </div>
                    <div class="step-panel">
                        <span class="step-panel-eyebrow">Você vai provar</span>
                        <h2 class="step-panel-title" id="provador-product-name"><?= Html::e($produto['nome']) ?></h2>
                        <span class="step-panel-price" id="provador-product-price"><?= Catalogo::money($produto['valor']) ?></span>

                        <?php if ($tamanhoSelecionado !== null): ?>
                            <p class="provador-size">
                                Tamanho selecionado: <strong><?= Html::e($tamanhoSelecionado['tamanho']) ?></strong>
                            </p>
                        <?php endif; ?>

                        <hr class="step-panel-divider">

                        <p class="step-panel-lead">Três passos. Em segundos, você vê a peça em um avatar 3D com a sua silhueta.</p>

                        <ol class="step-tips">
                            <li>
                                <span class="tip-num">01</span>
                                <div>
                                    <strong>Boa iluminação</strong>
                                    <span>Fique de frente para uma fonte de luz natural.</span>
                                </div>
                            </li>
                            <li>
                                <span class="tip-num">02</span>
                                <div>
                                    <strong>Fundo neutro</strong>
                                    <span>Uma parede lisa ajuda a separar você do cenário.</span>
                                </div>
                            </li>
                            <li>
                                <span class="tip-num">03</span>
                                <div>
                                    <strong>Roupas justas</strong>
                                    <span>Peças coladas tornam a silhueta mais fiel.</span>
                                </div>
                            </li>
                        </ol>

                        <div class="provador-measures">
                            <span class="step-panel-eyebrow">Suas medidas</span>

                            <?php if ($usuario !== null): ?>
                                <p class="provador-measures-lead">Opcional. Ficam salvas na sua conta para as próximas provas.</p>

                                <form method="post" action="/provador-virtual/medidas" class="provador-measures-form" novalidate>
                                    <?= Sessao::csrfCampo() ?>
                                    <input type="hidden" name="produto_id" value="<?= $idProduto ?>">
                                    <input type="hidden" name="tamanho_id" value="<?= $tamanhoSelecionado !== null ? (int) $tamanhoSelecionado['id_produto_tamanho'] : '' ?>">

                                    <div class="provador-field">
                                        <label for="medida-busto">Busto (cm)</label>
                                        <input type="number" id="medida-busto" name="busto" value="<?= Html::e($valorMedida('busto')) ?>" min="1" max="300" step="0.1" inputmode="decimal" placeholder="96">
                                    </div>
                                    <div class="provador-field">
                                        <label for="medida-cintura">Cintura (cm)</label>
                                        <input type="number" id="medida-cintura" name="cintura" value="<?= Html::e($valorMedida('cintura')) ?>" min="1" max="300" step="0.1" inputmode="decimal" placeholder="78">
                                    </div>
                                    <div class="provador-field">
                                        <label for="medida-quadril">Quadril (cm)</label>
                                        <input type="number" id="medida-quadril" name="quadril" value="<?= Html::e($valorMedida('quadril')) ?>" min="1" max="300" step="0.1" inputmode="decimal" placeholder="102">
                                    </div>

                                    <button type="submit" class="btn-measures">Salvar medidas</button>
                                </form>

                                <?php if (isset($erros['medidas'])): ?>
                                    <p class="provador-field-error"><?= Html::e($erros['medidas']) ?></p>
                                <?php endif; ?>
                            <?php else: ?>
                                <p class="provador-measures-lead">Entre na sua conta para salvar suas medidas e reaproveitá-las nas próximas provas.</p>
                                <a class="btn-measures" href="/login">Entrar e salvar</a>
                            <?php endif; ?>
                        </div>

                        <p class="provador-privacy">
                            <i class="icon-lock"></i>
                            Suas fotos ficam apenas no navegador e não são enviadas para o servidor.
                        </p>
                    </div>
                </div>
            </section>

            <!-- STEP 2: Captura do rosto -->
            <section class="wizard-step" data-step="2">
                <div class="step-grid">
                    <div class="step-media capture-stage" data-guide="face">
                        <video id="video-face" class="capture-video" autoplay playsinline muted></video>
                        <img id="preview-face" class="capture-preview" alt="Foto do rosto" hidden>

                        <div class="capture-guide face-guide" id="guide-face">
                            <svg viewBox="0 0 300 380" preserveAspectRatio="xMidYMid meet" aria-hidden="true">
                                <ellipse cx="150" cy="190" rx="110" ry="160" />
                            </svg>
                        </div>

                        <div class="capture-flash" id="flash-face" aria-hidden="true"></div>

                        <div class="capture-error" id="error-face" hidden>
                            <i class="icon-video-off"></i>
                            <p>Câmera indisponível</p>
                            <small>Permita o uso da câmera nas configurações do navegador ou pule esta etapa.</small>
                        </div>
                    </div>

                    <div class="step-panel">
                        <span class="step-panel-eyebrow">02 · Rosto</span>
                        <h2 class="step-panel-title">Posicione seu rosto.</h2>
                        <p class="step-panel-lead">Centralize o rosto dentro da guia oval. Quando estiver bem enquadrada, clique em capturar.</p>

                        <ul class="capture-checklist">
                            <li><i class="icon-check"></i> Rosto sem óculos escuros</li>
                            <li><i class="icon-check"></i> Olhe diretamente para a câmera</li>
                            <li><i class="icon-check"></i> Expressão neutra ou sorriso suave</li>
                        </ul>

                        <div class="step-actions">
                            <button type="button" class="btn-capture" id="btn-capture-face">
                                <i class="icon-camera"></i>
                                <span>Capturar foto</span>
                            </button>
                            <button type="button" class="btn-retake" id="btn-retake-face" hidden>
                                <i class="icon-rotate-ccw"></i>
                                <span>Refazer</span>
                            </button>
                            <button type="button" class="btn-skip" id="btn-skip-face">Pular e ver o avatar</button>
                        </div>

                        <p class="provador-privacy">
                            <i class="icon-lock"></i>
                            A captura acontece no seu navegador; nada é enviado.
                        </p>
                    </div>
                </div>
            </section>

            <!-- STEP 3: Captura do corpo -->
            <section class="wizard-step" data-step="3">
                <div class="step-grid">
                    <div class="step-media capture-stage" data-guide="body">
                        <video id="video-body" class="capture-video" autoplay playsinline muted></video>
                        <img id="preview-body" class="capture-preview" alt="Foto do corpo" hidden>

                        <div class="capture-guide body-guide" id="guide-body">
                            <svg viewBox="0 0 220 460" preserveAspectRatio="xMidYMid meet" aria-hidden="true">
                                <path d="M110 35
                                         c-22 0 -36 17 -36 38
                                         c0 18 9 33 22 39
                                         c-18 6 -33 22 -38 46
                                         l-14 70
                                         c-2 11 6 18 14 18
                                         l16 0
                                         l-6 130
                                         c-1 14 6 22 16 22
                                         l12 0
                                         l4 -90
                                         l4 90
                                         l12 0
                                         c10 0 17 -8 16 -22
                                         l-6 -130
                                         l16 0
                                         c8 0 16 -7 14 -18
                                         l-14 -70
                                         c-5 -24 -20 -40 -38 -46
                                         c13 -6 22 -21 22 -39
                                         c0 -21 -14 -38 -36 -38 z" />
                            </svg>
                        </div>

                        <div class="capture-flash" id="flash-body" aria-hidden="true"></div>

                        <div class="capture-error" id="error-body" hidden>
                            <i class="icon-video-off"></i>
                            <p>Câmera indisponível</p>
                            <small>Permita o uso da câmera nas configurações do navegador ou pule esta etapa.</small>
                        </div>
                    </div>

                    <div class="step-panel">
                        <span class="step-panel-eyebrow">03 · Corpo</span>
                        <h2 class="step-panel-title">Mostre seu corpo.</h2>
                        <p class="step-panel-lead">Afaste a câmera, fique de frente e mantenha a silhueta dentro da guia.</p>

                        <ul class="capture-checklist">
                            <li><i class="icon-check"></i> Distância de aproximadamente 2 metros</li>
                            <li><i class="icon-check"></i> Braços levemente afastados do corpo</li>
                            <li><i class="icon-check"></i> Pés alinhados, postura natural</li>
                        </ul>

                        <div class="step-actions">
                            <button type="button" class="btn-capture" id="btn-capture-body">
                                <i class="icon-camera"></i>
                                <span>Capturar foto</span>
                            </button>
                            <button type="button" class="btn-retake" id="btn-retake-body" hidden>
                                <i class="icon-rotate-ccw"></i>
                                <span>Refazer</span>
                            </button>
                            <button type="button" class="btn-skip" id="btn-skip-body">Pular e ver o avatar</button>
                        </div>

                        <p class="provador-privacy">
                            <i class="icon-lock"></i>
                            A captura acontece no seu navegador; nada é enviado.
                        </p>
                    </div>
                </div>
            </section>

            <!-- STEP 4: Avatar 3D -->
            <section class="wizard-step" data-step="4">
                <div class="step-grid step-grid-avatar">
                    <div class="step-media avatar-stage">
                        <canvas id="avatar-canvas" aria-label="Avatar 3D da peça"></canvas>

                        <div class="avatar-loading" id="avatar-loading">
                            <div class="avatar-loading-bar">
                                <span></span>
                            </div>
                            <strong id="avatar-loading-text">Gerando seu avatar</strong>
                            <span id="avatar-loading-sub">Preparando o modelo 3D da peça</span>
                        </div>

                        <div class="avatar-hint" id="avatar-hint" hidden>
                            <i class="icon-move"></i>
                            <span>Arraste para girar</span>
                        </div>
                    </div>

                    <div class="step-panel">
                        <span class="step-panel-eyebrow">04 · Resultado</span>
                        <h2 class="step-panel-title">Seu avatar está pronto.</h2>
                        <p class="step-panel-lead">Gire, aproxime e veja a peça por todos os ângulos.</p>

                        <div class="avatar-product-block" id="avatar-summary" hidden>
                            <div class="avatar-product-image">
                                <img id="avatar-summary-image" src="<?= Html::e($imagem) ?>" alt="<?= Html::e($produto['nome']) ?>">
                            </div>
                            <div>
                                <span class="step-panel-eyebrow">Provando</span>
                                <strong id="avatar-summary-product"><?= Html::e($produto['nome']) ?></strong>
                                <span id="avatar-summary-price" class="avatar-product-price"><?= Catalogo::money($produto['valor']) ?></span>
                                <?php if ($tamanhoSelecionado !== null): ?>
                                    <span class="avatar-product-size">Tamanho <?= Html::e($tamanhoSelecionado['tamanho']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <p class="provador-privacy">
                            <i class="icon-lock"></i>
                            O avatar é uma prévia ilustrativa da peça; nenhuma foto é armazenada.
                        </p>
                    </div>
                </div>
            </section>
        </div>

        <!-- Navegação inferior -->
        <div class="provador-nav-bar">
            <div class="container provador-nav-row">
                <button type="button" class="btn-nav btn-nav-prev" id="btn-prev">
                    <i class="icon-arrow-left"></i> Voltar
                </button>
                <button type="button" class="btn-nav btn-nav-next" id="btn-next">
                    Começar <i class="icon-arrow-right"></i>
                </button>
            </div>
        </div>
    </main>

    <?php require __DIR__ . '/partials/site_footer.php'; ?>

    <script type="application/json" id="provador-dados"><?= json_encode($dadosProvador, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
    <script src="<?= asset('/js/provador-virtual.js') ?>"></script>
</body>
</html>
