/**
 * Bia Modas - Sistema de Autenticação (Client-Side)
 * =================================================
 * Armazena dados no localStorage. Não há backend.
 * A senha é ofuscada com Base64 + salt — NÃO é criptografia segura.
 * Serve apenas para demonstração/UX local.
 */

const USERS_KEY = 'bia_modas_users';
const SESSION_KEY = 'bia_modas_session';

// --- Utilitários ---

function generateId() {
    return Date.now().toString(36) + Math.random().toString(36).substr(2, 9);
}

function simpleHash(str) {
    // Ofuscação simples (NÃO é segurança real)
    const salt = 'bia-modas-2025';
    return btoa(salt + str + salt);
}

function getUsers() {
    return JSON.parse(localStorage.getItem(USERS_KEY)) || [];
}

function saveUsers(users) {
    localStorage.setItem(USERS_KEY, JSON.stringify(users));
}

function getSession() {
    return JSON.parse(localStorage.getItem(SESSION_KEY)) || null;
}

function setSession(session) {
    if (session) {
        localStorage.setItem(SESSION_KEY, JSON.stringify(session));
    } else {
        localStorage.removeItem(SESSION_KEY);
    }
}

// --- API Pública ---

function isLoggedIn() {
    return getSession() !== null;
}

function getCurrentUser() {
    const session = getSession();
    if (!session) return null;
    const users = getUsers();
    return users.find(u => u.id === session.userId) || null;
}

function findUserByEmail(email) {
    const users = getUsers();
    return users.find(u => u.email.toLowerCase() === email.toLowerCase()) || null;
}

function registerUser(userData) {
    const users = getUsers();

    // Validações básicas
    if (findUserByEmail(userData.email)) {
        return { success: false, message: 'Este e-mail já está cadastrado.' };
    }
    if (!userData.nome || !userData.email || !userData.senha) {
        return { success: false, message: 'Preencha todos os campos obrigatórios.' };
    }
    if (userData.senha.length < 6) {
        return { success: false, message: 'A senha deve ter no mínimo 6 caracteres.' };
    }

    const newUser = {
        id: generateId(),
        nome: userData.nome.trim(),
        email: userData.email.trim().toLowerCase(),
        telefone: userData.telefone || '',
        senha: simpleHash(userData.senha),
        endereco: {
            cep: userData.endereco?.cep || '',
            logradouro: userData.endereco?.logradouro || '',
            numero: userData.endereco?.numero || '',
            bairro: userData.endereco?.bairro || '',
            cidade: userData.endereco?.cidade || '',
            estado: userData.endereco?.estado || ''
        },
        medidas: {
            busto: userData.medidas?.busto || '',
            cintura: userData.medidas?.cintura || '',
            quadril: userData.medidas?.quadril || '',
            altura: userData.medidas?.altura || '',
            blusa: userData.medidas?.blusa || '',
            calca: userData.medidas?.calca || '',
            vestido: userData.medidas?.vestido || ''
        },
        preferencias: {
            cores: userData.preferencias?.cores || [],
            estilos: userData.preferencias?.estilos || [],
            tipoCorpo: userData.preferencias?.tipoCorpo || '',
            newsletter: userData.preferencias?.newsletter || false
        },
        criadoEm: new Date().toISOString()
    };

    users.push(newUser);
    saveUsers(users);

    // Loga automaticamente
    setSession({
        userId: newUser.id,
        email: newUser.email,
        nome: newUser.nome,
        logadoEm: new Date().toISOString()
    });

    return { success: true, message: 'Conta criada com sucesso!', user: newUser };
}

function loginUser(email, senha) {
    const user = findUserByEmail(email);
    if (!user) {
        return { success: false, message: 'E-mail ou senha incorretos.' };
    }
    if (user.senha !== simpleHash(senha)) {
        return { success: false, message: 'E-mail ou senha incorretos.' };
    }

    setSession({
        userId: user.id,
        email: user.email,
        nome: user.nome,
        logadoEm: new Date().toISOString()
    });

    return { success: true, message: `Bem-vinda, ${user.nome.split(' ')[0]}!`, user };
}

function logoutUser() {
    setSession(null);
    return { success: true, message: 'Você saiu da sua conta.' };
}

function updateCurrentUser(updates) {
    const session = getSession();
    if (!session) return { success: false, message: 'Nenhum usuário logado.' };

    const users = getUsers();
    const idx = users.findIndex(u => u.id === session.userId);
    if (idx === -1) return { success: false, message: 'Usuário não encontrado.' };

    // Se estiver alterando email, verificar duplicidade
    if (updates.email && updates.email !== users[idx].email) {
        if (findUserByEmail(updates.email)) {
            return { success: false, message: 'Este e-mail já está em uso.' };
        }
    }

    // Atualiza campos permitidos
    const allowed = ['nome', 'email', 'telefone', 'endereco', 'medidas', 'preferencias'];
    allowed.forEach(key => {
        if (updates[key] !== undefined) {
            if (typeof updates[key] === 'object' && !Array.isArray(updates[key])) {
                users[idx][key] = { ...users[idx][key], ...updates[key] };
            } else {
                users[idx][key] = updates[key];
            }
        }
    });

    // Se mudou senha
    if (updates.senha && updates.senha.length >= 6) {
        users[idx].senha = simpleHash(updates.senha);
    }

    // Atualiza sessão se nome ou email mudou
    if (updates.nome || updates.email) {
        session.nome = users[idx].nome;
        session.email = users[idx].email;
        setSession(session);
    }

    saveUsers(users);
    return { success: true, message: 'Dados atualizados com sucesso!', user: users[idx] };
}

