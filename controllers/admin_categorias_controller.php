<?php

require_once __DIR__ . '/../core/admin.php';
require_once __DIR__ . '/../core/sessao.php';
require_once __DIR__ . '/../repositories/admin_repository.php';

class AdminCategoriasController
{
    private AdminRepository $repository;

    public function __construct(private PDO $pdo)
    {
        Admin::exigir();
        $this->repository = new AdminRepository($pdo);
    }

    public function index(): void
    {
        $categorias = $this->repository->listarCategorias();
        $flashes = Sessao::mensagensFlash();

        $editando = null;
        $idEditar = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT);

        if (is_int($idEditar) && $idEditar > 0) {
            $editando = $this->repository->buscarCategoria($idEditar);
        }

        $tituloAdmin = 'Categorias';
        $abaAdmin = 'categorias';

        require __DIR__ . '/../views/admin/categorias.php';
    }

    public function salvar(): void
    {
        $this->exigirCsrf();

        $id = filter_var($_POST['id_categoria'] ?? null, FILTER_VALIDATE_INT);
        $id = is_int($id) && $id > 0 ? $id : null;
        $nome = trim((string) ($_POST['nome'] ?? ''));
        $descricao = trim((string) ($_POST['descricao'] ?? ''));

        $errors = [];

        if (tamanho_texto($nome) < 2 || tamanho_texto($nome) > 255) {
            $errors['nome'] = 'Informe um nome entre 2 e 255 caracteres.';
        }

        if (tamanho_texto($descricao) > 255) {
            $errors['descricao'] = 'A descrição deve ter até 255 caracteres.';
        }

        if ($errors !== []) {
            Sessao::flash('danger', implode(' ', $errors));
            redirect($id === null ? '/admin/categorias' : '/admin/categorias?editar=' . $id);
        }

        try {
            if ($id === null) {
                $this->repository->criarCategoria($nome, $descricao !== '' ? $descricao : null);
                Sessao::flash('success', 'Categoria criada com sucesso.');
            } else {
                $this->repository->atualizarCategoria($id, $nome, $descricao !== '' ? $descricao : null);
                Sessao::flash('success', 'Categoria atualizada com sucesso.');
            }
        } catch (PDOException $excecao) {
            Sessao::flash(
                'danger',
                $excecao->getCode() === '23000'
                    ? 'Já existe uma categoria com esse nome.'
                    : 'Não foi possível salvar a categoria.'
            );
        }

        redirect('/admin/categorias');
    }

    public function excluir(): void
    {
        $this->exigirCsrf();

        $id = filter_var($_POST['id_categoria'] ?? null, FILTER_VALIDATE_INT);

        if (!is_int($id) || $id <= 0) {
            Sessao::flash('danger', 'Categoria inválida.');
            redirect('/admin/categorias');
        }

        $this->repository->excluirCategoria($id);
        Sessao::flash('success', 'Categoria removida. Os produtos continuam cadastrados.');
        redirect('/admin/categorias');
    }

    private function exigirCsrf(): void
    {
        if (!Sessao::validarCsrf($_POST['_csrf'] ?? null)) {
            Sessao::flash('danger', 'Sessão expirada. Tente novamente.');
            redirect('/admin/categorias');
        }
    }
}
