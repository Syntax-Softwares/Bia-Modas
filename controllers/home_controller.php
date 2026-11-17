<?php

require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../repositories/produto_repository.php';

class HomeController
{
    private ProdutoRepository $repository;

    public function __construct(private PDO $pdo)
    {
        $this->repository = new ProdutoRepository($pdo);
    }

    public function index(): void
    {
        $data = [
            'novidades' => $this->repository->getNovidades(8),
            'promocoes' => $this->repository->getPromocoes(8),
            'estaLogado' => Auth::check(),
            'usuario' => Auth::user(),
            'contaHref' => Auth::check() ? '/conta' : '/login',
            'flashes' => Auth::takeFlashes(),
        ];

        require __DIR__ . '/../views/index.php';
    }
}
