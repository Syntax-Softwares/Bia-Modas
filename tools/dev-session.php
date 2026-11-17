<?php

/**
 * FERRAMENTA SOMENTE DE DESENVOLVIMENTO.
 *
 * Cria uma sessao valida para testar /perfil enquanto o MVC05 (login) ainda
 * nao esta integrado. Nao e exposta via HTTP: fica fora de public/.
 *
 * Uso:
 *   docker compose exec app php tools/dev-session.php 1
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$ambiente = getenv('APP_ENV') ?: 'production';

if ($ambiente === 'production') {
    fwrite(STDERR, "Indisponivel em producao.\n");
    exit(1);
}

$id = filter_var($argv[1] ?? null, FILTER_VALIDATE_INT);

if (!is_int($id) || $id <= 0) {
    fwrite(STDERR, "Uso: php tools/dev-session.php <id_usuario>\n");
    exit(1);
}

require_once __DIR__ . '/../core/database.php';

$pdo = Database::conenct();
$stmt = $pdo->prepare(
    '
    SELECT id_usuario, nome, email, is_admin
    FROM usuarios
    WHERE id_usuario = :id
      AND deletado_em IS NULL
    LIMIT 1
    '
);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$usuario = $stmt->fetch();

if ($usuario === false) {
    fwrite(STDERR, "Usuario {$id} nao encontrado.\n");
    exit(1);
}

session_name('BIAMODAS_SESSID');
session_id(bin2hex(random_bytes(24)));

if (session_start() === false) {
    fwrite(STDERR, "Nao foi possivel iniciar a sessao.\n");
    exit(1);
}

$_SESSION['usuario'] = [
    'id_usuario' => (int) $usuario['id_usuario'],
    'nome' => (string) $usuario['nome'],
    'email' => (string) $usuario['email'],
    'is_admin' => filter_var($usuario['is_admin'] ?? false, FILTER_VALIDATE_BOOLEAN),
];
$_SESSION['_bm_criada'] = time();
$_SESSION['_bm_ultimo'] = time();
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$idSessao = session_id();
session_write_close();

// O Apache roda como www-data; garante leitura do arquivo criado pelo root.
$savePath = session_save_path() ?: sys_get_temp_dir();

if (preg_match('/^\d+;(.+)$/', $savePath, $achado) === 1) {
    $savePath = $achado[1];
}

$arquivo = rtrim($savePath, '/\\') . DIRECTORY_SEPARATOR . 'sess_' . $idSessao;

if (is_file($arquivo)) {
    @chown($arquivo, 'www-data') || @chown($arquivo, 33);
    @chgrp($arquivo, 'www-data') || @chgrp($arquivo, 33);
    @chmod($arquivo, 0640);
}

echo "Sessao criada para o usuario {$usuario['nome']} (id {$usuario['id_usuario']}).\n";
echo "Cookie: BIAMODAS_SESSID={$idSessao}\n";
echo "Navegador: http://localhost:8080/perfil\n";
echo "Teste via curl:\n";
echo "  curl -b 'BIAMODAS_SESSID={$idSessao}' http://localhost:8080/perfil\n";
