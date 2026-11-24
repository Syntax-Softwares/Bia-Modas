<?php

require_once __DIR__ . '/../core/sessao.php';
require_once __DIR__ . '/../core/comprador.php';
require_once __DIR__ . '/../core/html.php';
require_once __DIR__ . '/../core/catalogo.php';
require_once __DIR__ . '/../core/rate_limit.php';
require_once __DIR__ . '/../repositories/catalogo_repository.php';
require_once __DIR__ . '/../repositories/favorito_repository.php';
require_once __DIR__ . '/../repositories/carrinho_repository.php';

/**
 * MVC07 - catalogo, busca, filtros e detalhe do produto.
 */
final class CatalogoController
{
    private const POR_PAGINA = 12;
    private const LIMITE_GET = 180;

    private CatalogoRepository $catalogo;
    private FavoritoRepository $favoritos;
    private CarrinhoRepository $carrinho;

    public function __construct(private PDO $pdo)
    {
        $this->catalogo = new CatalogoRepository($pdo);
        $this->favoritos = new FavoritoRepository($pdo);
        $this->carrinho = new CarrinhoRepository($pdo);
    }

    public function index(): void
    {
        Sessao::cabecalhosSeguranca();
        $this->limitarGet();

        $categorias = $this->catalogo->categoriasComContagem();
        $tamanhosDisponiveis = $this->catalogo->tamanhosDisponiveis();
        $coresDisponiveis = $this->catalogo->coresDisponiveis();
        $estilosDisponiveis = $this->catalogo->estilosDisponiveis();

        $busca = $this->texto($_GET['busca'] ?? '', 100);
        $categoria = $this->texto($_GET['categoria'] ?? '', 255);

        $tamanhos = $this->listaSelecionada(
            $_GET['tamanhos'] ?? [],
            array_map(static fn (string $t): string => $t, $tamanhosDisponiveis)
        );
        $cores = $this->listaSelecionada(
            $_GET['cores'] ?? [],
            array_map(static fn (array $c): string => $c['cor'], $coresDisponiveis)
        );
        $estilos = $this->listaSelecionada(
            $_GET['estilos'] ?? [],
            array_map(static fn (array $e): string => $e['estilo'], $estilosDisponiveis)
        );

        [$precoMin, $precoMax] = $this->precos(
            $_GET['preco_min'] ?? null,
            $_GET['preco_max'] ?? null
        );

        $promocoes = filter_var($_GET['promocoes'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $ordenacao = (string) ($_GET['ordenacao'] ?? 'relevancia');
        $ordenacao = in_array($ordenacao, ['relevancia', 'menor-preco', 'maior-preco', 'nome'], true)
            ? $ordenacao
            : 'relevancia';

        $pagina = filter_var(
            $_GET['pagina'] ?? 1,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'default' => 1]]
        );
        $pagina = is_int($pagina) && $pagina > 0 ? $pagina : 1;

        $filtros = [
            'busca' => $busca !== '' ? $busca : null,
            'categoria' => $categoria !== '' ? $categoria : null,
            'tamanhos' => $tamanhos,
            'cores' => $cores,
            'estilos' => $estilos,
            'preco_min' => $precoMin,
            'preco_max' => $precoMax,
            'promocoes' => $promocoes ? 1 : null,
            'ordenacao' => $ordenacao !== 'relevancia' ? $ordenacao : null,
        ];

        $resultado = $this->catalogo->listar($filtros, $pagina, self::POR_PAGINA);

        if ($busca !== '') {
            $titulo = 'Resultados para "' . $busca . '"';
        } elseif ($promocoes) {
            $titulo = 'Promoções';
        } elseif ($categoria !== '') {
            $titulo = $categoria;
        } else {
            $titulo = 'Todos os Produtos';
        }

        $sugestoes = $this->catalogo->sugestoes();
        $totalProdutos = $this->catalogo->contarAtivos();
        $flash = Sessao::mensagensFlash();

        $comprador = Comprador::atual();
        $favoritosIds = $this->favoritos->ids($comprador);
        $resumo = $this->carrinho->resumoDe($comprador);
        $carrinhoIds = array_column($resumo['itens'], 'produto_id');
        $favoritosTotal = count($favoritosIds);
        $quantidadeCarrinho = $resumo['quantidade'];

        require __DIR__ . '/../views/catalogo.php';
    }

