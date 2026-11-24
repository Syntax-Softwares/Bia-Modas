<?php
/** @var int $codigo */
/** @var string $titulo */
/** @var string $mensagem */
/** @var string $voltar */

$codigo = (int) ($codigo ?? 400);
$titulo = (string) ($titulo ?? 'Nao foi possivel continuar');
$mensagem = (string) ($mensagem ?? 'Tente novamente em instantes.');
$voltar = (string) ($voltar ?? '/perfil');
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="<?= asset('/favicon.ico') ?>" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('/img/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= asset('/img/favicon-192x192.png') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= asset('/img/apple-touch-icon.png') ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= Html::e($titulo) ?> | Bia Modas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lucide-static@latest/font/lucide.css">
<link rel="stylesheet" href="<?= asset('/css/base.css') ?>">
    <link rel="stylesheet" href="<?= asset('/css/layout.css') ?>">
    <link rel="stylesheet" href="<?= asset('/css/components.css') ?>">
    <link rel="stylesheet" href="<?= asset('/css/auth.css') ?>">
</head>
<body>
    <main class="auth-container">
        <div class="auth-card">
            <div class="auth-card-body">
                <p class="text-muted mb-1">Erro <?= Html::e($codigo) ?></p>
                <h1><?= Html::e($titulo) ?></h1>
                <p><?= Html::e($mensagem) ?></p>
                <a href="<?= Html::e($voltar) ?>" class="btn-primary-solid">Voltar</a>
            </div>
        </div>
    </main>
</body>
</html>
