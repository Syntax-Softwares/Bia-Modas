// ============================================================
// Provador Virtual — fluxo de captura (rosto -> corpo -> avatar 3D)
//
// Integrado ao MVC10: o produto vem do banco (JSON renderizado pela view),
// o tamanho selecionado e validado no servidor e o carrinho e o real.
// As fotos capturadas permanecem apenas neste navegador.
// ============================================================

const THREE_CDN = 'https://cdn.jsdelivr.net/npm/three@0.128.0/build/three.min.js';
const ORBIT_CDN = 'https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js';
const GLTF_CDN = 'https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/GLTFLoader.js';
const AVATAR_TARGET_HEIGHT = 4.5;
const FAKE_AVATAR_DELAY_MS = 1200;

function lerDadosProvador() {
    const elemento = document.getElementById('provador-dados');

    if (!elemento) {
        return {};
    }

    try {
        return JSON.parse(elemento.textContent || '{}');
    } catch (erro) {
        return {};
    }
}

const provadorConfig = lerDadosProvador();

const state = {
    step: 1,
    product: {
        produtoId: provadorConfig.produtoId || null,
        nome: provadorConfig.nome || 'Produto',
        preco: provadorConfig.preco || '',
        imagem: provadorConfig.imagem || '/img/logoBiaModas.png',
        produtoUrl: provadorConfig.produtoUrl || '/',
        carrinhoUrl: provadorConfig.carrinhoUrl || '/carrinho',
        tamanhoId: provadorConfig.tamanhoId || null,
        tamanhoNome: provadorConfig.tamanhoNome || null,
        modelo: provadorConfig.modelo || '/model.glb'
    },
    photos: { face: null, body: null },
    streams: { face: null, body: null },
    avatar: null,
    enviando: false
};

// ---------------------------------------------------------
// Inicialização
// ---------------------------------------------------------

document.addEventListener('DOMContentLoaded', () => {
    renderProductHeader();
    updateStepHeader(1);

    const next = document.getElementById('btn-next');
    const prev = document.getElementById('btn-prev');

    if (next) next.addEventListener('click', handleNext);
    if (prev) prev.addEventListener('click', handlePrev);

    ligarCaptura('face');
    ligarCaptura('body');

    ['face', 'body'].forEach((tipo) => {
        const pular = document.getElementById(`btn-skip-${tipo}`);

        if (pular) {
            pular.addEventListener('click', () => goToStep(4));
        }
    });

    window.addEventListener('beforeunload', stopAllStreams);
    window.addEventListener('pagehide', stopAllStreams);

    updateNav();
});

function ligarCaptura(tipo) {
    const capturar = document.getElementById(`btn-capture-${tipo}`);
    const refazer = document.getElementById(`btn-retake-${tipo}`);

    if (capturar) capturar.addEventListener('click', () => capturePhoto(tipo));
    if (refazer) refazer.addEventListener('click', () => retakePhoto(tipo));
}

function definirTexto(id, texto) {
    const elemento = document.getElementById(id);
    if (elemento) elemento.textContent = texto;
}

function definirImagem(id, src, alt) {
    const imagem = document.getElementById(id);

    if (imagem) {
        imagem.src = src;
        imagem.alt = alt;
    }
}

function renderProductHeader() {
    const produto = state.product;

    definirTexto('provador-product-name', produto.nome);
    definirTexto('provador-product-price', produto.preco);
    definirTexto('avatar-summary-product', produto.nome);
    definirTexto('avatar-summary-price', produto.preco);
    definirImagem('provador-product-img', produto.imagem, produto.nome);
    definirImagem('avatar-summary-image', produto.imagem, produto.nome);
}

const STEP_CONFIG = {
    1: { eyebrow: 'Bem-vinda', title: 'Experimente antes de levar.', progress: 25 },
    2: { eyebrow: 'Passo 02', title: 'Vamos capturar seu rosto.', progress: 50 },
    3: { eyebrow: 'Passo 03', title: 'Agora, sua silhueta.', progress: 75 },
    4: { eyebrow: 'Pronto', title: 'Seu avatar está pronto.', progress: 100 }
};

