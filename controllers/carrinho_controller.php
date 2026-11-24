<?php

require_once __DIR__ . '/../core/sessao.php';
require_once __DIR__ . '/../core/comprador.php';
require_once __DIR__ . '/../repositories/favorito_repository.php';
require_once __DIR__ . '/../repositories/carrinho_repository.php';

class CarrinhoController
{
    private CarrinhoRepository $carrinho;
    private FavoritoRepository $favoritos;

    public function __construct(private PDO $pdo)
    {
        $this->carrinho = new CarrinhoRepository($pdo);
        $this->favoritos = new FavoritoRepository($pdo);
    }

    public function index(): void
    {
        $comprador = Comprador::atual();
        $favoritosIds = $this->favoritos->ids($comprador);
        $resumo = $this->carrinho->resumoDe($comprador);

        $data = [
            'resumo' => $resumo,
            'favoritosIds' => $favoritosIds,
            'favoritosTotal' => count($favoritosIds),
            'quantidadeCarrinho' => $resumo['quantidade'],
            'flashes' => Sessao::flashes(),
        ];

        require __DIR__ . '/../views/carrinho.php';
    }

    public function dados(): void
    {
        json_response($this->resumoAtual());
    }

    public function alternar(): void
    {
        $this->exigirCsrf();

        $idProdutoTamanho = post_int('produto_tamanho_id');
        if ($idProdutoTamanho === null || $idProdutoTamanho <= 0) {
            json_erro('Tamanho do produto inválido.');
        }

        $resultado = $this->carrinho->alternar($this->carrinhoId(), $idProdutoTamanho);

        if (!$resultado['ok']) {
            json_erro($resultado['mensagem'] ?? 'Não foi possível atualizar o carrinho.');
        }

        $adicionado = $resultado['acao'] === 'adicionado';

        json_response([
            'ok' => true,
            'acao' => $resultado['acao'],
            'produto_tamanho_id' => $idProdutoTamanho,
            'noCarrinho' => $adicionado,
            'mensagem' => $adicionado
                ? 'Produto adicionado ao carrinho'
                : 'Produto removido do carrinho',
        ] + $this->resumoAtual());
    }

    public function adicionar(): void
    {
        $this->exigirCsrf();

        $idProdutoTamanho = post_int('produto_tamanho_id');
        $quantidade = post_int('quantidade') ?? 1;

        if ($idProdutoTamanho === null || $idProdutoTamanho <= 0) {
            json_erro('Selecione um tamanho antes de adicionar ao carrinho.');
        }
        if ($quantidade <= 0) {
            json_erro('Quantidade inválida.');
        }

        $resultado = $this->carrinho->adicionar($this->carrinhoId(), $idProdutoTamanho, $quantidade);

        if (!$resultado['ok']) {
            json_erro($resultado['mensagem'] ?? 'Não foi possível adicionar ao carrinho.');
        }

        json_response([
            'ok' => true,
            'mensagem' => 'Produto adicionado ao carrinho',
        ] + $this->resumoAtual());
    }

    public function atualizar(): void
    {
        $this->exigirCsrf();

        $idProdutoTamanho = post_int('produto_tamanho_id');
        $quantidade = post_int('quantidade');

        if ($idProdutoTamanho === null || $idProdutoTamanho <= 0) {
            json_erro('Tamanho do produto inválido.');
        }
        if ($quantidade === null) {
            json_erro('Quantidade inválida.');
        }

        $resultado = $this->carrinho->definirQuantidade($this->carrinhoId(), $idProdutoTamanho, $quantidade);

        if (!$resultado['ok']) {
            json_erro($resultado['mensagem'] ?? 'Não foi possível atualizar a quantidade.');
        }

        json_response(['ok' => true] + $this->resumoAtual());
    }

    public function remover(): void
    {
        $this->exigirCsrf();

        $idProdutoTamanho = post_int('produto_tamanho_id');
        if ($idProdutoTamanho === null || $idProdutoTamanho <= 0) {
            json_erro('Tamanho do produto inválido.');
        }

        $this->carrinho->remover($this->carrinhoId(), $idProdutoTamanho);

        json_response(['ok' => true, 'mensagem' => 'Produto removido do carrinho'] + $this->resumoAtual());
    }

    private function carrinhoId(): int
    {
        return $this->carrinho->obter(Comprador::atual());
    }

    private function resumoAtual(): array
    {
        return ['ok' => true] + $this->carrinho->resumoDe(Comprador::atual());
    }

    private function exigirCsrf(): void
    {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['_csrf'] ?? null);

        if (!Sessao::validarCsrf(is_string($token) ? $token : null)) {
            json_erro('Sessão expirada. Recarregue a página e tente novamente.', 403);
        }
    }
}
