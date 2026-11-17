(function () {
    'use strict';

    const { moeda, toast, postar } = window.BiaModas;
    const pagina = document.body.dataset.page;

    const estado = { itens: [], quantidade: 0, subtotal: 0 };
    let ocupado = false;
    let versaoEstado = 0;

    const offcanvas = document.getElementById('cart-offcanvas');
    const overlay = document.getElementById('cart-overlay');

    function escaparHtml(texto) {
        const div = document.createElement('div');
        div.textContent = texto == null ? '' : String(texto);
        return div.innerHTML;
    }

    function abrirCarrinho() {
        if (!offcanvas) return;
        offcanvas.classList.add('open');
        offcanvas.setAttribute('aria-hidden', 'false');
        if (overlay) overlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function fecharCarrinho() {
        if (!offcanvas) return;
        offcanvas.classList.remove('open');
        offcanvas.setAttribute('aria-hidden', 'true');
        if (overlay) overlay.classList.remove('open');
        document.body.style.overflow = '';
    }

    function itemDoEstado(idProduto) {
        return estado.itens.find((item) => String(item.produto_id) === String(idProduto));
    }

    function atualizarBotaoProduto(idProduto, noCarrinho) {
        document.querySelectorAll(`[data-adicionar][data-produto-id="${idProduto}"]`).forEach((botao) => {
            const nome = botao.dataset.produtoNome || 'produto';

            botao.classList.toggle('active', noCarrinho);
            botao.setAttribute('aria-pressed', noCarrinho ? 'true' : 'false');
            botao.setAttribute('aria-label', noCarrinho
                ? `Remover ${nome} do carrinho`
                : `Adicionar ${nome} ao carrinho`);

            const icone = botao.querySelector('i');
            if (icone) {
                icone.className = 'icon-shopping-bag';
            }

            const rotulo = botao.querySelector('[data-cart-label]');
            if (rotulo) {
                rotulo.textContent = noCarrinho ? 'Remover do carrinho' : 'Adicionar ao carrinho';
            }
        });
    }

    function sincronizarBotoesProduto() {
        const ids = new Set(estado.itens.map((item) => String(item.produto_id)));

        document.querySelectorAll('[data-adicionar]').forEach((botao) => {
            atualizarBotaoProduto(botao.dataset.produtoId, ids.has(String(botao.dataset.produtoId)));
        });
    }

    async function alternarCarrinho(idProduto) {
        if (ocupado) return;
        ocupado = true;
        versaoEstado += 1;

        try {
            const resposta = await postar('/carrinho/alternar', { produto_id: idProduto, quantidade: 1 });

            if (resposta.mensagem) toast(resposta.mensagem);
            aplicarResumo(resposta);
            atualizarBotaoProduto(idProduto, resposta.noCarrinho === true);
        } catch (erro) {
            toast(erro.message, true);
        } finally {
            ocupado = false;
        }
    }

    function lerEstadoInicial() {
        const elemento = document.getElementById('estado-carrinho-inicial');
        if (!elemento) return null;

        try {
            return JSON.parse(elemento.textContent);
        } catch (erro) {
            return null;
        }
    }

    function htmlVazio() {        return '<div class="cart-empty">'
            + '<i class="icon-shopping-bag"></i>'
            + '<p>Seu carrinho está vazio</p>'
            + '<p style="font-size: 0.9rem;">Adicione produtos para continuar</p>'
            + '</div>';
    }

    function renderizarOffcanvas() {
        const lista = document.getElementById('cart-items');
        const rodape = document.getElementById('cart-footer');
        const total = document.getElementById('cart-total');

        if (!lista) return;

        if (estado.itens.length === 0) {
            lista.innerHTML = htmlVazio();
            if (rodape) rodape.hidden = true;
            return;
        }

        lista.innerHTML = estado.itens.map((item) => `
            <div class="cart-item">
                <div class="cart-item-image">
                    <img src="${escaparHtml(item.imagem || '/img/logoBiaModas.png')}" alt="${escaparHtml(item.nome)}">
                </div>
                <div class="cart-item-info">
                    <div class="cart-item-title">${escaparHtml(item.nome)}</div>
                    <div class="cart-item-price">${moeda(item.valor_unitario)}</div>
                    <div class="qty-control mt-2">
                        <button type="button" data-cart-menos data-produto-id="${item.produto_id}" aria-label="Diminuir quantidade">&minus;</button>
                        <span data-cart-qtd>${item.quantidade}</span>
                        <button type="button" data-cart-mais data-produto-id="${item.produto_id}" aria-label="Aumentar quantidade">+</button>
                    </div>
                </div>
                <button type="button" class="cart-item-remove" data-cart-remover data-produto-id="${item.produto_id}" aria-label="Remover ${escaparHtml(item.nome)} do carrinho">
                    <i class="icon-trash-2"></i>
                </button>
            </div>
        `).join('');

        if (rodape) rodape.hidden = false;
        if (total) total.textContent = moeda(estado.subtotal);
    }

    function aplicarResumo(dados) {
        estado.itens = Array.isArray(dados.itens) ? dados.itens : [];
        estado.quantidade = Number(dados.quantidade) || 0;
        estado.subtotal = Number(dados.subtotal) || 0;

        const contador = document.getElementById('cart-count');
        if (contador) contador.textContent = estado.quantidade;

        renderizarOffcanvas();
        sincronizarBotoesProduto();
    }

    async function carregar() {
        const versao = versaoEstado;

        try {
            const resposta = await fetch('/carrinho/dados', { credentials: 'same-origin' });
            if (!resposta.ok) return;

            const dados = await resposta.json();

            if (versao !== versaoEstado) return;

            aplicarResumo(dados);
        } catch (erro) {
        }
    }

    async function mutar(url, dados) {
        if (ocupado) return;
        ocupado = true;
        versaoEstado += 1;

        try {
            const resposta = await postar(url, dados);
            if (resposta.mensagem) toast(resposta.mensagem);

            if (pagina === 'carrinho') {
                window.location.reload();
                return;
            }

            aplicarResumo(resposta);
        } catch (erro) {
            toast(erro.message, true);
        } finally {
            ocupado = false;
        }
    }

    document.addEventListener('click', (evento) => {
        const abrir = evento.target.closest('[data-cart-open]');
        if (abrir && offcanvas) {
            evento.preventDefault();
            abrirCarrinho();
            return;
        }

        if (evento.target.closest('[data-cart-close]')) {
            fecharCarrinho();
            return;
        }

        const adicionar = evento.target.closest('[data-adicionar]');
        if (adicionar) {
            evento.preventDefault();
            alternarCarrinho(adicionar.dataset.produtoId);
            return;
        }

        const mais = evento.target.closest('[data-cart-mais]');
        if (mais) {
            const item = itemDoEstado(mais.dataset.produtoId);
            if (item) mutar('/carrinho/atualizar', { produto_id: item.produto_id, quantidade: item.quantidade + 1 });
            return;
        }

        const menos = evento.target.closest('[data-cart-menos]');
        if (menos) {
            const item = itemDoEstado(menos.dataset.produtoId);
            if (item) mutar('/carrinho/atualizar', { produto_id: item.produto_id, quantidade: item.quantidade - 1 });
            return;
        }

        const remover = evento.target.closest('[data-cart-remover]');
        if (remover) {
            mutar('/carrinho/remover', { produto_id: remover.dataset.produtoId });
            return;
        }
    });

    document.addEventListener('keydown', (evento) => {
        if (evento.key === 'Escape') fecharCarrinho();
    });

    const estadoInicial = lerEstadoInicial();
    if (estadoInicial) aplicarResumo(estadoInicial);

    carregar();
})();
