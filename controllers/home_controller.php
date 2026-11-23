<?php

require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/sessao.php';
require_once __DIR__ . '/../repositories/produto_repository.php';
require_once __DIR__ . '/../repositories/favorito_repository.php';
require_once __DIR__ . '/../repositories/carrinho_repository.php';

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

        $usuario = Auth::user();

        $data = [
            'categoriasHome' => $this->repository->categoriasComImagem(),
            'novidades' => $this->repository->getNovidades(8),
            'promocoes' => $this->repository->getPromocoes(8),
            'favoritosIds' => $favoritosIds,
            'favoritosTotal' => count($favoritosIds),
            'resumo' => $resumo,
            'carrinhoIds' => array_column($resumo['itens'], 'produto_id'),
            'quantidadeCarrinho' => $resumo['quantidade'],
            'estaLogado' => $usuario !== null,
            'usuario' => $usuario,
            'contaHref' => $usuario !== null ? '/perfil' : '/login',
            'flashes' => Auth::takeFlashes(),
        ];

        require __DIR__ . '/../views/index.php';
    }
}
