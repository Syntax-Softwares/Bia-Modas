<?php
/**
 * Mensagens flash unificadas.
 *
 * Aceita tanto $flashes (MVC05/06/07) quanto $data['flashes'] (MVC08/09) e
 * as duas nomenclaturas de chave usadas pelos modulos integrados.
 */
$listaFlashes = $flashes ?? ($data['flashes'] ?? []);
$listaFlashes = is_array($listaFlashes) ? $listaFlashes : [];

$mapaTipos = [
    'success' => 'success',
    'sucesso' => 'success',
    'danger' => 'danger',
    'erro' => 'danger',
    'error' => 'danger',
    'warning' => 'warning',
    'aviso' => 'warning',
    'info' => 'info',
];
?>
<?php foreach ($listaFlashes as $flash): ?>
    <?php
    $tipoOriginal = (string) ($flash['type'] ?? $flash['tipo'] ?? 'info');
    $tipo = $mapaTipos[$tipoOriginal] ?? 'info';
    $texto = (string) ($flash['message'] ?? $flash['mensagem'] ?? '');
    ?>
    <?php if ($texto !== ''): ?>
        <div class="alert alert-<?= e($tipo) ?> alert-dismissible fade show auth-alert" role="alert">
            <?= e($texto) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    <?php endif; ?>
<?php endforeach; ?>
