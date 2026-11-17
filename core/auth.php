<?php

/**
 * Gerencia a sessão do usuário, mensagens flash, dados antigos de formulário
 * e proteção CSRF.
 */
class Auth
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');

        session_name('BIAMODAS_SESSID');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    public static function user(): ?array
    {
        $usuario = $_SESSION['usuario'] ?? null;

        return is_array($usuario) ? $usuario : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function login(array $usuario): void
    {
        session_regenerate_id(true);

        $_SESSION['usuario'] = [
            'id_usuario' => (int) $usuario['id_usuario'],
            'nome' => (string) $usuario['nome'],
            'email' => (string) $usuario['email'],
            'is_admin' => filter_var($usuario['is_admin'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ];
    }

    public static function logout(): void
    {
        unset($_SESSION['usuario'], $_SESSION['csrf_token']);
        session_regenerate_id(true);
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public static function csrfField(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::csrfToken()) . '">';
    }

    public static function validCsrf(?string $token): bool
    {
        $sessionToken = $_SESSION['csrf_token'] ?? null;

        return is_string($token)
            && is_string($sessionToken)
            && hash_equals($sessionToken, $token);
    }

    public static function flash(string $type, string $message): void
    {
        $_SESSION['flash'][] = [
            'type' => $type,
            'message' => $message,
        ];
    }

    public static function takeFlashes(): array
    {
        $flashes = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);

        return is_array($flashes) ? $flashes : [];
    }

    public static function setOld(array $old): void
    {
        unset($old['senha'], $old['senha_confirmar'], $old['_csrf']);
        $_SESSION['old'] = $old;
    }

    public static function takeOld(): array
    {
        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['old']);

        return is_array($old) ? $old : [];
    }

    public static function setErrors(array $errors): void
    {
        $_SESSION['errors'] = $errors;
    }

    public static function takeErrors(): array
    {
        $errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['errors']);

        return is_array($errors) ? $errors : [];
    }
}
