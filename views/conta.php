<?php
$estaLogado = true;
$contaHref = '/conta';
$busca = '';
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Minha conta Bia Modas." />
    <title>Minha Conta | Bia Modas</title>

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
</head>
<body>
    <?php require __DIR__ . '/partials/site_header.php'; ?>

    <main class="auth-container">
        <div class="auth-card">
            <div class="auth-card-header">
                <div class="auth-avatar-logged account-avatar"><?= e(inicial($usuario['nome'])) ?></div>
                <h1><?= e($usuario['nome']) ?></h1>
                <p><?= e($usuario['email']) ?></p>
            </div>

            <div class="auth-card-body">
                <?php require __DIR__ . '/partials/alerts.php'; ?>

                <h2 class="auth-section-title">Dados da conta</h2>

                <dl class="account-list">
                    <div>
                        <dt>Nome</dt>
                        <dd><?= e($usuario['nome']) ?></dd>
                    </div>
                    <div>
                        <dt>E-mail</dt>
                        <dd><?= e($usuario['email']) ?></dd>
                    </div>
                    <div>
                        <dt>Telefone</dt>
                        <dd><?= e($usuario['telefone'] ?: 'Não informado') ?></dd>
                    </div>
                    <div>
                        <dt>Cliente desde</dt>
                        <dd><?= !empty($usuario['criado_em']) ? e(date('d/m/Y', strtotime($usuario['criado_em']))) : '—' ?></dd>
                    </div>
                </dl>

                <h2 class="auth-section-title">Endereço principal</h2>

                <?php if (!empty($usuario['endereco_logradouro'])): ?>
                    <address class="account-address">
                        <?= e($usuario['endereco_logradouro']) ?>, <?= e($usuario['endereco_numero']) ?>
                        <?php if (!empty($usuario['endereco_complemento'])): ?>
                            — <?= e($usuario['endereco_complemento']) ?>
                        <?php endif; ?>
                        <br>
                        <?= e($usuario['endereco_bairro']) ?> — <?= e($usuario['endereco_cidade']) ?>/<?= e($usuario['endereco_uf']) ?>
                        <br>
                        CEP <?= e($usuario['endereco_cep']) ?>
                    </address>
                <?php else: ?>
                    <p class="account-empty">Nenhum endereço cadastrado.</p>
                <?php endif; ?>

                <h2 class="auth-section-title">Medidas</h2>

                <?php if ($usuario['medida_busto'] !== null || $usuario['medida_cintura'] !== null || $usuario['medida_quadril'] !== null): ?>
                    <dl class="account-list">
                        <div>
                            <dt>Busto</dt>
                            <dd><?= $usuario['medida_busto'] !== null ? e($usuario['medida_busto']) . ' cm' : '—' ?></dd>
                        </div>
                        <div>
                            <dt>Cintura</dt>
                            <dd><?= $usuario['medida_cintura'] !== null ? e($usuario['medida_cintura']) . ' cm' : '—' ?></dd>
                        </div>
                        <div>
                            <dt>Quadril</dt>
                            <dd><?= $usuario['medida_quadril'] !== null ? e($usuario['medida_quadril']) . ' cm' : '—' ?></dd>
                        </div>
                    </dl>
                <?php else: ?>
                    <p class="account-empty">Nenhuma medida cadastrada.</p>
                <?php endif; ?>

                <div class="account-actions">
                    <a href="/" class="btn-outline-account">Continuar comprando</a>
                    <form method="post" action="/logout">
                        <?= Auth::csrfField() ?>
                        <button type="submit" class="btn-primary-solid">
                            <i class="icon-log-out me-2"></i>Sair da conta
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="/js/site.js"></script>
</body>
</html>
