<?php
/** @var array|null $sessaoUsuario */
$sessaoUsuario = Sessao::usuario();
$nomeHeader = (string) ($sessaoUsuario['nome'] ?? '');
?>
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
                    <input type="text" name="busca" placeholder="O que você procura?" aria-label="Pesquisar produtos" maxlength="100">
                    <button type="submit" aria-label="Buscar">
                        <i class="bi bi-search"></i>
                    </button>
                </form>
            </div>
            <div class="col-lg-3 col-md-3 col-6 text-end">
                <div class="header-actions justify-content-end">
                    <a href="/perfil" class="auth-header-link" aria-label="Minha conta" aria-current="page">
                        <?php if ($nomeHeader !== ''): ?>
                            <span class="auth-avatar-logged"><?= Html::e(Html::inicial($nomeHeader)) ?></span>
                        <?php else: ?>
                            <i class="bi bi-person"></i>
                        <?php endif; ?>
                    </a>
                    <a href="/favoritos" aria-label="Favoritos">
                        <i class="bi bi-heart"></i>
                    </a>
                    <a href="/carrinho" aria-label="Carrinho">
                        <i class="bi bi-bag"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>
