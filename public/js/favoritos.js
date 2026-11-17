(function () {
    'use strict';

    const { toast, postar } = window.BiaModas;
    const pagina = document.body.dataset.page;
    let versaoEstado = 0;

    function botoesDoProduto(idProduto) {
        return document.querySelectorAll(`[data-favorito][data-produto-id="${idProduto}"]`);
    }

    function atualizarBotao(idProduto, favorito) {
        botoesDoProduto(idProduto).forEach((botao) => {
            botao.classList.toggle('active', favorito);
            botao.setAttribute('aria-pressed', favorito ? 'true' : 'false');
            botao.setAttribute('aria-label', favorito ? 'Remover dos favoritos' : 'Adicionar aos favoritos');

            const icone = botao.querySelector('i');
            if (icone) {
                icone.className = favorito ? 'bi bi-heart-fill' : 'bi bi-heart';
            }
        });
    }

    function atualizarContador(total) {
        const contador = document.getElementById('favoritos-count');
        if (contador) contador.textContent = Number(total) || 0;
    }

    function removerDaLista(idProduto) {
        const cartao = document.querySelector(`[data-produto-card="${idProduto}"]`);
        if (cartao) cartao.remove();

        const restantes = document.querySelectorAll('#favoritos-lista [data-produto-card]').length;
        if (restantes === 0) {
            const vazio = document.getElementById('favoritos-vazio');
            if (vazio) vazio.hidden = false;
        }
    }

    function reconciliarLista(ids) {
        document.querySelectorAll('#favoritos-lista [data-produto-card]').forEach((cartao) => {
            if (!ids.has(String(cartao.dataset.produtoCard))) {
                cartao.remove();
            }
        });

        const restantes = document.querySelectorAll('#favoritos-lista [data-produto-card]').length;
        const vazio = document.getElementById('favoritos-vazio');
        if (vazio) vazio.hidden = restantes > 0;
    }

    function aplicarSnapshot(ids, total) {
        const conjunto = new Set((ids || []).map(String));

        document.querySelectorAll('[data-favorito]').forEach((botao) => {
            atualizarBotao(botao.dataset.produtoId, conjunto.has(String(botao.dataset.produtoId)));
        });

        atualizarContador(total);

        if (pagina === 'favoritos') {
            reconciliarLista(conjunto);
        }
    }

    document.addEventListener('click', async (evento) => {
        const alvo = evento.target.closest('[data-favorito]');
        if (!alvo) return;

        evento.preventDefault();
        if (alvo.disabled) return;

        versaoEstado += 1;
        alvo.disabled = true;
        const idProduto = alvo.dataset.produtoId;

        try {
            const resposta = await postar('/favoritos/alternar', { produto_id: idProduto });

            atualizarBotao(idProduto, resposta.favorito);
            atualizarContador(resposta.total);

            if (resposta.mensagem) toast(resposta.mensagem);

            if (pagina === 'favoritos' && !resposta.favorito) {
                removerDaLista(idProduto);
            }
        } catch (erro) {
            toast(erro.message, true);
        } finally {
            alvo.disabled = false;
        }
    });

    window.addEventListener('pageshow', async (evento) => {
        if (!evento.persisted) return;

        const versao = versaoEstado;

        try {
            const resposta = await fetch('/favoritos/dados', { credentials: 'same-origin' });
            if (!resposta.ok) return;

            const dados = await resposta.json();

            if (versao !== versaoEstado) return;

            aplicarSnapshot(dados.ids, dados.total);
        } catch (erro) {
        }
    });
})();
