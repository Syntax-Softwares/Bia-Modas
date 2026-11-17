<?php

require_once __DIR__ . '/../repositories/produto_repository.php';
require_once __DIR__ . '/../repositories/favorito_repository.php';
require_once __DIR__ . '/../repositories/carrinho_repository.php';
require_once __DIR__ . '/../core/sessao.php';

class HomeController
{
    private ProdutoRepository $repository;
    private FavoritoRepository $favoritos;
    private CarrinhoRepository $carrinho;

    public function __construct(private PDO $pdo)
    {
        $this->repository = new ProdutoRepository($pdo);
        $this->favoritos = new FavoritoRepository($pdo);
        $this->carrinho = new CarrinhoRepository($pdo);
    }

    public function index(): void
    {
        $visitante = Sessao::visitanteId();
        $favoritosIds = $this->favoritos->ids($visitante);
        $resumo = $this->carrinho->resumoPorVisitante($visitante);

        $data = [
            'novidades' => $this->repository->get_novidades(),
            'favoritosIds' => $favoritosIds,
            'favoritosTotal' => count($favoritosIds),
            'resumo' => $resumo,
            'carrinhoIds' => array_column($resumo['itens'], 'produto_id'),
            'quantidadeCarrinho' => $resumo['quantidade'],
        ];

        require __DIR__ . '/../views/index.php';
    }
}
