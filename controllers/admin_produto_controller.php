<?php

require_once __DIR__ . '/admin_controller.php';
require_once __DIR__ . '/../core/upload.php';
require_once __DIR__ . '/../repositories/admin_produto_repository.php';
require_once __DIR__ . '/../repositories/categoria_repository.php';
require_once __DIR__ . '/../repositories/tamanho_repository.php';
require_once __DIR__ . '/../repositories/oferta_repository.php';

class AdminProdutoController extends AdminController
{
    private const POR_PAGINA = 20;

    private AdminProdutoRepository $repository;
    private CategoriaRepository $categorias;
    private TamanhoRepository $tamanhos;
    private OfertaRepository $ofertas;

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);

        $this->repository = new AdminProdutoRepository($pdo);
        $this->categorias = new CategoriaRepository($pdo);
        $this->tamanhos = new TamanhoRepository($pdo);
        $this->ofertas = new OfertaRepository($pdo);
    }

    public function index(): void
    {
        $pagina = $this->paginaAtual();
        $filtros = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'status' => (string) ($_GET['status'] ?? ''),
            'categoria_id' => (int) ($_GET['categoria_id'] ?? 0),
        ];

        if (!in_array($filtros['status'], ['', 'ativos', 'inativos'], true)) {
            $filtros['status'] = '';
        }

        $resultado = $this->repository->listar($filtros, $pagina, self::POR_PAGINA);
        $totalPaginas = max(1, (int) ceil($resultado['total'] / self::POR_PAGINA));
        $categoriasFiltro = $this->categorias->listarParaSelect();
        $flashes = Auth::takeFlashes();
        $admin = $this->admin;

        $titulo = 'Produtos';
        $ativo = 'produtos';

        ob_start();
        require __DIR__ . '/../views/admin/produtos/listar.php';
        $conteudo = (string) ob_get_clean();

        require __DIR__ . '/../views/admin/layout.php';
    }

    public function novo(): void
    {
        $old = Auth::takeOld();
        $errors = Auth::takeErrors();
        $flashes = Auth::takeFlashes();
        $admin = $this->admin;

        $valores = [
            'nome' => $old['nome'] ?? '',
            'sku' => $old['sku'] ?? '',
            'descricao' => $old['descricao'] ?? '',
            'valor' => $old['valor'] ?? '',
            'estoque' => $old['estoque'] ?? 0,
            'medida_produto_id' => $old['medida_produto_id'] ?? '',
            'oferta_id' => $old['oferta_id'] ?? '',
            'ativo' => $old['ativo'] ?? 1,
        ];

        $categoriasSelecionadas = array_map('intval', (array) ($old['categorias'] ?? []));
        $principalSelecionada = (int) ($old['categoria_principal'] ?? 0);

        $categorias = $this->categorias->listarParaSelect();
        $tamanhos = $this->tamanhos->listarParaSelect();
        $ofertas = $this->ofertas->listar();
        $imagemAtual = null;

        $titulo = 'Novo produto';
        $ativo = 'produtos';

        ob_start();
        require __DIR__ . '/../views/admin/produtos/form.php';
        $conteudo = (string) ob_get_clean();

        require __DIR__ . '/../views/admin/layout.php';
    }

    public function criar(): void
    {
        $this->validarCsrf('/admin/produtos/novo');

        [$dados, $errors, $old] = $this->validar(null);

        if ($errors !== []) {
            $this->voltarComErros(
                '/admin/produtos/novo',
                $old,
                $errors,
                'Revise os campos destacados para criar o produto.'
            );
        }

        try {
            $dados['imagem'] = Upload::imagemProduto($_FILES['imagem'] ?? null);
        } catch (UploadException $exception) {
            $this->voltarComErros(
                '/admin/produtos/novo',
                $old,
                ['imagem' => $exception->getMessage()],
                'Não foi possível enviar a imagem.'
            );
        }

        try {
            $this->repository->criar($dados);
        } catch (PDOException $exception) {
            Upload::removerImagem($dados['imagem']);

            if ($exception->getCode() === '23000') {
                $this->voltarComErros(
                    '/admin/produtos/novo',
                    $old,
                    ['sku' => 'Este SKU já está em uso (inclusive por produtos excluídos).'],
                    'Não foi possível criar o produto.'
                );
            }

            throw $exception;
        }

        Auth::flash('success', 'Produto criado com sucesso.');
        redirect('/admin/produtos');
    }

    public function editar(string $id): void
    {
        $produto = $this->repository->getPorId((int) $id);

        if ($produto === null) {
            $this->naoEncontrado();
        }

        $old = Auth::takeOld();
        $errors = Auth::takeErrors();
        $flashes = Auth::takeFlashes();
        $admin = $this->admin;

        $valores = [
            'nome' => $old['nome'] ?? $produto['nome'],
            'sku' => $old['sku'] ?? $produto['sku'],
            'descricao' => $old['descricao'] ?? $produto['descricao'],
            'valor' => $old['valor'] ?? $produto['valor'],
            'estoque' => $old['estoque'] ?? $produto['estoque'],
            'medida_produto_id' => $old['medida_produto_id'] ?? $produto['medida_produto_id'],
            'oferta_id' => $old['oferta_id'] ?? $produto['oferta_id'],
            'ativo' => $old['ativo'] ?? $produto['ativo'],
        ];

        $categoriasProduto = array_map(
            static fn (array $linha): int => (int) $linha['categoria_id'],
            $produto['categorias']
        );

        $categoriasSelecionadas = isset($old['categorias'])
            ? array_map('intval', (array) $old['categorias'])
            : $categoriasProduto;

        $principalPadrao = 0;

        foreach ($produto['categorias'] as $linha) {
            if (filter_var($linha['principal'], FILTER_VALIDATE_BOOLEAN)) {
                $principalPadrao = (int) $linha['categoria_id'];
                break;
            }
        }

        $principalSelecionada = isset($old['categoria_principal'])
            ? (int) $old['categoria_principal']
            : ($principalPadrao ?: ($categoriasProduto[0] ?? 0));

        $categorias = $this->categorias->listarParaSelect();
        $tamanhos = $this->tamanhos->listarParaSelect();
        $ofertas = $this->ofertas->listar();
        $imagemAtual = $produto['imagem'];

        $titulo = 'Editar produto';
        $ativo = 'produtos';

        ob_start();
        require __DIR__ . '/../views/admin/produtos/form.php';
        $conteudo = (string) ob_get_clean();

        require __DIR__ . '/../views/admin/layout.php';
    }

    public function atualizar(string $id): void
    {
        $id = (int) $id;
        $destino = '/admin/produtos/' . $id . '/editar';
        $this->validarCsrf($destino);

        $produto = $this->repository->getPorId($id);

        if ($produto === null) {
            $this->naoEncontrado();
        }

        [$dados, $errors, $old] = $this->validar($id);

        if ($errors !== []) {
            $this->voltarComErros($destino, $old, $errors, 'Revise os campos destacados para salvar o produto.');
        }

        try {
            $dados['imagem'] = Upload::imagemProduto($_FILES['imagem'] ?? null);
        } catch (UploadException $exception) {
            $this->voltarComErros($destino, $old, ['imagem' => $exception->getMessage()], 'Não foi possível enviar a imagem.');
        }

        try {
            $this->repository->atualizar($id, $dados);
        } catch (PDOException $exception) {
            Upload::removerImagem($dados['imagem']);

            if ($exception->getCode() === '23000') {
                $this->voltarComErros(
                    $destino,
                    $old,
                    ['sku' => 'Este SKU já está em uso (inclusive por produtos excluídos).'],
                    'Não foi possível salvar o produto.'
                );
            }

            throw $exception;
        }

        if ($dados['imagem'] !== null) {
            Upload::removerImagem($produto['imagem']);
        }

        Auth::flash('success', 'Produto atualizado com sucesso.');
        redirect('/admin/produtos');
    }

    public function excluir(string $id): void
    {
        $id = (int) $id;
        $this->validarCsrf('/admin/produtos');

        if ($this->repository->getPorId($id) === null) {
            Auth::flash('danger', 'Produto não encontrado.');
            redirect('/admin/produtos');
        }

        $this->repository->excluir($id);
        Auth::flash('success', 'Produto excluído com sucesso.');
        redirect('/admin/produtos');
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, string>, 2: array<string, mixed>}
     */
    private function validar(?int $id): array
    {
        $nome = $this->texto('nome');
        $sku = strtoupper($this->texto('sku'));
        $descricao = $this->texto('descricao');
        $valor = $this->decimal('valor');
        $estoque = $this->inteiro('estoque');
        $medidaId = $this->inteiro('medida_produto_id');
        $ofertaBruta = $this->texto('oferta_id');
        $ofertaId = $ofertaBruta === '' ? null : $this->inteiro('oferta_id');
        $ativo = isset($_POST['ativo']);

        $categoriasRecebidas = array_values(array_unique(array_map('intval', (array) ($_POST['categorias'] ?? []))));
        $principal = (int) ($_POST['categoria_principal'] ?? 0);

        $errors = [];

        if (mb_strlen($nome) < 3 || mb_strlen($nome) > 255) {
            $errors['nome'] = 'Informe um nome entre 3 e 255 caracteres.';
        }

        if ($sku === '') {
            $errors['sku'] = 'Informe o SKU.';
        } elseif (!preg_match('/^[A-Z0-9._-]{1,50}$/', $sku)) {
            $errors['sku'] = 'Use até 50 caracteres: letras, números, ponto, hífen ou sublinhado.';
        } elseif ($this->repository->skuEmUso($sku, $id)) {
            $errors['sku'] = 'Este SKU já está em uso (inclusive por produtos excluídos).';
        }

        if (mb_strlen($descricao) > 255) {
            $errors['descricao'] = 'A descrição deve ter no máximo 255 caracteres.';
        }

        if ($valor === null || $valor <= 0 || $valor > 999999.99) {
            $errors['valor'] = 'Informe um valor maior que zero.';
        }

        if ($estoque === null || $estoque < 0 || $estoque > 99999) {
            $errors['estoque'] = 'Informe um estoque entre 0 e 99.999.';
        }

        $tamanhosValidos = array_map(
            static fn (array $linha): int => (int) $linha['id_medida_produto'],
            $this->tamanhos->listarParaSelect()
        );

        if ($medidaId === null || !in_array($medidaId, $tamanhosValidos, true)) {
            $errors['medida_produto_id'] = 'Selecione um tamanho válido.';
        }

        if ($ofertaBruta !== '' && $ofertaId === null) {
            $errors['oferta_id'] = 'Oferta inválida.';
        } elseif ($ofertaId !== null && $this->ofertas->getPorId($ofertaId) === null) {
            $errors['oferta_id'] = 'Oferta não encontrada.';
        }

        $categoriasValidas = array_map(
            static fn (array $linha): int => (int) $linha['id_categoria'],
            $this->categorias->listarParaSelect()
        );

        if ($categoriasRecebidas === [] || array_diff($categoriasRecebidas, $categoriasValidas) !== []) {
            $errors['categorias'] = 'Selecione pelo menos uma categoria válida.';
        } elseif (!in_array($principal, $categoriasRecebidas, true)) {
            $errors['categoria_principal'] = 'Escolha uma categoria principal entre as selecionadas.';
        }

        $dados = [
            'nome' => $nome,
            'sku' => $sku,
            'descricao' => $descricao === '' ? null : $descricao,
            'valor' => $valor,
            'estoque' => $estoque,
            'medida_produto_id' => $medidaId,
            'oferta_id' => $ofertaId,
            'ativo' => $ativo,
            'categorias' => $categoriasRecebidas,
            'categoria_principal' => $principal,
        ];

        $old = [
            'nome' => $nome,
            'sku' => $sku,
            'descricao' => $descricao,
            'valor' => $valor,
            'estoque' => $estoque,
            'medida_produto_id' => $medidaId,
            'oferta_id' => $ofertaId,
            'ativo' => $ativo ? 1 : 0,
            'categorias' => $categoriasRecebidas,
            'categoria_principal' => $principal,
        ];

        return [$dados, $errors, $old];
    }
}
