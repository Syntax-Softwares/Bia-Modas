/* Bia Modas - interações do painel administrativo */
(function () {
    'use strict';

    document.addEventListener('submit', function (event) {
        const form = event.target.closest('form[data-confirm]');
        if (!form) return;

        const mensagem = form.getAttribute('data-confirm') || 'Confirmar esta ação?';
        if (!window.confirm(mensagem)) {
            event.preventDefault();
        }
    });

    const input = document.querySelector('[data-image-input]');
    const preview = document.querySelector('[data-image-preview]');

    if (input && preview) {
        input.addEventListener('input', function () {
            const caminho = input.value.trim();
            preview.src = caminho !== '' ? caminho : '/img/logoBiaModas.png';
        });
    }

    document.querySelectorAll('[data-size-check]').forEach(function (check) {
        const item = check.closest('.admin-size-item');
        const stock = item ? item.querySelector('[data-size-stock]') : null;

        if (!stock) return;

        function sincronizar() {
            stock.disabled = !check.checked;
            if (!check.checked) stock.value = 0;
        }

        check.addEventListener('change', sincronizar);
        sincronizar();
    });
})();
