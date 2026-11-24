<?php
// Evita duplicidade quando a pagina tambem inclui este partial explicitamente.
if (!empty($GLOBALS['__bia_cart_offcanvas'])) {
    return;
}
$GLOBALS['__bia_cart_offcanvas'] = true;
?>
<div class="cart-overlay" id="cart-overlay" data-cart-close></div>

<?php
$resumoInicial = $resumoInicial ?? ($data['resumo'] ?? null);
?>
<?php if (is_array($resumoInicial)): ?>
<script type="application/json" id="estado-carrinho-inicial"><?= json_encode($resumoInicial, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<?php endif; ?>

<aside class="cart-offcanvas" id="cart-offcanvas" aria-label="Meu carrinho" aria-hidden="true">
    <div class="cart-header">
        <h3><i class="icon-shopping-bag me-2"></i>Meu Carrinho</h3>
        <button type="button" class="cart-close" data-cart-close aria-label="Fechar carrinho">
            <i class="icon-x"></i>
        </button>
    </div>

    <div class="cart-items" id="cart-items">
        <div class="cart-empty">
            <i class="icon-shopping-bag"></i>
            <p>Seu carrinho está vazio</p>
            <p style="font-size: 0.9rem;">Adicione produtos para continuar</p>
        </div>
    </div>

    <div class="cart-footer" id="cart-footer" hidden>
        <div class="cart-total">
            <span>Total:</span>
            <span id="cart-total">R$ 0,00</span>
        </div>
        <a class="btn-checkout" href="/carrinho">Ver carrinho e finalizar</a>
    </div>
</aside>
