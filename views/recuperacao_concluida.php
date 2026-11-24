<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <link rel="icon" href="<?= asset('/favicon.ico') ?>" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('/img/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= asset('/img/favicon-192x192.png') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= asset('/img/apple-touch-icon.png') ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Sua senha foi alterada com sucesso." />
    <meta http-equiv="refresh" content="3;url=/login" />
    <title>Senha alterada | Bia Modas</title>

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
                <h1>Senha alterada com sucesso</h1>
            </div>
            <div class="auth-card-body" style="text-align: center;">
                <div style="font-size: 3rem; color: #198754; margin-bottom: 12px;">
                    <i class="icon-circle-check"></i>
                </div>

                <p style="margin-bottom: 20px;">
                    Sua nova senha já está valendo. Você será redirecionada para o login em
                    <strong>3 segundos</strong>.
                </p>

                <a href="/login" class="btn-primary-solid btn-block" style="display: inline-block; text-decoration: none;">
                    <i class="icon-log-in me-2"></i>Ir para o login agora
                </a>
            </div>
        </div>
    </div>
</body>
</html>
