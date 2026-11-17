<?php
$tituloAdmin = (string) ($tituloAdmin ?? 'Painel administrativo');
$abaAdmin = (string) ($abaAdmin ?? '');
$flashes = $flashes ?? [];
$adminUsuario = Admin::usuario();
$navAtivo = static fn (string $aba): string => $abaAdmin === $aba ? ' active' : '';
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($tituloAdmin) ?> | Admin Bia Modas</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lucide-static@latest/font/lucide.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/css/base.css">
    <link rel="stylesheet" href="/css/layout.css">
    <link rel="stylesheet" href="/css/admin.css">
</head>
<body class="admin-page">
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <a class="admin-brand" href="/admin">
                <img src="/img/logoBiaModas.png" alt="Bia Modas">
                <span>Admin</span>
            </a>

            <nav class="admin-nav" aria-label="Menu administrativo">
                <a class="admin-nav-link<?= $navAtivo('dashboard') ?>" href="/admin">
                    <i class="icon-layout-dashboard"></i>
                    <span>Dashboard</span>
                </a>
                <a class="admin-nav-link<?= $navAtivo('produtos') ?>" href="/admin/produtos">
                    <i class="icon-shopping-bag"></i>
                    <span>Produtos</span>
                </a>
                <a class="admin-nav-link<?= $navAtivo('categorias') ?>" href="/admin/categorias">
                    <i class="icon-tags"></i>
                    <span>Categorias</span>
                </a>
                <a class="admin-nav-link<?= $navAtivo('pedidos') ?>" href="/admin/pedidos">
                    <i class="icon-receipt"></i>
                    <span>Pedidos</span>
                </a>
                <a class="admin-nav-link<?= $navAtivo('usuarios') ?>" href="/admin/usuarios">
                    <i class="icon-users"></i>
                    <span>Usuários</span>
                </a>
            </nav>

            <div class="admin-sidebar-footer">
                <a class="admin-nav-link" href="/" target="_blank" rel="noopener">
                    <i class="icon-external-link"></i>
                    <span>Ver loja</span>
                </a>
                <form method="post" action="/logout">
                    <?= Auth::csrfField() ?>
                    <button type="submit" class="admin-nav-link admin-logout">
                        <i class="icon-log-out"></i>
                        <span>Sair</span>
                    </button>
                </form>
            </div>
        </aside>

        <div class="admin-main">
            <header class="admin-topbar">
                <div>
                    <span class="admin-eyebrow">Painel administrativo</span>
                    <h1><?= e($tituloAdmin) ?></h1>
                </div>

                <div class="admin-user">
                    <span class="admin-avatar"><?= e(inicial((string) ($adminUsuario['nome'] ?? 'A'))) ?></span>
                    <span class="admin-user-info">
                        <strong><?= e($adminUsuario['nome'] ?? 'Administrador') ?></strong>
                        <small><?= e($adminUsuario['email'] ?? '') ?></small>
                    </span>
                </div>
            </header>

            <main class="admin-content">
                <?php require __DIR__ . '/../../partials/alerts.php'; ?>