function updateStepHeader(step) {
    const config = STEP_CONFIG[step];

    if (!config) return;

    definirTexto('step-eyebrow', config.eyebrow);
    definirTexto('step-title', config.title);
    definirTexto('step-counter-current', String(step).padStart(2, '0'));

    const preenchimento = document.getElementById('progress-fill');

    if (preenchimento) {
        preenchimento.style.right = `${100 - config.progress}%`;
    }
}

// ---------------------------------------------------------
// Navegação entre steps
// ---------------------------------------------------------

function handleNext() {
    if (state.step === 1) {
        goToStep(2);
    } else if (state.step === 2) {
        if (state.photos.face) goToStep(3);
    } else if (state.step === 3) {
        if (state.photos.body) goToStep(4);
    } else if (state.step === 4) {
        finalizar();
    }
}

function handlePrev() {
    if (state.step === 1) return;
    goToStep(state.step - 1);
}

function goToStep(target) {
    const anterior = state.step;
    state.step = target;

    document.querySelectorAll('.wizard-step').forEach((elemento) => {
        elemento.classList.toggle('active', Number(elemento.dataset.step) === target);
    });

    updateStepHeader(target);
    onLeaveStep(anterior);
    onEnterStep(target);
    updateNav();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function onEnterStep(step) {
    if (step === 2 && !state.photos.face) {
        startCamera('face');
    } else if (step === 3 && !state.photos.body) {
        startCamera('body');
    } else if (step === 4) {
        generateAvatar();
    }
}

function onLeaveStep(step) {
    if (step === 2) stopCamera('face');
    if (step === 3) stopCamera('body');

    if (step === 4 && state.avatar) {
        // Pausa o loop de renderizacao enquanto o canvas estiver oculto.
        state.avatar.running = false;
    }
}

function updateNav() {
    const prev = document.getElementById('btn-prev');
    const next = document.getElementById('btn-next');

    if (!prev || !next) return;

    prev.style.visibility = state.step === 1 ? 'hidden' : 'visible';

    let rotulo = 'Começar';
    let icone = 'icon-arrow-right';
    let desabilitado = false;

    if (state.step === 2) {
        rotulo = 'Continuar';
        desabilitado = !state.photos.face;
    } else if (state.step === 3) {
        rotulo = 'Gerar avatar';
        icone = 'icon-sparkles';
        desabilitado = !state.photos.body;
    } else if (state.step === 4) {
        if (state.product.tamanhoId) {
            rotulo = 'Adicionar ao carrinho';
            icone = 'icon-shopping-bag';
        } else {
            rotulo = 'Voltar ao produto';
        }
    }

    next.innerHTML = `${rotulo} <i class="${icone}"></i>`;
    next.disabled = desabilitado || state.enviando;
}

// ---------------------------------------------------------
// Finalização: carrinho real (tamanho validado no servidor)
// ---------------------------------------------------------

function finalizar() {
    if (state.enviando) return;

    if (!state.product.tamanhoId) {
        window.location.href = state.product.produtoUrl;
        return;
    }

    const api = window.BiaModas;
    const botao = document.getElementById('btn-next');

    if (!api || typeof api.postar !== 'function') {
        window.location.href = state.product.produtoUrl;
        return;
    }

    state.enviando = true;
    if (botao) botao.disabled = true;

    api.postar('/carrinho/adicionar', {
        produto_tamanho_id: state.product.tamanhoId,
        quantidade: 1
    }).then(() => {
        window.location.href = state.product.carrinhoUrl;
    }).catch((erro) => {
        state.enviando = false;
        if (botao) botao.disabled = false;

        if (typeof api.toast === 'function') {
            api.toast(erro.message, true);
        }
    });
}

// ---------------------------------------------------------
// Câmera (getUserMedia)
// ---------------------------------------------------------

function startCamera(tipo) {
    const video = document.getElementById(`video-${tipo}`);
    const erro = document.getElementById(`error-${tipo}`);

    if (!video || state.streams[tipo]) return;

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        showCameraError(tipo);
        return;
    }

    navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'user', width: { ideal: 1280 }, height: { ideal: 960 } },
        audio: false
    }).then((stream) => {
        state.streams[tipo] = stream;
        video.srcObject = stream;

        if (erro) erro.hidden = true;
    }).catch((excecao) => {
        console.warn(`Câmera ${tipo} indisponível:`, excecao);
        showCameraError(tipo);
    });
}

