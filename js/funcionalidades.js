/**
 * Bia Modas - Funcionalidades de UX
 * ==================================
 * - Filtro por categoria na navegação
 * - Vistos recentemente (localStorage)
 */

const RECENTES_KEY = 'bia_modas_recentes';

// --- Utilitários ---

function extractProductMeta(name) {
    const lower = name.toLowerCase();
    let cor = '';
    let tipo = '';
    let estilo = '';

    // Extrai cor do nome
    const cores = {
        'preto': 'Preto', 'preta': 'Preto',
        'branco': 'Branco', 'branca': 'Branco',
        'vermelho': 'Vermelho', 'vermelha': 'Vermelho',
        'azul': 'Azul',
        'verde': 'Verde',
        'rosa': 'Rosa',
        'estampado': 'Estampado',
        'estampada': 'Estampado',
        'neutro': 'Neutro', 'neutra': 'Neutro',
        'bege': 'Bege', 'jeans': 'Jeans',
        'marrom': 'Marrom'
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

    // Extrai estilo do nome
    const estilos = [
        { key: 'elegante', val: 'Elegante' },
        { key: 'casual', val: 'Casual' },
        { key: 'esportiva', val: 'Esportivo' },
        { key: 'esportivo', val: 'Esportivo' },
        { key: 'romântico', val: 'Romântico' },
        { key: 'romantico', val: 'Romântico' },
        { key: 'vintage', val: 'Vintage' },
        { key: 'boho', val: 'Vintage' },
        { key: 'minimalista', val: 'Minimalista' },
        { key: 'básica', val: 'Minimalista' },
        { key: 'basica', val: 'Minimalista' }
    ];
    for (const e of estilos) {
        if (lower.includes(e.key)) { estilo = e.val; break; }
    }

    return { cor, tipo, estilo };
}

function getRecentes() {
    return JSON.parse(localStorage.getItem(RECENTES_KEY)) || [];
}

function saveRecentView(product) {
    let recentes = getRecentes();
    // Remove duplicados
    recentes = recentes.filter(r => r.nome !== product.nome);
    // Normaliza preço pra sempre ter "R$ " — corrige cards antigos sem prefixo
    const precoNormalizado = typeof formatPriceForUrl === 'function'
        ? formatPriceForUrl(product.preco)
        : product.preco;
    // Adiciona no início
    recentes.unshift({
        nome: product.nome,
        preco: precoNormalizado,
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
    // Normaliza preço caso esteja salvo sem o "R$ " (cards antigos do localStorage)
    const normalizado = Object.assign({}, product, {
        preco: typeof formatPriceForUrl === 'function' ? formatPriceForUrl(product.preco) : product.preco
    });
    return `
        <div class="carousel-slide" data-cor="${meta.cor}" data-tipo="${meta.tipo}" data-categoria="${product.categoria}">
            ${buildProductCardInner(normalizado, { withLinks: false })}
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

// --- Inicialização ---

document.addEventListener('DOMContentLoaded', () => {
    if (!document.getElementById('carousel-novidades')) return; // Só roda na index

    renderRecentes();
});
