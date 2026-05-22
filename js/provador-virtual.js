// ============================================================
// Provador Virtual — Fluxo de captura (rosto → corpo → avatar 3D)
// ============================================================

const THREE_CDN = 'https://cdn.jsdelivr.net/npm/three@0.128.0/build/three.min.js';
const ORBIT_CDN = 'https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js';
const GLTF_CDN  = 'https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/GLTFLoader.js';
const AVATAR_MODEL_PATH = './osama_bin_laden.glb';
const AVATAR_TARGET_HEIGHT = 4.5;
const FAKE_AVATAR_DELAY_MS = 2200; // tempo da animação de "gerando avatar"

const state = {
    step: 1,
    product: null,
    photos: { face: null, body: null },
    streams: { face: null, body: null },
    avatar: null
};

// ---------------------------------------------------------
// Inicialização
// ---------------------------------------------------------

document.addEventListener('DOMContentLoaded', () => {
    state.product = readQueryParams();
    renderProductHeader(state.product);
    updateStepHeader(1);

    document.getElementById('btn-next').addEventListener('click', handleNext);
    document.getElementById('btn-prev').addEventListener('click', handlePrev);

    document.getElementById('btn-capture-face').addEventListener('click', () => capturePhoto('face'));
    document.getElementById('btn-retake-face').addEventListener('click', () => retakePhoto('face'));
    document.getElementById('btn-capture-body').addEventListener('click', () => capturePhoto('body'));
    document.getElementById('btn-retake-body').addEventListener('click', () => retakePhoto('body'));

    window.addEventListener('beforeunload', stopAllStreams);

    updateNav();
});

function readQueryParams() {
    const params = new URLSearchParams(window.location.search);
    return {
        nome: params.get('nome') || 'Produto',
        preco: params.get('preco') || 'R$ 0,00',
        imagem: params.get('imagem') || './img/logoBiaModas.png',
        categoria: params.get('categoria') || 'Plus Size',
        badge: params.get('badge') || ''
    };
}

function renderProductHeader(product) {
    const img = document.getElementById('provador-product-img');
    if (img) {
        img.src = product.imagem;
        img.alt = product.nome;
    }
    const name = document.getElementById('provador-product-name');
    if (name) name.textContent = product.nome;
    const price = document.getElementById('provador-product-price');
    if (price) price.textContent = product.preco;

    const summaryImg = document.getElementById('avatar-summary-image');
    if (summaryImg) summaryImg.src = product.imagem;
}

const STEP_CONFIG = {
    1: { eyebrow: 'Bem-vinda', title: 'Experimente antes de levar.',  progress: 25 },
    2: { eyebrow: 'Passo 02',  title: 'Vamos capturar seu rosto.',    progress: 50 },
    3: { eyebrow: 'Passo 03',  title: 'Agora, sua silhueta.',         progress: 75 },
    4: { eyebrow: 'Pronto',    title: 'Seu avatar está pronto.',      progress: 100 }
};

function updateStepHeader(step) {
    const cfg = STEP_CONFIG[step];
    if (!cfg) return;

    const eyebrow = document.getElementById('step-eyebrow');
    if (eyebrow) eyebrow.textContent = cfg.eyebrow;

    const title = document.getElementById('step-title');
    if (title) title.textContent = cfg.title;

    const counter = document.getElementById('step-counter-current');
    if (counter) counter.textContent = String(step).padStart(2, '0');

    const fill = document.getElementById('progress-fill');
    if (fill) fill.style.right = `${100 - cfg.progress}%`;
}

// ---------------------------------------------------------
// Navegação entre steps
// ---------------------------------------------------------

function handleNext() {
    if (state.step === 1) {
        goToStep(2);
    } else if (state.step === 2) {
        if (!state.photos.face) return;
        goToStep(3);
    } else if (state.step === 3) {
        if (!state.photos.body) return;
        goToStep(4);
    } else if (state.step === 4) {
        // Final: adiciona ao carrinho e volta pro produto
        if (typeof addToCart === 'function') {
            addToCart(state.product.nome, state.product.preco, state.product.imagem);
        }
        window.location.href = './index.html';
    }
}

