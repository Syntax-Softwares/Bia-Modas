<?php
require __DIR__ . '/partials/header.php';
?>
<section class="admin-panel">
    <div class="admin-panel-header">
        <h2>Usuários cadastrados</h2>
        <span class="admin-muted"><?= count($usuarios) ?> usuário(s)</span>
    </div>

    <?php if ($usuarios === []): ?>
        <div class="admin-empty-state">
            <i class="bi bi-people"></i>
            <h2>Nenhum usuário cadastrado</h2>
            <p>As contas criadas na loja aparecerão aqui.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Usuário</th>
                        <th>Telefone</th>
                        <th>Pedidos</th>
                        <th>Perfil</th>
                        <th>Cadastro</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $usuario): ?>
                        <?php $id = (int) $usuario['id_usuario']; ?>
                        <tr>
                            <td>
                                <strong><?= e($usuario['nome']) ?></strong>
                                <small class="admin-muted d-block"><?= e($usuario['email']) ?></small>
                            </td>
                            <td><?= e(formatar_telefone($usuario['telefone'] ?? '')) ?: '—' ?></td>
                            <td><?= (int) $usuario['total_pedidos'] ?></td>
                            <td>
                                <?php if ($usuario['is_admin']): ?>
                                    <span class="admin-badge admin-badge-admin">Administrador</span>
                                <?php else: ?>
                                    <span class="admin-badge admin-badge-pendente">Cliente</span>
                                <?php endif; ?>
                            </td>
                            <td><?= data_hora($usuario['criado_em']) ?></td>
                            <td class="text-end">
                                <a class="admin-btn admin-btn-ghost" href="/admin/usuarios/ver?id=<?= $id ?>">
                                    <i class="bi bi-eye"></i> Detalhes
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