function stopCamera(tipo) {
    const stream = state.streams[tipo];

    if (!stream) return;

    stream.getTracks().forEach((track) => track.stop());
    state.streams[tipo] = null;

    const video = document.getElementById(`video-${tipo}`);

    if (video) video.srcObject = null;
}

function stopAllStreams() {
    stopCamera('face');
    stopCamera('body');
}

function showCameraError(tipo) {
    const erro = document.getElementById(`error-${tipo}`);
    const capturar = document.getElementById(`btn-capture-${tipo}`);

    if (erro) erro.hidden = false;
    if (capturar) capturar.disabled = true;
}

// ---------------------------------------------------------
// Captura de fotos
// ---------------------------------------------------------

function capturePhoto(tipo) {
    const video = document.getElementById(`video-${tipo}`);
    const preview = document.getElementById(`preview-${tipo}`);
    const flash = document.getElementById(`flash-${tipo}`);
    const palco = video ? video.closest('.capture-stage') : null;
    const capturar = document.getElementById(`btn-capture-${tipo}`);
    const refazer = document.getElementById(`btn-retake-${tipo}`);

    if (!video || !preview || !video.videoWidth) return;

    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;

    const contexto = canvas.getContext('2d');

    // Espelha para manter a mesma leitura do vídeo (estilo selfie).
    contexto.translate(canvas.width, 0);
    contexto.scale(-1, 1);
    contexto.drawImage(video, 0, 0, canvas.width, canvas.height);

    const dataUrl = canvas.toDataURL('image/jpeg', 0.85);

    state.photos[tipo] = dataUrl;
    preview.src = dataUrl;

    if (flash) {
        flash.classList.remove('flash');
        void flash.offsetWidth;
        flash.classList.add('flash');
    }

    window.setTimeout(() => {
        preview.hidden = false;
        video.style.opacity = '0';

        if (palco) palco.classList.add('captured');
        if (capturar) capturar.hidden = true;
        if (refazer) refazer.hidden = false;

        stopCamera(tipo);
        updateNav();
    }, 180);
}

function retakePhoto(tipo) {
    const video = document.getElementById(`video-${tipo}`);
    const preview = document.getElementById(`preview-${tipo}`);
    const palco = video ? video.closest('.capture-stage') : null;
    const capturar = document.getElementById(`btn-capture-${tipo}`);
    const refazer = document.getElementById(`btn-retake-${tipo}`);

    state.photos[tipo] = null;

    if (preview) {
        preview.hidden = true;
        preview.src = '';
    }

    if (video) video.style.opacity = '';
    if (palco) palco.classList.remove('captured');
    if (capturar) capturar.hidden = false;
    if (refazer) refazer.hidden = true;

    startCamera(tipo);
    updateNav();
}

// ---------------------------------------------------------
// Avatar 3D
// ---------------------------------------------------------

function generateAvatar() {
    const carregando = document.getElementById('avatar-loading');
    const dica = document.getElementById('avatar-hint');
    const resumo = document.getElementById('avatar-summary');

    if (carregando) carregando.classList.remove('hidden');
    if (dica) dica.hidden = true;
    if (resumo) resumo.hidden = true;

    definirTexto('avatar-summary-product', state.product.nome);
    definirTexto('avatar-summary-price', state.product.preco);

    const threePromise = loadThreeJs();
    const atraso = new Promise((resolve) => window.setTimeout(resolve, FAKE_AVATAR_DELAY_MS));

    Promise.all([threePromise, atraso]).then(() => {
        if (!state.avatar) {
            state.avatar = initAvatarScene();
            return;
        }

        // Reentrada no passo 4: retoma o loop e reexibe o modelo ja carregado.
        state.avatar.running = true;
        animateAvatar();

        if (state.avatar.resizeHandler) {
            state.avatar.resizeHandler();
        }

        if (state.avatar.avatar) {
            revealAvatar();
        }
    }).catch((erro) => {
        console.error('Falha ao carregar o avatar 3D:', erro);
        definirTexto('avatar-loading-text', 'Não foi possível carregar o avatar');
        definirTexto('avatar-loading-sub', 'Verifique sua conexão e tente novamente.');
    });
}

