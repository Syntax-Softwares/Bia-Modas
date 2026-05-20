function renderCarouselTrack(trackId, productIds, repeat) {
    const track = document.getElementById(trackId);
    if (!track || typeof PRODUTOS === 'undefined') return;
    let ids = productIds.slice();
    for (let i = 1; i < (repeat || 1); i++) ids = ids.concat(productIds);
    const products = ids.map(id => PRODUTOS.find(p => p.id === id)).filter(Boolean);
    track.innerHTML = products.map(buildCarouselSlideHTML).join('');
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    updateCartUI();

    // Search bar Enter key
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') handleSearch();
        });
    }

    if (document.getElementById('carousel-novidades')) {
        renderCarouselTrack('carousel-novidades', ['vestido-verde-plus', 'regata-rosa-plus', 'blusa-vermelha-plus', 'blusa-preta-plus'], 2);
        initCarousel('novidades');
    }
    if (document.getElementById('carousel-recentes')) {
        renderCarouselTrack('carousel-recentes', ['blusa-azul-plus', 'vestido-verde-plus', 'blusa-vermelha-plus', 'blusa-preta-plus'], 2);
        initCarousel('recentes');
    }
    if (document.getElementById('carousel-promocoes')) {
        renderCarouselTrack('carousel-promocoes', ['blusa-azul-plus', 'blusa-vermelha-plus', 'regata-rosa-plus', 'blusa-preta-plus'], 2);
        initCarousel('promocoes');
    }

    if (document.querySelector('.product-card')) {
        initProductCards();
    }

    updateFavoriteButtons();

    // Ano dinamico no footer
    const yearEl = document.getElementById('current-year');
    if (yearEl) {
        yearEl.textContent = new Date().getFullYear();
    }
});

// Update on resize
let resizeTimer;
window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(updateAllCarousels, 250);
});

// Init product cards links and favorite buttons
function initProductCards() {
    document.querySelectorAll('.product-card').forEach(card => {
        const img = card.querySelector('.product-image img');
        const title = card.querySelector('.product-title');
        const favBtn = card.querySelector('button[aria-label="Adicionar aos favoritos"]');
        const cartBtn = card.querySelector('button[aria-label="Adicionar ao carrinho"]');
        const viewBtn = card.querySelector('button[aria-label="Visualizar"]');

        const name = img ? img.alt : '';
        const image = img ? img.src : '';
        const priceEl = card.querySelector('.price-current');
        const price = priceEl ? priceEl.textContent.trim() : '';
        const categoryEl = card.querySelector('.product-category');
        const category = categoryEl ? categoryEl.textContent.trim() : 'Plus Size';
        const badgeEl = card.querySelector('.product-badge');
        const badge = badgeEl ? badgeEl.textContent.trim() : '';

        const productUrl = buildProductUrl({ nome: name, preco: price, imagem: image, categoria: category, badge });

        // Make image clickable
        if (img && !img.closest('a')) {
            const wrapper = document.createElement('a');
            wrapper.href = productUrl;
            wrapper.style.display = 'block';
            wrapper.style.width = '100%';
            wrapper.style.height = '100%';
            img.parentNode.insertBefore(wrapper, img);
            wrapper.appendChild(img);
        }

        // Make title clickable
        if (title && !title.querySelector('a')) {
            const link = document.createElement('a');
            link.href = productUrl;
            link.style.color = 'inherit';
            link.style.textDecoration = 'none';
            link.textContent = title.textContent;
            title.innerHTML = '';
            title.appendChild(link);
        }

        // Setup favorite button
        if (favBtn) {
            favBtn.dataset.favoriteBtn = '';
            favBtn.dataset.name = name;
            favBtn.dataset.price = price;
            favBtn.dataset.image = image;
            favBtn.dataset.category = category;
            favBtn.onclick = function() { handleFavoriteClick(this); };
        }

        // Setup view button link
        if (viewBtn) {
            const viewLink = document.createElement('a');
            viewLink.href = productUrl;
            viewLink.style.textDecoration = 'none';
            viewLink.setAttribute('aria-label', 'Visualizar');
            viewBtn.parentNode.insertBefore(viewLink, viewBtn);
            viewLink.appendChild(viewBtn);
        }
    });
}

function handleSearch() {
    const input = document.getElementById('search-input');
    if (!input) return;
    const termo = input.value.trim();
    if (termo) {
        window.location.href = `./categoria.html?busca=${encodeURIComponent(termo)}`;
    } else {
        window.location.href = './categoria.html';
    }
}
