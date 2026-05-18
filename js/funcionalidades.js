/**
 * Bia Modas - Funcionalidades de UX
 * ==================================
 * - Filtro por categoria na navegação
 * - Vistos recentemente (localStorage)
 * - Recomendados baseados nas preferências do usuário
 */

const RECENTES_KEY = 'bia_modas_recentes';
const RECOMENDADOS_KEY = 'bia_modas_recomendados';

// --- Utilitários ---

function extractProductMeta(name) {
    const lower = name.toLowerCase();
    let cor = '';
    let tipo = '';

    // Extrai cor do nome
    const cores = {
        'preto': 'Preto', 'preta': 'Preto',
        'branco': 'Branco', 'branca': 'Branco',
        'vermelho': 'Vermelho', 'vermelha': 'Vermelho',
        'azul': 'Azul',
        'verde': 'Verde',
        'rosa': 'Rosa',
        'estampado': 'Estampado',
        'neutro': 'Neutro'
    };
    for (const [key, val] of Object.entries(cores)) {
        if (lower.includes(key)) { cor = val; break; }
    }

    // Extrai tipo do nome
    const tipos = [
        { key: 'vestido', val: 'Vestidos' },
        { key: 'regata', val: 'Blusas' },
        { key: 'blusa', val: 'Blusas' },
        { key: 'calça', val: 'Calças' },
        { key: 'calca', val: 'Calças' }
    ];
    for (const t of tipos) {
        if (lower.includes(t.key)) { tipo = t.val; break; }
    }

    return { cor, tipo };
}

function getRecentes() {
    return JSON.parse(localStorage.getItem(RECENTES_KEY)) || [];
}

function saveRecentView(product) {
    let recentes = getRecentes();
    // Remove duplicados
    recentes = recentes.filter(r => r.nome !== product.nome);
    // Adiciona no início
    recentes.unshift({
        nome: product.nome,
        preco: product.preco,
        imagem: product.imagem,
        categoria: product.categoria,
        badge: product.badge,
        vistoEm: new Date().toISOString()
    });
    // Limita a 8
    if (recentes.length > 8) recentes = recentes.slice(0, 8);
    localStorage.setItem(RECENTES_KEY, JSON.stringify(recentes));
}

