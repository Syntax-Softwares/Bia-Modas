<?php
/** @var string $titulo */
/** @var string $ativo */
/** @var array $admin */
/** @var array $flashes */
/** @var string $conteudo */
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <title><?= e($titulo) ?> | Admin Bia Modas</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/css/base.css">
    <link rel="stylesheet" href="/css/admin.css">
</head>
<body class="admin-body">
    <div class="admin-shell">
        <aside class="admin-sidebar" id="admin-sidebar">
            <?php require __DIR__ . '/partials/sidebar.php'; ?>
        </aside>

        <div class="admin-sidebar-overlay" id="admin-sidebar-overlay"></div>

        <div class="admin-main">
            <header class="admin-topbar">
                <div class="admin-topbar-left">
                    <button
                        type="button"
                        class="admin-icon-btn"
                        id="admin-menu-toggle"
                        aria-label="Abrir menu"
                        aria-controls="admin-sidebar"
                        aria-expanded="false"
                    >
                        <i class="bi bi-list"></i>
                    </button>
                    <div class="admin-topbar-context">
                        <span>Bia Modas</span>
                        <strong><?= e($titulo) ?></strong>
                    </div>
                </div>

                <div class="admin-topbar-user">
                    <span class="admin-avatar" title="<?= e($admin['nome']) ?>"><?= e(inicial($admin['nome'])) ?></span>
                    <div class="admin-user-info">
                        <strong><?= e($admin['nome']) ?></strong>
                        <span><?= e($admin['email']) ?></span>
                    </div>
                    <form method="post" action="/logout" class="admin-logout">
                        <?= Auth::csrfField() ?>
                        <button type="submit" class="admin-icon-btn" aria-label="Sair" title="Sair">
                            <i class="bi bi-box-arrow-right"></i>
                        </button>
                    </form>
                </div>
            </header>

            <main class="admin-content">
                <?php require __DIR__ . '/../partials/alerts.php'; ?>
                <?= $conteudo ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        (function () {
            const body = document.body;
            const toggle = document.getElementById('admin-menu-toggle');
            const overlay = document.getElementById('admin-sidebar-overlay');

            function fechar() {
                body.classList.remove('admin-sidebar-open');
                if (toggle) {
                    toggle.setAttribute('aria-expanded', 'false');
                }
            }

            if (toggle) {
                toggle.addEventListener('click', function () {
                    const aberto = body.classList.toggle('admin-sidebar-open');
                    toggle.setAttribute('aria-expanded', aberto ? 'true' : 'false');
                });
            }

            if (overlay) {
                overlay.addEventListener('click', fechar);
            }
        })();
    </script>
</body>
</html>
