/* Bia Modas - selecao de tamanho na pagina do produto */
(function () {
    'use strict';

    const opcoes = document.querySelector('[data-size-options]');
    if (!opcoes) return;

    const botaoCarrinho = document.querySelector('[data-adicionar][data-requer-tamanho]');
    const rotulo = document.querySelector('[data-size-selected-label]');
    const estoque = document.querySelector('[data-size-stock]');
    const botoes = Array.from(opcoes.querySelectorAll('[data-size-id]'));

    const linkProvador = document.querySelector('[data-provador-link]');
    const baseProvador = linkProvador
        ? (linkProvador.dataset.provadorBase || linkProvador.getAttribute('href') || '')
        : '';

    function atualizarLinkProvador(id) {
        if (!linkProvador || !baseProvador) return;

        linkProvador.href = id
            ? baseProvador + '&tamanho=' + encodeURIComponent(id)
            : baseProvador;
    }

    function selecionar(botaoTamanho) {
        if (!botaoTamanho || botaoTamanho.disabled) return;

        botoes.forEach(function (botao) {
            botao.classList.toggle('active', botao === botaoTamanho);
        });

        const id = botaoTamanho.dataset.sizeId || '';
        const nome = botaoTamanho.dataset.sizeNome || '';
        const quantidade = Number(botaoTamanho.dataset.sizeEstoque || 0);

        if (botaoCarrinho) botaoCarrinho.dataset.produtoTamanhoId = id;
        if (rotulo) rotulo.textContent = nome;

        atualizarLinkProvador(id);

        if (estoque) {
            if (quantidade <= 0) {
                estoque.textContent = 'Tamanho esgotado';
            } else if (quantidade <= 5) {
                estoque.textContent = 'Últimas ' + quantidade + ' unidades';
            } else {
                estoque.textContent = 'Disponível em estoque';
            }

            estoque.classList.toggle('low', quantidade > 0 && quantidade <= 5);
            estoque.classList.toggle('out', quantidade <= 0);
        }
    }

    botoes.forEach(function (botao) {
        botao.addEventListener('click', function () {
            selecionar(botao);
        });
    });

    const primeiroDisponivel = botoes.find(function (botao) {
        return !botao.disabled;
    });

    if (primeiroDisponivel) selecionar(primeiroDisponivel);
})();
