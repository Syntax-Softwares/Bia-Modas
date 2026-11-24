/* Bia Modas - mascaras de telefone e CPF */
(function () {
    'use strict';

    function somenteDigitos(valor) {
        return (valor || '').replace(/\D+/g, '');
    }

    function formatarTelefone(digitos) {
        if (digitos.length === 11) {
            return '(' + digitos.slice(0, 2) + ') ' + digitos.slice(2, 7) + '-' + digitos.slice(7);
        }

        if (digitos.length === 10) {
            return '(' + digitos.slice(0, 2) + ') ' + digitos.slice(2, 6) + '-' + digitos.slice(6);
        }

        return digitos;
    }

    function formatarCpf(digitos) {
        if (digitos.length !== 11) return digitos;

        return digitos.slice(0, 3) + '.' + digitos.slice(3, 6) + '.' + digitos.slice(6, 9) + '-' + digitos.slice(9);
    }

    function completo(tipo, digitos) {
        if (tipo === 'cpf') return digitos.length === 11;
        return digitos.length === 10 || digitos.length === 11;
    }

    function formatar(tipo, digitos) {
        return tipo === 'cpf' ? formatarCpf(digitos) : formatarTelefone(digitos);
    }

    document.querySelectorAll('[data-mask="telefone"], [data-mask="cpf"]').forEach(function (input) {
        const tipo = input.dataset.mask;
        const maximo = 11;
        const digitosIniciais = somenteDigitos(input.value).slice(0, maximo);

        input.value = completo(tipo, digitosIniciais) ? formatar(tipo, digitosIniciais) : digitosIniciais;

        input.addEventListener('keydown', function (evento) {
            if (evento.ctrlKey || evento.metaKey || evento.altKey) return;
            if (evento.key.length === 1 && !/[0-9]/.test(evento.key)) {
                evento.preventDefault();
            }
        });

        input.addEventListener('input', function () {
            const posicao = input.selectionStart || 0;
            const antes = somenteDigitos(input.value.slice(0, posicao)).length;
            const digitos = somenteDigitos(input.value).slice(0, maximo);

            input.value = digitos;

            const novaPosicao = Math.min(antes, digitos.length);
            input.setSelectionRange(novaPosicao, novaPosicao);
        });

        input.addEventListener('focus', function () {
            input.value = somenteDigitos(input.value).slice(0, maximo);
        });

        input.addEventListener('blur', function () {
            const digitos = somenteDigitos(input.value).slice(0, maximo);
            input.value = completo(tipo, digitos) ? formatar(tipo, digitos) : digitos;
        });
    });
})();
