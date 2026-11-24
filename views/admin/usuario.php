<?php
require __DIR__ . '/partials/header.php';

$ehAdmin = (bool) $usuario['is_admin'];
$ehProprioUsuario = (int) $usuario['id_usuario'] === (int) $adminAtual['id_usuario'];
?>
<section class="admin-order-grid">
    <div class="admin-order-side">
        <div class="admin-panel">
            <div class="admin-panel-header">
                <h2>Dados do usuário</h2>
                <a href="/admin/usuarios" class="admin-link">Voltar</a>
            </div>

            <div class="admin-user-hero">
                <span class="admin-avatar admin-avatar-lg"><?= e(inicial((string) $usuario['nome'])) ?></span>
                <div>
                    <strong><?= e($usuario['nome']) ?></strong>
                    <small><?= e($usuario['email']) ?></small>
                </div>
            </div>

            <dl class="admin-dl">
                <div><dt>ID</dt><dd>#<?= (int) $usuario['id_usuario'] ?></dd></div>
                <div><dt>Telefone</dt><dd><?= e(formatar_telefone($usuario['telefone'] ?? '')) ?: '—' ?></dd></div>
                <div><dt>CPF</dt><dd><?= e($usuario['cpf'] ?? '—') ?></dd></div>
                <div><dt>Nascimento</dt><dd><?= e($usuario['data_nascimento'] ?? '—') ?></dd></div>
                <div><dt>Cadastro</dt><dd><?= data_hora($usuario['criado_em']) ?></dd></div>
                <div>
                    <dt>Perfil</dt>
                    <dd>
                        <span class="admin-badge admin-badge-<?= $ehAdmin ? 'admin' : 'pendente' ?>">
                            <?= $ehAdmin ? 'Administrador' : 'Cliente' ?>
                        </span>
                    </dd>
                </div>
            </dl>

            <div class="admin-stack-actions">
                <form method="post" action="/admin/usuarios/alternar-admin" data-confirm="<?= $ehAdmin ? 'Remover o acesso administrativo deste usuário?' : 'Conceder acesso administrativo a este usuário?' ?>">
                    <?= Sessao::csrfCampo() ?>
                    <input type="hidden" name="id_usuario" value="<?= (int) $usuario['id_usuario'] ?>">
                    <input type="hidden" name="novo_valor" value="<?= $ehAdmin ? '0' : '1' ?>">
                    <button type="submit" class="admin-btn <?= $ehAdmin ? 'admin-btn-danger' : 'admin-btn-primary' ?>" <?= $ehProprioUsuario && $ehAdmin ? 'disabled' : '' ?>>
                        <i class="icon-<?= $ehAdmin ? 'shield-x' : 'shield-check' ?>"></i>
                        <?= $ehAdmin ? 'Remover admin' : 'Tornar administrador' ?>
                    </button>
                </form>

                <form method="post" action="/admin/usuarios/excluir" data-confirm="Excluir este usuário?">
                    <?= Sessao::csrfCampo() ?>
                    <input type="hidden" name="id_usuario" value="<?= (int) $usuario['id_usuario'] ?>">
                    <button type="submit" class="admin-btn admin-btn-danger" <?= $ehProprioUsuario ? 'disabled' : '' ?>>
                        <i class="icon-trash-2"></i> Excluir usuário
                    </button>
                </form>
            </div>
        </div>

        <div class="admin-panel">
            <div class="admin-panel-header">
                <h2>Endereços</h2>
            </div>

            <?php if ($enderecos === []): ?>
                <p class="admin-empty">Nenhum endereço cadastrado.</p>
            <?php else: ?>
                <ul class="admin-address-list">
                    <?php foreach ($enderecos as $endereco): ?>
                        <li>
                            <strong>
                                <?= e($endereco['nome_endereco']) ?>
                                <?php if ($endereco['principal']): ?>
                                    <span class="admin-badge admin-badge-pago">Principal</span>
                                <?php endif; ?>
                            </strong>
                            <span>
                                <?= e($endereco['logradouro']) ?>, <?= e($endereco['numero']) ?>
                                <?php if (!empty($endereco['complemento'])): ?> — <?= e($endereco['complemento']) ?><?php endif; ?>
                            </span>
                            <span><?= e($endereco['bairro']) ?> · <?= e($endereco['cidade']) ?>/<?= e($endereco['uf']) ?> · CEP <?= e($endereco['cep']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <div class="admin-panel">
        <div class="admin-panel-header">
            <h2>Pedidos do usuário</h2>
            <span class="admin-muted"><?= count($pedidos) ?> pedido(s)</span>
        </div>

        <?php if ($pedidos === []): ?>
            <p class="admin-empty">Este usuário ainda não tem pedidos vinculados à conta.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Pedido</th>
                            <th>Data</th>
                            <th>Total</th>
                            <th>Situação</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pedidos as $pedido): ?>
                            <tr>
                                <td><strong>#<?= (int) $pedido['numero_pedido'] ?></strong></td>
                                <td><?= data_hora($pedido['criado_em']) ?></td>
                                <td><?= money($pedido['valor_total']) ?></td>
                                <td>
                                    <span class="admin-badge admin-badge-<?= $pedido['situacao'] ? 'pago' : 'pendente' ?>">
                                        <?= $pedido['situacao'] ? 'Concluído' : 'Em andamento' ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a class="admin-btn admin-btn-ghost" href="/admin/pedidos/ver?id=<?= (int) $pedido['id_compra'] ?>">
                                        Abrir
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
