<?php

require_once __DIR__ . '/admin_controller.php';
require_once __DIR__ . '/../repositories/admin_usuario_repository.php';

class AdminUsuarioController extends AdminController
{
    private const POR_PAGINA = 20;

    private AdminUsuarioRepository $repository;

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);

        $this->repository = new AdminUsuarioRepository($pdo);
    }

    public function index(): void
    {
        $pagina = $this->paginaAtual();
        $filtros = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'tipo' => (string) ($_GET['tipo'] ?? ''),
        ];

        if (!in_array($filtros['tipo'], ['', 'admins', 'clientes'], true)) {
            $filtros['tipo'] = '';
        }

        $resultado = $this->repository->listar($filtros, $pagina, self::POR_PAGINA);
        $totalPaginas = max(1, (int) ceil($resultado['total'] / self::POR_PAGINA));
        $flashes = Auth::takeFlashes();
        $admin = $this->admin;

        $titulo = 'Usuários';
        $ativo = 'usuarios';

        ob_start();
        require __DIR__ . '/../views/admin/usuarios/listar.php';
        $conteudo = (string) ob_get_clean();

        require __DIR__ . '/../views/admin/layout.php';
    }

    public function novo(): void
    {
        $valores = array_merge(
            [
                'nome' => '',
                'email' => '',
                'telefone' => '',
                'cpf' => '',
                'is_admin' => 0,
            ],
            Auth::takeOld()
        );
        $errors = Auth::takeErrors();
        $flashes = Auth::takeFlashes();
        $admin = $this->admin;

        $titulo = 'Novo usuário';
        $ativo = 'usuarios';

        ob_start();
        require __DIR__ . '/../views/admin/usuarios/form.php';
        $conteudo = (string) ob_get_clean();

        require __DIR__ . '/../views/admin/layout.php';
    }

    public function criar(): void
    {
        $this->validarCsrf('/admin/usuarios/novo');

        [$dados, $errors, $old] = $this->validar(null, true);

        if ($errors !== []) {
            $this->voltarComErros(
                '/admin/usuarios/novo',
                $old,
                $errors,
                'Revise os campos destacados para criar o usuário.'
            );
        }

        $dados['senha_hash'] = password_hash($dados['senha'], PASSWORD_DEFAULT);

        try {
            $this->repository->criar($dados);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $this->voltarComErros(
                    '/admin/usuarios/novo',
                    $old,
                    ['email' => 'Este e-mail ou CPF já está cadastrado.'],
                    'Não foi possível criar o usuário.'
                );
            }

            throw $exception;
        }

        Auth::flash('success', 'Usuário criado com sucesso.');
        redirect('/admin/usuarios');
    }

    public function editar(string $id): void
    {
        $usuario = $this->repository->getPorId((int) $id);

        if ($usuario === null) {
            $this->naoEncontrado();
        }

        $valores = array_merge($usuario, Auth::takeOld());
        $errors = Auth::takeErrors();
        $flashes = Auth::takeFlashes();
        $admin = $this->admin;

        $titulo = 'Editar usuário';
        $ativo = 'usuarios';

        ob_start();
        require __DIR__ . '/../views/admin/usuarios/form.php';
        $conteudo = (string) ob_get_clean();

        require __DIR__ . '/../views/admin/layout.php';
    }

    public function atualizar(string $id): void
    {
        $id = (int) $id;
        $destino = '/admin/usuarios/' . $id . '/editar';
        $this->validarCsrf($destino);

        $usuario = $this->repository->getPorId($id);

        if ($usuario === null) {
            $this->naoEncontrado();
        }

        [$dados, $errors, $old] = $this->validar($id, false);

        $ehAdminAtual = filter_var($usuario['is_admin'], FILTER_VALIDATE_BOOLEAN);

        if ($ehAdminAtual && !$dados['is_admin'] && $this->repository->contarAdminsAtivos() <= 1) {
            $errors['is_admin'] = 'É necessário manter pelo menos um administrador ativo.';
        }

        if ($id === (int) $this->admin['id_usuario'] && !$dados['is_admin']) {
            $errors['is_admin'] = 'Você não pode remover seu próprio acesso administrativo.';
        }

        if ($errors !== []) {
            $this->voltarComErros($destino, $old, $errors, 'Revise os campos destacados para salvar o usuário.');
        }

        $dados['senha_hash'] = $dados['senha'] !== ''
            ? password_hash($dados['senha'], PASSWORD_DEFAULT)
            : null;

        try {
            $this->repository->atualizar($id, $dados);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $this->voltarComErros(
                    $destino,
                    $old,
                    ['email' => 'Este e-mail ou CPF já está cadastrado.'],
                    'Não foi possível salvar o usuário.'
                );
            }

            throw $exception;
        }

        if ($id === (int) $this->admin['id_usuario']) {
            $atualizado = $this->repository->getPorId($id);

            if ($atualizado !== null) {
                Auth::sync($atualizado);
            }
        }

        Auth::flash('success', 'Usuário atualizado com sucesso.');
        redirect('/admin/usuarios');
    }

    public function excluir(string $id): void
    {
        $id = (int) $id;
        $this->validarCsrf('/admin/usuarios');

        $usuario = $this->repository->getPorId($id);

        if ($usuario === null) {
            Auth::flash('danger', 'Usuário não encontrado.');
            redirect('/admin/usuarios');
        }

        if ($id === (int) $this->admin['id_usuario']) {
            Auth::flash('danger', 'Você não pode excluir a sua própria conta.');
            redirect('/admin/usuarios');
        }

        if (filter_var($usuario['is_admin'], FILTER_VALIDATE_BOOLEAN) && $this->repository->contarAdminsAtivos() <= 1) {
            Auth::flash('danger', 'É necessário manter pelo menos um administrador ativo.');
            redirect('/admin/usuarios');
        }

        $this->repository->excluir($id);
        Auth::flash('success', 'Usuário excluído com sucesso.');
        redirect('/admin/usuarios');
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, string>, 2: array<string, mixed>}
     */
    private function validar(?int $id, bool $senhaObrigatoria): array
    {
        $nome = $this->texto('nome');
        $email = strtolower($this->texto('email'));
        $telefone = $this->texto('telefone');
        $cpf = somente_digitos($this->texto('cpf'));
        $isAdmin = isset($_POST['is_admin']);
        $senha = (string) ($_POST['senha'] ?? '');
        $senhaConfirmar = (string) ($_POST['senha_confirmar'] ?? '');

        $errors = [];

        if (mb_strlen($nome) < 3 || mb_strlen($nome) > 255) {
            $errors['nome'] = 'Informe um nome entre 3 e 255 caracteres.';
        }

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Informe um e-mail válido.';
        } elseif (mb_strlen($email) > 255) {
            $errors['email'] = 'O e-mail deve ter no máximo 255 caracteres.';
        } elseif ($this->repository->emailEmUso($email, $id)) {
            $errors['email'] = 'Este e-mail já está cadastrado.';
        }

        $telefoneDigitos = somente_digitos($telefone);

        if ($telefoneDigitos !== '' && (strlen($telefoneDigitos) < 10 || strlen($telefoneDigitos) > 11)) {
            $errors['telefone'] = 'Informe um telefone com DDD (10 ou 11 dígitos).';
        }

        if ($cpf !== '') {
            if (strlen($cpf) !== 11) {
                $errors['cpf'] = 'O CPF deve ter 11 dígitos.';
            } elseif ($this->repository->cpfEmUso($cpf, $id)) {
                $errors['cpf'] = 'Este CPF já está cadastrado.';
            }
        }

        if (!$senhaObrigatoria && $senha === '' && $senhaConfirmar === '') {
            // Senha em branco na edição mantém a atual.
        } else {
            if (strlen($senha) < 6) {
                $errors['senha'] = 'A senha deve ter no mínimo 6 caracteres.';
            } elseif (strlen($senha) > 100) {
                $errors['senha'] = 'A senha deve ter no máximo 100 caracteres.';
            }

            if ($senhaConfirmar !== $senha) {
                $errors['senha_confirmar'] = 'As senhas não coincidem.';
            }
        }

        $dados = [
            'nome' => $nome,
            'email' => $email,
            'telefone' => $telefone === '' ? null : $telefone,
            'cpf' => $cpf === '' ? null : $cpf,
            'is_admin' => $isAdmin,
            'senha' => $senha,
        ];

        $old = [
            'nome' => $nome,
            'email' => $email,
            'telefone' => $telefone,
            'cpf' => $cpf,
            'is_admin' => $isAdmin ? 1 : 0,
        ];

        return [$dados, $errors, $old];
    }
}
