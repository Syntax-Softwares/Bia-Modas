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
        'neutro': 'Neutro', 'neutra': 'Neutro'
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

// Mapeia tipo de corpo para tipos de roupa que mais combinam
function getTiposRecomendadosPorCorpo(tipoCorpo) {
    const mapa = {
        'ampulheta': ['Vestidos', 'Blusas', 'Calças'],
        'triangulo': ['Vestidos', 'Calças', 'Blusas'],
        'triangulo-invertido': ['Blusas', 'Vestidos', 'Calças'],
        'retangulo': ['Vestidos', 'Blusas', 'Calças'],
        'oval': ['Blusas', 'Vestidos', 'Calças']
    };
    return mapa[tipoCorpo] || [];
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
    return `
        <div class="carousel-slide" data-cor="${meta.cor}" data-tipo="${meta.tipo}" data-categoria="${product.categoria}">
            ${buildProductCardInner(product, { withLinks: false })}
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
    const tiposRecomendados = getTiposRecomendadosPorCorpo(prefs.tipoCorpo);

    // Coleta todos os produtos da página atual
    const todosCards = Array.from(document.querySelectorAll('.carousel-slide'));
    const pontuados = todosCards.map(card => {
        let score = 0;
        const cor = card.dataset.cor || '';
        const tipo = card.dataset.tipo || '';
        const estilo = card.dataset.estilo || '';
        const categoria = card.dataset.categoria || '';

        // Pontua por cor favorita
        if (coresPref.includes(cor)) score += 2;

        // Pontua por estilo favorito
        if (estilosPref.includes(estilo)) score += 2;

        // Pontua por tipo de corpo (ordenação do array define prioridade)
        if (prefs.tipoCorpo && tiposRecomendados.includes(tipo)) {
            const idx = tiposRecomendados.indexOf(tipo);
            score += Math.max(1, 3 - idx); // 3 pontos para o 1º, 2 para o 2º, 1 para o 3º
        }

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

// --- Inicialização ---

function enrichProductCards() {
    document.querySelectorAll('.carousel-slide').forEach(slide => {
        const title = slide.querySelector('.product-title')?.textContent || '';
        const category = slide.querySelector('.product-category')?.textContent || '';
        const meta = extractProductMeta(title);
        slide.dataset.categoria = category;
        slide.dataset.cor = meta.cor;
        slide.dataset.tipo = meta.tipo;
        slide.dataset.estilo = meta.estilo;
    });
}

document.addEventListener('DOMContentLoaded', () => {
    if (!document.getElementById('carousel-novidades')) return; // Só roda na index

    enrichProductCards();
    renderRecentes();
    renderRecomendados();
});
