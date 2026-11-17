<?php
/** @var array $flash */
if ($flash === []) {
    return;
}

$tiposPermitidos = ['success', 'danger', 'info', 'warning'];
?>
<div class="profile-alerts">
    <?php foreach ($flash as $mensagem): ?>
        <?php
        $tipo = (string) ($mensagem['type'] ?? 'info');
        $tipo = in_array($tipo, $tiposPermitidos, true) ? $tipo : 'info';
        ?>
        <div class="alert alert-<?= Html::e($tipo) ?>" role="alert">
            <?= Html::e($mensagem['message'] ?? '') ?>
        </div>
    <?php endforeach; ?>
</div>
