<?php

require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../repositories/produto_repository.php';
require_once __DIR__ . '/../repositories/favorito_repository.php';

class CategoriaController
{
    private ProdutoRepository $repository;
    private FavoritoRepository $favoritos;

    public function __construct(private PDO $pdo)
    {
        $this->repository = new ProdutoRepository($pdo);
        $this->favoritos = new FavoritoRepository($pdo);
    }

    public function index(): void
    {
        $categoria = isset($_GET['categoria'])
            ? trim((string) $_GET['categoria'])
            : null;
        $busca = isset($_GET['busca'])
            ? trim((string) $_GET['busca'])
            : null;
        $somentePromocoes = isset($_GET['promocoes'])
            && $_GET['promocoes'] !== '0'
            && $_GET['promocoes'] !== '';

        $categoria = $categoria === '' ? null : $categoria;
        $busca = $busca === '' ? null : $busca;

        if ($somentePromocoes) {
            $titulo = 'Promoções';
        } elseif ($busca !== null) {
            $titulo = 'Busca por “' . $busca . '”';
        } elseif ($categoria !== null) {
            $titulo = $categoria;
        } else {
            $titulo = 'Catálogo';
        }

        $data = [
            'produtos' => $this->repository->listar($categoria, $busca, $somentePromocoes, 60),
            'categoria' => $categoria,
            'busca' => $busca,
            'somentePromocoes' => $somentePromocoes,
            'titulo' => $titulo,
            'favoritosIds' => Auth::check()
                ? $this->favoritos->ids((int) Auth::user()['id_usuario'])
                : [],
            'estaLogado' => Auth::check(),
            'usuario' => Auth::user(),
            'contaHref' => Auth::check() ? '/conta' : '/login',
            'flashes' => Auth::takeFlashes(),
        ];

        require __DIR__ . '/../views/categoria.php';
    }
}
