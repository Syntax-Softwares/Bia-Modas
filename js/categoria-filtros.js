/**
 * Bia Modas - Filtros da Página de Categoria
 * ==========================================
 * Lógica de filtros, ordenação e renderização do grid de produtos.
 * Antigo código inline de categoria.html.
 */
(function() {
    'use strict';

    const params = new URLSearchParams(window.location.search);
    const categoriaParam = params.get('categoria');
    const promocoesParam = params.get('promocoes');
    const buscaParam = params.get('busca');

    // State
    let filtrosAtivos = {
        categoria: categoriaParam || 'todos',
        cores: [],
        estilos: [],
        tamanhos: [],
        precoMin: null,
        precoMax: null,
        promocao: promocoesParam === '1',
        ordenacao: '',
        busca: buscaParam || ''
    };

    // Preenche search bar com termo de busca
    if (buscaParam) {
        const searchInput = document.getElementById('search-input');
        if (searchInput) searchInput.value = buscaParam;
    }

    // Render sidebar filters
    function renderSidebar() {
        // Categories
        const catList = document.getElementById('filter-categories');
        const categorias = ['Todos', ...getCategorias()];
        const todosProdutos = getProdutos();
        catList.innerHTML = categorias.map(cat => {
            const val = cat.toLowerCase();
            const count = val === 'todos' ? todosProdutos.length : todosProdutos.filter(p =>
                p.categoria.toLowerCase() === val || p.tipo.toLowerCase() === val
            ).length;
            const active = filtrosAtivos.categoria === val ? 'active' : '';
            return `<li><a href="#" class="${active}" onclick="setCategory('${cat}'); return false;">${cat} <span class="count">${count}</span></a></li>`;
        }).join('');

        // Colors
        const colorsContainer = document.getElementById('filter-colors');
        const cores = getCores();
        const corMap = {
            'Preto': '#212529', 'Branco': '#f8f9fa', 'Vermelho': '#dc3545',
            'Azul': '#0d6efd', 'Verde': '#198754', 'Rosa': '#d63384',
            'Estampado': '#fd7e14', 'Neutro': '#6c757d'
        };
        colorsContainer.innerHTML = cores.map(cor => {
            const active = filtrosAtivos.cores.includes(cor) ? 'active' : '';
            const corHex = corMap[cor] || '#ccc';
            const border = cor === 'Branco' ? 'border: 1px solid #ddd;' : '';
            return `<div class="color-swatch ${active}" onclick="toggleColor('${cor}')" style="background: ${corHex}; ${border}" title="${cor}"></div>`;
        }).join('');

        // Prices
        const pricesContainer = document.getElementById('filter-prices');
        const faixas = [
            { label: 'Até R$ 100', min: 0, max: 100 },
            { label: 'R$ 100 a R$ 150', min: 100, max: 150 },
            { label: 'R$ 150 a R$ 200', min: 150, max: 200 },
            { label: 'Acima de R$ 200', min: 200, max: null }
        ];
        pricesContainer.innerHTML = faixas.map(f => {
            const active = filtrosAtivos.precoMin === f.min && filtrosAtivos.precoMax === f.max ? 'active' : '';
            return `<button class="filter-price-btn ${active}" onclick="setPriceRange(${f.min}, ${f.max})">${f.label}</button>`;
        }).join('');

        // Sizes
        const sizesContainer = document.getElementById('filter-sizes');
        const tamanhos = getTamanhos();
        sizesContainer.innerHTML = tamanhos.map(t => {
            const active = filtrosAtivos.tamanhos.includes(t) ? 'active' : '';
            return `<button class="filter-size-btn ${active}" onclick="toggleSize('${t}')">${t}</button>`;
        }).join('');

        // Estilos
        const estilosContainer = document.getElementById('filter-estilos');
        const estilos = getEstilos();
        estilosContainer.innerHTML = estilos.map(e => {
            const active = filtrosAtivos.estilos.includes(e) ? 'checked' : '';
            return `<div class="filter-checkbox-tag">
                <input type="checkbox" id="estilo-${e}" ${active} onchange="toggleEstilo('${e}')">
                <label for="estilo-${e}">${e}</label>
            </div>`;
        }).join('');

        // Promo
        document.getElementById('filter-promo').checked = filtrosAtivos.promocao;
    }

    // Apply filters and render grid
    function applyFilters() {
        filtrosAtivos.ordenacao = document.getElementById('sort-select').value;
        const produtos = filtrarProdutos(filtrosAtivos);
        renderGrid(produtos);
        renderSidebar();
        renderActiveChips();
        updateMobileFilterCount();
        updatePageTitle();
    }

    function renderGrid(produtos) {
        const grid = document.getElementById('products-grid');
        const countEl = document.getElementById('results-count');

        if (produtos.length === 0) {
            countEl.textContent = 'Nenhum produto encontrado';
            grid.innerHTML = `
                <div class="col-12">
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <h3>Nenhum produto encontrado</h3>
                        <p>Tente ajustar os filtros para encontrar o que procura.</p>
                    </div>
                </div>
            `;
        } else {
            countEl.textContent = `${produtos.length} produto${produtos.length !== 1 ? 's' : ''} encontrado${produtos.length !== 1 ? 's' : ''}`;
            grid.innerHTML = produtos.map(p => buildProductCardHTML(p)).join('');
        }

        // Update favorites - lê nome via data attribute (mais robusto que regex em onclick)
        document.querySelectorAll('.product-actions button[data-fav-name]').forEach(btn => {
            const nome = btn.dataset.favName;
            if (nome && typeof isFavorite === 'function' && isFavorite(nome)) {
                btn.innerHTML = '<i class="bi bi-heart-fill"></i>';
                btn.classList.add('active');
            }
        });
    }

    let activeChips = [];

    function renderActiveChips() {
        const container = document.getElementById('active-filters');
        activeChips = [];

        if (filtrosAtivos.categoria && filtrosAtivos.categoria !== 'todos') {
            activeChips.push({ label: 'Categoria: ' + capitalize(filtrosAtivos.categoria), remove: () => setCategory('Todos') });
        }
        filtrosAtivos.cores.forEach(c => {
            activeChips.push({ label: 'Cor: ' + c, remove: () => toggleColor(c) });
        });
        filtrosAtivos.estilos.forEach(e => {
            activeChips.push({ label: 'Estilo: ' + e, remove: () => toggleEstilo(e) });
        });
        filtrosAtivos.tamanhos.forEach(t => {
            activeChips.push({ label: 'Tamanho: ' + t, remove: () => toggleSize(t) });
        });
        if (filtrosAtivos.precoMin !== null || filtrosAtivos.precoMax !== null) {
            let label = 'Preço: ';
            if (filtrosAtivos.precoMax === null) label += 'Acima de R$ ' + filtrosAtivos.precoMin;
            else if (filtrosAtivos.precoMin === 0) label += 'Até R$ ' + filtrosAtivos.precoMax;
            else label += 'R$ ' + filtrosAtivos.precoMin + ' - R$ ' + filtrosAtivos.precoMax;
            activeChips.push({ label, remove: () => setPriceRange(null, null) });
        }
        if (filtrosAtivos.promocao) {
            activeChips.push({ label: 'Em Promoção', remove: () => { filtrosAtivos.promocao = false; applyFilters(); } });
        }
        if (filtrosAtivos.busca) {
            activeChips.push({ label: 'Busca: ' + filtrosAtivos.busca, remove: () => { filtrosAtivos.busca = ''; applyFilters(); } });
        }

        container.innerHTML = activeChips.map((c, i) => `
            <span class="filter-chip">${c.label} <button onclick="removeChip(${i})" aria-label="Remover filtro"><i class="bi bi-x"></i></button></span>
        `).join('');
    }

    function updateMobileFilterCount() {
        const btn = document.getElementById('mobile-filter-count');
        let count = 0;
        if (filtrosAtivos.categoria !== 'todos') count++;
        count += filtrosAtivos.cores.length;
        count += filtrosAtivos.estilos.length;
        count += filtrosAtivos.tamanhos.length;
        if (filtrosAtivos.precoMin !== null) count++;
        if (filtrosAtivos.promocao) count++;
        if (filtrosAtivos.busca) count++;
        if (count > 0) {
            btn.textContent = '(' + count + ')';
            btn.style.display = '';
        } else {
            btn.style.display = 'none';
        }
    }

    function updatePageTitle() {
        const titleEl = document.getElementById('page-title');
        const subEl = document.getElementById('page-subtitle');
        const breadEl = document.getElementById('breadcrumb-current');
        let titulo = 'Todos os Produtos';
        if (filtrosAtivos.busca) {
            titulo = 'Resultados para "' + filtrosAtivos.busca + '"';
        } else if (filtrosAtivos.categoria && filtrosAtivos.categoria !== 'todos') {
            titulo = capitalize(filtrosAtivos.categoria);
        }
        titleEl.textContent = titulo;
        if (breadEl) breadEl.textContent = titulo;
        const produtos = filtrarProdutos(filtrosAtivos);
        subEl.textContent = produtos.length + ' produto' + (produtos.length !== 1 ? 's' : '') + ' na coleção';
    }

    // Expose functions to global scope
    window.setCategory = function(cat) {
        filtrosAtivos.categoria = cat.toLowerCase();
        applyFilters();
    };
    window.toggleColor = function(cor) {
        const idx = filtrosAtivos.cores.indexOf(cor);
        if (idx >= 0) filtrosAtivos.cores.splice(idx, 1);
        else filtrosAtivos.cores.push(cor);
        applyFilters();
    };
    window.toggleEstilo = function(estilo) {
        const idx = filtrosAtivos.estilos.indexOf(estilo);
        if (idx >= 0) filtrosAtivos.estilos.splice(idx, 1);
        else filtrosAtivos.estilos.push(estilo);
        applyFilters();
    };
    window.toggleSize = function(tam) {
        const idx = filtrosAtivos.tamanhos.indexOf(tam);
        if (idx >= 0) filtrosAtivos.tamanhos.splice(idx, 1);
        else filtrosAtivos.tamanhos.push(tam);
        applyFilters();
    };
    window.setPriceRange = function(min, max) {
        if (filtrosAtivos.precoMin === min && filtrosAtivos.precoMax === max) {
            filtrosAtivos.precoMin = null;
            filtrosAtivos.precoMax = null;
        } else {
            filtrosAtivos.precoMin = min;
            filtrosAtivos.precoMax = max;
        }
        applyFilters();
    };
    window.applyFilters = function() {
        filtrosAtivos.promocao = document.getElementById('filter-promo').checked;
        applyFilters();
    };
    window.clearAllFilters = function() {
        filtrosAtivos = {
            categoria: 'todos',
            cores: [],
            estilos: [],
            tamanhos: [],
            precoMin: null,
            precoMax: null,
            promocao: false,
            ordenacao: '',
            busca: ''
        };
        document.getElementById('sort-select').value = '';
        document.getElementById('filter-promo').checked = false;
        const searchInput = document.getElementById('search-input');
        if (searchInput) searchInput.value = '';
        applyFilters();
    };
    window.removeChip = function(index) {
        if (activeChips[index]) {
            activeChips[index].remove();
        }
    };
    // Sidebar drawer (substitui offcanvas + colapso inline)
    window.openSidebar = function() {
        const sidebar = document.getElementById('category-sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');
        if (!sidebar) return;
        sidebar.classList.add('open');
        if (backdrop) backdrop.classList.add('open');
        document.body.classList.add('sidebar-open');
        // Em mobile, trava scroll do body
        if (window.innerWidth < 992) document.body.style.overflow = 'hidden';
    };

    window.closeSidebar = function() {
        const sidebar = document.getElementById('category-sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');
        if (!sidebar) return;
        sidebar.classList.remove('open');
        if (backdrop) backdrop.classList.remove('open');
        document.body.classList.remove('sidebar-open');
        document.body.style.overflow = '';
    };

    // ESC fecha
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            const sidebar = document.getElementById('category-sidebar');
            if (sidebar && sidebar.classList.contains('open')) window.closeSidebar();
        }
    });

    // Compat: chamadas antigas continuam funcionando
    window.openFilterOffcanvas = window.openSidebar;
    window.closeFilterOffcanvas = window.closeSidebar;

    // Initialize
    renderSidebar();
    applyFilters();

    // No desktop, sidebar abre por padrão
    if (window.innerWidth >= 992) {
        window.openSidebar();
    }

})();
