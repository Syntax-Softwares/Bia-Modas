/* Bia Modas - widget de estrelas e contador do formulario de avaliacao. */
(function () {
    'use strict';

    document.querySelectorAll('[data-avaliacao-nota]').forEach(function (grupo) {
        const texto = grupo.querySelector('[data-nota-texto]');
        const radios = grupo.querySelectorAll('input[type="radio"]');

        function atualizar(nota) {
            if (!texto) {
                return;
            }

            texto.textContent = nota > 0
                ? 'Sua nota: ' + nota + ' de 5'
                : 'Clique nas estrelas';
        }

        radios.forEach(function (radio) {
            radio.addEventListener('change', function () {
                if (radio.checked) {
                    atualizar(Number(radio.value));
                }
            });
        });
    });

    document.querySelectorAll('[data-contador]').forEach(function (contador) {
        const formulario = contador.closest('form');
        const campo = formulario ? formulario.querySelector('textarea[maxlength]') : null;

        if (!campo) {
            return;
        }

        const limite = Number(campo.getAttribute('maxlength')) || 500;

        function atualizarContador() {
            contador.textContent = campo.value.length + '/' + limite;
        }

        campo.addEventListener('input', atualizarContador);
        atualizarContador();
    });
})();
