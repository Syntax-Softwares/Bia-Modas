<?php if (!empty($flashes)): ?>
    <?php foreach ($flashes as $flash): ?>
        <?php
        $tipo = in_array($flash['type'] ?? 'info', ['success', 'danger', 'warning', 'info'], true)
            ? $flash['type']
            : 'info';
        ?>
        <div class="alert alert-<?= e($tipo) ?> alert-dismissible fade show auth-alert" role="alert">
            <?= e($flash['message'] ?? '') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