// --- Máscaras e Helpers de Validação ---

function maskPhone(value) {
    return value
        .replace(/\D/g, '')
        .replace(/(\d{2})(\d)/, '($1) $2')
        .replace(/(\d{5})(\d)/, '$1-$2')
        .replace(/(-\d{4})\d+?$/, '$1');
}

function maskCEP(value) {
    return value
        .replace(/\D/g, '')
        .replace(/(\d{5})(\d)/, '$1-$2')
        .replace(/(-\d{3})\d+?$/, '$1');
}

function validateEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function checkPasswordStrength(password) {
    let score = 0;
    if (password.length >= 6) score++;
    if (password.length >= 8) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/[0-9]/.test(password)) score++;
    if (/[^A-Za-z0-9]/.test(password)) score++;

    if (score <= 2) return { level: 'fraca', label: 'Fraca', color: '#dc3545' };
    if (score <= 4) return { level: 'media', label: 'Média', color: '#ffc107' };
    return { level: 'forte', label: 'Forte', color: '#28a745' };
}

// --- UI: Header Dropdown ---

function initAuthHeader() {
    const container = document.querySelector('.header-actions');
    if (!container) return;

    const avatarLink = container.querySelector('a[href="./usuario.html"]');
    if (!avatarLink) return;

    const session = getSession();

    if (session) {
        // Usuário logado — mostrar inicial
        const initial = session.nome ? session.nome.charAt(0).toUpperCase() : 'U';
        avatarLink.innerHTML = `<div class="auth-avatar-logged">${initial}</div>`;
        avatarLink.setAttribute('aria-label', `Minha conta: ${session.nome}`);
        avatarLink.href = '#';
        avatarLink.onclick = function(e) {
            e.preventDefault();
            toggleAuthDropdown();
        };
    } else {
        // Deslogado
        avatarLink.innerHTML = `<i class="bi bi-person"></i>`;
        avatarLink.setAttribute('aria-label', 'Entrar ou criar conta');
        avatarLink.href = '#';
        avatarLink.onclick = function(e) {
            e.preventDefault();
            toggleAuthDropdown();
        };
    }
}

function toggleAuthDropdown() {
    let dropdown = document.getElementById('auth-dropdown');
    if (dropdown) {
        dropdown.classList.toggle('show');
        return;
    }

    const session = getSession();
    dropdown = document.createElement('div');
    dropdown.id = 'auth-dropdown';
    dropdown.className = 'auth-dropdown';

    if (session) {
        dropdown.innerHTML = `
            <div class="auth-dropdown-header">
                <div class="auth-dropdown-avatar">${session.nome.charAt(0).toUpperCase()}</div>
                <div class="auth-dropdown-info">
                    <strong>Olá, ${session.nome.split(' ')[0]}</strong>
                    <span>${session.email}</span>
                </div>
            </div>
            <div class="auth-dropdown-divider"></div>
            <a href="./usuario.html" class="auth-dropdown-item"><i class="bi bi-person"></i> Minha Conta</a>
            <a href="#" class="auth-dropdown-item" onclick="handleLogout(event)"><i class="bi bi-box-arrow-right"></i> Sair</a>
        `;
    } else {
        dropdown.innerHTML = `
            <div class="auth-dropdown-header" style="padding: 20px; text-align: center;">
                <div style="font-size: 2rem; color: var(--color-light-gray); margin-bottom: 8px;"><i class="bi bi-person-circle"></i></div>
                <strong style="display: block; margin-bottom: 4px;">Bem-vinda!</strong>
                <span style="font-size: 0.85rem; color: var(--color-gray);">Entre para uma experiência personalizada</span>
            </div>
            <div class="auth-dropdown-divider"></div>
            <a href="./login.html" class="auth-dropdown-item"><i class="bi bi-box-arrow-in-right"></i> Entrar</a>
            <a href="./registro.html" class="auth-dropdown-item"><i class="bi bi-person-plus"></i> Criar Conta</a>
        `;
    }

    const avatarLink = document.querySelector('.header-actions a[aria-label*="conta"], .header-actions a[aria-label*="Entrar"]');
    if (avatarLink) {
        avatarLink.style.position = 'relative';
        avatarLink.appendChild(dropdown);
    }

    // Fechar ao clicar fora
    setTimeout(() => {
        document.addEventListener('click', function closeDropdown(e) {
            if (!dropdown.contains(e.target) && !avatarLink.contains(e.target)) {
                dropdown.remove();
                document.removeEventListener('click', closeDropdown);
            }
        });
    }, 10);
}

function handleLogout(e) {
    e.preventDefault();
    const result = logoutUser();
    showToast(result.message);
    setTimeout(() => {
        window.location.href = './index.html';
    }, 600);
}

// --- Proteção de rotas ---

function requireAuth() {
    if (!isLoggedIn()) {
        window.location.href = './login.html?redirect=' + encodeURIComponent(window.location.pathname.split('/').pop());
    }
}

// --- Inicialização ---

document.addEventListener('DOMContentLoaded', () => {
    initAuthHeader();
});
