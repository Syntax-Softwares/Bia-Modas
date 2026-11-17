<?php

require_once __DIR__ . '/admin_controller.php';
require_once __DIR__ . '/../repositories/categoria_repository.php';

class AdminCategoriaController extends AdminController
{
    private CategoriaRepository $repository;

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);

        $this->repository = new CategoriaRepository($pdo);
    }

    public function index(): void
    {
        $categorias = $this->repository->listar();
        $flashes = Auth::takeFlashes();
        $admin = $this->admin;

        $titulo = 'Categorias';
        $ativo = 'categorias';

        ob_start();
        require __DIR__ . '/../views/admin/categorias/listar.php';
        $conteudo = (string) ob_get_clean();

        require __DIR__ . '/../views/admin/layout.php';
    }

    public function novo(): void
    {
        $categoria = null;
        $valores = [
            'nome' => '',
            'descricao' => '',
            'categoria_pai_id' => '',
        ];
        $valores = array_merge($valores, Auth::takeOld());
        $errors = Auth::takeErrors();
        $flashes = Auth::takeFlashes();
        $admin = $this->admin;
        $pais = $this->repository->listarParaSelect();

        $titulo = 'Nova categoria';
        $ativo = 'categorias';

        ob_start();
        require __DIR__ . '/../views/admin/categorias/form.php';
        $conteudo = (string) ob_get_clean();

        require __DIR__ . '/../views/admin/layout.php';
    }

    public function criar(): void
    {
        $this->validarCsrf('/admin/categorias/novo');

        [$dados, $errors, $old] = $this->validar(null);

        if ($errors !== []) {
            $this->voltarComErros(
                '/admin/categorias/novo',
                $old,
                $errors,
                'Revise os campos destacados para criar a categoria.'
            );
        }

        try {
            $this->repository->criar($dados);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $this->voltarComErros(
                    '/admin/categorias/novo',
                    $old,
                    ['nome' => 'Já existe uma categoria com este nome.'],
                    'Não foi possível criar a categoria.'
                );
            }

            throw $exception;
        }

        Auth::flash('success', 'Categoria criada com sucesso.');
        redirect('/admin/categorias');
    }

    public function editar(string $id): void
    {
        $categoria = $this->repository->getPorId((int) $id);

        if ($categoria === null) {
            $this->naoEncontrado();
        }

        $valores = array_merge($categoria, Auth::takeOld());
        $errors = Auth::takeErrors();
        $flashes = Auth::takeFlashes();
        $admin = $this->admin;
        $pais = $this->repository->listarParaSelect((int) $categoria['id_categoria']);

        $titulo = 'Editar categoria';
        $ativo = 'categorias';

        ob_start();
        require __DIR__ . '/../views/admin/categorias/form.php';
        $conteudo = (string) ob_get_clean();

        require __DIR__ . '/../views/admin/layout.php';
    }

    public function atualizar(string $id): void
    {
        $categoria = $this->repository->getPorId((int) $id);

        if ($categoria === null) {
            $this->naoEncontrado();
        }

        $destino = '/admin/categorias/' . (int) $id . '/editar';
        $this->validarCsrf($destino);

        [$dados, $errors, $old] = $this->validar((int) $id);

        if ($errors !== []) {
            $this->voltarComErros($destino, $old, $errors, 'Revise os campos destacados para salvar a categoria.');
        }

        try {
            $this->repository->atualizar((int) $id, $dados);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $this->voltarComErros(
                    $destino,
                    $old,
                    ['nome' => 'Já existe uma categoria com este nome.'],
                    'Não foi possível salvar a categoria.'
                );
            }

            throw $exception;
        }

        Auth::flash('success', 'Categoria atualizada com sucesso.');
        redirect('/admin/categorias');
    }

    public function excluir(string $id): void
    {
        $id = (int) $id;
        $this->validarCsrf('/admin/categorias');

        $categoria = $this->repository->getPorId($id);

        if ($categoria === null) {
            Auth::flash('danger', 'Categoria não encontrada.');
            redirect('/admin/categorias');
        }

        if ($this->repository->temProdutosVinculados($id)) {
            Auth::flash('danger', 'Não é possível excluir: existem produtos vinculados a esta categoria.');
            redirect('/admin/categorias');
        }

        if ($this->repository->temSubcategorias($id)) {
            Auth::flash('danger', 'Não é possível excluir: existem subcategorias vinculadas a esta categoria.');
            redirect('/admin/categorias');
        }

        $this->repository->excluir($id);
        Auth::flash('success', 'Categoria excluída com sucesso.');
        redirect('/admin/categorias');
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, string>, 2: array<string, mixed>}
     */
    private function validar(?int $id): array
    {
        $nome = $this->texto('nome');
        $descricao = $this->texto('descricao');
        $paiBruto = $this->texto('categoria_pai_id');
        $paiId = $paiBruto === '' ? null : $this->inteiro('categoria_pai_id');

        $errors = [];

        if (mb_strlen($nome) < 2 || mb_strlen($nome) > 255) {
            $errors['nome'] = 'Informe um nome entre 2 e 255 caracteres.';
        } elseif ($this->repository->nomeEmUso($nome, $id)) {
            $errors['nome'] = 'Já existe uma categoria com este nome.';
        }

        if (mb_strlen($descricao) > 255) {
            $errors['descricao'] = 'A descrição deve ter no máximo 255 caracteres.';
        }

        if ($paiBruto !== '' && $paiId === null) {
            $errors['categoria_pai_id'] = 'Categoria pai inválida.';
        } elseif ($paiId !== null) {
            if ($id !== null && $paiId === $id) {
                $errors['categoria_pai_id'] = 'Uma categoria não pode ser pai dela mesma.';
            } elseif ($this->repository->getPorId($paiId) === null) {
                $errors['categoria_pai_id'] = 'Categoria pai não encontrada.';
            } elseif ($id !== null && $this->repository->formariaCiclo($id, $paiId)) {
                $errors['categoria_pai_id'] = 'Esta escolha criaria um ciclo na hierarquia.';
            }
        }

        $dados = [
            'nome' => $nome,
            'descricao' => $descricao === '' ? null : $descricao,
            'categoria_pai_id' => $paiId,
        ];

        $old = [
            'nome' => $nome,
            'descricao' => $descricao,
            'categoria_pai_id' => $paiBruto,
        ];

        return [$dados, $errors, $old];
    }
}