function loadThreeJs() {
    if (window.THREE && window.THREE.OrbitControls && window.THREE.GLTFLoader) {
        return Promise.resolve();
    }

    return loadScript(THREE_CDN)
        .then(() => loadScript(ORBIT_CDN))
        .then(() => loadScript(GLTF_CDN));
}

function loadScript(src) {
    return new Promise((resolve, reject) => {
        const existente = document.querySelector(`script[src="${src}"]`);

        if (existente) {
            if (existente.dataset.loaded === '1') {
                resolve();
                return;
            }

            existente.addEventListener('load', () => resolve(), { once: true });
            existente.addEventListener('error', () => reject(new Error('Falha ao carregar ' + src)), { once: true });
            return;
        }

        const script = document.createElement('script');
        script.src = src;
        script.onload = () => {
            script.dataset.loaded = '1';
            resolve();
        };
        script.onerror = () => reject(new Error('Falha ao carregar ' + src));
        document.head.appendChild(script);
    });
}

function initAvatarScene() {
    const canvas = document.getElementById('avatar-canvas');

    if (!canvas) return null;

    const largura = canvas.clientWidth || canvas.parentElement.clientWidth;
    const altura = canvas.clientHeight || canvas.parentElement.clientHeight;

    const scene = new THREE.Scene();
    scene.background = null;

    const camera = new THREE.PerspectiveCamera(34, largura / altura, 0.1, 100);
    camera.position.set(0, 2.55, 6.4);

    const renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.setSize(largura, altura, false);
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

    const chao = new THREE.Mesh(
        new THREE.CircleGeometry(3, 48),
        new THREE.ShadowMaterial({ opacity: 0.18 })
    );
    chao.rotation.x = -Math.PI / 2;
    chao.receiveShadow = true;
    scene.add(chao);

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
    controls.addEventListener('start', () => {
        controls.autoRotate = false;
    });

    const cenaEstado = {
        scene,
        camera,
        renderer,
        controls,
        avatar: null,
        running: true,
        resizeHandler: null
    };

    const loader = new THREE.GLTFLoader();
    loader.load(
        state.product.modelo,
        (gltf) => {
            const model = gltf.scene;
            const box = new THREE.Box3().setFromObject(model);
            const size = box.getSize(new THREE.Vector3());
            const center = box.getCenter(new THREE.Vector3());
            const escala = size.y > 0 ? AVATAR_TARGET_HEIGHT / size.y : 1;

            model.scale.setScalar(escala);
            model.position.x = -center.x * escala;
            model.position.y = -box.min.y * escala;
            model.position.z = -center.z * escala;

            model.traverse((no) => {
                if (no.isMesh) {
                    no.castShadow = true;
                    no.receiveShadow = true;
                }
            });

            scene.add(model);
            cenaEstado.avatar = model;
            revealAvatar();
        },
        undefined,
        (erro) => {
            console.error('Falha ao carregar o modelo 3D:', erro);
            const carregando = document.getElementById('avatar-loading');

            if (carregando) {
                carregando.innerHTML = '<strong>Não foi possível carregar o modelo 3D</strong>';
            }
        }
    );

    function resize() {
        const larguraAtual = canvas.clientWidth || canvas.parentElement.clientWidth;
        const alturaAtual = canvas.clientHeight || canvas.parentElement.clientHeight;

        if (larguraAtual === 0 || alturaAtual === 0) return;

        camera.aspect = larguraAtual / alturaAtual;
        camera.updateProjectionMatrix();
        renderer.setSize(larguraAtual, alturaAtual, false);
    }

    cenaEstado.resizeHandler = resize;
    window.addEventListener('resize', resize);

    state.avatar = cenaEstado;
    animateAvatar();

    return cenaEstado;
}

function animateAvatar() {
    if (!state.avatar || !state.avatar.running) return;

    state.avatar.controls.update();
    state.avatar.renderer.render(state.avatar.scene, state.avatar.camera);
    requestAnimationFrame(animateAvatar);
}

function revealAvatar() {
    const carregando = document.getElementById('avatar-loading');
    const dica = document.getElementById('avatar-hint');
    const resumo = document.getElementById('avatar-summary');

    if (carregando) carregando.classList.add('hidden');
    if (dica) dica.hidden = false;
    if (resumo) resumo.hidden = false;
}
