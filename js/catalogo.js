/**
 * Bia Modas - Catálogo de Produtos
 * ================================
 * Centraliza todos os produtos da loja para uso nas páginas de categoria,
 * recomendações e filtros.
 */

const PRODUTOS = [
    {
        id: 'vestido-verde-plus',
        nome: 'Vestido Verde Plus Size',
        preco: 'R$ 179,90',
        precoOriginal: '',
        imagem: './img/VestidoVerdePlusSize.webp',
        categoria: 'Plus Size',
        tipo: 'Vestidos',
        cor: 'Verde',
        badge: 'Novo'
    },
    {
        id: 'regata-rosa-plus',
        nome: 'Regata Rosa Plus Size',
        preco: 'R$ 89,90',
        precoOriginal: '',
        imagem: './img/RegataRosaPlusSize.webp',
        categoria: 'Plus Size',
        tipo: 'Blusas',
        cor: 'Rosa',
        badge: 'Novo'
    },
    {
        id: 'blusa-vermelha-plus',
        nome: 'Blusa Plus Size Vermelha',
        preco: 'R$ 109,90',
        precoOriginal: 'R$ 159,90',
        imagem: './img/BlusaPlusSizeVermelho.webp',
        categoria: 'Plus Size',
        tipo: 'Blusas',
        cor: 'Vermelho',
        badge: '-31%'
    },
    {
        id: 'blusa-preta-plus',
        nome: 'Blusa Plus Size Preta',
        preco: 'R$ 99,90',
        precoOriginal: 'R$ 124,90',
        imagem: './img/BlusaPlusSizePreta.webp',
        categoria: 'Plus Size',
        tipo: 'Blusas',
        cor: 'Preto',
        badge: '-20%'
    },
    {
        id: 'blusa-azul-plus',
        nome: 'Blusa Plus Size Azul',
        preco: 'R$ 123,00',
        precoOriginal: 'R$ 200,00',
        imagem: './img/BlusaPlusSizeAzul.webp',
        categoria: 'Plus Size',
        tipo: 'Blusas',
        cor: 'Azul',
        badge: '-39%'
    }
];

function getProdutos() {
    return PRODUTOS;
}

function getProdutosPorCategoria(categoria) {
    const cat = categoria.toLowerCase().trim();
    return PRODUTOS.filter(p => {
        return p.categoria.toLowerCase() === cat ||
               p.tipo.toLowerCase() === cat;
    });
}

function getProdutosEmPromocao() {
    return PRODUTOS.filter(p => {
        const b = p.badge || '';
        return b.includes('%') || b.includes('-');
    });
}

function getProdutosPorCor(cor) {
    return PRODUTOS.filter(p => p.cor.toLowerCase() === cor.toLowerCase());
}

function getProdutoPorId(id) {
    return PRODUTOS.find(p => p.id === id) || null;
}

function buildProductCardHTML(product) {
    const priceOriginal = product.precoOriginal ? `<span class="price-original">${product.precoOriginal}</span>` : '';
    const discountHtml = (product.badge && product.badge.includes('%')) || (product.badge && product.badge.includes('-'))
        ? `<span class="price-discount">${product.badge}</span>`
        : (product.badge && product.badge !== 'Novo' && product.badge !== 'Mais Vendido'
            ? `<span class="price-discount">${product.badge}</span>`
            : '');
    const badgeHtml = product.badge ? `<span class="product-badge">${product.badge}</span>` : '';
    const escapNome = product.nome.replace(/'/g, "\\'");

    return `
        <div class="col-lg-3 col-md-4 col-6 mb-4">
            <div class="product-card">
                <div class="product-image">
                    <a href="./produto.html?nome=${encodeURIComponent(product.nome)}&preco=${encodeURIComponent(product.preco)}&imagem=${encodeURIComponent(product.imagem)}&categoria=${encodeURIComponent(product.categoria)}&badge=${encodeURIComponent(product.badge)}">
                        <img src="${product.imagem}" alt="${product.nome}">
                    </a>
                    ${badgeHtml}
                    <div class="product-actions">
                        <button aria-label="Adicionar aos favoritos" onclick="toggleProductFavoriteInline(this, '${escapNome}', '${product.preco}', '${product.imagem}', '${product.categoria}')"><i class="bi bi-heart"></i></button>
                        <button aria-label="Adicionar ao carrinho" onclick="addToCart('${escapNome}', '${product.preco}', '${product.imagem}')"><i class="bi bi-cart-plus"></i></button>
                        <a href="./produto.html?nome=${encodeURIComponent(product.nome)}&preco=${encodeURIComponent(product.preco)}&imagem=${encodeURIComponent(product.imagem)}&categoria=${encodeURIComponent(product.categoria)}&badge=${encodeURIComponent(product.badge)}" style="text-decoration:none;color:inherit;" aria-label="Visualizar"><button><i class="bi bi-eye"></i></button></a>
                    </div>
                </div>
                <div class="product-info">
                    <div class="product-category">${product.categoria}</div>
                    <h3 class="product-title">
                        <a href="./produto.html?nome=${encodeURIComponent(product.nome)}&preco=${encodeURIComponent(product.preco)}&imagem=${encodeURIComponent(product.imagem)}&categoria=${encodeURIComponent(product.categoria)}&badge=${encodeURIComponent(product.badge)}" style="color:inherit;text-decoration:none;">${product.nome}</a>
                    </h3>
                    <div class="product-price">
                        ${priceOriginal}
                        <span class="price-current">${product.preco}</span>
                        ${discountHtml}
                    </div>
                </div>
            </div>
        </div>
    `;
}

function toggleProductFavoriteInline(btn, name, price, image, category) {
    const product = { name, price, image, category };
    toggleFavorite(product);
    if (isFavorite(name)) {
        btn.innerHTML = '<i class="bi bi-heart-fill"></i>';
        btn.classList.add('active');
    } else {
        btn.innerHTML = '<i class="bi bi-heart"></i>';
        btn.classList.remove('active');
    }
}
