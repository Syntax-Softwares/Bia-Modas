<?php

/**
 * Helpers globais compartilhados por todas as areas da aplicacao.
 *
 * Reune os helpers do MVC05 (home/auth), MVC06/07 (perfil/catalogo) e
 * MVC08/09 (carrinho/checkout), mantendo os nomes usados por cada modulo.
 */

/**
 * Escapa um valor para exibicao segura em HTML.
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Formata um valor monetario no padrao brasileiro.
 */
function money(float|int|string|null $value): string
{
    return 'R$ ' . number_format((float) $value, 2, ',', '.');
}

/**
 * Redireciona o usuario e encerra a execucao.
 */
function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

/**
 * Alias usado pelos modulos de carrinho/checkout.
 */
function redirecionar(string $url): never
{
    redirect($url);
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
 * Calcula o preco original a partir do valor promocional e do percentual.
 * O percentual e uma fracao: 0.20 = 20%.
 */
function preco_original(float $valor, ?float $percentual): ?float
{
    if ($percentual === null || $percentual <= 0 || $percentual >= 1) {
        return null;
    }

    return $valor / (1 - $percentual);
}

/**
 * Remove todos os caracteres que nao sao digitos.
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
 * Acrescenta a versao do arquivo ao caminho de um asset (cache busting).
 */
function asset(string $caminho): string
{
    $arquivo = __DIR__ . '/../public/' . ltrim($caminho, '/');
    $versao = is_file($arquivo) ? filemtime($arquivo) : false;

    return $versao === false ? $caminho : $caminho . '?v=' . $versao;
}

function json_response(array $dados, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_erro(string $mensagem, int $status = 422): never
{
    json_response(['ok' => false, 'mensagem' => $mensagem], $status);
}

function post_int(string $campo): ?int
{
    $valor = filter_var($_POST[$campo] ?? null, FILTER_VALIDATE_INT);

    return $valor === false ? null : $valor;
}

function data_hora(?string $valor): string
{
    if ($valor === null || $valor === '') {
        return '';
    }

    $timestamp = strtotime($valor);

    return $timestamp === false ? '' : date('d/m/Y H:i', $timestamp);
}

function status_pagamento(?string $status): array
{
    return match ($status) {
        'pago' => ['rotulo' => 'Pagamento aprovado', 'classe' => 'pago'],
        'cancelado' => ['rotulo' => 'Pagamento cancelado', 'classe' => 'cancelado'],
        default => ['rotulo' => 'Aguardando pagamento', 'classe' => 'pendente'],
    };
}

function rotulo_pagamento(?string $metodo): string
{
    return match ($metodo) {
        'pix' => 'PIX',
        'cartao' => 'Cartão de crédito',
        'boleto' => 'Boleto',
        default => 'Não informado',
    };
}

function formatar_telefone(?string $telefone): string
{
    $digitos = preg_replace('/\D+/', '', (string) $telefone);

    if (strlen($digitos) === 11) {
        return '(' . substr($digitos, 0, 2) . ') ' . substr($digitos, 2, 5) . '-' . substr($digitos, 7);
    }

    if (strlen($digitos) === 10) {
        return '(' . substr($digitos, 0, 2) . ') ' . substr($digitos, 2, 4) . '-' . substr($digitos, 6);
    }

    return (string) $telefone;
}

function tamanho_texto(string $valor): int
{
    return function_exists('mb_strlen') ? mb_strlen($valor, 'UTF-8') : strlen($valor);
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

    $html = '<span class="estrelas' . ($classe !== '' ? ' ' . e($classe) : '') . '" role="img" aria-label="'
        . e(number_format($nota, 1, ',', '.')) . ' de 5 estrelas">';

    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $cheias) {
            $estado = 'estrela-cheia';
            $glifo = '★';
        } elseif ($meia && $i === $cheias + 1) {
            $estado = 'estrela-meia';
            $glifo = '★';
        } else {
            $estado = 'estrela-vazia';
            $glifo = '☆';
        }

        $html .= '<span class="' . $estado . '" aria-hidden="true">' . $glifo . '</span>';
    }

    return $html . '</span>';
}
