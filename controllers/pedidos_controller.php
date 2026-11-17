<?php

require_once __DIR__ . '/../core/sessao.php';
require_once __DIR__ . '/../repositories/carrinho_repository.php';
require_once __DIR__ . '/../repositories/favorito_repository.php';
require_once __DIR__ . '/../repositories/pedido_repository.php';

class PedidosController
{
    private CarrinhoRepository $carrinho;
    private FavoritoRepository $favoritos;
    private PedidoRepository $pedidos;

    public function __construct(private PDO $pdo)
    {
        $this->carrinho = new CarrinhoRepository($pdo);
        $this->favoritos = new FavoritoRepository($pdo);
        $this->pedidos = new PedidoRepository($pdo);
    }

    public function index(): void
    {
        $visitante = Sessao::visitanteId();
        $resumo = $this->carrinho->resumoPorVisitante($visitante);
        $favoritosIds = $this->favoritos->ids($visitante);

        $data = [
            'pedidos' => $this->pedidos->listarPorVisitante($visitante),
            'resumo' => $resumo,
            'favoritosIds' => $favoritosIds,
            'favoritosTotal' => count($favoritosIds),
            'carrinhoIds' => array_column($resumo['itens'], 'produto_id'),
            'quantidadeCarrinho' => $resumo['quantidade'],
            'flashes' => Sessao::flashes(),
        ];

        require __DIR__ . '/../views/pedidos.php';
    }

    public function ver(): void
    {
        $numero = filter_var($_GET['numero'] ?? null, FILTER_VALIDATE_INT);

        if ($numero === false || $numero <= 0) {
            $this->naoEncontrado();
            return;
        }

        $visitante = Sessao::visitanteId();
        $pedido = $this->pedidos->buscarPorNumero($visitante, $numero);

        if ($pedido === null) {
            $this->naoEncontrado();
            return;
        }

        $resumo = $this->carrinho->resumoPorVisitante($visitante);
        $favoritosIds = $this->favoritos->ids($visitante);

        $data = [
            'pedido' => $pedido,
            'itens' => $this->pedidos->itensDaCompra((int) $pedido['id_compra']),
            'resumo' => $resumo,
            'favoritosIds' => $favoritosIds,
            'favoritosTotal' => count($favoritosIds),
            'carrinhoIds' => array_column($resumo['itens'], 'produto_id'),
            'quantidadeCarrinho' => $resumo['quantidade'],
        ];

        require __DIR__ . '/../views/pedido.php';
    }

    private function naoEncontrado(): void
    {
        http_response_code(404);
        require __DIR__ . '/../views/404.php';
    }
}
