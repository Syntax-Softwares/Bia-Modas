<?php

/**
 * Escapa um valor para exibição segura em HTML.
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Formata um valor monetário no padrão brasileiro.
 */
function money(float|int|string|null $value): string
{
    return 'R$ ' . number_format((float) $value, 2, ',', '.');
}

/**
 * Redireciona o usuário e encerra a execução.
 */
function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

/**
 * Retorna a primeira letra de um nome (com suporte a UTF-8).
 */
function inicial(string $nome): string
{
    $nome = trim($nome);

    if ($nome === '') {
        return '?';
    }

    if (preg_match('/^./u', $nome, $match) === 1) {
        return strtoupper($match[0]);
    }

    return strtoupper(substr($nome, 0, 1));
}

/**
 * Calcula o preço original a partir do valor promocional e do percentual.
 * O percentual é uma fração: 0.20 = 20%.
 */
function preco_original(float $valor, ?float $percentual): ?float
{
    if ($percentual === null || $percentual <= 0 || $percentual >= 1) {
        return null;
    }

    return $valor / (1 - $percentual);
}

/**
 * Remove todos os caracteres que não são dígitos.
 */
function somente_digitos(string $value): string
{
    return preg_replace('/\D+/', '', $value) ?? '';
}

/**
 * Indica se ao menos um dos valores informados foi preenchido.
 */
function algum_preenchido(array $values): bool
{
    foreach ($values as $value) {
        if (is_string($value) ? trim($value) !== '' : $value !== null) {
            return true;
        }
    }

    return false;
}
