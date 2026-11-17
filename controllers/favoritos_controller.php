<?php

require_once __DIR__ . '/../core/sessao.php';
require_once __DIR__ . '/../repositories/produto_repository.php';
require_once __DIR__ . '/../repositories/favorito_repository.php';
require_once __DIR__ . '/../repositories/carrinho_repository.php';

class FavoritosController
{
    private ProdutoRepository $produtos;
    private FavoritoRepository $favoritos;
    private CarrinhoRepository $carrinho;

    public function __construct(private PDO $pdo)
    {
        $this->produtos = new ProdutoRepository($pdo);
        $this->favoritos = new FavoritoRepository($pdo);
        $this->carrinho = new CarrinhoRepository($pdo);
    }

    public function index(): void
    {
        $visitante = Sessao::visitanteId();
        $favoritosIds = $this->favoritos->ids($visitante);
        $resumo = $this->carrinho->resumoPorVisitante($visitante);

        $produtos = [];
        $mapa = $this->produtos->buscarPorIds($favoritosIds);
        foreach ($favoritosIds as $idProduto) {
            if (isset($mapa[$idProduto])) {
                $produtos[] = $mapa[$idProduto];
            }
        }

        $data = [
            'produtos' => $produtos,
            'favoritosIds' => $favoritosIds,
            'favoritosTotal' => count($favoritosIds),
            'resumo' => $resumo,
            'carrinhoIds' => array_column($resumo['itens'], 'produto_id'),
            'quantidadeCarrinho' => $resumo['quantidade'],
            'flashes' => Sessao::flashes(),
        ];

        require __DIR__ . '/../views/favoritos.php';
    }

    public function alternar(): void
    {
        $this->exigirCsrf();

        $idProduto = post_int('produto_id');
        if ($idProduto === null || $idProduto <= 0) {
            json_erro('Produto inválido.');
        }

        $resultado = $this->favoritos->alternar(Sessao::visitanteId(), $idProduto);

        if (!$resultado['ok']) {
            json_erro($resultado['mensagem'] ?? 'Não foi possível atualizar o favorito.');
        }

        json_response([
            'ok' => true,
            'favorito' => $resultado['favorito'],
            'total' => $resultado['total'],
            'mensagem' => $resultado['favorito']
                ? 'Produto adicionado aos favoritos'
                : 'Produto removido dos favoritos',
        ]);
    }

    public function dados(): void
    {
        $visitante = Sessao::visitanteId();
        $ids = $this->favoritos->ids($visitante);

        json_response([
            'ok' => true,
            'ids' => $ids,
            'total' => count($ids),
        ]);
    }

    private function exigirCsrf(): void
    {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['_csrf'] ?? null);

        if (!Sessao::validarCsrf(is_string($token) ? $token : null)) {
            json_erro('Sessão expirada. Recarregue a página e tente novamente.', 419);
        }
    }
}