function handlePrev() {
    if (state.step === 1) return;
    goToStep(state.step - 1);
}

function goToStep(target) {
    const prevStep = state.step;
    state.step = target;

    document.querySelectorAll('.wizard-step').forEach(el => {
        el.classList.toggle('active', Number(el.dataset.step) === target);
    });

    updateStepHeader(target);
    onLeaveStep(prevStep);
    onEnterStep(target);
    updateNav();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function onEnterStep(step) {
    if (step === 2) startCamera('face');
    else if (step === 3) startCamera('body');
    else if (step === 4) generateAvatar();
}

function onLeaveStep(step) {
    if (step === 2) stopCamera('face');
    else if (step === 3) stopCamera('body');
}

function updateNav() {
    const prev = document.getElementById('btn-prev');
    const next = document.getElementById('btn-next');
    if (!prev || !next) return;

    prev.style.visibility = state.step === 1 ? 'hidden' : 'visible';

    const cfg = {
        1: { label: 'Começar', icon: 'bi-arrow-right', disabled: false },
        2: { label: 'Continuar', icon: 'bi-arrow-right', disabled: !state.photos.face },
        3: { label: 'Gerar avatar', icon: 'bi-stars',     disabled: !state.photos.body },
        4: { label: 'Adicionar ao carrinho', icon: 'bi-cart-plus', disabled: false }
    }[state.step];

    next.innerHTML = `${cfg.label} <i class="bi ${cfg.icon}"></i>`;
    next.disabled = cfg.disabled;
}

// ---------------------------------------------------------
// Câmera (getUserMedia)
// ---------------------------------------------------------

function startCamera(type) {
    const video = document.getElementById(`video-${type}`);
    const errorEl = document.getElementById(`error-${type}`);
    if (!video) return;

    if (state.streams[type]) return; // já rodando

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        showCameraError(type);
        return;
    }

    navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'user', width: { ideal: 1280 }, height: { ideal: 960 } },
        audio: false
    })
    .then(stream => {
        state.streams[type] = stream;
        video.srcObject = stream;
        if (errorEl) errorEl.hidden = true;
    })
    .catch(err => {
        console.warn(`Câmera ${type} indisponível:`, err);
        showCameraError(type);
    });
}

function stopCamera(type) {
    const stream = state.streams[type];
    if (!stream) return;
    stream.getTracks().forEach(t => t.stop());
    state.streams[type] = null;
    const video = document.getElementById(`video-${type}`);
    if (video) video.srcObject = null;
}

function stopAllStreams() {
    stopCamera('face');
    stopCamera('body');
}

function showCameraError(type) {
    const errorEl = document.getElementById(`error-${type}`);
    const captureBtn = document.getElementById(`btn-capture-${type}`);
    if (errorEl) errorEl.hidden = false;
    if (captureBtn) captureBtn.disabled = true;
}

// ---------------------------------------------------------
// Captura de fotos
// ---------------------------------------------------------

function capturePhoto(type) {
    const video = document.getElementById(`video-${type}`);
    const preview = document.getElementById(`preview-${type}`);
    const flash = document.getElementById(`flash-${type}`);
    const stage = video?.closest('.capture-stage');
    const captureBtn = document.getElementById(`btn-capture-${type}`);
    const retakeBtn = document.getElementById(`btn-retake-${type}`);

    if (!video || !preview || !video.videoWidth) return;

    // Snapshot do frame atual
    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    const ctx = canvas.getContext('2d');
    // espelha horizontalmente para manter coerência com o vídeo
    ctx.translate(canvas.width, 0);
    ctx.scale(-1, 1);
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    const dataUrl = canvas.toDataURL('image/jpeg', 0.85);

    state.photos[type] = dataUrl;
    preview.src = dataUrl;

    // Efeito flash
    if (flash) {
        flash.classList.remove('flash');
        void flash.offsetWidth; // força reflow para reiniciar a animação
        flash.classList.add('flash');
    }

    // Troca UI: oculta vídeo, exibe preview, troca botões
    setTimeout(() => {
        preview.hidden = false;
        video.style.opacity = '0';
        stage?.classList.add('captured');
        if (captureBtn) captureBtn.hidden = true;
        if (retakeBtn) retakeBtn.hidden = false;
        stopCamera(type);
        updateNav();
    }, 180);
}

