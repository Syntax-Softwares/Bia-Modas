<?php

require_once __DIR__ . '/../core/admin.php';
require_once __DIR__ . '/../core/sessao.php';
require_once __DIR__ . '/../repositories/admin_repository.php';

class AdminProdutosController
{
    private AdminRepository $repository;

    public function __construct(private PDO $pdo)
    {
        Admin::exigir();
        $this->repository = new AdminRepository($pdo);
    }

    public function index(): void
    {
        $busca = trim((string) ($_GET['busca'] ?? ''));
        $produtos = $this->repository->listarProdutos($busca);
        $flashes = Sessao::mensagensFlash();

        $tituloAdmin = 'Produtos';
        $abaAdmin = 'produtos';

        require __DIR__ . '/../views/admin/produtos.php';
    }

    public function novo(): void
    {
        $categorias = $this->repository->categoriasAtivas();
        $tamanhos = $this->repository->listarTamanhos();
        $isEdicao = false;
        $errors = [];
        $form = $this->formularioVazio();
        $flashes = Sessao::mensagensFlash();

        $tituloAdmin = 'Novo produto';
        $abaAdmin = 'produtos';

        require __DIR__ . '/../views/admin/produto_form.php';
    }

    public function editar(): void
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

        if (!is_int($id) || $id <= 0) {
            $this->naoEncontrado();
            return;
        }

        $produto = $this->repository->buscarProduto($id);

        if ($produto === null) {
            $this->naoEncontrado();
            return;
        }

        $categorias = $this->repository->categoriasAtivas();
        $tamanhos = $this->repository->listarTamanhos();
        $isEdicao = true;
        $errors = [];
        $form = $this->formularioDoBanco($produto, $this->repository->tamanhosDoProduto($id));
        $flashes = Sessao::mensagensFlash();

        $tituloAdmin = 'Editar produto';
        $abaAdmin = 'produtos';

