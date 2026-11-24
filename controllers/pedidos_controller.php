<?php

require_once __DIR__ . '/../core/sessao.php';
require_once __DIR__ . '/../core/comprador.php';
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
        $usuarioId = $this->exigirLogin();
        $comprador = Comprador::atual();
        $resumo = $this->carrinho->resumoDe($comprador);
        $favoritosIds = $this->favoritos->ids($comprador);

        $data = [
            'pedidos' => $this->pedidos->listarDoUsuario($usuarioId),
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
        $usuarioId = $this->exigirLogin();

        $numero = filter_var($_GET['numero'] ?? null, FILTER_VALIDATE_INT);

        if ($numero === false || $numero <= 0) {
            $this->naoEncontrado();
            return;
        }

        $pedido = $this->pedidos->buscarPorNumeroDoUsuario($usuarioId, $numero);

        if ($pedido === null) {
            $this->naoEncontrado();
            return;
        }

        $comprador = Comprador::atual();
        $resumo = $this->carrinho->resumoDe($comprador);
        $favoritosIds = $this->favoritos->ids($comprador);

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

    private function exigirLogin(): int
    {
        $usuarioId = Sessao::id();

        if ($usuarioId === null) {
            Sessao::definirDestinoAposLogin('/pedidos');
            Sessao::flash('info', 'Entre na sua conta para ver seus pedidos.');
            Sessao::redirecionar('/login');
        }

        return $usuarioId;
    }

    private function naoEncontrado(): void
    {
        http_response_code(404);
        require __DIR__ . '/../views/404.php';
    }
}
