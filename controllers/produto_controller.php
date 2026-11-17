<?php

require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../repositories/produto_repository.php';
require_once __DIR__ . '/../repositories/avaliacao_repository.php';
require_once __DIR__ . '/../repositories/favorito_repository.php';

class ProdutoController
{
    private ProdutoRepository $repository;
    private AvaliacaoRepository $avaliacoes;
    private FavoritoRepository $favoritos;

    public function __construct(private PDO $pdo)
    {
        $this->repository = new ProdutoRepository($pdo);
        $this->avaliacoes = new AvaliacaoRepository($pdo);
        $this->favoritos = new FavoritoRepository($pdo);
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

        $produtoId = (int) $produto['id_produto'];
        $usuario = Auth::user();
        $usuarioId = $usuario !== null ? (int) $usuario['id_usuario'] : null;

        $data = [
            'produto' => $produto,
            'avaliacoes' => $this->avaliacoes->listarPorProduto($produtoId),
            'resumoAvaliacoes' => $this->avaliacoes->resumo($produtoId),
            'avaliacaoDoUsuario' => $usuarioId !== null
                ? $this->avaliacoes->doUsuario($produtoId, $usuarioId)
                : null,
            'relacionados' => $this->repository->getRelacionados($produtoId),
            'favoritosIds' => $usuarioId !== null
                ? $this->favoritos->ids($usuarioId)
                : [],
            'estaLogado' => Auth::check(),
            'usuario' => $usuario,
            'contaHref' => Auth::check() ? '/conta' : '/login',
            'flashes' => Auth::takeFlashes(),
            'errors' => Auth::takeErrors(),
            'old' => Auth::takeOld(),
        ];

        require __DIR__ . '/../views/produto.php';
    }
}
