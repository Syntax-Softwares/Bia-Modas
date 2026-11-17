<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Recupere o acesso à sua conta Bia Modas." />
    <title>Recuperar senha | Bia Modas</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/css/base.css">
    <link rel="stylesheet" href="/css/layout.css">
    <link rel="stylesheet" href="/css/components.css">
    <link rel="stylesheet" href="/css/auth.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-card-header">
                <a href="/" class="logo auth-logo-link">
                    <img src="/img/logoBiaModas.png" alt="Bia Modas">
                </a>
                <h1>Recuperar senha</h1>
                <p>Informe seu e-mail para receber um código de verificação</p>
            </div>
            <div class="auth-card-body">
                <?php require __DIR__ . '/partials/alerts.php'; ?>

                <form class="auth-form" method="post" action="/recuperar-senha" novalidate>
                    <?= Auth::csrfField() ?>

                    <div class="form-group">
                        <label for="recuperar-email">E-mail <span class="required">*</span></label>
                        <input
                            type="email"
                            class="form-control<?= isset($errors['email']) ? ' is-invalid' : '' ?>"
                            id="recuperar-email"
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

                    <button type="submit" class="btn-primary-solid btn-block">
                        <i class="bi bi-envelope me-2"></i>Enviar código
                    </button>
                </form>

                <div class="auth-footer">
                    Lembrei a senha. <a href="/login">Entrar</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
