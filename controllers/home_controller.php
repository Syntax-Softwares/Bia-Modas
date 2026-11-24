<?php

require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/sessao.php';
require_once __DIR__ . '/../core/comprador.php';
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
        $comprador = Comprador::atual();
        $favoritosIds = $this->favoritos->ids($comprador);
        $resumo = $this->carrinho->resumoDe($comprador);

        $usuario = Auth::user();
        $novidades = $this->repository->getNovidades(8);
        $promocoes = $this->repository->getPromocoes(8);

        [$paraVoce, $tituloParaVoce, $subtituloParaVoce] = $this->montarRecomendacoes(
            $usuario !== null ? (int) $usuario['id_usuario'] : null,
            $favoritosIds,
            $novidades
        );

        $data = [
            'categoriasHome' => $this->repository->categoriasComImagem(),
            'novidades' => $novidades,
            'promocoes' => $promocoes,
            'paraVoce' => $paraVoce,
            'tituloParaVoce' => $tituloParaVoce,
            'subtituloParaVoce' => $subtituloParaVoce,
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

    /**
     * Monta a secao "Para Voce" com tres niveis de fallback:
     * favoritos do usuario -> mais favoritados da loja -> novidades.
     *
     * @param array<int, int> $favoritosIds
     * @param array<int, array<string, mixed>> $novidades
     * @return array{0: array<int, array<string, mixed>>, 1: string, 2: string}
     */
    private function montarRecomendacoes(?int $usuarioId, array $favoritosIds, array $novidades): array
    {
        if ($usuarioId !== null && $favoritosIds !== []) {
            $paraVoce = $this->repository->getParaVoce($usuarioId, 8);

            if ($paraVoce !== []) {
                return [
                    $paraVoce,
                    'Para Você',
                    'Selecionados a partir das peças que você favoritou',
                ];
            }
        }

        $maisDesejados = $this->repository->getMaisDesejados(8);

        if ($maisDesejados !== []) {
            return [
                $maisDesejados,
                'Mais desejados',
                'As peças que a loja mais está salvando',
            ];
        }

        return [
            $novidades,
            'Novidades',
            'O que acabou de chegar na loja',
        ];
    }
}
