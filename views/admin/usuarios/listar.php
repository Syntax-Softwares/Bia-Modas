<?php
$queryFiltros = ['q' => $filtros['q'], 'tipo' => $filtros['tipo']];
?>
<div class="admin-page-head">
    <div>
        <p class="admin-eyebrow">Pessoas</p>
        <h1>Usuários</h1>
        <p class="admin-muted"><?= (int) $resultado['total'] ?> usuário<?= (int) $resultado['total'] === 1 ? '' : 's' ?> encontrado<?= (int) $resultado['total'] === 1 ? '' : 's' ?></p>
    </div>
    <a href="/admin/usuarios/novo" class="admin-btn admin-btn--primary"><i class="bi bi-plus-lg"></i> Novo usuário</a>
</div>

<form method="get" action="/admin/usuarios" class="admin-toolbar">
    <div class="admin-field admin-field--grow">
        <label for="filtro-q">Buscar</label>
        <input type="text" id="filtro-q" name="q" value="<?= e($filtros['q']) ?>" placeholder="Nome ou e-mail">
    </div>

    <div class="admin-field">
        <label for="filtro-tipo">Tipo</label>
        <select id="filtro-tipo" name="tipo">
            <option value=""<?= $filtros['tipo'] === '' ? ' selected' : '' ?>>Todos</option>
            <option value="clientes"<?= $filtros['tipo'] === 'clientes' ? ' selected' : '' ?>>Clientes</option>
            <option value="admins"<?= $filtros['tipo'] === 'admins' ? ' selected' : '' ?>>Administradores</option>
        </select>
    </div>

    <div class="admin-toolbar-actions">
        <button type="submit" class="admin-btn admin-btn--outline"><i class="bi bi-search"></i> Filtrar</button>
        <a href="/admin/usuarios" class="admin-btn admin-btn--ghost">Limpar</a>
    </div>
</form>

<?php if ($resultado['itens'] === []): ?>
    <div class="admin-empty-state">
        <i class="bi bi-people"></i>
        <h2>Nenhum usuário encontrado</h2>
        <p>Ajuste os filtros ou cadastre um novo usuário.</p>
        <a href="/admin/usuarios/novo" class="admin-btn admin-btn--primary">Novo usuário</a>
    </div>
<?php else: ?>
    <div class="admin-panel">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Usuário</th>
                        <th>Telefone</th>
                        <th>Tipo</th>
                        <th>Cadastro</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resultado['itens'] as $usuario): ?>
                        <tr>
                            <td>
                                <a href="/admin/usuarios/<?= (int) $usuario['id_usuario'] ?>/editar" class="admin-link">
                                    <?= e($usuario['nome']) ?>
                                </a>
                                <span class="admin-muted d-block"><?= e($usuario['email']) ?></span>
                            </td>
                            <td><?= e($usuario['telefone'] ?: '—') ?></td>
                            <td>
                                <?php if (filter_var($usuario['is_admin'], FILTER_VALIDATE_BOOLEAN)): ?>
                                    <span class="admin-badge admin-badge--primary">Administrador</span>
                                <?php else: ?>
                                    <span class="admin-badge">Cliente</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $usuario['criado_em'] ? e(date('d/m/Y', strtotime($usuario['criado_em']))) : '—' ?></td>
                            <td class="text-end">
                                <div class="admin-actions">
                                    <a href="/admin/usuarios/<?= (int) $usuario['id_usuario'] ?>/editar" class="admin-btn admin-btn--sm admin-btn--outline" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if ((int) $usuario['id_usuario'] !== (int) $admin['id_usuario']): ?>
                                        <form method="post" action="/admin/usuarios/<?= (int) $usuario['id_usuario'] ?>" onsubmit="return confirm('Excluir este usuário?');">
                                            <?= Auth::csrfField() ?>
                                            <input type="hidden" name="_method" value="DELETE">
                                            <button type="submit" class="admin-btn admin-btn--sm admin-btn--danger" title="Excluir">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($totalPaginas > 1): ?>
        <nav class="admin-pagination" aria-label="Paginação de usuários">
            <a
                href="<?= e(url_com_query('/admin/usuarios', $queryFiltros + ['pagina' => max(1, $pagina - 1)])) ?>"
                class="admin-btn admin-btn--sm admin-btn--outline<?= $pagina <= 1 ? ' is-disabled' : '' ?>"
                <?= $pagina <= 1 ? 'aria-disabled="true" tabindex="-1"' : '' ?>
            ><i class="bi bi-chevron-left"></i></a>

            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                <a
                    href="<?= e(url_com_query('/admin/usuarios', $queryFiltros + ['pagina' => $i])) ?>"
                    class="admin-btn admin-btn--sm<?= $i === $pagina ? ' admin-btn--primary' : ' admin-btn--outline' ?>"
                ><?= $i ?></a>
            <?php endfor; ?>

            <a
                href="<?= e(url_com_query('/admin/usuarios', $queryFiltros + ['pagina' => min($totalPaginas, $pagina + 1)])) ?>"
                class="admin-btn admin-btn--sm admin-btn--outline<?= $pagina >= $totalPaginas ? ' is-disabled' : '' ?>"
                <?= $pagina >= $totalPaginas ? 'aria-disabled="true" tabindex="-1"' : '' ?>
            ><i class="bi bi-chevron-right"></i></a>
        </nav>
    <?php endif; ?>
<?php endif; ?>
