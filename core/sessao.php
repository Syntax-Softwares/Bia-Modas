<?php

/**
 * Sessao do usuario, CSRF, mensagens flash e cabecalhos de seguranca.
 *
 * A chave $_SESSION['usuario'] e a mesma usada pelo MVC05 (Auth), para que a
 * integracao final funcione sem alterar os controllers do perfil.
 */
final class Sessao
{
    public const NOME = 'BIAMODAS_SESSID';

    private const IDLE = 1800;       // 30 minutos sem atividade
    private const ABSOLUTO = 28800;   // limite absoluto de 8 horas

    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            self::atualizarAtividade();

            return;
        }

        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? '') === '443');

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.gc_maxlifetime', (string) self::IDLE);

        session_name(self::NOME);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
        self::atualizarAtividade();
    }

    private static function atualizarAtividade(): void
    {
        $agora = time();
        $criada = (int) ($_SESSION['_bm_criada'] ?? 0);
        $ultimo = (int) ($_SESSION['_bm_ultimo'] ?? $agora);

        if ($criada > 0 && ($agora - $ultimo > self::IDLE || $agora - $criada > self::ABSOLUTO)) {
            // Sessao expirada: descarta os dados e troca o ID.
            $_SESSION = [];
            session_regenerate_id(true);
            $criada = 0;
        }

        if ($criada === 0) {
            $_SESSION['_bm_criada'] = $agora;
        }

        $_SESSION['_bm_ultimo'] = $agora;
    }

    public static function usuario(): ?array
    {
        $usuario = $_SESSION['usuario'] ?? null;

        return is_array($usuario) && isset($usuario['id_usuario']) ? $usuario : null;
    }

    public static function id(): ?int
    {
        $usuario = self::usuario();

        return $usuario === null ? null : (int) $usuario['id_usuario'];
    }

    public static function exigirLogin(): array
    {
        $usuario = self::usuario();

        if ($usuario === null) {
            self::flash('info', 'Entre na sua conta para continuar.');
            self::redirecionar('/login');
        }

        return $usuario;
    }

    public static function atualizarUsuario(array $dados): void
    {
        if (!isset($_SESSION['usuario']) || !is_array($_SESSION['usuario'])) {
            return;
        }

        foreach (['nome', 'email'] as $campo) {
            if (isset($dados[$campo])) {
                $_SESSION['usuario'][$campo] = (string) $dados[$campo];
            }
        }
    }

    public static function regenerar(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function sair(): void
    {
        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            if (ini_get('session.use_cookies')) {
                $parametros = session_get_cookie_params();

                setcookie(session_name(), '', [
                    'expires' => time() - 42000,
                    'path' => $parametros['path'] ?? '/',
                    'domain' => $parametros['domain'] ?? '',
                    'secure' => (bool) ($parametros['secure'] ?? false),
                    'httponly' => (bool) ($parametros['httponly'] ?? true),
                    'samesite' => $parametros['samesite'] ?? 'Lax',
                ]);
            }

            session_destroy();
        }
    }

    public static function csrfToken(): string
    {
        $token = $_SESSION['csrf_token'] ?? null;

        if (!is_string($token) || strlen($token) < 32) {
            $token = bin2hex(random_bytes(32));
            $_SESSION['csrf_token'] = $token;
        }

        return $token;
    }

    public static function csrfCampo(): string
    {
        return '<input type="hidden" name="_csrf" value="' . Html::e(self::csrfToken()) . '">';
    }

    public static function validarCsrf(mixed $token): bool
    {
        $esperado = $_SESSION['csrf_token'] ?? null;

        return is_string($token)
            && is_string($esperado)
            && $esperado !== ''
            && hash_equals($esperado, $token);
    }

    public static function flash(string $tipo, string $mensagem): void
    {
        $_SESSION['flash'][] = [
            'type' => $tipo,
            'message' => $mensagem,
        ];
    }

    public static function mensagensFlash(): array
    {
        $mensagens = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);

        return is_array($mensagens) ? $mensagens : [];
    }

    public static function setOld(array $dados): void
    {
        foreach (['senha', 'senha_atual', 'senha_nova', 'senha_confirmar', '_csrf'] as $campo) {
            unset($dados[$campo]);
        }

        $_SESSION['old'] = $dados;
    }

    public static function old(): array
    {
        $dados = $_SESSION['old'] ?? [];
        unset($_SESSION['old']);

        return is_array($dados) ? $dados : [];
    }

    public static function setErros(array $erros): void
    {
        $_SESSION['errors'] = $erros;
    }

    public static function erros(): array
    {
        $erros = $_SESSION['errors'] ?? [];
        unset($_SESSION['errors']);

        return is_array($erros) ? $erros : [];
    }

    public static function redirecionar(string $destino): never
    {
        header('Location: ' . $destino, true, 303);
        exit;
    }

    public static function cabecalhosSeguranca(): void
    {
        header_remove('X-Powered-By');

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: no-referrer');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        header('Cache-Control: no-store, no-cache, must-revalidate, private');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('X-Robots-Tag: noindex, nofollow');
        header(
            "Content-Security-Policy: default-src 'self'; "
            . "base-uri 'self'; object-src 'none'; frame-ancestors 'none'; "
            . "form-action 'self'; img-src 'self' data:; "
            . "script-src 'self' https://cdn.jsdelivr.net; "
            . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; "
            . "font-src 'self' data: https://cdn.jsdelivr.net https://fonts.gstatic.com; "
            . "connect-src 'self'"
        );
    }
}
