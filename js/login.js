(function() {
    if (isLoggedIn()) {
        window.location.href = './index.html';
        return;
    }

    const form = document.getElementById('login-form');
    const emailInput = document.getElementById('login-email');
    const senhaInput = document.getElementById('login-senha');

    function validate() {
        let valid = true;

        if (!validateEmail(emailInput.value.trim())) {
            emailInput.classList.add('is-invalid');
            valid = false;
        } else {
            emailInput.classList.remove('is-invalid');
        }

        if (senhaInput.value.length < 1) {
            senhaInput.classList.add('is-invalid');
            valid = false;
        } else {
            senhaInput.classList.remove('is-invalid');
        }

        return valid;
    }

    emailInput.addEventListener('input', function() {
        if (this.value.trim()) validate();
    });
    senhaInput.addEventListener('input', function() {
        if (this.value) validate();
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        if (!validate()) return;

        const result = loginUser(emailInput.value.trim(), senhaInput.value);
        showToast(result.message);
        if (result.success) {
            const redirect = new URLSearchParams(window.location.search).get('redirect') || './index.html';
            setTimeout(() => window.location.href = redirect, 800);
        }
    });
})();
