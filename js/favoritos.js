// Favorites System
const FAVORITES_KEY = 'bia_modas_favorites';

function getFavorites() {
    return JSON.parse(localStorage.getItem(FAVORITES_KEY)) || [];
}

function saveFavorites(favorites) {
    localStorage.setItem(FAVORITES_KEY, JSON.stringify(favorites));
}

function isFavorite(name) {
    const favorites = getFavorites();
    return favorites.some(item => item.name === name);
}

function toggleFavorite(product) {
    const favorites = getFavorites();
    const index = favorites.findIndex(item => item.name === product.name);

    if (index >= 0) {
        favorites.splice(index, 1);
        saveFavorites(favorites);
        showToast('Produto removido dos favoritos');
        return false;
    } else {
        favorites.push(product);
        saveFavorites(favorites);
        showToast('Produto adicionado aos favoritos');
        return true;
    }
}

function updateFavoriteButtons() {
    document.querySelectorAll('[data-favorite-btn]').forEach(btn => {
        const name = btn.dataset.name;
        if (isFavorite(name)) {
            btn.classList.add('active');
            btn.innerHTML = '<i class="bi bi-heart-fill"></i>';
        } else {
            btn.classList.remove('active');
            btn.innerHTML = '<i class="bi bi-heart"></i>';
        }
    });
}

function renderFavorites() {
    const container = document.getElementById('favorites-list');
    if (!container) return;

    const favorites = getFavorites();

    if (favorites.length === 0) {
        container.innerHTML = `
            <div class="col-12 text-center py-5">
                <i class="bi bi-heart" style="font-size: 3rem; color: var(--color-light-gray);"></i>
                <p class="mt-3" style="color: var(--color-gray);">Nenhum produto favoritado</p>
                <a href="./index.html" class="btn-primary-custom mt-3">Ver Produtos</a>
            </div>
        `;
        return;
    }

    let html = '';
    favorites.forEach(item => {
        html += `
            <div class="col-lg-3 col-md-4 col-6">
                <div class="product-card">
                    <div class="product-image">
                        <a href="./produto.html?nome=${encodeURIComponent(item.name)}&preco=${encodeURIComponent(item.price)}&imagem=${encodeURIComponent(item.image)}&categoria=${encodeURIComponent(item.category || 'Plus Size')}">
                            <img src="${item.image}" alt="${item.name}">
                        </a>
                        <div class="product-actions">
                            <button aria-label="Remover dos favoritos" onclick="removeFavoriteByName('${item.name}')">
                                <i class="bi bi-heart-fill" style="color: var(--color-primary);"></i>
                            </button>
                            <button aria-label="Adicionar ao carrinho" onclick="addToCart('${item.name}', '${item.price}', '${item.image}')">
                                <i class="bi bi-cart-plus"></i>
                            </button>
                            <a href="./produto.html?nome=${encodeURIComponent(item.name)}&preco=${encodeURIComponent(item.price)}&imagem=${encodeURIComponent(item.image)}&categoria=${encodeURIComponent(item.category || 'Plus Size')}" aria-label="Visualizar">
                                <button><i class="bi bi-eye"></i></button>
                            </a>
                        </div>
                    </div>
                    <div class="product-info">
                        <div class="product-category">${item.category || 'Plus Size'}</div>
                        <h3 class="product-title">
                            <a href="./produto.html?nome=${encodeURIComponent(item.name)}&preco=${encodeURIComponent(item.price)}&imagem=${encodeURIComponent(item.image)}&categoria=${encodeURIComponent(item.category || 'Plus Size')}" style="color: inherit; text-decoration: none;">
                                ${item.name}
                            </a>
                        </h3>
                        <div class="product-price">
                            <span class="price-current">${item.price}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

function removeFavoriteByName(name) {
    const favorites = getFavorites();
    const index = favorites.findIndex(item => item.name === name);
    if (index >= 0) {
        favorites.splice(index, 1);
        saveFavorites(favorites);
        renderFavorites();
        showToast('Produto removido dos favoritos');
    }
}

function handleFavoriteClick(btn) {
    const product = {
        name: btn.dataset.name,
        price: btn.dataset.price,
        image: btn.dataset.image,
        category: btn.dataset.category || 'Plus Size'
    };
    toggleFavorite(product);
    updateFavoriteButtons();
}
