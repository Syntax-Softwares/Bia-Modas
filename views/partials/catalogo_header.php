<?php
// Cabecalho unificado da loja para as paginas do catalogo.
$busca = (string) ($buscaAtual ?? '');
$sugestoes = is_array($sugestoes ?? null) ? $sugestoes : [];
$contaHref = Sessao::usuario() !== null ? '/perfil' : '/login';
require __DIR__ . '/site_header.php';
