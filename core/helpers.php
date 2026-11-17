<?php

function e(mixed $valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

function money(mixed $valor): string
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

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

function redirecionar(string $url): never
{
    header('Location: ' . $url);
    exit;
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