function retakePhoto(type) {
    const video = document.getElementById(`video-${type}`);
    const preview = document.getElementById(`preview-${type}`);
    const stage = video?.closest('.capture-stage');
    const captureBtn = document.getElementById(`btn-capture-${type}`);
    const retakeBtn = document.getElementById(`btn-retake-${type}`);

    state.photos[type] = null;
    if (preview) {
        preview.hidden = true;
        preview.src = '';
    }
    if (video) video.style.opacity = '';
    stage?.classList.remove('captured');
    if (captureBtn) captureBtn.hidden = false;
    if (retakeBtn) retakeBtn.hidden = true;

    startCamera(type);
    updateNav();
}

// ---------------------------------------------------------
// Avatar 3D — reaproveita estrutura do produto.js
// ---------------------------------------------------------

function generateAvatar() {
    const loading = document.getElementById('avatar-loading');
    const hint = document.getElementById('avatar-hint');
    const summary = document.getElementById('avatar-summary');

    // Reset visual ao reentrar
    if (loading) loading.classList.remove('hidden');
    if (hint) hint.hidden = true;
    if (summary) summary.hidden = true;

    const productName = document.getElementById('avatar-summary-product');
    const productPrice = document.getElementById('avatar-summary-price');
    if (productName) productName.textContent = state.product.nome;
    if (productPrice) productPrice.textContent = state.product.preco;

    // Carrega Three.js em paralelo ao "tempo de processamento" simulado
    const threePromise = loadThreeJs();
    const minDelay = new Promise(r => setTimeout(r, FAKE_AVATAR_DELAY_MS));

    Promise.all([threePromise, minDelay])
        .then(() => {
            if (!state.avatar) {
                state.avatar = initAvatarScene();
            } else if (!state.avatar.running) {
                state.avatar.running = true;
                animateAvatar();
                state.avatar.resizeHandler && state.avatar.resizeHandler();
            }
        })
        .catch(err => {
            console.error('Falha ao carregar avatar 3D:', err);
            const text = document.getElementById('avatar-loading-text');
            const sub = document.getElementById('avatar-loading-sub');
            if (text) text.textContent = 'Não foi possível carregar o avatar';
            if (sub) sub.textContent = 'Verifique sua conexão e tente novamente.';
        });
}

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
    const canvas = document.getElementById('avatar-canvas');
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

    scene.add(new THREE.AmbientLight(0xffffff, 0.55));

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

    const floor = new THREE.Mesh(
        new THREE.CircleGeometry(3, 48),
        new THREE.ShadowMaterial({ opacity: 0.18 })
    );
    floor.rotation.x = -Math.PI / 2;
    floor.receiveShadow = true;
    scene.add(floor);

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

    const sceneState = { scene, camera, renderer, controls, avatar: null, running: true, resizeHandler: null };

    const loader = new THREE.GLTFLoader();
    loader.load(
        AVATAR_MODEL_PATH,
        (gltf) => {
            const model = gltf.scene;
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
            sceneState.avatar = model;
            revealAvatar();
        },
        undefined,
        (err) => {
            console.error('Falha ao carregar modelo 3D:', err);
            const loading = document.getElementById('avatar-loading');
            if (loading) loading.innerHTML = '<strong>Não foi possível carregar o modelo 3D</strong>';
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
    sceneState.resizeHandler = resize;
    window.addEventListener('resize', resize);

    state.avatar = sceneState;
    animateAvatar();
    return sceneState;
}

function animateAvatar() {
    if (!state.avatar || !state.avatar.running) return;
    state.avatar.controls.update();
    state.avatar.renderer.render(state.avatar.scene, state.avatar.camera);
    requestAnimationFrame(animateAvatar);
}

function revealAvatar() {
    const loading = document.getElementById('avatar-loading');
    const hint = document.getElementById('avatar-hint');
    const summary = document.getElementById('avatar-summary');
    if (loading) loading.classList.add('hidden');
    if (hint) hint.hidden = false;
    if (summary) summary.hidden = false;
}
