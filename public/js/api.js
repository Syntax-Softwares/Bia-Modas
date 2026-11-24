(function () {
    'use strict';

    const formatoMoeda = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
    const meta = document.querySelector('meta[name="csrf-token"]');
    const csrf = meta ? meta.getAttribute('content') : '';

    function moeda(valor) {
        return formatoMoeda.format(Number(valor) || 0);
    }

    let timerToast = null;

    function toast(mensagem, erro) {
        const elemento = document.getElementById('toast-notification');
        if (!elemento) return;

        const texto = document.getElementById('toast-message');
        const icone = elemento.querySelector('i');

        if (texto) texto.textContent = mensagem;
        if (icone) icone.className = erro ? 'icon-circle-alert' : 'icon-circle-check';

        elemento.classList.toggle('is-error', Boolean(erro));
        elemento.classList.add('show');

        clearTimeout(timerToast);
        timerToast = setTimeout(() => elemento.classList.remove('show'), 3000);
    }

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

        let corpo;
        try {
            corpo = await resposta.json();
        } catch (erro) {
            throw new Error('Resposta inválida do servidor. Recarregue a página e tente novamente.');
        }

        if (!resposta.ok || corpo.ok === false) {
            throw new Error(corpo.mensagem || 'Não foi possível concluir a ação.');
        }

        return corpo;
    }

    window.BiaModas = { csrf, moeda, toast, postar };
})();
