<?php
$estaLogado = $estaLogado ?? false;
$usuario = $usuario ?? null;
$contaHref = $contaHref ?? '/login';
$busca = $busca ?? '';
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
                <div class="search-box mx-auto">
                    <input
                        type="text"
                        id="search-input"
                        value="<?= e($busca) ?>"
                        placeholder="O que você procura?"
                        aria-label="Pesquisar produtos"
                    >
                    <button type="button" aria-label="Buscar" onclick="handleSearch()">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-6 text-end">
                <div class="header-actions justify-content-end">
                    <?php if ($estaLogado && $usuario !== null && filter_var($usuario['is_admin'] ?? false, FILTER_VALIDATE_BOOLEAN)): ?>
                        <a href="/admin" class="auth-header-link" aria-label="Painel administrativo" title="Painel administrativo">
                            <i class="bi bi-speedometer2"></i>
                        </a>
                    <?php endif; ?>
                    <a href="<?= e($contaHref) ?>" class="auth-header-link" aria-label="Minha conta">
                        <?php if ($estaLogado && $usuario !== null): ?>
                            <span class="auth-avatar-logged" title="<?= e($usuario['nome']) ?>"><?= e(inicial($usuario['nome'])) ?></span>
                        <?php else: ?>
                            <i class="bi bi-person"></i>
                        <?php endif; ?>
                    </a>
                    <a href="/favoritos" aria-label="Favoritos">
                        <i class="bi bi-heart"></i>
                    </a>
                    <a href="#" aria-label="Carrinho" onclick="openCart(); return false;">
                        <i class="bi bi-bag"></i>
                        <span class="badge-count" id="cart-count">0</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<div class="cart-overlay" id="cart-overlay" onclick="closeCart()"></div>
<div class="cart-offcanvas" id="cart-offcanvas">
    <div class="cart-header">
        <h3><i class="bi bi-bag me-2"></i>Meu Carrinho</h3>
        <button class="cart-close" onclick="closeCart()" aria-label="Fechar carrinho">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="cart-items" id="cart-items">
        <div class="cart-empty">
            <i class="bi bi-bag"></i>
            <p>Seu carrinho está vazio</p>
            <p style="font-size: 0.9rem;">Adicione produtos para continuar</p>
        </div>
    </div>
    <div class="cart-footer" id="cart-footer" style="display: none;">
        <div class="cart-total">
            <span>Total:</span>
            <span id="cart-total">R$ 0,00</span>
        </div>
        <button class="btn-checkout" onclick="finalizeOrder()">
            <i class="bi bi-whatsapp"></i>
            Finalizar no WhatsApp
        </button>
    </div>
</div>
