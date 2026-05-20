(function() {
    requireAuth();

    const user = getCurrentUser();
    if (!user) return;

    // Header info
    document.getElementById('profile-name').textContent = user.nome;
    document.getElementById('profile-email').textContent = user.email;
    document.getElementById('profile-avatar').textContent = user.nome.charAt(0).toUpperCase();

    // Dados
    document.getElementById('pf-nome').value = user.nome || '';
    document.getElementById('pf-email').value = user.email || '';
    document.getElementById('pf-telefone').value = user.telefone || '';

    // Endereço
    document.getElementById('pf-cep').value = user.endereco?.cep || '';
    document.getElementById('pf-endereco').value = user.endereco?.logradouro || '';
    document.getElementById('pf-numero').value = user.endereco?.numero || '';
    document.getElementById('pf-bairro').value = user.endereco?.bairro || '';
    document.getElementById('pf-cidade').value = user.endereco?.cidade || '';
    document.getElementById('pf-estado').value = user.endereco?.estado || '';

    // Medidas
    document.getElementById('pf-busto').value = user.medidas?.busto || '';
    document.getElementById('pf-cintura').value = user.medidas?.cintura || '';
    document.getElementById('pf-quadril').value = user.medidas?.quadril || '';
    document.getElementById('pf-altura').value = user.medidas?.altura || '';
    document.getElementById('pf-blusa').value = user.medidas?.blusa || '';
    document.getElementById('pf-calca').value = user.medidas?.calca || '';
    document.getElementById('pf-vestido').value = user.medidas?.vestido || '';

    // Preferências
    (user.preferencias?.cores || []).forEach(c => {
        const cb = document.querySelector(`input[name="pf-cores"][value="${c}"]`);
        if (cb) cb.checked = true;
    });
    (user.preferencias?.estilos || []).forEach(e => {
        const cb = document.querySelector(`input[name="pf-estilos"][value="${e}"]`);
        if (cb) cb.checked = true;
    });
    document.getElementById('pf-tipo-corpo').value = user.preferencias?.tipoCorpo || '';
    document.getElementById('pf-newsletter').checked = user.preferencias?.newsletter || false;

    // Máscaras
    const telInput = document.getElementById('pf-telefone');
    telInput.addEventListener('input', function() { this.value = maskPhone(this.value); });
    document.getElementById('pf-cep').addEventListener('input', function() { this.value = maskCEP(this.value); });
    document.getElementById('pf-cep').addEventListener('blur', function() {
        autoCompleteCEP(this.value, {
            logradouro: 'pf-endereco',
            bairro: 'pf-bairro',
            localidade: 'pf-cidade',
            uf: 'pf-estado'
        });
    });
})();

function switchTab(tab) {
    document.querySelectorAll('.profile-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.profile-tab-content').forEach(t => t.classList.remove('active'));
    document.querySelector(`.profile-tab[onclick="switchTab('${tab}')"]`).classList.add('active');
    document.getElementById('tab-' + tab).classList.add('active');
}

function saveProfile(e, section) {
    e.preventDefault();
    let updates = {};

    if (section === 'dados') {
        updates.nome = document.getElementById('pf-nome').value.trim();
        updates.email = document.getElementById('pf-email').value.trim();
        updates.telefone = document.getElementById('pf-telefone').value;
    } else if (section === 'endereco') {
        updates.endereco = {
            cep: document.getElementById('pf-cep').value,
            logradouro: document.getElementById('pf-endereco').value,
            numero: document.getElementById('pf-numero').value,
            bairro: document.getElementById('pf-bairro').value,
            cidade: document.getElementById('pf-cidade').value,
            estado: document.getElementById('pf-estado').value
        };
    } else if (section === 'medidas') {
        updates.medidas = {
            busto: document.getElementById('pf-busto').value,
            cintura: document.getElementById('pf-cintura').value,
            quadril: document.getElementById('pf-quadril').value,
            altura: document.getElementById('pf-altura').value,
            blusa: document.getElementById('pf-blusa').value,
            calca: document.getElementById('pf-calca').value,
            vestido: document.getElementById('pf-vestido').value
        };
    } else if (section === 'preferencias') {
        const cores = [];
        document.querySelectorAll('input[name="pf-cores"]:checked').forEach(cb => cores.push(cb.value));
        const estilos = [];
        document.querySelectorAll('input[name="pf-estilos"]:checked').forEach(cb => estilos.push(cb.value));
        updates.preferencias = {
            cores: cores,
            estilos: estilos,
            tipoCorpo: document.getElementById('pf-tipo-corpo').value,
            newsletter: document.getElementById('pf-newsletter').checked
        };
    } else if (section === 'seguranca') {
        const senha = document.getElementById('pf-senha').value;
        const confirmar = document.getElementById('pf-senha-confirmar').value;
        if (senha !== confirmar) {
            showToast('As senhas não coincidem.');
            return false;
        }
        if (senha && senha.length < 6) {
            showToast('A senha deve ter no mínimo 6 caracteres.');
            return false;
        }
        if (senha) updates.senha = senha;
    }

    const result = updateCurrentUser(updates);
    if (result.success) {
        showToast(result.message);
        if (updates.nome) {
            document.getElementById('profile-name').textContent = result.user.nome;
            document.getElementById('profile-avatar').textContent = result.user.nome.charAt(0).toUpperCase();
        }
        if (updates.email) document.getElementById('profile-email').textContent = result.user.email;
    } else {
        showToast(result.message);
    }
    return false;
}