        require __DIR__ . '/../views/admin/produto_form.php';
    }

    public function salvar(): void
    {
        $this->exigirCsrf();

        $id = filter_var($_POST['id_produto'] ?? null, FILTER_VALIDATE_INT);
        $id = is_int($id) && $id > 0 ? $id : null;

        $form = [
            'nome' => trim((string) ($_POST['nome'] ?? '')),
            'sku' => trim((string) ($_POST['sku'] ?? '')),
            'descricao' => trim((string) ($_POST['descricao'] ?? '')),
            'valor' => str_replace(',', '.', trim((string) ($_POST['valor'] ?? ''))),
            'tamanhos' => $this->normalizarTamanhos($_POST['tamanhos'] ?? []),
            'categoria_id' => trim((string) ($_POST['categoria_id'] ?? '')),
            'cor' => trim((string) ($_POST['cor'] ?? '')),
            'estilo' => trim((string) ($_POST['estilo'] ?? '')),
            'oferta_percentual' => trim((string) ($_POST['oferta_percentual'] ?? '')),
            'imagem' => trim((string) ($_POST['imagem'] ?? '')),
            'ativo' => isset($_POST['ativo']),
        ];

        $errors = $this->validar($form, $id);

        if ($errors !== []) {
            $categorias = $this->repository->categoriasAtivas();
            $tamanhos = $this->repository->listarTamanhos();
            $isEdicao = $id !== null;
            $flashes = Sessao::mensagensFlash();

            $tituloAdmin = $isEdicao ? 'Editar produto' : 'Novo produto';
            $abaAdmin = 'produtos';

            require __DIR__ . '/../views/admin/produto_form.php';
            return;
        }

        $dados = [
            'nome' => $form['nome'],
            'sku' => $form['sku'],
            'descricao' => $form['descricao'],
            'valor' => (float) $form['valor'],
            'tamanhos' => $form['tamanhos'],
            'categoria_id' => (int) $form['categoria_id'],
            'cor' => $form['cor'],
            'estilo' => $form['estilo'],
            'oferta_percentual' => ((float) $form['oferta_percentual']) > 0
                ? ((float) $form['oferta_percentual']) / 100
                : null,
            'imagem' => $form['imagem'],
            'ativo' => $form['ativo'],
        ];

        try {
            if ($id === null) {
                $this->repository->criarProduto($dados);
                Sessao::flash('success', 'Produto cadastrado com sucesso.');
            } else {
                $this->repository->atualizarProduto($id, $dados);
                Sessao::flash('success', 'Produto atualizado com sucesso.');
            }
        } catch (PDOException $excecao) {
            $errors['sku'] = $excecao->getCode() === '23000'
                ? 'Este SKU já está cadastrado em outro produto.'
                : 'Não foi possível salvar o produto.';

            $categorias = $this->repository->categoriasAtivas();
            $tamanhos = $this->repository->listarTamanhos();
            $isEdicao = $id !== null;
            $flashes = [];

            $tituloAdmin = $isEdicao ? 'Editar produto' : 'Novo produto';
            $abaAdmin = 'produtos';

            require __DIR__ . '/../views/admin/produto_form.php';
            return;
        }

        redirect('/admin/produtos');
    }

    public function excluir(): void
    {
        $this->exigirCsrf();

        $id = filter_var($_POST['id_produto'] ?? null, FILTER_VALIDATE_INT);

        if (!is_int($id) || $id <= 0) {
            Sessao::flash('danger', 'Produto inválido.');
            redirect('/admin/produtos');
        }

        $this->repository->excluirProduto($id);
        Sessao::flash('success', 'Produto removido do catálogo.');
        redirect('/admin/produtos');
    }

    private function validar(array $form, ?int $id): array
    {
        $errors = [];

        if (tamanho_texto($form['nome']) < 3 || tamanho_texto($form['nome']) > 255) {
            $errors['nome'] = 'Informe um nome entre 3 e 255 caracteres.';
        }

        if ($form['sku'] === '' || strlen($form['sku']) > 50) {
            $errors['sku'] = 'Informe um SKU de até 50 caracteres.';
        }

        if (tamanho_texto($form['descricao']) > 255) {
            $errors['descricao'] = 'A descrição deve ter até 255 caracteres.';
        }

        $valor = filter_var($form['valor'], FILTER_VALIDATE_FLOAT);

        if (!is_float($valor) || $valor <= 0) {
            $errors['valor'] = 'Informe um valor maior que zero.';
        }

        if ($form['tamanhos'] === []) {
            $errors['tamanhos'] = 'Selecione pelo menos um tamanho e informe o estoque.';
        } else {
            $catalogo = [];

            foreach ($this->repository->listarTamanhos() as $tamanho) {
                $catalogo[(int) $tamanho['id_tamanho']] = true;
            }

            foreach ($form['tamanhos'] as $idTamanho => $estoque) {
                if (!isset($catalogo[(int) $idTamanho])) {
                    $errors['tamanhos'] = 'Um dos tamanhos selecionados é inválido.';
                    break;
                }

                if (!is_int($estoque) || $estoque < 0) {
                    $errors['tamanhos'] = 'Informe um estoque válido para cada tamanho.';
                    break;
                }
            }
        }

        $categoriaId = filter_var($form['categoria_id'], FILTER_VALIDATE_INT);

        if (!is_int($categoriaId) || $categoriaId <= 0 || $this->repository->buscarCategoria($categoriaId) === null) {
            $errors['categoria_id'] = 'Selecione uma categoria válida.';
        }

        if ($form['cor'] !== '' && strlen($form['cor']) > 30) {
            $errors['cor'] = 'A cor deve ter até 30 caracteres.';
        }

        if ($form['estilo'] !== '' && strlen($form['estilo']) > 30) {
            $errors['estilo'] = 'O estilo deve ter até 30 caracteres.';
        }

        if ($form['oferta_percentual'] !== '') {
            $percentual = filter_var($form['oferta_percentual'], FILTER_VALIDATE_FLOAT);

            if (!is_float($percentual) || $percentual <= 0 || $percentual >= 100) {
                $errors['oferta_percentual'] = 'Informe um percentual entre 1 e 99.';
            }
        }

        if ($form['imagem'] !== '' && strlen($form['imagem']) > 255) {
            $errors['imagem'] = 'O caminho da imagem deve ter até 255 caracteres.';
        }

        return $errors;
    }

    private function normalizarTamanhos(mixed $entrada): array
    {
        if (!is_array($entrada)) {
            return [];
        }

        $tamanhos = [];

        foreach ($entrada as $id => $dados) {
            $id = filter_var($id, FILTER_VALIDATE_INT);

            if (!is_int($id) || $id <= 0 || !is_array($dados) || empty($dados['selecionado'])) {
                continue;
            }

            $estoque = filter_var($dados['estoque'] ?? 0, FILTER_VALIDATE_INT);
            $tamanhos[$id] = $estoque === false ? -1 : $estoque;
        }

        return $tamanhos;
    }

    private function formularioVazio(): array
    {
        return [
            'id_produto' => null,
            'nome' => '',
            'sku' => '',
            'descricao' => '',
            'valor' => '',
            'tamanhos' => [],
            'categoria_id' => '',
            'cor' => '',
            'estilo' => '',
            'oferta_percentual' => '',
            'imagem' => '',
            'ativo' => true,
        ];
    }

    private function formularioDoBanco(array $produto, array $tamanhos): array
    {
        return [
            'id_produto' => (int) $produto['id_produto'],
            'nome' => (string) $produto['nome'],
            'sku' => (string) $produto['sku'],
            'descricao' => (string) ($produto['descricao'] ?? ''),
            'valor' => number_format((float) $produto['valor'], 2, ',', ''),
            'tamanhos' => $tamanhos,
            'categoria_id' => (string) ((int) ($produto['categoria_id'] ?? 0)),
            'cor' => (string) ($produto['cor'] ?? ''),
            'estilo' => (string) ($produto['estilo'] ?? ''),
            'oferta_percentual' => $produto['oferta_percentual'] !== null
                ? (string) (int) round((float) $produto['oferta_percentual'] * 100)
                : '',
            'imagem' => (string) ($produto['imagem'] ?? ''),
            'ativo' => (bool) $produto['ativo'],
        ];
    }

    private function exigirCsrf(): void
    {
        if (!Sessao::validarCsrf($_POST['_csrf'] ?? null)) {
            Sessao::flash('danger', 'Sessão expirada. Tente novamente.');
            redirect('/admin/produtos');
        }
    }

    private function naoEncontrado(): void
    {
        http_response_code(404);
        $codigo = 404;
        $titulo = 'Produto não encontrado';
        $mensagem = 'O produto informado não existe ou foi removido.';
        $voltar = '/admin/produtos';

        require __DIR__ . '/../views/erro.php';
    }
}
