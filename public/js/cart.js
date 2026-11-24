(function () {
    'use strict';

    const { moeda, toast, postar } = window.BiaModas;
    const pagina = document.body.dataset.page;

    const estado = { itens: [], quantidade: 0, subtotal: 0 };
    let ocupado = false;
    let versaoEstado = 0;

    const offcanvas = document.getElementById('cart-offcanvas');
    const overlay = document.getElementById('cart-overlay');

    const variacoesCache = new Map();
    let seletorTamanho = null;
    let seletorTamanhoBotao = null;
    let cardSeletorTamanho = null;

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

    function fecharSeletorTamanho() {
        if (!seletorTamanho) return;

        seletorTamanho.remove();
        seletorTamanho = null;
        seletorTamanhoBotao = null;

        if (cardSeletorTamanho) {
            cardSeletorTamanho.classList.remove('is-picker-open');
            cardSeletorTamanho = null;
        }
    }

    function posicionarSeletorTamanho() {
        if (!seletorTamanho || !seletorTamanhoBotao) return;

        const botao = seletorTamanhoBotao.getBoundingClientRect();

        if (botao.bottom < 0
            || botao.top > window.innerHeight
            || botao.right < 0
            || botao.left > window.innerWidth) {
            fecharSeletorTamanho();
            return;
        }

        const largura = seletorTamanho.offsetWidth;
        const altura = seletorTamanho.offsetHeight;
        const margem = 8;

        let esquerda = botao.right + 12;
        let topo = botao.top;

        if (esquerda + largura > window.innerWidth - margem) {
            esquerda = Math.max(margem, botao.left - largura - 12);
        }

        if (topo + altura > window.innerHeight - margem) {
            topo = Math.max(margem, window.innerHeight - altura - margem);
        }

        seletorTamanho.style.left = esquerda + 'px';
        seletorTamanho.style.top = topo + 'px';
    }

    function renderizarOpcoesTamanho(seletor, variacoes) {
        const opcoes = seletor.querySelector('.size-picker-options');
        if (!opcoes) return;

        if (!Array.isArray(variacoes) || variacoes.length === 0) {
            opcoes.innerHTML = '<span class="size-picker-empty">Nenhum tamanho cadastrado. '
                + '<a href="/produto?id=' + encodeURIComponent(seletor.dataset.produtoId) + '">Ver produto</a></span>';
            posicionarSeletorTamanho();
            return;
        }

        opcoes.innerHTML = variacoes.map((variacao) => {
            const semEstoque = Number(variacao.estoque) <= 0;
            const nome = escaparHtml(variacao.tamanho);

            return '<button type="button"'
                + ' data-size-id="' + escaparHtml(variacao.id_produto_tamanho) + '"'
                + (semEstoque ? ' disabled title="Tamanho esgotado"' : ' title="Adicionar tamanho ' + nome + '"')
                + '>' + nome + '</button>';
        }).join('');

        opcoes.querySelectorAll('[data-size-id]').forEach((botao) => {
            botao.addEventListener('click', () => {
                const idTamanho = botao.dataset.sizeId;
                const produtoId = seletor.dataset.produtoId;

                fecharSeletorTamanho();
                adicionarAoCarrinho(produtoId, idTamanho);
            });
        });

        posicionarSeletorTamanho();
    }

    function buscarVariacoes(idProduto) {
        if (!variacoesCache.has(String(idProduto))) {
            const requisicao = fetch('/produto/variacoes?id=' + encodeURIComponent(idProduto), {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' }
            })
                .then((resposta) => {
                    if (!resposta.ok) throw new Error('Não foi possível carregar os tamanhos.');
                    return resposta.json();
                })
                .then((dados) => (Array.isArray(dados.variacoes) ? dados.variacoes : []))
                .catch((erro) => {
                    variacoesCache.delete(String(idProduto));
                    throw erro;
                });

            variacoesCache.set(String(idProduto), requisicao);
        }

        return variacoesCache.get(String(idProduto));
    }

    async function abrirSeletorTamanho(botao) {
        const idProduto = botao.dataset.produtoId;

        if (!idProduto) return;

        if (seletorTamanho && seletorTamanhoBotao === botao) {
            fecharSeletorTamanho();
            return;
        }

        fecharSeletorTamanho();

        seletorTamanhoBotao = botao;
        cardSeletorTamanho = botao.closest('.product-card');

        if (cardSeletorTamanho) {
            cardSeletorTamanho.classList.add('is-picker-open');
        }

        seletorTamanho = document.createElement('div');
        seletorTamanho.className = 'size-picker';
        seletorTamanho.dataset.produtoId = String(idProduto);
        seletorTamanho.setAttribute('role', 'dialog');
        seletorTamanho.setAttribute('aria-label', 'Escolha o tamanho de ' + (botao.dataset.produtoNome || 'produto'));
        seletorTamanho.innerHTML = '<div class="size-picker-title">Escolha o tamanho</div>'
            + '<div class="size-picker-options"><span class="size-picker-loading">Carregando tamanhos...</span></div>';

        document.body.appendChild(seletorTamanho);
        posicionarSeletorTamanho();

        try {
            const variacoes = await buscarVariacoes(idProduto);

            if (!seletorTamanho || seletorTamanho.dataset.produtoId !== String(idProduto)) return;

            renderizarOpcoesTamanho(seletorTamanho, variacoes);
        } catch (erro) {
            if (!seletorTamanho || seletorTamanho.dataset.produtoId !== String(idProduto)) return;

            const opcoes = seletorTamanho.querySelector('.size-picker-options');
            if (opcoes) opcoes.innerHTML = '<span class="size-picker-empty">' + escaparHtml(erro.message) + '</span>';
            posicionarSeletorTamanho();
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
                    <div class="cart-item-size">Tamanho: ${escaparHtml(item.tamanho || '—')}</div>
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
        if (seletorTamanho
            && !seletorTamanho.contains(evento.target)
            && !evento.target.closest('[data-adicionar]')) {
            fecharSeletorTamanho();
        }

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

            if (!adicionar.dataset.produtoTamanhoId && adicionar.dataset.requerTamanho) {
                abrirSeletorTamanho(adicionar);
                return;
            }

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
        if (evento.key === 'Escape') {
            fecharSeletorTamanho();
            fecharCarrinho();
        }
    });

    let posicaoAgendada = 0;

    function reposicionarSeletorTamanho() {
        if (!seletorTamanho || posicaoAgendada) return;

        posicaoAgendada = window.requestAnimationFrame(() => {
            posicaoAgendada = 0;
            posicionarSeletorTamanho();
        });
    }

    window.addEventListener('resize', reposicionarSeletorTamanho);
    window.addEventListener('scroll', reposicionarSeletorTamanho, { passive: true, capture: true });

    const estadoInicial = lerEstadoInicial();
    if (estadoInicial) aplicarResumo(estadoInicial);

    carregar();
})();
