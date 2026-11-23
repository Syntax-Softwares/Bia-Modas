<?php

require_once __DIR__ . '/../core/admin.php';
require_once __DIR__ . '/../core/sessao.php';
require_once __DIR__ . '/../repositories/admin_repository.php';

class AdminTamanhosController
{
    private AdminRepository $repository;

    public function __construct(private PDO $pdo)
    {
        Admin::exigir();
        $this->repository = new AdminRepository($pdo);
    }

    public function index(): void
    {
        $tamanhos = $this->repository->listarTamanhosAdmin();
        $flashes = Sessao::mensagensFlash();

        $editando = null;
        $idEditar = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT);

        if (is_int($idEditar) && $idEditar > 0) {
            $editando = $this->repository->buscarTamanho($idEditar);
        }

        $tituloAdmin = 'Tamanhos';
        $abaAdmin = 'tamanhos';

        require __DIR__ . '/../views/admin/tamanhos.php';
    }

    public function salvar(): void
    {
        $this->exigirCsrf();

        $id = filter_var($_POST['id_tamanho'] ?? null, FILTER_VALIDATE_INT);
        $id = is_int($id) && $id > 0 ? $id : null;
        $nome = strtoupper(trim((string) ($_POST['nome'] ?? '')));
        $ordemEntrada = trim((string) ($_POST['ordem'] ?? ''));
        $ordem = $ordemEntrada === '' ? 0 : filter_var($ordemEntrada, FILTER_VALIDATE_INT);

        $errors = [];

        if ($nome === '' || strlen($nome) > 10) {
            $errors['nome'] = 'Informe um tamanho de 1 a 10 caracteres.';
        }

        if ($ordem === false || $ordem < 0 || $ordem > 9999) {
            $errors['ordem'] = 'Informe uma ordem entre 0 e 9999.';
        }

        if ($errors !== []) {
            Sessao::flash('danger', implode(' ', $errors));
            redirect($id === null ? '/admin/tamanhos' : '/admin/tamanhos?editar=' . $id);
        }

        try {
            if ($id === null) {
                $this->repository->criarTamanho($nome, (int) $ordem);
                Sessao::flash('success', 'Tamanho cadastrado com sucesso.');
            } else {
                $this->repository->atualizarTamanho($id, $nome, (int) $ordem);
                Sessao::flash('success', 'Tamanho atualizado com sucesso.');
            }
        } catch (PDOException $excecao) {
            Sessao::flash(
                'danger',
                $excecao->getCode() === '23000'
                    ? 'Já existe um tamanho com esse nome.'
                    : 'Não foi possível salvar o tamanho.'
            );
        }

        redirect('/admin/tamanhos');
    }

    public function excluir(): void
    {
        $this->exigirCsrf();

        $id = filter_var($_POST['id_tamanho'] ?? null, FILTER_VALIDATE_INT);

        if (!is_int($id) || $id <= 0) {
            Sessao::flash('danger', 'Tamanho inválido.');
            redirect('/admin/tamanhos');
        }

        $emUso = $this->repository->contarProdutosComTamanho($id);

        if ($emUso > 0) {
            Sessao::flash(
                'danger',
                'Não é possível excluir este tamanho: ' . $emUso . ' produto(s) usam essa variação.'
            );
            redirect('/admin/tamanhos');
        }

        $this->repository->excluirTamanho($id);
        Sessao::flash('success', 'Tamanho removido.');
        redirect('/admin/tamanhos');
    }

    private function exigirCsrf(): void
    {
        if (!Sessao::validarCsrf($_POST['_csrf'] ?? null)) {
            Sessao::flash('danger', 'Sessão expirada. Tente novamente.');
            redirect('/admin/tamanhos');
        }
    }
}
