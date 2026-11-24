/* Bia Modas - interações da loja (carrosséis, carrinho e busca) */
(function () {
    'use strict';

    const carousels = {};

    function getItemsPerView() {
        if (window.innerWidth >= 992) return 4;
        if (window.innerWidth >= 768) return 3;
        if (window.innerWidth >= 576) return 2;
        return 1;
    }

    function buildDots(id, totalPages, currentPage) {
        const dotsContainer = document.getElementById('dots-' + id);
        if (!dotsContainer) return;

        dotsContainer.innerHTML = '';

        for (let i = 0; i < totalPages; i += 1) {
            const dot = document.createElement('button');
            dot.type = 'button';
            dot.className = 'carousel-dot' + (i === currentPage ? ' active' : '');
            dot.setAttribute('aria-label', 'Página ' + (i + 1));
            dot.addEventListener('click', function () {
                goToPage(id, i);
            });
            dotsContainer.appendChild(dot);
        }
    }

    function updateButtons(id) {
        const carousel = carousels[id];
        const track = document.getElementById('carousel-' + id);

        if (!carousel || !track) return;

        const container = track.closest('.carousel-container');
        if (!container) return;

        const prev = container.querySelector('.carousel-btn.prev');
        const next = container.querySelector('.carousel-btn.next');

        if (prev) prev.disabled = carousel.currentPage === 0;
        if (next) next.disabled = carousel.currentPage >= carousel.totalPages - 1;
    }

    function updatePosition(id) {
        const carousel = carousels[id];
        const track = document.getElementById('carousel-' + id);

        if (!carousel || !track) return;

        track.style.transform = 'translateX(-' + (carousel.currentPage * 100) + '%)';

        const dotsContainer = document.getElementById('dots-' + id);
        if (dotsContainer) {
            dotsContainer.querySelectorAll('.carousel-dot').forEach(function (dot, index) {
                dot.classList.toggle('active', index === carousel.currentPage);
            });
        }

        updateButtons(id);
    }

    function initCarousel(id) {
        const track = document.getElementById('carousel-' + id);
        if (!track) return;

        const totalItems = track.querySelectorAll('.carousel-slide').length;
        if (totalItems === 0) return;

        const itemsPerView = getItemsPerView();
        const totalPages = Math.ceil(totalItems / itemsPerView);

        carousels[id] = {
            currentPage: 0,
            totalPages: totalPages,
            itemsPerView: itemsPerView
        };

        buildDots(id, totalPages, 0);
        updatePosition(id);
    }

    function goToPage(id, page) {
        const carousel = carousels[id];
        if (!carousel || page < 0 || page >= carousel.totalPages) return;

        carousel.currentPage = page;
        updatePosition(id);
    }

    window.moveCarousel = function (id, direction) {
        const carousel = carousels[id];
        if (!carousel) return;

        goToPage(id, carousel.currentPage + direction);
    };

    function refreshCarousels() {
        const itemsPerView = getItemsPerView();

        Object.keys(carousels).forEach(function (id) {
            const track = document.getElementById('carousel-' + id);
            if (!track) return;

            const totalItems = track.querySelectorAll('.carousel-slide').length;
            const totalPages = Math.max(1, Math.ceil(totalItems / itemsPerView));

            carousels[id].itemsPerView = itemsPerView;
            carousels[id].totalPages = totalPages;

            if (carousels[id].currentPage >= totalPages) {
                carousels[id].currentPage = totalPages - 1;
            }

            buildDots(id, totalPages, carousels[id].currentPage);
            updatePosition(id);
        });
    }

    window.openCart = function () {
        document.getElementById('cart-overlay')?.classList.add('open');
        document.getElementById('cart-offcanvas')?.classList.add('open');
        document.body.style.overflow = 'hidden';
    };

    window.closeCart = function () {
        document.getElementById('cart-overlay')?.classList.remove('open');
        document.getElementById('cart-offcanvas')?.classList.remove('open');
        document.body.style.overflow = '';
    };

    window.finalizeOrder = function () {
        window.alert('Seu carrinho está vazio. Adicione produtos para continuar.');
    };

    window.handleSearch = function () {
        const input = document.getElementById('search-input');
        const termo = input ? input.value.trim() : '';

        window.location.href = termo === ''
            ? '/categoria'
            : '/categoria?busca=' + encodeURIComponent(termo);
    };

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.carousel-track').forEach(function (track) {
            initCarousel(track.id.replace('carousel-', ''));
        });

        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            searchInput.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    window.handleSearch();
                }
            });
        }

        const year = document.getElementById('current-year');
        if (year) {
            year.textContent = new Date().getFullYear();
        }
    });

    let resizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(refreshCarousels, 200);
    });
})();
