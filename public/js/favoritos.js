/* Bia Modas - favoritar/desfavoritar produtos via fetch. */
(function () {
    'use strict';

    const naPaginaDeFavoritos = window.location.pathname === '/favoritos';

    function aplicarEstado(botao, favoritado) {
        botao.classList.toggle('active', favoritado);
        botao.setAttribute('aria-pressed', favoritado ? 'true' : 'false');
        botao.setAttribute(
            'aria-label',
            favoritado ? 'Remover dos favoritos' : 'Salvar nos favoritos'
        );
        botao.setAttribute(
            'title',
            favoritado ? 'Remover dos favoritos' : 'Salvar nos favoritos'
        );

        const icone = botao.querySelector('i');

        if (icone) {
            icone.classList.toggle('bi-heart-fill', favoritado);
            icone.classList.toggle('bi-heart', !favoritado);
        }

        const rotulo = botao.querySelector('[data-favorito-texto]');

        if (rotulo) {
            rotulo.textContent = favoritado
                ? 'Salvo nos favoritos'
                : 'Salvar nos favoritos';
        }
    }

    function removerDoGrid(botao) {
        const coluna = botao.closest('.col-6, .col-md-4, .col-lg-3');

        if (coluna) {
            coluna.remove();
        }

        if (document.querySelectorAll('[data-favorito]').length === 0) {
            window.location.reload();
        }
    }

    document.addEventListener('click', function (evento) {
        const botao = evento.target.closest('[data-favorito]');

        if (!botao) {
            return;
        }

        evento.preventDefault();

        if (botao.dataset.carregando === '1') {
            return;
        }

        botao.dataset.carregando = '1';

        window.BiaModas
            .postar('/favoritos/alternar', { produto_id: botao.getAttribute('data-favorito') })
            .then(function (resposta) {
                aplicarEstado(botao, resposta.favorito);
                window.BiaModas.toast(resposta.mensagem);

                if (naPaginaDeFavoritos && resposta.favorito === false) {
                    removerDoGrid(botao);
                }
            })
            .catch(function (erro) {
                if (erro.status === 401) {
                    window.location.href = '/login';
                    return;
                }

                window.BiaModas.toast(erro.message, true);
            })
            .finally(function () {
                botao.dataset.carregando = '0';
            });
    });
})();
