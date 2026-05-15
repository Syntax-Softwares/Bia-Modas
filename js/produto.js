// Product Page Logic
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

function renderProductPage() {
    const product = getQueryParams();

    const imgEl = document.getElementById('product-image');
    const nameEl = document.getElementById('product-name');
    const priceEl = document.getElementById('product-price');
    const categoryEl = document.getElementById('product-category');
    const badgeEl = document.getElementById('product-badge');
    const favBtn = document.getElementById('btn-favorite');

    if (imgEl) imgEl.src = product.imagem;
    if (imgEl) imgEl.alt = product.nome;
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

    if (favBtn) {
        favBtn.dataset.name = product.nome;
        favBtn.dataset.price = product.preco;
        favBtn.dataset.image = product.imagem;
        favBtn.dataset.category = product.categoria;
        updateProductFavoriteButton();
    }
}

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
});
