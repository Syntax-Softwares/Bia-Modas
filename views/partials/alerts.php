<?php
$flashes = $data['flashes'] ?? [];
?>
<?php foreach ($flashes as $flash): ?>
    <div class="alerta alerta-<?= e($flash['tipo'] ?? 'aviso') ?>" role="status">
        <?= e($flash['mensagem'] ?? '') ?>
    </div>
<?php endforeach; ?>
