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
        $imagem = trim((string) ($_POST['imagem_atual'] ?? ''));

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
            if (isset($_FILES['imagem']) && (int) ($_FILES['imagem']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $imagem = $this->salvarImagem($_FILES['imagem']);
            }

            $imagemFinal = $imagem !== '' ? $imagem : null;

            if ($id === null) {
                $this->repository->criarCategoria($nome, $descricao !== '' ? $descricao : null, $imagemFinal);
                Sessao::flash('success', 'Categoria criada com sucesso.');
            } else {
                $this->repository->atualizarCategoria($id, $nome, $descricao !== '' ? $descricao : null, $imagemFinal);
                Sessao::flash('success', 'Categoria atualizada com sucesso.');
            }
        } catch (RuntimeException $excecao) {
            Sessao::flash('danger', $excecao->getMessage());
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

    private function salvarImagem(array $arquivo): string
    {
        if (!isset($arquivo['tmp_name'], $arquivo['error'], $arquivo['size'])) {
            throw new RuntimeException('Envie uma imagem válida.');
        }

        if ((int) $arquivo['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Não foi possível enviar a imagem.');
        }

        if ((int) $arquivo['size'] > 2 * 1024 * 1024) {
            throw new RuntimeException('A imagem deve ter no máximo 2 MB.');
        }

        $info = @getimagesize($arquivo['tmp_name']);

        if ($info === false) {
            throw new RuntimeException('O arquivo enviado não é uma imagem válida.');
        }

        $extensoes = [
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_WEBP => 'webp',
        ];

        if (!isset($extensoes[$info[2]])) {
            throw new RuntimeException('Use uma imagem JPG, PNG ou WebP.');
        }

        $diretorio = __DIR__ . '/../public/img/categorias';

        if (!is_dir($diretorio) && !mkdir($diretorio, 0775, true) && !is_dir($diretorio)) {
            throw new RuntimeException('Não foi possível criar a pasta de imagens.');
        }

        $nomeArquivo = 'categoria-' . bin2hex(random_bytes(8)) . '.' . $extensoes[$info[2]];
        $destino = $diretorio . '/' . $nomeArquivo;

        if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
            throw new RuntimeException('Não foi possível salvar a imagem enviada.');
        }

        return '/img/categorias/' . $nomeArquivo;
    }

    private function exigirCsrf(): void
    {
        if (!Sessao::validarCsrf($_POST['_csrf'] ?? null)) {
            Sessao::flash('danger', 'Sessão expirada. Tente novamente.');
            redirect('/admin/categorias');
        }
    }
}
