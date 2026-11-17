<?php
$usuarioHeader = class_exists('Sessao') ? Sessao::usuario() : null;
$estaLogado = $usuarioHeader !== null;
$contaHref = $estaLogado ? '/perfil' : '/login';
$busca = (string) ($busca ?? '');
$listaSugestoes = is_array($sugestoes ?? null) ? $sugestoes : [];
$quantidadeCarrinho = (int) ($quantidadeCarrinho ?? ($data['quantidadeCarrinho'] ?? 0));
$favoritosTotal = (int) ($favoritosTotal ?? ($data['favoritosTotal'] ?? 0));
$ocultarTopBar = (bool) ($ocultarTopBar ?? false);
?>
<?php if (!$ocultarTopBar): ?>
<div class="top-bar">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start">
                <span><i class="bi bi-truck me-2"></i>Frete grátis para Jaú em compras acima de R$ 199</span>
            </div>
            <div class="col-md-6 text-center text-md-end mt-2 mt-md-0">
                <a href="tel:+5514999999999" class="me-3"><i class="bi bi-telephone me-1"></i>(14) 99999-9999</a>
                <a href="https://www.instagram.com/lojabiamodass/" target="_blank" rel="noopener noreferrer"><i class="bi bi-instagram me-1"></i>@lojabiamodass</a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<header class="main-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-3 col-md-3 col-6">
                <a href="/" class="logo">
                    <img src="/img/logoBiaModas.png" alt="Bia Modas - Moda Feminina">
                </a>
            </div>
            <div class="col-lg-6 col-md-6 d-none d-md-block">
                <form class="search-box mx-auto" method="get" action="/categoria" role="search">
                    <input
                        type="text"
                        id="search-input"
                        name="busca"
                        value="<?= e($busca) ?>"
                        <?php if ($listaSugestoes !== []): ?>list="sugestoes-busca"<?php endif; ?>
                        placeholder="O que você procura?"
                        aria-label="Pesquisar produtos"
                        maxlength="100"
                        autocomplete="off"
                    >
                    <?php if ($listaSugestoes !== []): ?>
                        <datalist id="sugestoes-busca">
                            <?php foreach ($listaSugestoes as $sugestao): ?>
                                <option value="<?= e($sugestao) ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    <?php endif; ?>
                    <button type="submit" aria-label="Buscar">
                        <i class="bi bi-search"></i>
                    </button>
                </form>
            </div>
            <div class="col-lg-3 col-md-3 col-6 text-end">
                <div class="header-actions justify-content-end">
                    <?php if ($estaLogado && !empty($usuarioHeader['is_admin'])): ?>
                        <a href="/admin" class="auth-header-link" aria-label="Painel administrativo" title="Painel administrativo">
                            <i class="bi bi-speedometer2"></i>
                        </a>
                    <?php endif; ?>
                    <a href="<?= e($contaHref) ?>" class="auth-header-link" aria-label="Minha conta">
                        <?php if ($estaLogado): ?>
                            <span class="auth-avatar-logged" title="<?= e($usuarioHeader['nome'] ?? '') ?>"><?= e(inicial((string) ($usuarioHeader['nome'] ?? ''))) ?></span>
                        <?php else: ?>
                            <i class="bi bi-person"></i>
                        <?php endif; ?>
                    </a>
                    <a href="/pedidos" aria-label="Meus pedidos">
                        <i class="bi bi-receipt"></i>
                    </a>
                    <a href="/favoritos" aria-label="Favoritos">
                        <i class="bi bi-heart"></i>
                        <span class="badge-count" id="favoritos-count"><?= $favoritosTotal ?></span>
                    </a>
                    <a href="/carrinho" aria-label="Carrinho" data-cart-open>
                        <i class="bi bi-bag"></i>
                        <span class="badge-count" id="cart-count"><?= $quantidadeCarrinho ?></span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<?php require __DIR__ . '/cart_offcanvas.php'; ?>
