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

const TRY_ON_DEFAULT_CLOTH = 0xd1001f; // cor primária Bia Modas

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
    if (!thumbs) return;
    // O catálogo só tem 1 imagem por produto, então repetimos para simular vistas múltiplas.
    const images = [mainImage, mainImage, mainImage, mainImage];
    thumbs.innerHTML = images.map((src, i) => `
        <button class="gallery-thumb ${i === 0 ? 'active' : ''}" data-img="${src}" aria-label="Visualização ${i + 1}">
            <img src="${src}" alt="Vista ${i + 1}">
        </button>
    `).join('');

    thumbs.addEventListener('click', e => {
        const btn = e.target.closest('.gallery-thumb');
        if (!btn) return;
        thumbs.querySelectorAll('.gallery-thumb').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const mainImg = document.getElementById('product-image');
        if (mainImg) mainImg.src = btn.dataset.img;
    });
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
    syncAvatarColor(corPrincipal);

    container.addEventListener('click', e => {
        const btn = e.target.closest('.color-option');
        if (!btn) return;
        container.querySelectorAll('.color-option').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        if (label) label.textContent = btn.dataset.color;
        syncAvatarColor(btn.dataset.color);
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

// ============================================================
// Provador Virtual (mockup com avatar 3D real — Three.js)
// ============================================================

const THREE_CDN = 'https://cdn.jsdelivr.net/npm/three@0.128.0/build/three.min.js';
const ORBIT_CDN = 'https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js';
const GLTF_CDN  = 'https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/GLTFLoader.js';
const AVATAR_MODEL_PATH = './osama_bin_laden.glb';
const AVATAR_TARGET_HEIGHT = 4.5; // unidades da cena — normaliza tamanho do modelo importado

let tryOn = null;          // { scene, camera, renderer, controls, avatar, animId, resizeHandler }
let tryOnLoading = false;

function openVirtualTryOn() {
    const overlay = document.getElementById('try-on-overlay');
    if (!overlay) return;
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';

    if (!tryOn && !tryOnLoading) {
        tryOnLoading = true;
        loadThreeJs().then(() => {
            tryOn = initAvatarScene();
            tryOnLoading = false;
        }).catch(err => {
            console.error('Failed to load Three.js', err);
            tryOnLoading = false;
            const loading = document.getElementById('try-on-loading');
            if (loading) loading.innerHTML = '<span>Não foi possível carregar o provador virtual.</span>';
        });
    } else if (tryOn) {
        // Já iniciado: apenas resume animação
        if (!tryOn.running) {
            tryOn.running = true;
            animateScene();
        }
        tryOn.resizeHandler && tryOn.resizeHandler();
    }
}

function closeVirtualTryOn(e) {
    // Só fecha se clicou no overlay (fora do modal) ou foi acionado programaticamente
    if (e && e.target && !e.target.classList.contains('try-on-overlay') && e.type === 'click') {
        return;
    }
    const overlay = document.getElementById('try-on-overlay');
    if (!overlay) return;
    overlay.classList.remove('open');
    document.body.style.overflow = '';
    if (tryOn) tryOn.running = false; // pausa o loop de animação enquanto fechado
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        const overlay = document.getElementById('try-on-overlay');
        if (overlay && overlay.classList.contains('open')) closeVirtualTryOn();
    }
});

function loadThreeJs() {
    if (window.THREE && window.THREE.OrbitControls && window.THREE.GLTFLoader) return Promise.resolve();
    return loadScript(THREE_CDN)
        .then(() => loadScript(ORBIT_CDN))
        .then(() => loadScript(GLTF_CDN));
}

function loadScript(src) {
    return new Promise((resolve, reject) => {
        const existing = document.querySelector(`script[src="${src}"]`);
        if (existing) {
            if (existing.dataset.loaded === '1') return resolve();
            existing.addEventListener('load', () => resolve());
            existing.addEventListener('error', reject);
            return;
        }
        const s = document.createElement('script');
        s.src = src;
        s.onload = () => { s.dataset.loaded = '1'; resolve(); };
        s.onerror = reject;
        document.head.appendChild(s);
    });
}

