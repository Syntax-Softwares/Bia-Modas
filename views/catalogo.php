<?php
/** @var array $filtros */
/** @var array $resultado */
/** @var string $titulo */
/** @var array $categorias */
/** @var array $tamanhosDisponiveis */
/** @var array $coresDisponiveis */
/** @var array $estilosDisponiveis */
/** @var array $sugestoes */
/** @var array $flash */
/** @var int $totalProdutos */

$buscaAtual = (string) ($filtros['busca'] ?? '');
$categoriaAtual = (string) ($filtros['categoria'] ?? '');
$promocoesAtivas = !empty($filtros['promocoes']);
$coresSelecionadas = $filtros['cores'];
$estilosSelecionados = $filtros['estilos'];
$tamanhosSelecionados = $filtros['tamanhos'];
$ordenacaoAtual = (string) ($filtros['ordenacao'] ?? 'relevancia');
$temPreco = $filtros['preco_min'] !== null || $filtros['preco_max'] !== null;

$url = static fn (array $overrides = []): string => Catalogo::url($filtros, $overrides);

$alternar = static function (array $lista, string $valor): array {
    $indice = array_search($valor, $lista, true);

    if ($indice === false) {
        $lista[] = $valor;
    } else {
        unset($lista[$indice]);
    }

    return array_values($lista);
};

$totalFiltrosAtivos = ($buscaAtual !== '' ? 1 : 0)
    + ($categoriaAtual !== '' ? 1 : 0)
    + count($tamanhosSelecionados)
    + count($coresSelecionadas)
    + count($estilosSelecionados)
    + ($temPreco ? 1 : 0)
    + ($promocoesAtivas ? 1 : 0);

$precoLabel = '';

if ($filtros['preco_min'] !== null && $filtros['preco_max'] !== null) {
    $precoLabel = (float) $filtros['preco_min'] === 0.0
        ? 'Até ' . Catalogo::money($filtros['preco_max'])
        : Catalogo::money($filtros['preco_min']) . ' a ' . Catalogo::money($filtros['preco_max']);
} elseif ($filtros['preco_min'] !== null) {
    $precoLabel = 'Acima de ' . Catalogo::money($filtros['preco_min']);
} elseif ($filtros['preco_max'] !== null) {
    $precoLabel = 'Até ' . Catalogo::money($filtros['preco_max']);
}

$chips = [];

if ($buscaAtual !== '') {
    $chips[] = ['label' => 'Busca: ' . $buscaAtual, 'url' => $url(['busca' => null])];
}

if ($categoriaAtual !== '') {
    $chips[] = ['label' => 'Categoria: ' . $categoriaAtual, 'url' => $url(['categoria' => null])];
}

foreach ($tamanhosSelecionados as $tamanho) {
    $chips[] = [
        'label' => 'Tamanho: ' . $tamanho,
        'url' => $url(['tamanhos' => $alternar($tamanhosSelecionados, $tamanho)]),
    ];
}

foreach ($coresSelecionadas as $cor) {
    $chips[] = [
        'label' => 'Cor: ' . $cor,
        'url' => $url(['cores' => $alternar($coresSelecionadas, $cor)]),
    ];
}

foreach ($estilosSelecionados as $estilo) {
    $chips[] = [
        'label' => 'Estilo: ' . $estilo,
        'url' => $url(['estilos' => $alternar($estilosSelecionados, $estilo)]),
    ];
}

if ($temPreco) {
    $chips[] = ['label' => 'Preço: ' . $precoLabel, 'url' => $url(['preco_min' => null, 'preco_max' => null])];
}

if ($promocoesAtivas) {
    $chips[] = ['label' => 'Somente em promoção', 'url' => $url(['promocoes' => null])];
}

$camposOcultos = static function () use ($filtros): void {
    foreach ($filtros as $chave => $valor) {
        if ($chave === 'ordenacao' || $valor === null || $valor === '' || $valor === false) {
            continue;
        }

        if (is_array($valor)) {
            foreach ($valor as $item) {
                echo '<input type="hidden" name="' . Html::e($chave) . '[]" value="' . Html::e($item) . '">';
            }

            continue;
        }

        echo '<input type="hidden" name="' . Html::e($chave) . '" value="' . Html::e($valor) . '">';
    }
};

