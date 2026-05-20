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
        preco: 179.90,
        precoOriginal: null,
        precoFormatado: 'R$ 179,90',
        imagem: './img/VestidoVerdePlusSize.webp',
        categoria: 'Plus Size',
        tipo: 'Vestidos',
        cor: 'Verde',
        estilo: 'Elegante',
        tamanhos: ['P', 'M', 'G', 'GG', 'XG'],
        badge: 'Novo'
    },
    {
        id: 'regata-rosa-plus',
        nome: 'Regata Rosa Plus Size',
        preco: 89.90,
        precoOriginal: null,
        precoFormatado: 'R$ 89,90',
        imagem: './img/RegataRosaPlusSize.webp',
        categoria: 'Plus Size',
        tipo: 'Blusas',
        cor: 'Rosa',
        estilo: 'Casual',
        tamanhos: ['P', 'M', 'G', 'GG'],
        badge: 'Novo'
    },
    {
        id: 'blusa-vermelha-plus',
        nome: 'Blusa Plus Size Vermelha',
        preco: 109.90,
        precoOriginal: 159.90,
        precoFormatado: 'R$ 109,90',
        imagem: './img/BlusaPlusSizeVermelho.webp',
        categoria: 'Plus Size',
        tipo: 'Blusas',
        cor: 'Vermelho',
        estilo: 'Romântico',
        tamanhos: ['P', 'M', 'G', 'GG', 'XG'],
        badge: '-31%'
    },
    {
        id: 'blusa-preta-plus',
        nome: 'Blusa Plus Size Preta',
        preco: 99.90,
        precoOriginal: 124.90,
        precoFormatado: 'R$ 99,90',
        imagem: './img/BlusaPlusSizePreta.webp',
        categoria: 'Plus Size',
        tipo: 'Blusas',
        cor: 'Preto',
        estilo: 'Minimalista',
        tamanhos: ['P', 'M', 'G', 'GG'],
        badge: '-20%'
    },
    {
        id: 'blusa-azul-plus',
        nome: 'Blusa Plus Size Azul',
        preco: 123.00,
        precoOriginal: 200.00,
        precoFormatado: 'R$ 123,00',
        imagem: './img/BlusaPlusSizeAzul.webp',
        categoria: 'Plus Size',
        tipo: 'Blusas',
        cor: 'Azul',
        estilo: 'Casual',
        tamanhos: ['M', 'G', 'GG', 'XG'],
        badge: '-39%'
    },
    {
        id: 'vestido-elegante-preto',
        nome: 'Vestido Elegante Preto',
        preco: 249.90,
        precoOriginal: 349.90,
        precoFormatado: 'R$ 249,90',
        imagem: './img/VestidoVerdePlusSize.webp',
        categoria: 'Vestidos',
        tipo: 'Vestidos',
        cor: 'Preto',
        estilo: 'Elegante',
        tamanhos: ['P', 'M', 'G', 'GG'],
        badge: '-29%'
    },
    {
        id: 'calca-jeans-azul',
        nome: 'Calça Jeans Azul',
        preco: 149.90,
        precoOriginal: null,
        precoFormatado: 'R$ 149,90',
        imagem: './img/BlusaPlusSizeAzul.webp',
        categoria: 'Calças',
        tipo: 'Calças',
        cor: 'Azul',
        estilo: 'Casual',
        tamanhos: ['36', '38', '40', '42', '44', '46'],
        badge: 'Novo'
    },
    {
        id: 'calca-preta-social',
        nome: 'Calça Preta Social',
        preco: 189.90,
        precoOriginal: null,
        precoFormatado: 'R$ 189,90',
        imagem: './img/BlusaPlusSizePreta.webp',
        categoria: 'Calças',
        tipo: 'Calças',
        cor: 'Preto',
        estilo: 'Elegante',
        tamanhos: ['36', '38', '40', '42', '44'],
        badge: ''
    },
    {
        id: 'blusa-branca-basica',
        nome: 'Blusa Branca Básica',
        preco: 69.90,
        precoOriginal: null,
        precoFormatado: 'R$ 69,90',
        imagem: './img/BlusaPlusSizeVermelho.webp',
        categoria: 'Blusas',
        tipo: 'Blusas',
        cor: 'Branco',
        estilo: 'Minimalista',
        tamanhos: ['P', 'M', 'G', 'GG'],
        badge: ''
    },
    {
        id: 'vestido-floral-rosa',
        nome: 'Vestido Floral Rosa',
        preco: 199.90,
        precoOriginal: 259.90,
        precoFormatado: 'R$ 199,90',
        imagem: './img/RegataRosaPlusSize.webp',
        categoria: 'Vestidos',
        tipo: 'Vestidos',
        cor: 'Rosa',
        estilo: 'Romântico',
        tamanhos: ['P', 'M', 'G', 'GG'],
        badge: '-23%'
    },
    {
        id: 'regata-preta-fitness',
        nome: 'Regata Preta Fitness',
        preco: 59.90,
        precoOriginal: null,
        precoFormatado: 'R$ 59,90',
        imagem: './img/BlusaPlusSizePreta.webp',
        categoria: 'Blusas',
        tipo: 'Blusas',
        cor: 'Preto',
        estilo: 'Esportivo',
        tamanhos: ['P', 'M', 'G', 'GG'],
        badge: ''
    },
    {
        id: 'vestido-vintage-vermelho',
        nome: 'Vestido Vintage Vermelho',
        preco: 219.90,
        precoOriginal: null,
        precoFormatado: 'R$ 219,90',
        imagem: './img/BlusaPlusSizeVermelho.webp',
        categoria: 'Vestidos',
        tipo: 'Vestidos',
        cor: 'Vermelho',
        estilo: 'Vintage',
        tamanhos: ['P', 'M', 'G'],
        badge: 'Mais Vendido'
    },
    {
        id: 'calca-verde-cargo',
        nome: 'Calça Verde Cargo',
        preco: 159.90,
        precoOriginal: 199.90,
        precoFormatado: 'R$ 159,90',
        imagem: './img/VestidoVerdePlusSize.webp',
        categoria: 'Calças',
        tipo: 'Calças',
        cor: 'Verde',
        estilo: 'Casual',
        tamanhos: ['36', '38', '40', '42', '44'],
        badge: '-20%'
    },
    {
        id: 'blusa-estampada-boho',
        nome: 'Blusa Estampada Boho',
        preco: 119.90,
        precoOriginal: null,
        precoFormatado: 'R$ 119,90',
        imagem: './img/RegataRosaPlusSize.webp',
        categoria: 'Blusas',
        tipo: 'Blusas',
        cor: 'Estampado',
        estilo: 'Vintage',
        tamanhos: ['P', 'M', 'G', 'GG'],
        badge: ''
    },
    {
        id: 'vestido-neutro-linho',
        nome: 'Vestido Neutro Linho',
        preco: 229.90,
        precoOriginal: null,
        precoFormatado: 'R$ 229,90',
        imagem: './img/BlusaPlusSizeAzul.webp',
        categoria: 'Vestidos',
        tipo: 'Vestidos',
        cor: 'Neutro',
        estilo: 'Minimalista',
        tamanhos: ['P', 'M', 'G', 'GG'],
        badge: ''
    },
    {
        id: 'blusa-verde-esportiva',
        nome: 'Blusa Verde Esportiva',
        preco: 79.90,
        precoOriginal: null,
        precoFormatado: 'R$ 79,90',
        imagem: './img/VestidoVerdePlusSize.webp',
        categoria: 'Blusas',
        tipo: 'Blusas',
        cor: 'Verde',
        estilo: 'Esportivo',
        tamanhos: ['P', 'M', 'G', 'GG'],
        badge: 'Novo'
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

function getCategorias() {
    const cats = new Set();
    PRODUTOS.forEach(p => {
        cats.add(p.categoria);
        cats.add(p.tipo);
    });
    return Array.from(cats).sort();
}

function getCores() {
    const cores = new Set();
    PRODUTOS.forEach(p => cores.add(p.cor));
    return Array.from(cores).sort();
}

function getEstilos() {
    const estilos = new Set();
    PRODUTOS.forEach(p => estilos.add(p.estilo));
    return Array.from(estilos).sort();
}

function getTamanhos() {
    const tamanhos = new Set();
    PRODUTOS.forEach(p => (p.tamanhos || []).forEach(t => tamanhos.add(t)));
    return Array.from(tamanhos).sort((a, b) => {
        const order = ['PP','P','M','G','GG','XG','XXG','34','36','38','40','42','44','46','48','50','52','54','56'];
        return order.indexOf(a) - order.indexOf(b);
    });
}

function filtrarProdutos(filtros) {
    let resultado = [...PRODUTOS];

    if (filtros.categoria && filtros.categoria !== 'todos') {
        const cat = filtros.categoria.toLowerCase();
        resultado = resultado.filter(p =>
            p.categoria.toLowerCase() === cat ||
            p.tipo.toLowerCase() === cat
        );
    }

    if (filtros.cores && filtros.cores.length > 0) {
        const coresLower = filtros.cores.map(c => c.toLowerCase());
        resultado = resultado.filter(p => coresLower.includes(p.cor.toLowerCase()));
    }

    if (filtros.estilos && filtros.estilos.length > 0) {
        const estilosLower = filtros.estilos.map(e => e.toLowerCase());
        resultado = resultado.filter(p => estilosLower.includes(p.estilo.toLowerCase()));
    }

    if (filtros.tamanhos && filtros.tamanhos.length > 0) {
        resultado = resultado.filter(p =>
            p.tamanhos.some(t => filtros.tamanhos.includes(t))
        );
    }

    if (filtros.precoMin !== undefined && filtros.precoMin !== null) {
        resultado = resultado.filter(p => p.preco >= filtros.precoMin);
    }

    if (filtros.precoMax !== undefined && filtros.precoMax !== null) {
        resultado = resultado.filter(p => p.preco <= filtros.precoMax);
    }

    if (filtros.promocao) {
        resultado = resultado.filter(p => {
            const b = p.badge || '';
            return b.includes('%') || b.includes('-');
        });
    }

    if (filtros.busca) {
        const termo = filtros.busca.toLowerCase();
        resultado = resultado.filter(p =>
            p.nome.toLowerCase().includes(termo) ||
            p.categoria.toLowerCase().includes(termo) ||
            p.tipo.toLowerCase().includes(termo) ||
            p.cor.toLowerCase().includes(termo) ||
            p.estilo.toLowerCase().includes(termo)
        );
    }

    if (filtros.ordenacao) {
        if (filtros.ordenacao === 'menor-preco') {
            resultado.sort((a, b) => a.preco - b.preco);
        } else if (filtros.ordenacao === 'maior-preco') {
            resultado.sort((a, b) => b.preco - a.preco);
        } else if (filtros.ordenacao === 'nome') {
            resultado.sort((a, b) => a.nome.localeCompare(b.nome));
        }
    }

    return resultado;
}

function buildProductCardHTML(product) {
    const precoOriginalStr = product.precoOriginal
        ? `<span class="price-original">R$ ${product.precoOriginal.toFixed(2).replace('.', ',')}</span>`
        : '';
    const discountHtml = (product.badge && product.badge.includes('%')) || (product.badge && product.badge.includes('-'))
        ? `<span class="price-discount">${product.badge}</span>`
        : (product.badge && product.badge !== 'Novo' && product.badge !== 'Mais Vendido'
            ? `<span class="price-discount">${product.badge}</span>`
            : '');
    const badgeHtml = product.badge ? `<span class="product-badge">${product.badge}</span>` : '';
    const escapNome = product.nome.replace(/'/g, "\\'");

    return `
        <div class="col-lg-3 col-md-4 col-6 mb-4" data-cor="${product.cor}" data-tipo="${product.tipo}" data-estilo="${product.estilo}" data-categoria="${product.categoria}">
            <div class="product-card">
                <div class="product-image">
                    <a href="./produto.html?nome=${encodeURIComponent(product.nome)}&preco=${encodeURIComponent(product.precoFormatado)}&imagem=${encodeURIComponent(product.imagem)}&categoria=${encodeURIComponent(product.categoria)}&badge=${encodeURIComponent(product.badge)}">
                        <img src="${product.imagem}" alt="${product.nome}">
                    </a>
                    ${badgeHtml}
                    <div class="product-actions">
                        <button aria-label="Adicionar aos favoritos" onclick="toggleProductFavoriteInline(this, '${escapNome}', '${product.precoFormatado}', '${product.imagem}', '${product.categoria}')"><i class="bi bi-heart"></i></button>
                        <button aria-label="Adicionar ao carrinho" onclick="addToCart('${escapNome}', '${product.precoFormatado}', '${product.imagem}')"><i class="bi bi-cart-plus"></i></button>
                        <a href="./produto.html?nome=${encodeURIComponent(product.nome)}&preco=${encodeURIComponent(product.precoFormatado)}&imagem=${encodeURIComponent(product.imagem)}&categoria=${encodeURIComponent(product.categoria)}&badge=${encodeURIComponent(product.badge)}" style="text-decoration:none;color:inherit;" aria-label="Visualizar"><button><i class="bi bi-eye"></i></button></a>
                    </div>
                </div>
                <div class="product-info">
                    <div class="product-category">${product.categoria}</div>
                    <h3 class="product-title">
                        <a href="./produto.html?nome=${encodeURIComponent(product.nome)}&preco=${encodeURIComponent(product.precoFormatado)}&imagem=${encodeURIComponent(product.imagem)}&categoria=${encodeURIComponent(product.categoria)}&badge=${encodeURIComponent(product.badge)}" style="color:inherit;text-decoration:none;">${product.nome}</a>
                    </h3>
                    <div class="product-price">
                        ${precoOriginalStr}
                        <span class="price-current">${product.precoFormatado}</span>
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
