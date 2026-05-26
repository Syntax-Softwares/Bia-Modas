// Product Page Logic

const COLOR_HEX = {
    'Preto':     '#212529',
    'Branco':    '#f8f9fa',
    'Vermelho':  '#dc3545',
    'Azul':      '#0d6efd',
    'Verde':     '#198754',
    'Rosa':      '#d63384',
    'Estampado': 'linear-gradient(135deg, #fd7e14 0%, #d63384 50%, #6366f1 100%)',
    'Neutro':    '#a89683'
};

function getQueryParams() {
    const params = new URLSearchParams(window.location.search);
    return {
        nome: params.get('nome') || 'Produto',
        preco: params.get('preco') || 'R$ 0,00',
        imagem: params.get('imagem') || './img/logoBiaModas.png',
        categoria: params.get('categoria') || 'Plus Size',
        badge: params.get('badge') || ''
    };
}

function findProductInCatalog(nome) {
    if (typeof PRODUTOS === 'undefined') return null;
    return PRODUTOS.find(p => p.nome === nome) || null;
}

function renderProductPage() {
    const product = getQueryParams();
    const catalog = findProductInCatalog(product.nome);

    const imgEl = document.getElementById('product-image');
    const nameEl = document.getElementById('product-name');
    const priceEl = document.getElementById('product-price');
    const categoryEl = document.getElementById('product-category');
    const badgeEl = document.getElementById('product-badge');
    const favBtn = document.getElementById('btn-favorite');

    if (imgEl) {
        imgEl.src = product.imagem;
        imgEl.alt = product.nome;
    }
    if (nameEl) nameEl.textContent = product.nome;
    if (priceEl) priceEl.textContent = product.preco;
    if (categoryEl) categoryEl.textContent = product.categoria;
    if (badgeEl) {
        if (product.badge) {
            badgeEl.textContent = product.badge;
            badgeEl.style.display = 'inline-block';
        } else {
            badgeEl.style.display = 'none';
        }
    }

    renderGallery(product.imagem);
    renderColorOptions(catalog);
    renderSizeOptions(catalog);

    if (favBtn) {
        favBtn.dataset.name = product.nome;
        favBtn.dataset.price = product.preco;
        favBtn.dataset.image = product.imagem;
        favBtn.dataset.category = product.categoria;
        updateProductFavoriteButton();
    }
}

// --- Galeria de imagens ---

function renderGallery(mainImage) {
    const thumbs = document.getElementById('product-thumbs');
    const prevBtn = document.getElementById('gallery-prev');
    const nextBtn = document.getElementById('gallery-next');
    const mainImg = document.getElementById('product-image');
    if (!thumbs) return;

    // O catálogo só tem 1 imagem por produto, então repetimos para simular vistas múltiplas.
    const images = [mainImage, mainImage, mainImage, mainImage];
    let activeIndex = 0;

    thumbs.innerHTML = images.map((src, i) => `
        <button class="gallery-thumb ${i === 0 ? 'active' : ''}" data-index="${i}" data-img="${src}" aria-label="Visualização ${i + 1}">
            <img src="${src}" alt="Vista ${i + 1}">
        </button>
    `).join('');

    function setActiveThumb(idx) {
        activeIndex = (idx + images.length) % images.length;
        thumbs.querySelectorAll('.gallery-thumb').forEach((b, i) => {
            b.classList.toggle('active', i === activeIndex);
        });
        if (mainImg) mainImg.src = images[activeIndex];
    }

    thumbs.addEventListener('click', e => {
        const btn = e.target.closest('.gallery-thumb');
        if (!btn) return;
        setActiveThumb(Number(btn.dataset.index));
    });

    if (prevBtn) prevBtn.onclick = () => setActiveThumb(activeIndex - 1);
    if (nextBtn) nextBtn.onclick = () => setActiveThumb(activeIndex + 1);
}

// --- Seletor de cor ---

function renderColorOptions(catalog) {
    const container = document.getElementById('color-options');
    const label = document.getElementById('selected-color');
    if (!container) return;

    const corPrincipal = catalog?.cor || 'Padrão';
    // Mostra a cor real + variantes mock para demonstrar a UX
    const variantes = ['Preto', 'Branco', 'Vermelho', 'Rosa'];
    const lista = [corPrincipal, ...variantes.filter(v => v !== corPrincipal)].slice(0, 5);

    container.innerHTML = lista.map((cor, i) => {
        const valor = COLOR_HEX[cor] || '#cccccc';
        const isGradient = typeof valor === 'string' && valor.startsWith('linear');
        const bgStyle = isGradient ? `background: ${valor};` : `background-color: ${valor};`;
        return `<button class="color-option ${i === 0 ? 'active' : ''}" data-color="${cor}" style="${bgStyle}" title="${cor}" aria-label="Cor ${cor}"></button>`;
    }).join('');

    if (label) label.textContent = corPrincipal;

    container.addEventListener('click', e => {
        const btn = e.target.closest('.color-option');
        if (!btn) return;
        container.querySelectorAll('.color-option').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        if (label) label.textContent = btn.dataset.color;
    });
}

// --- Seletor de tamanho ---

function renderSizeOptions(catalog) {
    const container = document.getElementById('size-options');
    const label = document.getElementById('selected-size');
    if (!container) return;

    const tamanhos = (catalog && catalog.tamanhos && catalog.tamanhos.length)
        ? catalog.tamanhos
        : ['P', 'M', 'G', 'GG'];

    container.innerHTML = tamanhos.map((t, i) => {
        return `<button class="size-option ${i === 0 ? 'active' : ''}" data-size="${t}">${t}</button>`;
    }).join('');

    if (label) label.textContent = tamanhos[0];

    container.addEventListener('click', e => {
        const btn = e.target.closest('.size-option');
        if (!btn) return;
        container.querySelectorAll('.size-option').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        if (label) label.textContent = btn.dataset.size;
    });
}

// --- Favoritos ---

function updateProductFavoriteButton() {
    const favBtn = document.getElementById('btn-favorite');
    if (!favBtn) return;
    const name = favBtn.dataset.name;
    if (isFavorite(name)) {
        favBtn.innerHTML = '<i class="bi bi-heart-fill me-2"></i>Remover dos Favoritos';
        favBtn.classList.add('active');
    } else {
        favBtn.innerHTML = '<i class="bi bi-heart me-2"></i>Adicionar aos Favoritos';
        favBtn.classList.remove('active');
    }
}

function toggleProductFavorite() {
    const favBtn = document.getElementById('btn-favorite');
    if (!favBtn) return;
    const product = {
        name: favBtn.dataset.name,
        price: favBtn.dataset.price,
        image: favBtn.dataset.image,
        category: favBtn.dataset.category
    };
    toggleFavorite(product);
    updateProductFavoriteButton();
}

function addProductToCartFromPage() {
    const favBtn = document.getElementById('btn-favorite');
    if (!favBtn) return;
    addToCart(favBtn.dataset.name, favBtn.dataset.price, favBtn.dataset.image);
}

document.addEventListener('DOMContentLoaded', () => {
    renderProductPage();

    // Salva como visto recentemente
    const product = getQueryParams();
    if (product.nome && product.nome !== 'Produto' && typeof saveRecentView === 'function') {
        saveRecentView(product);
    }
});
