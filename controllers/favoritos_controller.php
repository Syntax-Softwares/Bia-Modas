<?php

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../repositories/favorito_repository.php';
require_once __DIR__ . '/../repositories/produto_repository.php';

class FavoritosController
{
    private FavoritoRepository $favoritos;
    private ProdutoRepository $produtos;

    public function __construct(private PDO $pdo)
    {
        $this->favoritos = new FavoritoRepository($pdo);
        $this->produtos = new ProdutoRepository($pdo);
    }

    public function index(): void
    {
        $usuario = Auth::user();

        if ($usuario === null) {
            Auth::flash('warning', 'Entre na sua conta para ver seus favoritos.');
            redirect('/login');
        }

        $ids = $this->favoritos->ids((int) $usuario['id_usuario']);

        $porId = [];
        foreach ($this->produtos->getPorIds($ids) as $produto) {
            $porId[(int) $produto['id_produto']] = $produto;
        }

        $produtos = [];
        foreach ($ids as $id) {
            if (isset($porId[$id])) {
                $produtos[] = $porId[$id];
            }
        }

        $data = [
            'produtos' => $produtos,
            'favoritosIds' => $ids,
            'estaLogado' => true,
            'usuario' => $usuario,
            'contaHref' => '/conta',
            'flashes' => Auth::takeFlashes(),
        ];

        require __DIR__ . '/../views/favoritos.php';
    }

    /**
     * Alterna o favorito via fetch e responde JSON.
     */
    public function alternar(): void
    {
        $usuario = Auth::user();

        if ($usuario === null) {
            json_erro('Entre na sua conta para salvar favoritos.', 401);
        }

        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['_csrf'] ?? null);

        if (!Auth::validCsrf(is_string($token) ? $token : null)) {
            json_erro('Sessão expirada. Recarregue a página e tente novamente.', 403);
        }

        $produtoId = post_int('produto_id');

        if ($produtoId === null || $produtoId <= 0) {
            json_erro('Produto inválido.');
        }

        $resultado = $this->favoritos->alternar((int) $usuario['id_usuario'], $produtoId);

        if (!$resultado['ok']) {
            json_erro($resultado['mensagem'] ?? 'Não foi possível atualizar o favorito.');
        }

        json_response([
            'ok' => true,
            'favorito' => $resultado['favorito'],
            'total' => $resultado['total'],
            'mensagem' => $resultado['favorito']
                ? 'Produto salvo nos favoritos'
                : 'Produto removido dos favoritos',
        ]);
    }
}
