<?php

require_once __DIR__ . '/../core/admin.php';
require_once __DIR__ . '/../core/sessao.php';
require_once __DIR__ . '/../repositories/admin_repository.php';

class AdminUsuariosController
{
    private AdminRepository $repository;
    private array $admin;

    public function __construct(private PDO $pdo)
    {
        $this->admin = Admin::exigir();
        $this->repository = new AdminRepository($pdo);
    }

    public function index(): void
    {
        $usuarios = $this->repository->listarUsuarios();
        $flashes = Sessao::mensagensFlash();

        $tituloAdmin = 'Usuários';
        $abaAdmin = 'usuarios';

        require __DIR__ . '/../views/admin/usuarios.php';
    }

    public function ver(): void
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

        if (!is_int($id) || $id <= 0) {
            $this->naoEncontrado();
            return;
        }

        $usuario = $this->repository->buscarUsuario($id);

        if ($usuario === null) {
            $this->naoEncontrado();
            return;
        }

        $enderecos = $this->repository->enderecosUsuario($id);
        $pedidos = $this->repository->pedidosUsuario($id);
        $flashes = Sessao::mensagensFlash();
        $adminAtual = $this->admin;

        $tituloAdmin = 'Usuário #' . (int) $usuario['id_usuario'];
        $abaAdmin = 'usuarios';

        require __DIR__ . '/../views/admin/usuario.php';
    }

    public function alternarAdmin(): void
    {
        $this->exigirCsrf();

        $id = filter_var($_POST['id_usuario'] ?? null, FILTER_VALIDATE_INT);
        $novoValor = (string) ($_POST['novo_valor'] ?? '1') === '1';

        if (!is_int($id) || $id <= 0) {
            Sessao::flash('danger', 'Usuário inválido.');
            redirect('/admin/usuarios');
        }

        if ($id === (int) $this->admin['id_usuario'] && !$novoValor) {
            Sessao::flash('danger', 'Você não pode remover seu próprio acesso administrativo.');
            redirect('/admin/usuarios/ver?id=' . $id);
        }

        $this->repository->definirAdmin($id, $novoValor);
        Sessao::flash('success', $novoValor ? 'Acesso administrativo concedido.' : 'Acesso administrativo removido.');

        redirect('/admin/usuarios/ver?id=' . $id);
    }

    public function excluir(): void
    {
        $this->exigirCsrf();

        $id = filter_var($_POST['id_usuario'] ?? null, FILTER_VALIDATE_INT);

        if (!is_int($id) || $id <= 0) {
            Sessao::flash('danger', 'Usuário inválido.');
            redirect('/admin/usuarios');
        }

        if ($id === (int) $this->admin['id_usuario']) {
            Sessao::flash('danger', 'Você não pode excluir a própria conta por aqui.');
            redirect('/admin/usuarios/ver?id=' . $id);
        }

        $this->repository->excluirUsuario($id);
        Sessao::flash('success', 'Usuário removido com sucesso.');

        redirect('/admin/usuarios');
    }

    private function exigirCsrf(): void
    {
        if (!Sessao::validarCsrf($_POST['_csrf'] ?? null)) {
            Sessao::flash('danger', 'Sessão expirada. Tente novamente.');
            redirect('/admin/usuarios');
        }
    }

    private function naoEncontrado(): void
    {
        http_response_code(404);
        $codigo = 404;
        $titulo = 'Usuário não encontrado';
        $mensagem = 'O usuário informado não existe ou foi removido.';
        $voltar = '/admin/usuarios';

        require __DIR__ . '/../views/erro.php';
    }
}
