<?php

require_once __DIR__ . '/auth.php';

/**
 * Controle de acesso ao painel administrativo.
 *
 * O painel usa a mesma sessao da loja (MVC05). A flag is_admin vem do banco
 * no login e e renovada quando o usuario entra novamente.
 */
final class Admin
{
    public static function usuario(): ?array
    {
        $usuario = Auth::user();

        if ($usuario === null || empty($usuario['is_admin'])) {
            return null;
        }

        return $usuario;
    }

    public static function exigir(): array
    {
        $usuario = Auth::user();

        if ($usuario === null) {
            Auth::flash('info', 'Entre com uma conta administrativa para continuar.');
            redirect('/login');
        }

        if (empty($usuario['is_admin'])) {
            http_response_code(403);
            $codigo = 403;
            $titulo = 'Acesso negado';
            $mensagem = 'Sua conta não tem permissão para acessar o painel administrativo.';
            $voltar = '/';

            require __DIR__ . '/../views/erro.php';
            exit;
        }

        return $usuario;
    }
}
