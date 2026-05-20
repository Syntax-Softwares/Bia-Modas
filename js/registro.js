(function() {
    if (isLoggedIn()) {
        window.location.href = './index.html';
        return;
    }

    let currentStep = 1;
    const totalSteps = 4;

    const telefoneInput = document.getElementById('reg-telefone');
    const cepInput = document.getElementById('reg-cep');
    const senhaInput = document.getElementById('reg-senha');

    // Máscaras
    telefoneInput.addEventListener('input', function() {
        this.value = maskPhone(this.value);
    });
    cepInput.addEventListener('input', function() {
        this.value = maskCEP(this.value);
    });

    // Força da senha
    senhaInput.addEventListener('input', function() {
        const strength = checkPasswordStrength(this.value);
        const container = document.getElementById('password-strength');
        const label = document.getElementById('password-strength-label');
        const segments = container.querySelectorAll('.password-strength-segment');

        if (this.value.length > 0) {
            container.style.display = 'block';
            let filled = 0;
            if (strength.level === 'fraca') filled = 1;
            else if (strength.level === 'media') filled = 2;
            else filled = 4;

            segments.forEach((seg, i) => {
                seg.style.background = i < filled ? strength.color : 'var(--color-light-gray)';
            });
            label.textContent = 'Força: ' + strength.label;
            label.style.color = strength.color;
        } else {
            container.style.display = 'none';
        }
    });

    // CEP auto-complete (ViaCEP)
    cepInput.addEventListener('blur', function() {
        autoCompleteCEP(this.value, {
            logradouro: 'reg-endereco',
            bairro: 'reg-bairro',
            localidade: 'reg-cidade',
            uf: 'reg-estado'
        }).then(ok => { if (ok) document.getElementById('reg-numero').focus(); });
    });

    window.handleNextClick = function() {
        if (currentStep === totalSteps) {
            document.getElementById('registro-form').dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
            return;
        }
        changeStep(1);
    };

    window.changeStep = function(dir) {
        if (dir === 1 && !validateStep(currentStep)) return;

        const newStep = currentStep + dir;
        if (newStep < 1 || newStep > totalSteps) return;

        document.querySelectorAll('.wizard-step-indicator').forEach(el => {
            const step = parseInt(el.dataset.step);
            el.classList.remove('active', 'completed');
            if (step === newStep) el.classList.add('active');
            else if (step < newStep) el.classList.add('completed');
        });

        document.querySelectorAll('.wizard-step').forEach(el => {
            el.classList.remove('active', 'prev');
            if (parseInt(el.dataset.step) === newStep) {
                el.classList.add('active');
                if (dir === -1) el.classList.add('prev');
            }
        });

        currentStep = newStep;
        updateNavButtons();
    };

    function updateNavButtons() {
        const prevBtn = document.getElementById('btn-prev');
        const nextBtn = document.getElementById('btn-next');

        prevBtn.style.visibility = currentStep === 1 ? 'hidden' : 'visible';

        if (currentStep === totalSteps) {
            nextBtn.innerHTML = '<i class="bi bi-check-lg"></i> Finalizar Cadastro';
        } else {
            nextBtn.innerHTML = 'Próximo <i class="bi bi-arrow-right"></i>';
        }
    }

    function validateStep(step) {
        let valid = true;

        if (step === 1) {
            const nome = document.getElementById('reg-nome');
            const email = document.getElementById('reg-email');
            const telefone = document.getElementById('reg-telefone');
            const senha = document.getElementById('reg-senha');
            const senhaConfirmar = document.getElementById('reg-senha-confirmar');

            if (nome.value.trim().length < 3) {
                nome.classList.add('is-invalid'); valid = false;
            } else { nome.classList.remove('is-invalid'); nome.classList.add('is-valid'); }

            if (!validateEmail(email.value.trim())) {
                email.classList.add('is-invalid');
                document.getElementById('reg-email-error').textContent = 'Digite um e-mail válido.';
                valid = false;
            } else if (findUserByEmail(email.value.trim())) {
                email.classList.add('is-invalid');
                document.getElementById('reg-email-error').textContent = 'Este e-mail já está cadastrado.';
                valid = false;
            } else { email.classList.remove('is-invalid'); email.classList.add('is-valid'); }

            if (telefone.value.replace(/\D/g, '').length < 10) {
                telefone.classList.add('is-invalid'); valid = false;
            } else { telefone.classList.remove('is-invalid'); telefone.classList.add('is-valid'); }

            if (senha.value.length < 6) {
                senha.classList.add('is-invalid'); valid = false;
            } else { senha.classList.remove('is-invalid'); senha.classList.add('is-valid'); }

            if (senha.value !== senhaConfirmar.value || senhaConfirmar.value === '') {
                senhaConfirmar.classList.add('is-invalid');
                document.getElementById('reg-senha-match-error').style.display = 'block';
                valid = false;
            } else { senhaConfirmar.classList.remove('is-invalid'); senhaConfirmar.classList.add('is-valid');
                document.getElementById('reg-senha-match-error').style.display = 'none'; }
        }

        return valid;
    }

    // Finalizar cadastro
    document.getElementById('registro-form').addEventListener('submit', function(e) {
        e.preventDefault();
        if (!validateStep(currentStep)) return;

        const cores = [];
        document.querySelectorAll('input[name="cores"]:checked').forEach(cb => cores.push(cb.value));

        const estilos = [];
        document.querySelectorAll('input[name="estilos"]:checked').forEach(cb => estilos.push(cb.value));

        const userData = {
            nome: document.getElementById('reg-nome').value.trim(),
            email: document.getElementById('reg-email').value.trim(),
            telefone: document.getElementById('reg-telefone').value,
            senha: document.getElementById('reg-senha').value,
            endereco: {
                cep: document.getElementById('reg-cep').value,
                logradouro: document.getElementById('reg-endereco').value,
                numero: document.getElementById('reg-numero').value,
                bairro: document.getElementById('reg-bairro').value,
                cidade: document.getElementById('reg-cidade').value,
                estado: document.getElementById('reg-estado').value
            },
            medidas: {
                busto: document.getElementById('reg-busto').value,
                cintura: document.getElementById('reg-cintura').value,
                quadril: document.getElementById('reg-quadril').value,
                altura: document.getElementById('reg-altura').value,
                blusa: document.getElementById('reg-blusa').value,
                calca: document.getElementById('reg-calca').value,
                vestido: document.getElementById('reg-vestido').value
            },
            preferencias: {
                cores: cores,
                estilos: estilos,
                tipoCorpo: document.getElementById('reg-tipo-corpo').value,
                newsletter: document.getElementById('reg-newsletter').checked
            }
        };

        const result = registerUser(userData);
        if (result.success) {
            showToast(result.message);
            setTimeout(() => window.location.href = './index.html', 1000);
        } else {
            showToast(result.message);
        }
    });
})();
