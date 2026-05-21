/**
 * Bia Modas - Componentes Compartilhados
 * ======================================
 * Injeta HTML reutilizável (top-bar, header, cart, toast, footer)
 * via template strings. Funciona 100% offline (file://).
 *
 * Uso: BiaModasComponents.inject({ topBar: true, header: true, ... });
 */
(function() {
    'use strict';

    const TOP_BAR = `
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
    </div>`;

    const HEADER = `
    <header class="main-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-3 col-md-3 col-6">
                    <a href="{{LOGO_HREF}}" class="logo">
                        <img src="./img/logoBiaModas.png" alt="Bia Modas - Moda Feminina">
                    </a>
                </div>
                <div class="col-lg-6 col-md-6 d-none d-md-block">
                    <div class="search-box mx-auto">
                        <input type="text" id="search-input" placeholder="O que você procura?" aria-label="Pesquisar produtos">
                        <button type="button" aria-label="Buscar" onclick="handleSearch()">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-lg-3 col-md-3 col-6 text-end">
                    <div class="header-actions justify-content-end">
                        <a href="{{CONTA_HREF}}" class="auth-header-link" aria-label="Minha conta">
                            <i class="bi bi-person"></i>
                        </a>
                        <a href="./favoritos.html" aria-label="Favoritos">
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
    </header>`;

    const CART = `
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
    </div>`;

    const TOAST = `
    <div class="toast-notification" id="toast-notification">
        <i class="bi bi-check-circle"></i>
        <span id="toast-message">Produto adicionado ao carrinho!</span>
    </div>`;

    const FOOTER = `
    <footer class="main-footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                    <div class="footer-widget">
                        <img src="./img/logoBiaModas.png" alt="Bia Modas" class="footer-logo">
                        <p>Moda feminina com estilo, qualidade e preços acessíveis. Há mais de 10 anos vestindo mulheres de Jaú e região.</p>
                        <div class="social-links">
                            <a href="https://www.instagram.com/lojabiamodass/" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                            <a href="https://www.facebook.com/jaubiamodas/" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                            <a href="https://api.whatsapp.com/send?phone=5514999999999&text=Ol%C3%A1%2C%20tudo%20bem%3F" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                    <div class="footer-widget">
                        <h3>Institucional</h3>
                        <ul>
                            <li><a href="#">Sobre Nós</a></li>
                            <li><a href="#">Nossas Lojas</a></li>
                            <li><a href="#">Trabalhe Conosco</a></li>
                            <li><a href="#">Política de Privacidade</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                    <div class="footer-widget">
                        <h3>Ajuda</h3>
                        <ul>
                            <li><a href="#">Como Comprar</a></li>
                            <li><a href="#">Prazos e Entregas</a></li>
                            <li><a href="#">Trocas e Devoluções</a></li>
                            <li><a href="#">Formas de Pagamento</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="footer-widget">
                        <h3>Contato</h3>
                        <div class="footer-contact">
                            <p><i class="bi bi-geo-alt"></i> R. Humaitá, 1310 - Centro<br>Jaú - SP, 17201-320</p>
                            <p><i class="bi bi-telephone"></i> (14) 99999-9999</p>
                            <p><i class="bi bi-envelope"></i> biamodas@email.com</p>
                            <p><i class="bi bi-clock"></i> Seg a Sáb: 9h às 18h</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <span id="current-year">2025</span> Bia Modas. Todos os direitos reservados. Desenvolvido com amor em Jau/SP</p>
            </div>
        </div>
    </footer>`;

    window.BiaModasComponents = {
        inject: function(options) {
            const cfg = Object.assign({
                topBar: true,
                header: true,
                cart: true,
                toast: true,
                footer: true,
                logoHref: './index.html',
                contaHref: '#'
            }, options);

            const body = document.body;
            if (!body) return;

            // Prepend: top-bar + header
            let prepend = '';
            if (cfg.topBar) prepend += TOP_BAR;
            if (cfg.header) {
                prepend += HEADER
                    .replace(/\{\{LOGO_HREF\}\}/g, cfg.logoHref)
                    .replace(/\{\{CONTA_HREF\}\}/g, cfg.contaHref);
            }
            if (prepend) {
                const wrapper = document.createElement('div');
                wrapper.innerHTML = prepend.trim();
                while (wrapper.firstChild) {
                    body.insertBefore(wrapper.firstChild, body.firstChild);
                }
            }

            // Append: cart + toast + footer
            // Adiado para DOMContentLoaded para garantir que vá para o final,
            // depois de todo o conteúdo da página ser parseado.
            let append = '';
            if (cfg.cart) append += CART;
            if (cfg.toast) append += TOAST;
            if (cfg.footer) append += FOOTER;
            if (append) {
                const appendHTML = append.trim();
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', function doAppend() {
                        const wrapper = document.createElement('div');
                        wrapper.innerHTML = appendHTML;
                        while (wrapper.firstChild) {
                            document.body.appendChild(wrapper.firstChild);
                        }
                    });
                } else {
                    const wrapper = document.createElement('div');
                    wrapper.innerHTML = appendHTML;
                    while (wrapper.firstChild) {
                        document.body.appendChild(wrapper.firstChild);
                    }
                }
            }
        }
    };
})();
