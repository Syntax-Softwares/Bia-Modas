<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Informe o código recebido e defina sua nova senha." />
    <title>Verificar código | Bia Modas</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lucide-static@latest/font/lucide.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= asset('/css/base.css') ?>">
    <link rel="stylesheet" href="<?= asset('/css/layout.css') ?>">
    <link rel="stylesheet" href="<?= asset('/css/components.css') ?>">
    <link rel="stylesheet" href="<?= asset('/css/auth.css') ?>">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-card-header">
                <a href="/" class="logo auth-logo-link">
                    <img src="/img/logoBiaModas.png" alt="Bia Modas">
                </a>
                <h1>Verifique seu e-mail</h1>
                <p>
                    Enviamos um código de 6 dígitos para
                    <strong><?= e($emailMascarado) ?></strong>.
                    Ele expira em 10 minutos.
                </p>
            </div>
            <div class="auth-card-body">
                <?php require __DIR__ . '/partials/alerts.php'; ?>

                <form class="auth-form" method="post" action="/recuperar-senha/verificar" novalidate>
                    <?= Auth::csrfField() ?>

                    <div class="form-group">
                        <label for="verificar-codigo">Código <span class="required">*</span></label>
                        <input
                            type="text"
                            class="form-control<?= isset($errors['codigo']) ? ' is-invalid' : '' ?>"
                            id="verificar-codigo"
                            name="codigo"
                            value="<?= e($old['codigo'] ?? '') ?>"
                            placeholder="000000"
                            inputmode="numeric"
                            pattern="[0-9]{6}"
                            maxlength="6"
                            autocomplete="one-time-code"
                            style="letter-spacing: 0.4em; font-size: 1.2rem; text-align: center;"
                            required
                        >
                        <?php if (isset($errors['codigo'])): ?>
                            <div class="invalid-feedback show"><?= e($errors['codigo']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="verificar-senha">Nova senha <span class="required">*</span></label>
                        <div class="password-field">
                            <input
                                type="password"
                                class="form-control<?= isset($errors['senha']) ? ' is-invalid' : '' ?>"
                                id="verificar-senha"
                                name="senha"
                                placeholder="Mínimo 6 caracteres"
                                autocomplete="new-password"
                                required
                            >
                            <button type="button" class="password-toggle" onclick="togglePassword('verificar-senha', this)" aria-label="Mostrar senha">
                                <i class="icon-eye"></i>
                            </button>
                        </div>
                        <?php if (isset($errors['senha'])): ?>
                            <div class="invalid-feedback show"><?= e($errors['senha']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="verificar-senha-confirmar">Confirmar nova senha <span class="required">*</span></label>
                        <div class="password-field">
                            <input
                                type="password"
                                class="form-control<?= isset($errors['senha_confirmar']) ? ' is-invalid' : '' ?>"
                                id="verificar-senha-confirmar"
                                name="senha_confirmar"
                                placeholder="Repita a senha"
                                autocomplete="new-password"
                                required
                            >
                            <button type="button" class="password-toggle" onclick="togglePassword('verificar-senha-confirmar', this)" aria-label="Mostrar senha">
                                <i class="icon-eye"></i>
                            </button>
                        </div>
                        <?php if (isset($errors['senha_confirmar'])): ?>
                            <div class="invalid-feedback show"><?= e($errors['senha_confirmar']) ?></div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn-primary-solid btn-block">
                        <i class="icon-shield-check me-2"></i>Redefinir senha
                    </button>
                </form>

                <form method="post" action="/recuperar-senha/reenviar" style="margin-top: 16px; text-align: center;">
                    <?= Auth::csrfField() ?>
                    <button type="submit" class="btn btn-link" style="color: var(--color-primary); text-decoration: none; font-weight: 500;">
                        Reenviar código
                    </button>
                </form>

                <div class="auth-footer">
                    <a href="/recuperar-senha">Recomeçar a recuperação</a>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
