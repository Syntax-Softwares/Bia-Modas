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

/**
 * Lê um inteiro enviado por POST. Devolve null quando ausente ou inválido.
 */
function post_int(string $campo): ?int
{
    $valor = filter_input(INPUT_POST, $campo, FILTER_VALIDATE_INT);

    return $valor === false || $valor === null ? null : (int) $valor;
}

/**
 * Encerra a requisição devolvendo JSON.
 */
function json_response(array $dados, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Resposta JSON de erro, no formato esperado pelo JS (`{ ok: false, mensagem }`).
 */
function json_erro(string $mensagem, int $status = 422): never
{
    json_response(['ok' => false, 'mensagem' => $mensagem], $status);
}

/**
 * Desenha 5 estrelas a partir de uma nota (aceita decimais: 4.3 -> 4 cheias
 * e 1 pela metade).
 */
function estrelas(float $nota, string $classe = ''): string
{
    $nota = max(0.0, min(5.0, $nota));
    $cheias = (int) floor($nota);
    $resto = $nota - $cheias;

    $meia = $resto >= 0.25 && $resto < 0.75;
    if ($resto >= 0.75) {
        $cheias++;
        $meia = false;
    }

    $html = '<span class="estrelas' . ($classe !== '' ? ' ' . e($classe) : '') . '" aria-hidden="true">';

    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $cheias) {
            $icone = 'bi-star-fill';
        } elseif ($meia && $i === $cheias + 1) {
            $icone = 'bi-star-half';
        } else {
            $icone = 'bi-star';
        }

        $html .= '<i class="bi ' . $icone . '"></i>';
    }

    return $html . '</span>';
}
