<?php

require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../repositories/produto_repository.php';
require_once __DIR__ . '/../repositories/favorito_repository.php';

class HomeController
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
        $usuario = Auth::user();
        $favoritosIds = $usuario !== null
            ? $this->favoritos->ids((int) $usuario['id_usuario'])
            : [];

        [$paraVoce, $tituloParaVoce, $subtituloParaVoce] = $this->montarRecomendacoes(
            $usuario !== null ? (int) $usuario['id_usuario'] : null,
            $favoritosIds
        );

        $data = [
            'novidades' => $this->repository->getNovidades(8),
            'promocoes' => $this->repository->getPromocoes(8),
            'paraVoce' => $paraVoce,
            'tituloParaVoce' => $tituloParaVoce,
            'subtituloParaVoce' => $subtituloParaVoce,
            'favoritosIds' => $favoritosIds,
            'estaLogado' => Auth::check(),
            'usuario' => $usuario,
            'contaHref' => Auth::check() ? '/conta' : '/login',
            'flashes' => Auth::takeFlashes(),
        ];

        require __DIR__ . '/../views/index.php';
    }

    /**
     * Monta a seção "Para Você" com três níveis de fallback:
     * favoritos do usuário -> mais favoritados da loja -> novidades.
     *
     * @param array<int, int> $favoritosIds
     * @return array{0: array<int, array<string, mixed>>, 1: string, 2: string}
     */
    private function montarRecomendacoes(?int $usuarioId, array $favoritosIds): array
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
            $this->repository->getNovidades(8),
            'Novidades',
            'O que acabou de chegar na loja',
        ];
    }
}
