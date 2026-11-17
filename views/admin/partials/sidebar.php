<?php
$menu = [
    [
        'grupo' => 'Visão geral',
        'itens' => [
            ['chave' => 'painel', 'rotulo' => 'Painel', 'icone' => 'bi-speedometer2', 'href' => '/admin'],
        ],
    ],
    [
        'grupo' => 'Catálogo',
        'itens' => [
            ['chave' => 'produtos', 'rotulo' => 'Produtos', 'icone' => 'bi-bag', 'href' => '/admin/produtos'],
            ['chave' => 'categorias', 'rotulo' => 'Categorias', 'icone' => 'bi-tags', 'href' => '/admin/categorias'],
            ['chave' => 'tamanhos', 'rotulo' => 'Tamanhos e medidas', 'icone' => 'bi-rulers', 'href' => '/admin/tamanhos'],
            ['chave' => 'estoque', 'rotulo' => 'Estoque', 'icone' => 'bi-box-seam', 'href' => '/admin/estoque'],
            ['chave' => 'ofertas', 'rotulo' => 'Ofertas', 'icone' => 'bi-percent', 'href' => '/admin/ofertas'],
            ['chave' => 'cupons', 'rotulo' => 'Cupons', 'icone' => 'bi-ticket-perforated', 'href' => '/admin/cupons'],
        ],
    ],
    [
        'grupo' => 'Pessoas',
        'itens' => [
            ['chave' => 'usuarios', 'rotulo' => 'Usuários', 'icone' => 'bi-people', 'href' => '/admin/usuarios'],
        ],
    ],
];
?>
<div class="admin-sidebar-inner">
    <a class="admin-brand" href="/admin">
        <img src="/img/logoBiaModas.png" alt="Bia Modas">
        <span>Admin</span>
    </a>

    <nav class="admin-nav" aria-label="Menu administrativo">
        <?php foreach ($menu as $grupo): ?>
            <span class="admin-nav-group"><?= e($grupo['grupo']) ?></span>
            <?php foreach ($grupo['itens'] as $item): ?>
                <a
                    href="<?= e($item['href']) ?>"
                    class="admin-nav-link<?= ($ativo ?? '') === $item['chave'] ? ' is-active' : '' ?>"
                >
                    <i class="bi <?= e($item['icone']) ?>"></i>
                    <span><?= e($item['rotulo']) ?></span>
                </a>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </nav>

    <div class="admin-sidebar-footer">
        <a href="/" class="admin-ext-link"><i class="bi bi-box-arrow-up-right"></i> Ver loja</a>
    </div>
</div>
