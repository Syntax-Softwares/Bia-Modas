/* Bia Modas - drawer de filtros do catálogo */
(function () {
    'use strict';

    const sidebar = document.getElementById('category-sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');
    const chave = 'bia-filtros-abertos';

    if (!sidebar || !backdrop) return;

    function abrir() {
        sidebar.classList.add('open');
        backdrop.classList.add('open');
        document.body.classList.add('sidebar-open');
        sessionStorage.setItem(chave, '1');
    }

    function fechar() {
        sidebar.classList.remove('open');
        backdrop.classList.remove('open');
        document.body.classList.remove('sidebar-open');
        sessionStorage.removeItem(chave);
    }

    document.addEventListener('click', function (evento) {
        if (evento.target.closest('[data-filter-open]')) {
            evento.preventDefault();
            abrir();
            return;
        }

        if (evento.target.closest('[data-filter-close]')) {
            evento.preventDefault();
            fechar();
        }
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') fechar();
    });

    if (sessionStorage.getItem(chave) === '1' && window.matchMedia('(min-width: 992px)').matches) {
        abrir();
    }
})();
