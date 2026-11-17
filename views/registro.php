<?php
// Abre o wizard no passo que contem os erros devolvidos pelo servidor.
$passoInicial = 1;

if (isset($errors['endereco'])) {
    $passoInicial = 2;
}

if (isset($errors['medidas'])) {
    $passoInicial = 3;
}
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Crie sua conta na Bia Modas e tenha uma experiência personalizada." />
    <title>Criar Conta | Bia Modas</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lucide-static@latest/font/lucide.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/css/base.css">
    <link rel="stylesheet" href="/css/layout.css">
    <link rel="stylesheet" href="/css/components.css">
    <link rel="stylesheet" href="/css/responsive.css">
    <link rel="stylesheet" href="/css/auth.css">
    <link rel="stylesheet" href="/css/carrinho.css">
</head>
<body class="auth-page">
    <?php $ocultarTopBar = true; require __DIR__ . '/partials/site_header.php'; ?>

    <div class="auth-container">
        <div class="auth-card auth-card-wide">
            <div class="auth-card-header">
                <h1>Criar sua conta</h1>
                <p>Personalize sua experiência de compra na Bia Modas</p>
            </div>
            <div class="auth-card-body">
                <?php require __DIR__ . '/partials/alerts.php'; ?>

                <form class="auth-form" method="post" action="/registro" id="registro-form" novalidate>
                    <?= Auth::csrfField() ?>

                    <div class="wizard-progress" aria-label="Etapas do cadastro">
                        <div class="wizard-step-indicator<?= $passoInicial === 1 ? ' active' : '' ?><?= $passoInicial > 1 ? ' completed' : '' ?>" data-step="1">
                            <div class="wizard-step-dot">1</div>
                            <span class="wizard-step-label">Conta</span>
                        </div>
                        <div class="wizard-step-indicator<?= $passoInicial === 2 ? ' active' : '' ?><?= $passoInicial > 2 ? ' completed' : '' ?>" data-step="2">
                            <div class="wizard-step-dot">2</div>
                            <span class="wizard-step-label">Endereço</span>
                        </div>
                        <div class="wizard-step-indicator<?= $passoInicial === 3 ? ' active' : '' ?>" data-step="3">
                            <div class="wizard-step-dot">3</div>
                            <span class="wizard-step-label">Medidas</span>
                        </div>
                    </div>

                    <!-- Passo 1: dados da conta -->
                    <div class="wizard-step<?= $passoInicial === 1 ? ' active' : '' ?>" data-step="1">
                        <div class="row">
                            <div class="col-md-12 form-group">
                                <label for="reg-nome">Nome completo <span class="required">*</span></label>
                                <input
                                    type="text"
                                    class="form-control<?= isset($errors['nome']) ? ' is-invalid' : '' ?>"
                                    id="reg-nome"
                                    name="nome"
                                    value="<?= e($old['nome'] ?? '') ?>"
                                    placeholder="Maria Silva"
                                    autocomplete="name"
                                    maxlength="255"
                                    required
                                >
                                <?php if (isset($errors['nome'])): ?>
                                    <div class="invalid-feedback show"><?= e($errors['nome']) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6 form-group">
                                <label for="reg-email">E-mail <span class="required">*</span></label>
                                <input
                                    type="email"
                                    class="form-control<?= isset($errors['email']) ? ' is-invalid' : '' ?>"
                                    id="reg-email"
                                    name="email"
                                    value="<?= e($old['email'] ?? '') ?>"
                                    placeholder="maria@email.com"
                                    autocomplete="email"
                                    maxlength="255"
                                    required
                                >
                                <?php if (isset($errors['email'])): ?>
                                    <div class="invalid-feedback show"><?= e($errors['email']) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6 form-group">
                                <label for="reg-telefone">Telefone <span class="required">*</span></label>
                                <input
                                    type="tel"
                                    class="form-control<?= isset($errors['telefone']) ? ' is-invalid' : '' ?>"
                                    id="reg-telefone"
                                    name="telefone"
                                    value="<?= e($old['telefone'] ?? '') ?>"
                                    placeholder="(14) 99999-9999"
                                    autocomplete="tel"
                                    maxlength="20"
                                    required
                                >
                                <?php if (isset($errors['telefone'])): ?>
                                    <div class="invalid-feedback show"><?= e($errors['telefone']) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6 form-group">
                                <label for="reg-senha">Senha <span class="required">*</span></label>
                                <div class="password-field">
                                    <input
                                        type="password"
                                        class="form-control<?= isset($errors['senha']) ? ' is-invalid' : '' ?>"
                                        id="reg-senha"
                                        name="senha"
                                        placeholder="Mínimo 6 caracteres"
                                        autocomplete="new-password"
                                        minlength="6"
                                        required
                                    >
                                    <button type="button" class="password-toggle" onclick="togglePassword('reg-senha', this)" aria-label="Mostrar senha">
                                        <i class="icon-eye"></i>
                                    </button>
                                </div>
                                <?php if (isset($errors['senha'])): ?>
                                    <div class="invalid-feedback show"><?= e($errors['senha']) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6 form-group">
                                <label for="reg-senha-confirmar">Confirmar senha <span class="required">*</span></label>
                                <div class="password-field">
                                    <input
                                        type="password"
                                        class="form-control<?= isset($errors['senha_confirmar']) ? ' is-invalid' : '' ?>"
                                        id="reg-senha-confirmar"
                                        name="senha_confirmar"
                                        placeholder="Repita a senha"
                                        autocomplete="new-password"
                                        minlength="6"
                                        required
                                    >
                                    <button type="button" class="password-toggle" onclick="togglePassword('reg-senha-confirmar', this)" aria-label="Mostrar senha">
                                        <i class="icon-eye"></i>
                                    </button>
                                </div>
                                <?php if (isset($errors['senha_confirmar'])): ?>
                                    <div class="invalid-feedback show"><?= e($errors['senha_confirmar']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Passo 2: endereco -->
                    <div class="wizard-step<?= $passoInicial === 2 ? ' active' : '' ?>" data-step="2">
                        <p class="form-text mb-3">Opcional. Você também pode cadastrar endereços depois, no seu perfil.</p>

                        <?php if (isset($errors['endereco'])): ?>
                            <div class="alert alert-danger auth-alert"><?= e($errors['endereco']) ?></div>
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label for="reg-cep">CEP</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="reg-cep"
                                    name="cep"
                                    value="<?= e($old['cep'] ?? '') ?>"
                                    placeholder="17201-320"
                                    autocomplete="postal-code"
                                    maxlength="9"
                                >
                            </div>
                            <div class="col-md-8 form-group">
                                <label for="reg-logradouro">Endereço</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="reg-logradouro"
                                    name="logradouro"
                                    value="<?= e($old['logradouro'] ?? '') ?>"
                                    placeholder="Rua, Avenida..."
                                    autocomplete="address-line1"
                                >
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="reg-numero">Número</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="reg-numero"
                                    name="numero"
                                    value="<?= e($old['numero'] ?? '') ?>"
                                    placeholder="123"
                                    maxlength="10"
                                >
                            </div>
                            <div class="col-md-8 form-group">
                                <label for="reg-complemento">Complemento</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="reg-complemento"
                                    name="complemento"
                                    value="<?= e($old['complemento'] ?? '') ?>"
                                    placeholder="Apartamento, bloco..."
                                    maxlength="60"
                                >
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="reg-bairro">Bairro</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="reg-bairro"
                                    name="bairro"
                                    value="<?= e($old['bairro'] ?? '') ?>"
                                    placeholder="Centro"
                                >
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="reg-cidade">Cidade</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="reg-cidade"
                                    name="cidade"
                                    value="<?= e($old['cidade'] ?? '') ?>"
                                    placeholder="Jaú"
                                >
                            </div>
                            <div class="col-md-2 form-group">
                                <label for="reg-uf">UF</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="reg-uf"
                                    name="uf"
                                    value="<?= e($old['uf'] ?? '') ?>"
                                    placeholder="SP"
                                    maxlength="2"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Passo 3: medidas -->
                    <div class="wizard-step<?= $passoInicial === 3 ? ' active' : '' ?>" data-step="3">
                        <p class="form-text mb-3">Opcional. Ajuda a sugerir tamanhos com mais precisão.</p>

                        <?php if (isset($errors['medidas'])): ?>
                            <div class="alert alert-danger auth-alert"><?= e($errors['medidas']) ?></div>
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label for="reg-busto">Busto (cm)</label>
                                <input
                                    type="number"
                                    class="form-control"
                                    id="reg-busto"
                                    name="busto"
                                    value="<?= e($old['busto'] ?? '') ?>"
                                    placeholder="96"
                                    min="1"
                                    max="300"
                                    step="0.1"
                                >
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="reg-cintura">Cintura (cm)</label>
                                <input
                                    type="number"
                                    class="form-control"
                                    id="reg-cintura"
                                    name="cintura"
                                    value="<?= e($old['cintura'] ?? '') ?>"
                                    placeholder="78"
                                    min="1"
                                    max="300"
                                    step="0.1"
                                >
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="reg-quadril">Quadril (cm)</label>
                                <input
                                    type="number"
                                    class="form-control"
                                    id="reg-quadril"
                                    name="quadril"
                                    value="<?= e($old['quadril'] ?? '') ?>"
                                    placeholder="102"
                                    min="1"
                                    max="300"
                                    step="0.1"
                                >
                            </div>
                        </div>
                    </div>

                    <div class="wizard-nav">
                        <button type="button" class="btn btn-secondary" id="btn-prev" <?= $passoInicial === 1 ? 'style="visibility: hidden;"' : '' ?>>
                            <i class="icon-arrow-left"></i> Voltar
                        </button>
                        <button type="button" class="btn btn-primary" id="btn-next">
                            <span id="btn-next-label"><?= $passoInicial === 3 ? 'Criar conta' : 'Próximo' ?></span>
                            <i class="<?= $passoInicial === 3 ? 'icon-user-check' : 'icon-arrow-right' ?>" id="btn-next-icon"></i>
                        </button>
                    </div>
                </form>

                <div class="auth-footer">
                    Já tem uma conta? <a href="/login">Entrar</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(id, button) {
            const input = document.getElementById(id);
            const icon = button.querySelector('i');

            if (!input) return;

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('icon-eye');
                icon.classList.add('icon-eye-off');
                button.setAttribute('aria-label', 'Ocultar senha');
            } else {
                input.type = 'password';
                icon.classList.remove('icon-eye-off');
                icon.classList.add('icon-eye');
                button.setAttribute('aria-label', 'Mostrar senha');
            }
        }

        (function () {
            const form = document.getElementById('registro-form');
            if (!form) return;

            const steps = Array.from(form.querySelectorAll('.wizard-step'));
            const indicators = Array.from(document.querySelectorAll('.wizard-step-indicator'));
            const btnPrev = document.getElementById('btn-prev');
            const btnNext = document.getElementById('btn-next');
            const btnLabel = document.getElementById('btn-next-label');
            const btnIcon = document.getElementById('btn-next-icon');
            let current = Math.max(0, Math.min(steps.length - 1, <?= (int) $passoInicial ?> - 1));

            function render() {
                steps.forEach((step, index) => {
                    step.classList.toggle('active', index === current);
                });

                indicators.forEach((indicator, index) => {
                    indicator.classList.toggle('active', index === current);
                    indicator.classList.toggle('completed', index < current);
                });

                btnPrev.style.visibility = current === 0 ? 'hidden' : 'visible';

                const last = current === steps.length - 1;
                btnLabel.textContent = last ? 'Criar conta' : 'Próximo';
                btnIcon.className = last ? 'icon-user-check' : 'icon-arrow-right';
            }

            function validateStep(index) {
                const inputs = steps[index].querySelectorAll('input, select, textarea');
                for (const input of inputs) {
                    if (!input.checkValidity()) {
                        input.reportValidity();
                        return false;
                    }
                }
                return true;
            }

            function firstInvalidStep() {
                for (let index = 0; index < steps.length; index += 1) {
                    if (!validateStep(index)) {
                        current = index;
                        render();
                        return false;
                    }
                }
                return true;
            }

            btnPrev.addEventListener('click', () => {
                if (current > 0) {
                    current -= 1;
                    render();
                }
            });

            btnNext.addEventListener('click', () => {
                if (current < steps.length - 1) {
                    if (validateStep(current)) {
                        current += 1;
                        render();
                    }
                    return;
                }

                if (firstInvalidStep()) {
                    form.submit();
                }
            });

            render();
        })();
    </script>
    <?php require __DIR__ . '/partials/scripts.php'; ?>
</body>
</html>
