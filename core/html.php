<?php

/**
 * Utilitarios de saida segura para as views.
 *
 * Todo dado vindo do banco ou do usuario deve passar por Html::e() antes de
 * ser impresso, evitando XSS (inclusive em atributos).
 */
final class Html
{
    public static function e(mixed $valor): string
    {
        return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function inicial(string $nome): string
    {
        $nome = trim($nome);

        if ($nome === '') {
            return '?';
        }

        if (preg_match('/^./u', $nome, $achado) === 1) {
            return strtoupper($achado[0]);
        }

        return strtoupper(substr($nome, 0, 1));
    }

    public static function data(?string $data): string
    {
        if ($data === null || $data === '') {
            return '—';
        }

        $obj = DateTime::createFromFormat('!Y-m-d', $data);

        return $obj === false ? '—' : $obj->format('d/m/Y');
    }

    public static function cep(?string $cep): string
    {
        $digitos = preg_replace('/\D+/', '', (string) $cep) ?? '';

        if (strlen($digitos) !== 8) {
            return (string) $cep;
        }

        return substr($digitos, 0, 5) . '-' . substr($digitos, 5, 3);
    }

    public static function telefone(?string $telefone): string
    {
        $digitos = preg_replace('/\D+/', '', (string) $telefone) ?? '';

        if (strlen($digitos) === 11) {
            return '(' . substr($digitos, 0, 2) . ') ' . substr($digitos, 2, 5) . '-' . substr($digitos, 7, 4);
        }

        if (strlen($digitos) === 10) {
            return '(' . substr($digitos, 0, 2) . ') ' . substr($digitos, 2, 4) . '-' . substr($digitos, 6, 4);
        }

        return (string) $telefone;
    }

    public static function cpf(?string $cpf): string
    {
        $digitos = preg_replace('/\D+/', '', (string) $cpf) ?? '';

        if (strlen($digitos) !== 11) {
            return (string) $cpf;
        }

        return substr($digitos, 0, 3) . '.' . substr($digitos, 3, 3) . '.' . substr($digitos, 6, 3) . '-' . substr($digitos, 9, 2);
    }
}
