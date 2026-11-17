(function () {
    'use strict';

    const carrosseis = {};
    const ids = ['novidades', 'promocoes', 'recomendados', 'recentes'];

    function porPagina() {
        const largura = window.innerWidth;
        if (largura >= 992) return 4;
        if (largura >= 768) return 3;
        if (largura >= 576) return 2;
        return 1;
    }

    function atualizarDots(id) {
        const estado = carrosseis[id];
        if (!estado || !estado.dots) return;

        const dots = estado.dots;
        const existentes = dots.querySelectorAll('.carousel-dot');
        const focado = document.activeElement && dots.contains(document.activeElement)
            ? Array.prototype.indexOf.call(existentes, document.activeElement)
            : -1;

        if (existentes.length !== estado.totalPaginas) {
            dots.innerHTML = '';

            for (let i = 0; i < estado.totalPaginas; i += 1) {
                const dot = document.createElement('button');
                dot.type = 'button';
                dot.className = 'carousel-dot' + (i === estado.pagina ? ' active' : '');
                dot.setAttribute('aria-label', `Página ${i + 1}`);
                dot.addEventListener('click', () => irPara(id, i));
                dots.appendChild(dot);
            }
        } else {
            existentes.forEach((dot, i) => {
                dot.classList.toggle('active', i === estado.pagina);
            });
        }

        if (focado >= 0) {
            const novo = dots.querySelectorAll('.carousel-dot')[Math.min(focado, estado.totalPaginas - 1)];
            if (novo) novo.focus();
        }
    }

    function atualizar(id) {
        const estado = carrosseis[id];
        if (!estado) return;

        const largura = 100 / estado.porPagina;

        estado.slides.forEach((slide) => {
            slide.style.flex = `0 0 ${largura}%`;
            slide.style.maxWidth = `${largura}%`;
        });

        estado.track.style.transform = `translateX(-${estado.pagina * 100}%)`;

        atualizarDots(id);

        const container = estado.track.closest('.carousel-container');
        if (!container) return;

        const anterior = container.querySelector('.carousel-btn.prev');
        const proximo = container.querySelector('.carousel-btn.next');
        if (anterior) anterior.disabled = estado.pagina === 0;
        if (proximo) proximo.disabled = estado.pagina >= estado.totalPaginas - 1;
    }

    function medir(id) {
        const estado = carrosseis[id];
        if (!estado) return;

        estado.porPagina = porPagina();
        estado.totalPaginas = Math.max(1, Math.ceil(estado.slides.length / estado.porPagina));

        if (estado.pagina >= estado.totalPaginas) {
            estado.pagina = estado.totalPaginas - 1;
        }

        atualizar(id);
    }

    function irPara(id, pagina) {
        const estado = carrosseis[id];
        if (!estado) return;
        if (pagina < 0 || pagina >= estado.totalPaginas) return;

        estado.pagina = pagina;
        atualizar(id);
    }

    function moveCarousel(id, direcao) {
        const estado = carrosseis[id];
        if (!estado) return;

        irPara(id, estado.pagina + direcao);
    }

    function montar(id) {
        const track = document.getElementById(`carousel-${id}`);
        if (!track) return;

        const slides = track.querySelectorAll('.carousel-slide');
        if (slides.length === 0) return;

        carrosseis[id] = {
            track,
            slides,
            dots: document.getElementById(`dots-${id}`),
            pagina: 0,
            porPagina: porPagina(),
            totalPaginas: 1,
        };

        medir(id);
    }

    window.moveCarousel = moveCarousel;

    document.addEventListener('DOMContentLoaded', () => {
        ids.forEach(montar);
    });

    let timer = null;
    window.addEventListener('resize', () => {
        clearTimeout(timer);
        timer = setTimeout(() => ids.forEach(medir), 150);
    });
})();
