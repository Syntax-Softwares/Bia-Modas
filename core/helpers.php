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
