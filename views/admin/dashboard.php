<?php
$cards = [
    [
        'rotulo' => 'Produtos ativos',
        'valor' => $resumo['produtos_ativos'],
        'icone' => 'bi-bag-check',
        'href' => '/admin/produtos?status=ativos',
        'tom' => 'primary',
    ],
    [
        'rotulo' => 'Estoque baixo (≤ 5)',
        'valor' => $resumo['estoque_baixo'],
        'icone' => 'bi-exclamation-triangle',
        'href' => '/admin/estoque?baixo=1',
        'tom' => 'warning',
    ],
    [
        'rotulo' => 'Clientes ativos',
        'valor' => $resumo['clientes'],
        'icone' => 'bi-people',
        'href' => '/admin/usuarios?tipo=clientes',
        'tom' => 'info',
    ],
    [
        'rotulo' => 'Ofertas ativas',
        'valor' => $resumo['ofertas_ativas'],
        'icone' => 'bi-percent',
        'href' => '/admin/ofertas',
        'tom' => 'success',
    ],
    [
        'rotulo' => 'Cupons',
        'valor' => $resumo['cupons_ativos'],
        'icone' => 'bi-ticket-perforated',
        'href' => '/admin/cupons',
        'tom' => 'secondary',
    ],
    [
        'rotulo' => 'Categorias',
        'valor' => $resumo['categorias'],
        'icone' => 'bi-tags',
        'href' => '/admin/categorias',
        'tom' => 'dark',
    ],
];
?>
<div class="admin-page-head">
    <div>
        <p class="admin-eyebrow">Visão geral</p>
        <h1>Painel administrativo</h1>
        <p class="admin-muted">Resumo do catálogo e dos cadastros da loja.</p>
    </div>
</div>

<div class="admin-stats">
    <?php foreach ($cards as $card): ?>
        <a href="<?= e($card['href']) ?>" class="admin-stat admin-stat--<?= e($card['tom']) ?>">
            <span class="admin-stat-icon"><i class="bi <?= e($card['icone']) ?>"></i></span>
            <span class="admin-stat-value"><?= (int) $card['valor'] ?></span>
            <span class="admin-stat-label"><?= e($card['rotulo']) ?></span>
        </a>
    <?php endforeach; ?>
</div>

<div class="admin-grid-2">
    <section class="admin-panel">
        <header class="admin-panel-head">
            <h2>Últimos produtos</h2>
            <a href="/admin/produtos" class="admin-link">Ver todos</a>
        </header>

        <?php if ($ultimosProdutos === []): ?>
            <p class="admin-empty">Nenhum produto cadastrado.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th class="text-end">Valor</th>
                            <th class="text-end">Estoque</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimosProdutos as $produto): ?>
                            <tr>
                                <td>
                                    <a href="/admin/produtos/<?= (int) $produto['id_produto'] ?>/editar" class="admin-link">
                                        <?= e($produto['nome']) ?>
                                    </a>
                                    <span class="admin-muted d-block"><?= e($produto['sku']) ?></span>
                                </td>
                                <td class="text-end"><?= money($produto['valor']) ?></td>
                                <td class="text-end">
                                    <span class="admin-badge<?= (int) $produto['estoque'] <= 5 ? ' admin-badge--warning' : '' ?>">
                                        <?= (int) $produto['estoque'] ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="admin-panel">
        <header class="admin-panel-head">
            <h2>Últimos usuários</h2>
            <a href="/admin/usuarios" class="admin-link">Ver todos</a>
        </header>

        <?php if ($ultimosUsuarios === []): ?>
            <p class="admin-empty">Nenhum usuário cadastrado.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Usuário</th>
                            <th>Tipo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimosUsuarios as $usuario): ?>
                            <tr>
                                <td>
                                    <a href="/admin/usuarios/<?= (int) $usuario['id_usuario'] ?>/editar" class="admin-link">
                                        <?= e($usuario['nome']) ?>
                                    </a>
                                    <span class="admin-muted d-block"><?= e($usuario['email']) ?></span>
                                </td>
                                <td>
                                    <?php if (filter_var($usuario['is_admin'], FILTER_VALIDATE_BOOLEAN)): ?>
                                        <span class="admin-badge admin-badge--primary">Admin</span>
                                    <?php else: ?>
                                        <span class="admin-badge">Cliente</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
