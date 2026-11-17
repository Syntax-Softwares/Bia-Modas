/* Bia Modas - utilitários de requisição (JSON + CSRF) e toast. */
(function () {
    'use strict';

    const meta = document.querySelector('meta[name="csrf-token"]');
    const csrf = meta ? meta.getAttribute('content') : '';

    const formatoMoeda = new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    });

    let timerToast = null;

    function moeda(valor) {
        return formatoMoeda.format(Number(valor) || 0);
    }

    /**
     * Mostra uma mensagem flutuante. Cria o elemento na primeira chamada,
     * então nenhuma view precisa carregar markup de toast.
     */
    function toast(mensagem, erro) {
        let elemento = document.getElementById('toast-notification');

        if (!elemento) {
            elemento = document.createElement('div');
            elemento.id = 'toast-notification';
            elemento.className = 'toast-notification';
            elemento.setAttribute('role', 'status');
            elemento.setAttribute('aria-live', 'polite');
            elemento.innerHTML = '<i class="bi bi-check-circle"></i><span></span>';
            document.body.appendChild(elemento);
        }

        const texto = elemento.querySelector('span');
        const icone = elemento.querySelector('i');

        if (texto) texto.textContent = mensagem;
        if (icone) icone.className = erro ? 'bi bi-exclamation-circle' : 'bi bi-check-circle';

        elemento.classList.toggle('is-error', Boolean(erro));
        elemento.classList.add('show');

        clearTimeout(timerToast);
        timerToast = setTimeout(function () {
            elemento.classList.remove('show');
        }, 3000);
    }

    /**
     * POST de formulário esperando JSON de volta.
     * Lança Error com `.status` quando a resposta não é 2xx ou `ok: false`.
     */
    async function postar(url, dados) {
        const resposta = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                'X-CSRF-Token': csrf,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams(dados || {}).toString()
        });

        let corpo = null;

        try {
            corpo = await resposta.json();
        } catch (erro) {
            corpo = null;
        }

        if (!resposta.ok || !corpo || corpo.ok === false) {
            const falha = new Error(
                (corpo && corpo.mensagem) || 'Não foi possível concluir a ação.'
            );
            falha.status = resposta.status;
            throw falha;
        }

        return corpo;
    }

    window.BiaModas = {
        csrf: csrf,
        moeda: moeda,
        toast: toast,
        postar: postar
    };
})();
