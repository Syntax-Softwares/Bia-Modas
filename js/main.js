// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    updateCartUI();

    if (document.getElementById('carousel-novidades')) {
        initCarousel('novidades');
    }
    if (document.getElementById('carousel-recentes')) {
        initCarousel('recentes');
    }
    if (document.getElementById('carousel-promocoes')) {
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

        const productUrl = `./produto.html?nome=${encodeURIComponent(name)}&preco=${encodeURIComponent(price)}&imagem=${encodeURIComponent(image)}&categoria=${encodeURIComponent(category)}&badge=${encodeURIComponent(badge)}`;

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
