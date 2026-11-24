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
        renderCarouselTrack('carousel-novidades', ['655763783_18587522434040712_5117797556536014863_n', '656202496_18587522443040712_9008272828061942804_n', '657598651_18573763777040712_6966380605204482899_n', '659331847_18577711228040712_207586077417158524_n'], 2);
        initCarousel('novidades');
    }
    if (document.getElementById('carousel-recentes')) {
        // Se funcionalidades.js tiver dados reais de recentes, não sobrescreve
        const temRecentesReais = typeof getRecentes === 'function' && getRecentes().length > 0;
        if (!temRecentesReais) {
            renderCarouselTrack('carousel-recentes', ['656166026_18573763729040712_759498418098033265_n', '656650964_18573763798040712_693249757322836689_n', '657539762_18573763816040712_7436584707047200505_n', '657820557_18577711375040712_2935750976970278950_n'], 2);
        }
        initCarousel('recentes');
    }
    if (document.getElementById('carousel-promocoes')) {
        renderCarouselTrack('carousel-promocoes', ['656166026_18573763729040712_759498418098033265_n', '656650964_18573763798040712_693249757322836689_n', '657539762_18573763816040712_7436584707047200505_n', '657820557_18577711375040712_2935750976970278950_n'], 2);
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