function buildProductCard(product) {
    const meta = extractProductMeta(product.nome);
    const hasBadge = product.badge && product.badge.trim() !== '';
    const badgeHtml = hasBadge ? `<span class="product-badge">${product.badge}</span>` : '';
    const priceOriginal = product.precoOriginal ? `<span class="price-original">${product.precoOriginal}</span>` : '';
    const discountHtml = product.desconto ? `<span class="price-discount">${product.desconto}</span>` : '';

    return `
        <div class="carousel-slide" data-cor="${meta.cor}" data-tipo="${meta.tipo}" data-categoria="${product.categoria}">
            <div class="product-card">
                <div class="product-image">
                    <img src="${product.imagem}" alt="${product.nome}">
                    ${badgeHtml}
                    <div class="product-actions">
                        <button aria-label="Adicionar aos favoritos"><i class="bi bi-heart"></i></button>
                        <button aria-label="Adicionar ao carrinho" onclick="addToCart('${product.nome.replace(/'/g, "\\'")}', '${product.preco}', '${product.imagem}')"><i class="bi bi-cart-plus"></i></button>
                        <button aria-label="Visualizar"><i class="bi bi-eye"></i></button>
                    </div>
                </div>
                <div class="product-info">
                    <div class="product-category">${product.categoria}</div>
                    <h3 class="product-title">${product.nome}</h3>
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

// --- Vistos Recentemente ---

function renderRecentes() {
    const section = document.getElementById('section-recentes');
    const track = document.getElementById('carousel-recentes');
    const dots = document.getElementById('dots-recentes');
    if (!section || !track) return;

    const recentes = getRecentes();

    if (recentes.length === 0) {
        section.style.display = 'none';
        return;
    }

    section.style.display = '';

    // Limpa o conteúdo hardcoded e renderiza os reais
    track.innerHTML = recentes.map(p => buildProductCard(p)).join('');
    if (dots) dots.innerHTML = '';

    // Reinicializa os links dos cards
    if (typeof initProductCards === 'function') {
        initProductCards();
    }
    // Reinicializa o carousel
    if (typeof initCarousel === 'function') {
        initCarousel('recentes');
    }
    if (typeof updateFavoriteButtons === 'function') {
        updateFavoriteButtons();
    }
}

// --- Recomendados baseado em preferências ---

function renderRecomendados() {
    const section = document.getElementById('section-recomendados');
    const track = document.getElementById('carousel-recomendados');
    if (!section || !track) return;

    const user = getCurrentUser ? getCurrentUser() : null;
    if (!user) {
        section.style.display = 'none';
        return;
    }

    const prefs = user.preferencias || {};
    const coresPref = prefs.cores || [];
    const estilosPref = prefs.estilos || [];

    // Coleta todos os produtos da página atual
    const todosCards = Array.from(document.querySelectorAll('.carousel-slide'));
    const pontuados = todosCards.map(card => {
        let score = 0;
        const cor = card.dataset.cor || '';
        const tipo = card.dataset.tipo || '';
        const categoria = card.dataset.categoria || '';

        if (coresPref.includes(cor)) score += 2;
        if (prefs.tipoCorpo && tipo) score += 1;

        return { card: card.cloneNode(true), score };
    }).filter(item => item.score > 0)
      .sort((a, b) => b.score - a.score)
      .slice(0, 8);

    if (pontuados.length === 0) {
        section.style.display = 'none';
        return;
    }

    section.style.display = '';
    track.innerHTML = pontuados.map(p => p.card.outerHTML).join('');

    if (typeof initProductCards === 'function') initProductCards();
    if (typeof initCarousel === 'function') initCarousel('recomendados');
    if (typeof updateFavoriteButtons === 'function') updateFavoriteButtons();
}

// --- Filtro por Categoria ---

function applyCategoryFilter(category) {
    const allSections = document.querySelectorAll('.products-section');
    const filterResult = document.getElementById('filter-result');
    const filterTitle = document.getElementById('filter-title');
    const filterCount = document.getElementById('filter-count');
    const btnClear = document.getElementById('btn-clear-filter');

    if (!filterResult || !filterTitle) return;

    // Esconde as seções originais
    allSections.forEach(sec => {
        if (sec.id !== 'filter-result-section') sec.style.display = 'none';
    });

    // Coleta todos os cards que correspondem
    const todosCards = Array.from(document.querySelectorAll('.carousel-slide'));
    const filtrados = todosCards.filter(card => {
        const cat = card.dataset.categoria || '';
        const tipo = card.dataset.tipo || '';
        return cat.toLowerCase() === category.toLowerCase() || tipo.toLowerCase() === category.toLowerCase();
    });

    // Remove duplicatas por nome
    const vistos = new Set();
    const unicos = [];
    filtrados.forEach(card => {
        const nome = card.querySelector('.product-title')?.textContent || '';
        if (!vistos.has(nome)) {
            vistos.add(nome);
            unicos.push(card.cloneNode(true));
        }
    });

    const track = document.getElementById('carousel-filtrados');
    if (track) {
        track.innerHTML = unicos.map(c => c.outerHTML).join('');
    }

    filterTitle.textContent = category;
    filterCount.textContent = `${unicos.length} produto${unicos.length !== 1 ? 's' : ''}`;
    filterResult.style.display = '';
    if (btnClear) btnClear.style.display = 'inline-flex';

    // Scroll para o resultado
    filterResult.scrollIntoView({ behavior: 'smooth', block: 'start' });

    // Reinicializa
    if (typeof initProductCards === 'function') initProductCards();
    if (typeof initCarousel === 'function') initCarousel('filtrados');
    if (typeof updateFavoriteButtons === 'function') updateFavoriteButtons();
}

function clearCategoryFilter() {
    const allSections = document.querySelectorAll('.products-section');
    const filterResult = document.getElementById('filter-result-section');
    const btnClear = document.getElementById('btn-clear-filter');

    allSections.forEach(sec => {
        if (sec.id !== 'filter-result-section') sec.style.display = '';
    });

    if (filterResult) filterResult.style.display = 'none';
    if (btnClear) btnClear.style.display = 'none';

    // Recarrega recentes e recomendados
    renderRecentes();
    renderRecomendados();

    // Reinicializa carousels originais
    if (typeof initCarousel === 'function') {
        ['novidades', 'recentes', 'promocoes'].forEach(id => {
            if (document.getElementById('carousel-' + id)) initCarousel(id);
        });
    }
}

function initCategoryFilter() {
    const params = new URLSearchParams(window.location.search);
    const categoria = params.get('categoria');
    const promocoes = params.get('promocoes');

    if (categoria) {
        applyCategoryFilter(categoria);
    } else if (promocoes) {
        applyPromocoesFilter();
    }
}

function applyPromocoesFilter() {
    const allSections = document.querySelectorAll('.products-section');
    const filterResult = document.getElementById('filter-result');
    const filterTitle = document.getElementById('filter-title');
    const filterCount = document.getElementById('filter-count');
    const btnClear = document.getElementById('btn-clear-filter');

    if (!filterResult || !filterTitle) return;

    allSections.forEach(sec => {
        if (sec.id !== 'filter-result-section') sec.style.display = 'none';
    });

    // Cards com badge de desconto ou promocional
    const todosCards = Array.from(document.querySelectorAll('.carousel-slide'));
    const filtrados = todosCards.filter(card => {
        const badge = card.querySelector('.product-badge')?.textContent || '';
        return badge.includes('%') || badge.includes('OFF') || badge.includes('Promo') || badge.includes('-');
    });

    const vistos = new Set();
    const unicos = [];
    filtrados.forEach(card => {
        const nome = card.querySelector('.product-title')?.textContent || '';
        if (!vistos.has(nome)) {
            vistos.add(nome);
            unicos.push(card.cloneNode(true));
        }
    });

    const track = document.getElementById('carousel-filtrados');
    if (track) {
        track.innerHTML = unicos.map(c => c.outerHTML).join('');
    }

    filterTitle.textContent = 'Promoções';
    filterCount.textContent = `${unicos.length} produto${unicos.length !== 1 ? 's' : ''}`;
    filterResult.style.display = '';
    if (btnClear) btnClear.style.display = 'inline-flex';

    filterResult.scrollIntoView({ behavior: 'smooth', block: 'start' });

    if (typeof initProductCards === 'function') initProductCards();
    if (typeof initCarousel === 'function') initCarousel('filtrados');
    if (typeof updateFavoriteButtons === 'function') updateFavoriteButtons();
}

// --- Inicialização ---

function enrichProductCards() {
    document.querySelectorAll('.carousel-slide').forEach(slide => {
        const title = slide.querySelector('.product-title')?.textContent || '';
        const category = slide.querySelector('.product-category')?.textContent || '';
        const meta = extractProductMeta(title);
        slide.dataset.categoria = category;
        slide.dataset.cor = meta.cor;
        slide.dataset.tipo = meta.tipo;
    });
}

document.addEventListener('DOMContentLoaded', () => {
    if (!document.getElementById('carousel-novidades')) return; // Só roda na index

    enrichProductCards();
    renderRecentes();
    renderRecomendados();
    initCategoryFilter();
});
