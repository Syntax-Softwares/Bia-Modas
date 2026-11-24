<?php

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/html.php';
require_once __DIR__ . '/../core/sessao.php';
require_once __DIR__ . '/../core/catalogo.php';
require_once __DIR__ . '/../core/validacao.php';
require_once __DIR__ . '/../core/rate_limit.php';
require_once __DIR__ . '/../core/comprador.php';
require_once __DIR__ . '/../repositories/catalogo_repository.php';
require_once __DIR__ . '/../repositories/favorito_repository.php';
require_once __DIR__ . '/../repositories/carrinho_repository.php';
require_once __DIR__ . '/../repositories/medida_repository.php';

/**
 * MVC10 - provador virtual e medidas do usuario.
 *
 * A tela de provador (front-end da branch de apresentacao) foi integrada ao
 * MVC: o produto vem do banco, o tamanho selecionado e validado, o carrinho e
 * o de verdade e as medidas do usuario autenticado sao persistidas em
 * `medidas` (1:1 com `usuarios`).
 *
 * As fotos capturadas pela camera ficam apenas no navegador: nao ha upload,
 * processamento com IA nem armazenamento de imagem.
 */
final class ProvadorController
{
    private const LIMITE_GET = 120;
    private const LIMITE_POST = 30;

    private CatalogoRepository $catalogo;
    private FavoritoRepository $favoritos;
    private CarrinhoRepository $carrinho;
    private MedidaRepository $medidas;

    public function __construct(private PDO $pdo)
    {
        $this->catalogo = new CatalogoRepository($pdo);
        $this->favoritos = new FavoritoRepository($pdo);
        $this->carrinho = new CarrinhoRepository($pdo);
        $this->medidas = new MedidaRepository($pdo);
    }

    public function index(): void
    {
        // Esta pagina precisa da camera; o restante do site continua bloqueado.
        Sessao::cabecalhosSeguranca(true);

        if (!RateLimit::consumir('provador_get', RateLimit::ip(), self::LIMITE_GET, 60)) {
            $this->erro(
                429,
                'Muitas requisicoes',
                'Aguarde um instante antes de abrir o provador novamente.',
                60,
                '/categoria'
            );
        }

        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

        if (!is_int($id) || $id <= 0) {
            $this->erro(404, 'Produto nao encontrado', 'O produto informado nao existe ou foi removido.', null, '/categoria');
        }

        $produto = $this->catalogo->buscarPorId($id);

        if ($produto === null) {
            $this->erro(404, 'Produto nao encontrado', 'O produto informado nao existe ou foi removido.', null, '/categoria');
        }

        $imagens = $this->catalogo->imagensDoProduto($id);
        $imagem = $imagens[0]['caminho_imagem'] ?? $produto['imagem'];

        $tamanhoId = filter_input(INPUT_GET, 'tamanho', FILTER_VALIDATE_INT);
        $tamanhoSelecionado = null;

        if (is_int($tamanhoId) && $tamanhoId > 0) {
            foreach ($produto['variacoes'] as $variacao) {
                if ((int) $variacao['id_produto_tamanho'] === $tamanhoId) {
                    $tamanhoSelecionado = $variacao;
                    break;
                }
            }
        }

        $usuario = Sessao::usuario();
        $medidas = null;

        if ($usuario !== null) {
            $medidas = $this->medidas->buscarDoUsuario((int) $usuario['id_usuario']);
        }

        // Dados exigidos pelo header/footer unificados da loja.
        $busca = '';
        $sugestoes = $this->catalogo->sugestoes();
        $comprador = Comprador::atual();
        $favoritosTotal = count($this->favoritos->ids($comprador));
        $resumo = $this->carrinho->resumoDe($comprador);
        $quantidadeCarrinho = $resumo['quantidade'];
        $resumoInicial = $resumo;

        $flash = Sessao::mensagensFlash();
        $erros = Sessao::erros();
        $old = Sessao::old();

        require __DIR__ . '/../views/provador_virtual.php';
    }

    public function salvarMedidas(): void
    {
        Sessao::cabecalhosSeguranca(true);

        $usuario = Sessao::usuario();

        if ($usuario === null) {
            Sessao::flash('info', 'Entre na sua conta para salvar suas medidas.');
            Sessao::redirecionar('/login');
        }

        $usuarioId = (int) $usuario['id_usuario'];
        $chave = 'u:' . $usuarioId . '|ip:' . RateLimit::ip();

        if (!RateLimit::consumir('provador_post', $chave, self::LIMITE_POST, 60)) {
            $this->erro(
                429,
                'Muitas requisicoes',
                'Aguarde um instante antes de salvar suas medidas novamente.',
                60,
                '/categoria'
            );
        }

        if (!Sessao::validarCsrf($_POST['_csrf'] ?? null)) {
            $this->erro(403, 'Sessao invalida', 'Recarregue a pagina e tente novamente.', null, '/categoria');
        }

        $dados = [
            'busto' => $this->decimalOuNull($_POST['busto'] ?? null),
            'cintura' => $this->decimalOuNull($_POST['cintura'] ?? null),
            'quadril' => $this->decimalOuNull($_POST['quadril'] ?? null),
        ];

        $erros = [];

        foreach ($dados as $valor) {
            if ($valor !== null && ($valor <= 0 || $valor > 300)) {
                $erros['medidas'] = 'As medidas devem ser valores positivos em centimetros (ate 300 cm).';
                break;
            }
        }

        $produtoId = filter_var($_POST['produto_id'] ?? null, FILTER_VALIDATE_INT);
        $tamanhoId = filter_var($_POST['tamanho_id'] ?? null, FILTER_VALIDATE_INT);
        $voltar = is_int($produtoId) && $produtoId > 0
            ? '/provador-virtual?id=' . $produtoId
            : '/provador-virtual';

        // O GET valida o tamanho contra as variacoes do produto.
        if (is_int($tamanhoId) && $tamanhoId > 0) {
            $voltar .= '&tamanho=' . $tamanhoId;
        }

        if ($erros !== []) {
            Sessao::setOld([
                'busto' => $this->textoMedida($_POST['busto'] ?? ''),
                'cintura' => $this->textoMedida($_POST['cintura'] ?? ''),
                'quadril' => $this->textoMedida($_POST['quadril'] ?? ''),
            ]);
            Sessao::setErros($erros);
            Sessao::flash('danger', 'Revise as medidas informadas.');
            Sessao::redirecionar($voltar);
        }

        try {
            $this->medidas->salvarDoUsuario($usuarioId, $dados);
        } catch (Throwable $excecao) {
            error_log('[provador] erro ao salvar medidas usuario=' . $usuarioId . ' codigo=' . $excecao->getCode());
            $this->erro(500, 'Erro interno', 'Nao foi possivel salvar suas medidas agora.', null, $voltar);
        }

        Sessao::flash('success', 'Medidas atualizadas com sucesso.');
        Sessao::redirecionar($voltar);
    }

    private function decimalOuNull(mixed $valor): ?float
    {
        $texto = $this->textoMedida($valor);

        if ($texto === '') {
            return null;
        }

        $texto = str_replace(',', '.', $texto);

        if (!is_numeric($texto)) {
            // Fora do intervalo valido para sinalizar erro na validacao.
            return -1.0;
        }

        return round((float) $texto, 1);
    }

    private function textoMedida(mixed $valor): string
    {
        $texto = Validacao::texto($valor);

        return substr($texto, 0, 12);
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
