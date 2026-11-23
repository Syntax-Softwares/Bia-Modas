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

    function itemDoEstado(idProdutoTamanho) {
        return estado.itens.find((item) => String(item.produto_tamanho_id) === String(idProdutoTamanho));
    }

    async function adicionarAoCarrinho(idProduto, idProdutoTamanho) {
        if (ocupado) return;

        if (!idProdutoTamanho) {
            window.location.href = '/produto?id=' + encodeURIComponent(idProduto);
            return;
        }

        ocupado = true;
        versaoEstado += 1;

        try {
            const resposta = await postar('/carrinho/adicionar', {
                produto_tamanho_id: idProdutoTamanho,
                quantidade: 1
            });

            if (resposta.mensagem) toast(resposta.mensagem);
            aplicarResumo(resposta);
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

    function htmlVazio() {
        return '<div class="cart-empty">'
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
                    <div class="cart-item-size">Tamanho: ${escaparHtml(item.tamanho || '—')}${item.sku ? ' · ' + escaparHtml(item.sku) : ''}</div>
                    <div class="cart-item-price">${moeda(item.valor_unitario)}</div>
                    <div class="qty-control mt-2">
                        <button type="button" data-cart-menos data-produto-tamanho-id="${item.produto_tamanho_id}" aria-label="Diminuir quantidade">&minus;</button>
                        <span data-cart-qtd>${item.quantidade}</span>
                        <button type="button" data-cart-mais data-produto-tamanho-id="${item.produto_tamanho_id}" aria-label="Aumentar quantidade">+</button>
                    </div>
                </div>
                <button type="button" class="cart-item-remove" data-cart-remover data-produto-tamanho-id="${item.produto_tamanho_id}" aria-label="Remover ${escaparHtml(item.nome)} do carrinho">
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
            adicionarAoCarrinho(adicionar.dataset.produtoId, adicionar.dataset.produtoTamanhoId);
            return;
        }

        const mais = evento.target.closest('[data-cart-mais]');
        if (mais) {
            const item = itemDoEstado(mais.dataset.produtoTamanhoId);
            if (item) {
                mutar('/carrinho/atualizar', {
                    produto_tamanho_id: item.produto_tamanho_id,
                    quantidade: item.quantidade + 1
                });
            }
            return;
        }

        const menos = evento.target.closest('[data-cart-menos]');
        if (menos) {
            const item = itemDoEstado(menos.dataset.produtoTamanhoId);
            if (item) {
                mutar('/carrinho/atualizar', {
                    produto_tamanho_id: item.produto_tamanho_id,
                    quantidade: item.quantidade - 1
                });
            }
            return;
        }

        const remover = evento.target.closest('[data-cart-remover]');
        if (remover) {
            mutar('/carrinho/remover', {
                produto_tamanho_id: remover.dataset.produtoTamanhoId
            });
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
