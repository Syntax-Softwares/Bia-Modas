<?php

require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../repositories/produto_repository.php';

class ProdutoController
{
    private ProdutoRepository $repository;

    public function __construct(private PDO $pdo)
    {
        $this->repository = new ProdutoRepository($pdo);
    }

    public function show(): void
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $produto = $id !== false && $id !== null
            ? $this->repository->getPorId((int) $id)
            : null;

        if ($produto === null) {
            http_response_code(404);
            require __DIR__ . '/../views/404.php';
            return;
        }

        $data = [
            'produto' => $produto,
            'estaLogado' => Auth::check(),
            'usuario' => Auth::user(),
            'contaHref' => Auth::check() ? '/conta' : '/login',
            'flashes' => Auth::takeFlashes(),
        ];

        require __DIR__ . '/../views/produto.php';
    }
}