    public function produto(): void
    {
        Sessao::cabecalhosSeguranca();
        $this->limitarGet();

        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

        if (!is_int($id) || $id <= 0) {
            $this->erro(404, 'Produto não encontrado', 'O produto informado não existe ou foi removido.', null, '/categoria');
        }

        $produto = $this->catalogo->buscarPorId($id);

        if ($produto === null) {
            $this->erro(404, 'Produto não encontrado', 'O produto informado não existe ou foi removido.', null, '/categoria');
        }

        $imagens = $this->catalogo->imagensDoProduto($id);
        $fotoId = filter_input(INPUT_GET, 'foto', FILTER_VALIDATE_INT);
        $fotoSelecionada = null;

        if (is_int($fotoId) && $fotoId > 0) {
            foreach ($imagens as $imagem) {
                if ((int) $imagem['id_imagem'] === $fotoId) {
                    $fotoSelecionada = $imagem;
                    break;
                }
            }
        }

        if ($fotoSelecionada === null && $imagens !== []) {
            $fotoSelecionada = $imagens[0];
        }

        $sugestoes = $this->catalogo->sugestoes();
        $flash = Sessao::mensagensFlash();

        $comprador = Comprador::atual();
        $favoritosIds = $this->favoritos->ids($comprador);
        $resumo = $this->carrinho->resumoDe($comprador);
        $carrinhoIds = array_column($resumo['itens'], 'produto_id');
        $favoritosTotal = count($favoritosIds);
        $quantidadeCarrinho = $resumo['quantidade'];

        require __DIR__ . '/../views/produto_detalhe.php';
    }

    private function limitarGet(): void
    {
        if (!RateLimit::consumir('catalogo_get', RateLimit::ip(), self::LIMITE_GET, 60)) {
            $this->erro(
                429,
                'Muitas requisições',
                'Aguarde um instante antes de navegar novamente.',
                60,
                '/categoria'
            );
        }
    }

    private function texto(mixed $valor, int $maximo): string
    {
        $texto = (string) $valor;
        $texto = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $texto) ?? '';
        $texto = trim($texto);

        if ($texto === '') {
            return '';
        }

        if (preg_match('/^.{0,' . $maximo . '}/us', $texto, $achado) === 1) {
            return $achado[0];
        }

        return substr($texto, 0, $maximo);
    }

    /**
     * @param array<int, string> $permitidos
     * @return array<int, string>
     */
    private function listaSelecionada(mixed $valor, array $permitidos): array
    {
        $valores = is_array($valor) ? $valor : [$valor];
        $selecionados = [];

        foreach ($valores as $item) {
            if (!is_string($item) && !is_numeric($item)) {
                continue;
            }

            $item = trim((string) $item);

            if ($item !== '' && in_array($item, $permitidos, true)) {
                $selecionados[] = $item;
            }
        }

        return array_values(array_unique($selecionados));
    }

    /**
     * @return array{0: ?float, 1: ?float}
     */
    private function precos(mixed $minimo, mixed $maximo): array
    {
        $min = $this->decimal($minimo);
        $max = $this->decimal($maximo);

        if ($min !== null && $max !== null && $min > $max) {
            [$min, $max] = [$max, $min];
        }

        return [$min, $max];
    }

    private function decimal(mixed $valor): ?float
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        $valor = str_replace(',', '.', (string) $valor);

        if (!is_numeric($valor)) {
            return null;
        }

        $numero = (float) $valor;

        if ($numero < 0 || $numero > 1000000) {
            return null;
        }

        return round($numero, 2);
    }

    private function erro(
        int $codigo,
        string $titulo,
        string $mensagem,
        ?int $retryAfter,
        string $voltar
    ): never {
        http_response_code($codigo);

        if ($retryAfter !== null) {
            header('Retry-After: ' . $retryAfter);
        }

        require __DIR__ . '/../views/erro.php';
        exit;
    }
}
