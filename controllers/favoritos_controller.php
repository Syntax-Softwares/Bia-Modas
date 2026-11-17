<?php

require_once __DIR__ . '/../core/auth.php';

class FavoritosController
{
    public function __construct(private PDO $pdo)
    {
    }

    public function index(): void
    {
        $data = [
            'estaLogado' => Auth::check(),
            'usuario' => Auth::user(),
            'contaHref' => Auth::check() ? '/conta' : '/login',
            'flashes' => Auth::takeFlashes(),
        ];

        require __DIR__ . '/../views/favoritos.php';
    }
}
