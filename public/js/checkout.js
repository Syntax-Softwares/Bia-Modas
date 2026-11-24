/* Bia Modas - troca entre endereco salvo e outro endereco no checkout */
(function () {
    'use strict';

    const form = document.querySelector('[data-checkout-form]');

    if (!form) return;

    const radiosEndereco = Array.prototype.slice.call(
        form.querySelectorAll('input[type="radio"][name="endereco_id"]')
    );
    const painelNovoEndereco = form.querySelector('[data-checkout-endereco-novo]');
    const opcoesEndereco = Array.prototype.slice.call(
        form.querySelectorAll('.checkout-endereco-opcao')
    );

    if (radiosEndereco.length === 0 || !painelNovoEndereco) return;

    function sincronizarEnderecos() {
        const selecionado = form.querySelector('input[type="radio"][name="endereco_id"]:checked');
        const novoSelecionado = Boolean(selecionado && selecionado.value === 'novo');

        painelNovoEndereco.hidden = !novoSelecionado;

        opcoesEndereco.forEach(function (opcao) {
            const radio = opcao.querySelector('input[type="radio"][name="endereco_id"]');
            opcao.classList.toggle('is-selecionado', Boolean(radio && radio.checked));
        });
    }

    radiosEndereco.forEach(function (radio) {
        radio.addEventListener('change', sincronizarEnderecos);
    });

    sincronizarEnderecos();
})();