function initAvatarScene() {
    const canvas = document.getElementById('try-on-canvas');
    if (!canvas) return null;

    const w = canvas.clientWidth || canvas.parentElement.clientWidth;
    const h = canvas.clientHeight || canvas.parentElement.clientHeight;

    const scene = new THREE.Scene();
    scene.background = null;

    const camera = new THREE.PerspectiveCamera(34, w / h, 0.1, 100);
    camera.position.set(0, 2.55, 6.4);

    const renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.setSize(w, h, false);
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    renderer.outputEncoding = THREE.sRGBEncoding;

    // Lighting
    const ambient = new THREE.AmbientLight(0xffffff, 0.55);
    scene.add(ambient);

    const key = new THREE.DirectionalLight(0xffffff, 0.95);
    key.position.set(4, 8, 5);
    key.castShadow = true;
    key.shadow.mapSize.set(1024, 1024);
    key.shadow.camera.left = -3;
    key.shadow.camera.right = 3;
    key.shadow.camera.top = 5;
    key.shadow.camera.bottom = -1;
    scene.add(key);

    const fill = new THREE.DirectionalLight(0xfde2e6, 0.3);
    fill.position.set(-4, 3, 2);
    scene.add(fill);

    const rim = new THREE.DirectionalLight(0xffffff, 0.2);
    rim.position.set(0, 4, -5);
    scene.add(rim);

    // Floor para receber sombra
    const floor = new THREE.Mesh(
        new THREE.CircleGeometry(3, 48),
        new THREE.ShadowMaterial({ opacity: 0.18 })
    );
    floor.rotation.x = -Math.PI / 2;
    floor.position.y = 0;
    floor.receiveShadow = true;
    scene.add(floor);

    // Controls
    const controls = new THREE.OrbitControls(camera, canvas);
    controls.enableDamping = true;
    controls.dampingFactor = 0.08;
    controls.target.set(0, 2.4, 0);
    controls.minDistance = 4.5;
    controls.maxDistance = 10;
    controls.minPolarAngle = Math.PI * 0.18;
    controls.maxPolarAngle = Math.PI * 0.62;
    controls.enablePan = false;
    controls.autoRotate = true;
    controls.autoRotateSpeed = 1.0;
    controls.addEventListener('start', () => { controls.autoRotate = false; });

    const state = {
        scene, camera, renderer, controls, avatar: null,
        running: true,
        animId: 0,
        resizeHandler: null
    };

    // Carrega o modelo .glb de forma assíncrona
    const loader = new THREE.GLTFLoader();
    loader.load(
        AVATAR_MODEL_PATH,
        (gltf) => {
            const model = gltf.scene;

            // Normaliza tamanho e posição: altura alvo + base no chão + centralizado em X/Z
            const box = new THREE.Box3().setFromObject(model);
            const size = box.getSize(new THREE.Vector3());
            const center = box.getCenter(new THREE.Vector3());
            const scale = size.y > 0 ? AVATAR_TARGET_HEIGHT / size.y : 1;
            model.scale.setScalar(scale);
            model.position.x = -center.x * scale;
            model.position.y = -box.min.y * scale;
            model.position.z = -center.z * scale;

            model.traverse(n => {
                if (n.isMesh) {
                    n.castShadow = true;
                    n.receiveShadow = true;
                }
            });

            scene.add(model);
            state.avatar = model;

            const loading = document.getElementById('try-on-loading');
            if (loading) loading.classList.add('hidden');
        },
        undefined,
        (err) => {
            console.error('Falha ao carregar modelo 3D:', err);
            const loading = document.getElementById('try-on-loading');
            if (loading) loading.innerHTML = '<span>Não foi possível carregar o modelo 3D.</span>';
        }
    );

    function resize() {
        const cw = canvas.clientWidth || canvas.parentElement.clientWidth;
        const ch = canvas.clientHeight || canvas.parentElement.clientHeight;
        if (cw === 0 || ch === 0) return;
        camera.aspect = cw / ch;
        camera.updateProjectionMatrix();
        renderer.setSize(cw, ch, false);
    }
    state.resizeHandler = resize;
    window.addEventListener('resize', resize);

    tryOn = state;
    animateScene();
    return state;
}

function animateScene() {
    if (!tryOn || !tryOn.running) return;
    tryOn.controls.update();
    tryOn.renderer.render(tryOn.scene, tryOn.camera);
    tryOn.animId = requestAnimationFrame(animateScene);
}

// Sincroniza cor da roupa do avatar com o seletor de cor da página.
// Modelos importados da net normalmente não expõem uma "malha de roupa" identificável,
// então esta função é no-op caso o modelo não defina userData.clothMat.
function syncAvatarColor(corNome) {
    if (!tryOn || !tryOn.avatar || !tryOn.avatar.userData || !tryOn.avatar.userData.clothMat) return;
    const mat = tryOn.avatar.userData.clothMat;
    const valor = COLOR_HEX[corNome];
    if (!valor || (typeof valor === 'string' && valor.startsWith('linear'))) {
        mat.color.setHex(TRY_ON_DEFAULT_CLOTH);
        return;
    }
    mat.color.set(valor);
}

document.addEventListener('DOMContentLoaded', () => {
    renderProductPage();

    // Salva como visto recentemente
    const product = getQueryParams();
    if (product.nome && product.nome !== 'Produto' && typeof saveRecentView === 'function') {
        saveRecentView(product);
    }
});
