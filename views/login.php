<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Entre na sua conta Bia Modas." />
    <title>Entrar | Bia Modas</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lucide-static@latest/font/lucide.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= asset('/css/base.css') ?>">
    <link rel="stylesheet" href="<?= asset('/css/layout.css') ?>">
    <link rel="stylesheet" href="<?= asset('/css/components.css') ?>">
    <link rel="stylesheet" href="<?= asset('/css/responsive.css') ?>">
    <link rel="stylesheet" href="<?= asset('/css/auth.css') ?>">
    <link rel="stylesheet" href="<?= asset('/css/carrinho.css') ?>">
</head>
<body class="auth-page">
    <?php $ocultarTopBar = true; require __DIR__ . '/partials/site_header.php'; ?>

    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-card-header">
                <h1>Bem-vinda de volta!</h1>
                <p>Entre com seus dados para continuar</p>
            </div>
            <div class="auth-card-body">
                <?php require __DIR__ . '/partials/alerts.php'; ?>

                <form class="auth-form" method="post" action="/login" novalidate>
                    <?= Auth::csrfField() ?>

                    <div class="form-group">
                        <label for="login-email">E-mail <span class="required">*</span></label>
                        <input
                            type="email"
                            class="form-control<?= isset($errors['email']) ? ' is-invalid' : '' ?>"
                            id="login-email"
                            name="email"
                            value="<?= e($old['email'] ?? '') ?>"
                            placeholder="seu@email.com"
                            autocomplete="email"
                            required
                        >
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback show"><?= e($errors['email']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="login-senha">Senha <span class="required">*</span></label>
                        <div class="password-field">
                            <input
                                type="password"
                                class="form-control<?= isset($errors['senha']) ? ' is-invalid' : '' ?>"
                                id="login-senha"
                                name="senha"
                                placeholder="Sua senha"
                                autocomplete="current-password"
                                required
                            >
                            <button type="button" class="password-toggle" onclick="togglePassword('login-senha', this)" aria-label="Mostrar senha">
                                <i class="icon-eye"></i>
                            </button>
                        </div>
                        <?php if (isset($errors['senha'])): ?>
                            <div class="invalid-feedback show"><?= e($errors['senha']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group" style="display: flex; justify-content: flex-end; align-items: center; font-size: 0.9rem;">
                        <a href="/recuperar-senha" style="color: var(--color-primary); text-decoration: none; font-weight: 500;">Esqueceu a senha?</a>
                    </div>

                    <button type="submit" class="btn-primary-solid" style="width: 100%; margin-top: 4px;">
                        <i class="icon-log-in me-2"></i>Entrar
                    </button>
                </form>

                <div class="auth-footer">
                    Ainda não tem uma conta? <a href="/registro">Cadastre-se</a>
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
    </script>
    <?php require __DIR__ . '/partials/scripts.php'; ?>
</body>
</html>