$faixasPreco = [
    ['label' => 'Até R$ 100', 'min' => 0, 'max' => 100],
    ['label' => 'R$ 100 a R$ 150', 'min' => 100, 'max' => 150],
    ['label' => 'R$ 150 a R$ 200', 'min' => 150, 'max' => 200],
    ['label' => 'Acima de R$ 200', 'min' => 200, 'max' => null],
];
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Bia Modas - Catálogo de moda plus size.">
    <title><?= Html::e($titulo) ?> | Bia Modas</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/css/base.css">
    <link rel="stylesheet" href="/css/layout.css">
    <link rel="stylesheet" href="/css/components.css">
    <link rel="stylesheet" href="/css/responsive.css">
    <link rel="stylesheet" href="/css/auth.css">
    <link rel="stylesheet" href="/css/categoria.css">
    <link rel="stylesheet" href="/css/catalogo.css">
</head>
<body>
    <?php require __DIR__ . '/partials/catalogo_header.php'; ?>

    <section class="page-header">
        <div class="container">
            <nav class="breadcrumb" aria-label="Navegação">
                <a href="/">Início</a>
                <i class="bi bi-chevron-right" aria-hidden="true"></i>
                <span><?= Html::e($titulo) ?></span>
            </nav>
            <h1><?= Html::e($titulo) ?></h1>
            <p>
                <?php if ($resultado['total'] === 0): ?>
                    Nenhum produto encontrado
                <?php else: ?>
                    <?= (int) $resultado['total'] ?> produto<?= $resultado['total'] === 1 ? '' : 's' ?> encontrado<?= $resultado['total'] === 1 ? '' : 's' ?>
                <?php endif; ?>
            </p>
        </div>
    </section>

    <section class="filter-page">
        <div class="container">
            <?php require __DIR__ . '/partials/catalogo_alerts.php'; ?>

            <div class="catalogo-layout">
                <aside class="catalogo-sidebar" aria-label="Filtros do catálogo">
                    <details class="catalogo-filtros" open>
                        <summary>
                            <span>Filtros</span>
                            <?php if ($totalFiltrosAtivos > 0): ?>
                                <span class="catalogo-filtros-count"><?= $totalFiltrosAtivos ?></span>
                            <?php endif; ?>
                        </summary>

                        <div class="catalogo-filtros-corpo">
                            <a class="btn-clear-filters" href="/categoria">
                                <i class="bi bi-x-circle me-1"></i>Limpar todos os filtros
                            </a>

                            <div class="filter-section">
                                <h4>Categorias</h4>
                                <ul class="filter-category-list">
                                    <li>
                                        <a href="<?= Html::e($url(['categoria' => null])) ?>" class="<?= $categoriaAtual === '' ? 'active' : '' ?>">
                                            Todos <span class="count"><?= (int) $totalProdutos ?></span>
                                        </a>
                                    </li>
                                    <?php foreach ($categorias as $categoria): ?>
                                        <?php $ativa = $categoriaAtual === $categoria['nome']; ?>
                                        <li>
                                            <a
                                                href="<?= Html::e($url(['categoria' => $ativa ? null : $categoria['nome']])) ?>"
                                                class="<?= $ativa ? 'active' : '' ?>"
                                            >
                                                <?= Html::e($categoria['nome']) ?>
                                                <span class="count"><?= (int) $categoria['total'] ?></span>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>

                            <?php if ($coresDisponiveis !== []): ?>
                                <div class="filter-section">
                                    <h4>Cores</h4>
                                    <div class="filter-colors">
                                        <?php foreach ($coresDisponiveis as $corItem): ?>
                                            <?php
                                            $cor = $corItem['cor'];
                                            $ativa = in_array($cor, $coresSelecionadas, true);
                                            $estiloCor = 'background: ' . Catalogo::corHex($cor) . ';';
                                            if (Catalogo::corEhClara($cor)) {
                                                $estiloCor .= 'border: 1px solid #ddd;';
                                            }
                                            ?>
                                            <a
                                                href="<?= Html::e($url(['cores' => $alternar($coresSelecionadas, $cor)])) ?>"
                                                class="color-swatch<?= $ativa ? ' active' : '' ?>"
                                                style="<?= Html::e($estiloCor) ?>"
                                                title="<?= Html::e($cor) ?> (<?= (int) $corItem['total'] ?>)"
                                                aria-label="Cor <?= Html::e($cor) ?>"
                                                aria-pressed="<?= $ativa ? 'true' : 'false' ?>"
                                            ></a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="filter-section">
                                <h4>Preço</h4>
                                <div class="filter-price-options">
                                    <?php foreach ($faixasPreco as $faixa): ?>
                                        <?php
                                        $ativa = $filtros['preco_min'] !== null
                                            && (float) $filtros['preco_min'] === (float) $faixa['min']
                                            && (
                                                ($faixa['max'] === null && $filtros['preco_max'] === null)
                                                || ($faixa['max'] !== null && $filtros['preco_max'] !== null && (float) $filtros['preco_max'] === (float) $faixa['max'])
                                            );
                                        $destino = $ativa
                                            ? ['preco_min' => null, 'preco_max' => null]
                                            : ['preco_min' => $faixa['min'], 'preco_max' => $faixa['max']];
                                        ?>
                                        <a
                                            href="<?= Html::e($url($destino)) ?>"
                                            class="filter-price-btn<?= $ativa ? ' active' : '' ?>"
                                        >
                                            <?= Html::e($faixa['label']) ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <?php if ($tamanhosDisponiveis !== []): ?>
                                <div class="filter-section">
                                    <h4>Tamanhos</h4>
                                    <div class="filter-sizes">
                                        <?php foreach ($tamanhosDisponiveis as $tamanho): ?>
                                            <?php $ativo = in_array($tamanho, $tamanhosSelecionados, true); ?>
                                            <a
                                                href="<?= Html::e($url(['tamanhos' => $alternar($tamanhosSelecionados, $tamanho)])) ?>"
                                                class="filter-size-btn<?= $ativo ? ' active' : '' ?>"
                                            >
                                                <?= Html::e($tamanho) ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($estilosDisponiveis !== []): ?>
                                <div class="filter-section">
                                    <h4>Estilos</h4>
                                    <div class="filter-checkbox-group">
                                        <?php foreach ($estilosDisponiveis as $estiloItem): ?>
                                            <?php
                                            $estilo = $estiloItem['estilo'];
                                            $ativo = in_array($estilo, $estilosSelecionados, true);
                                            ?>
                                            <a
                                                href="<?= Html::e($url(['estilos' => $alternar($estilosSelecionados, $estilo)])) ?>"
                                                class="filter-estilo<?= $ativo ? ' active' : '' ?>"
                                            >
                                                <?= Html::e($estilo) ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="filter-section">
                                <a
                                    href="<?= Html::e($url(['promocoes' => $promocoesAtivas ? null : 1])) ?>"
                                    class="filter-promo-toggle<?= $promocoesAtivas ? ' active' : '' ?>"
                                >
                                    <i class="bi bi-tag-fill" aria-hidden="true"></i>
                                    <span>Somente em Promoção</span>
                                </a>
                            </div>
                        </div>
                    </details>
                </aside>

                <div class="catalogo-conteudo">
                    <div class="filter-page-topbar">
                        <span class="filter-results-count">
                            <?= (int) $resultado['total'] ?> produto<?= $resultado['total'] === 1 ? '' : 's' ?>
                        </span>

                        <form class="filter-sort" method="get" action="/categoria">
                            <?php $camposOcultos(); ?>
                            <label for="sort-select">Ordenar</label>
                            <select id="sort-select" name="ordenacao">
                                <option value="relevancia" <?= $ordenacaoAtual === 'relevancia' ? 'selected' : '' ?>>Relevância</option>
                                <option value="menor-preco" <?= $ordenacaoAtual === 'menor-preco' ? 'selected' : '' ?>>Menor Preço</option>
                                <option value="maior-preco" <?= $ordenacaoAtual === 'maior-preco' ? 'selected' : '' ?>>Maior Preço</option>
                                <option value="nome" <?= $ordenacaoAtual === 'nome' ? 'selected' : '' ?>>Nome A-Z</option>
                            </select>
                            <button type="submit" class="filter-sort-apply">Aplicar</button>
                        </form>
                    </div>

                    <?php if ($chips !== []): ?>
                        <div class="active-filters">
                            <?php foreach ($chips as $chip): ?>
                                <span class="filter-chip">
                                    <?= Html::e($chip['label']) ?>
                                    <a href="<?= Html::e($chip['url']) ?>" aria-label="Remover filtro <?= Html::e($chip['label']) ?>">
                                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                                    </a>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($resultado['itens'] === []): ?>
                        <div class="catalog-empty">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <h2>Nenhum produto encontrado</h2>
                            <p>
                                <?php if ($buscaAtual !== ''): ?>
                                    Não encontramos resultados para "<?= Html::e($buscaAtual) ?>".
                                <?php else: ?>
                                    Não há produtos com os filtros selecionados.
                                <?php endif; ?>
                                Tente outra busca ou remova alguns filtros.
                            </p>
                            <a href="/categoria" class="btn-primary-solid">Limpar filtros</a>

                            <?php if ($categorias !== []): ?>
                                <div class="catalog-empty-suggestions">
                                    <h3>Explore por categoria</h3>
                                    <div class="catalog-empty-tags">
                                        <?php foreach (array_slice($categorias, 0, 6) as $categoria): ?>
                                            <a href="<?= Html::e($url(['categoria' => $categoria['nome'], 'busca' => null, 'pagina' => null])) ?>" class="catalog-empty-tag">
                                                <?= Html::e($categoria['nome']) ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="row g-4">
                            <?php foreach ($resultado['itens'] as $produto): ?>
                                <div class="col-6 col-md-4 col-lg-3">
                                    <?php require __DIR__ . '/partials/catalogo_product_card.php'; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($resultado['paginas'] > 1): ?>
                            <?php
                            $paginaAtual = (int) $resultado['pagina'];
                            $paginas = (int) $resultado['paginas'];
                            $inicio = max(1, $paginaAtual - 2);
                            $fim = min($paginas, $paginaAtual + 2);
                            ?>
                            <nav class="catalogo-paginacao" aria-label="Paginação do catálogo">
                                <?php if ($paginaAtual > 1): ?>
                                    <a href="<?= Html::e($url(['pagina' => $paginaAtual - 1])) ?>" class="paginacao-nav">Anterior</a>
                                <?php endif; ?>

                                <?php if ($inicio > 1): ?>
                                    <a href="<?= Html::e($url(['pagina' => 1])) ?>">1</a>
                                    <?php if ($inicio > 2): ?><span class="paginacao-reticencias">…</span><?php endif; ?>
                                <?php endif; ?>

                                <?php for ($pagina = $inicio; $pagina <= $fim; $pagina++): ?>
                                    <?php if ($pagina === $paginaAtual): ?>
                                        <span class="active" aria-current="page"><?= $pagina ?></span>
                                    <?php else: ?>
                                        <a href="<?= Html::e($url(['pagina' => $pagina])) ?>"><?= $pagina ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>

                                <?php if ($fim < $paginas): ?>
                                    <?php if ($fim < $paginas - 1): ?><span class="paginacao-reticencias">…</span><?php endif; ?>
                                    <a href="<?= Html::e($url(['pagina' => $paginas])) ?>"><?= $paginas ?></a>
                                <?php endif; ?>

                                <?php if ($paginaAtual < $paginas): ?>
                                    <a href="<?= Html::e($url(['pagina' => $paginaAtual + 1])) ?>" class="paginacao-nav">Próxima</a>
                                <?php endif; ?>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <?php require __DIR__ . '/partials/catalogo_footer.php'; ?>
</body>
</html>
