<?php

require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../repositories/usuario_repository.php';

class AuthController
{
    private UsuarioRepository $repository;

    public function __construct(private PDO $pdo)
    {
        $this->repository = new UsuarioRepository($pdo);
    }

    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect('/perfil');
        }

        $old = Auth::takeOld();
        $errors = Auth::takeErrors();
        $flashes = Auth::takeFlashes();

        require __DIR__ . '/../views/login.php';
    }

    public function login(): void
    {
        if (!Auth::validCsrf($_POST['_csrf'] ?? null)) {
            Auth::flash('danger', 'Sessão expirada. Tente novamente.');
            redirect('/login');
        }

        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $senha = (string) ($_POST['senha'] ?? '');

        $errors = [];

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Informe um e-mail válido.';
        }

        if ($senha === '') {
            $errors['senha'] = 'Informe sua senha.';
        }

        if ($errors !== []) {
            Auth::setOld(['email' => $email]);
            Auth::setErrors($errors);
            redirect('/login');
        }

        $usuario = $this->repository->buscarPorEmail($email);

        if ($usuario === null || !password_verify($senha, $usuario['senha_hash'])) {
            Auth::setOld(['email' => $email]);
            Auth::flash('danger', 'E-mail ou senha inválidos.');
            redirect('/login');
        }

        if (password_needs_rehash($usuario['senha_hash'], PASSWORD_DEFAULT)) {
            $this->repository->atualizarSenhaHash(
                (int) $usuario['id_usuario'],
                password_hash($senha, PASSWORD_DEFAULT)
            );
        }

        Auth::login($usuario);
        Auth::flash('success', 'Bem-vinda de volta, ' . $this->primeiroNome($usuario['nome']) . '!');

        redirect('/perfil');
    }

    public function showRegister(): void
    {
        if (Auth::check()) {
            redirect('/perfil');
        }

        $old = Auth::takeOld();
        $errors = Auth::takeErrors();
        $flashes = Auth::takeFlashes();

        require __DIR__ . '/../views/registro.php';
    }

    public function register(): void
    {
        if (!Auth::validCsrf($_POST['_csrf'] ?? null)) {
            Auth::flash('danger', 'Sessão expirada. Tente novamente.');
            redirect('/registro');
        }

        $nome = trim((string) ($_POST['nome'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $telefone = trim((string) ($_POST['telefone'] ?? ''));
        $senha = (string) ($_POST['senha'] ?? '');
        $senhaConfirmar = (string) ($_POST['senha_confirmar'] ?? '');

        $endereco = [
            'nome_endereco' => 'Principal',
            'logradouro' => trim((string) ($_POST['logradouro'] ?? '')),
            'numero' => trim((string) ($_POST['numero'] ?? '')),
            'complemento' => trim((string) ($_POST['complemento'] ?? '')),
            'bairro' => trim((string) ($_POST['bairro'] ?? '')),
            'cidade' => trim((string) ($_POST['cidade'] ?? '')),
            'uf' => strtoupper(trim((string) ($_POST['uf'] ?? ''))),
            'cep' => somente_digitos((string) ($_POST['cep'] ?? '')),
        ];

        $medidas = [
            'busto' => $this->decimalOuNull($_POST['busto'] ?? null),
            'cintura' => $this->decimalOuNull($_POST['cintura'] ?? null),
            'quadril' => $this->decimalOuNull($_POST['quadril'] ?? null),
        ];

        $old = [
            'nome' => $nome,
            'email' => $email,
            'telefone' => $telefone,
            'logradouro' => $endereco['logradouro'],
            'numero' => $endereco['numero'],
            'complemento' => $endereco['complemento'],
            'bairro' => $endereco['bairro'],
            'cidade' => $endereco['cidade'],
            'uf' => $endereco['uf'],
            'cep' => $endereco['cep'],
            'busto' => $medidas['busto'],
            'cintura' => $medidas['cintura'],
            'quadril' => $medidas['quadril'],
        ];

        $errors = [];

        if (strlen($nome) < 3) {
            $errors['nome'] = 'Informe seu nome completo (mínimo 3 caracteres).';
        } elseif (strlen($nome) > 255) {
            $errors['nome'] = 'O nome deve ter no máximo 255 caracteres.';
        }

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Informe um e-mail válido.';
        } elseif (strlen($email) > 255) {
            $errors['email'] = 'O e-mail deve ter no máximo 255 caracteres.';
        } elseif ($this->repository->emailEmUso($email)) {
            $errors['email'] = 'Este e-mail já está cadastrado.';
        }

        $telefoneDigitos = somente_digitos($telefone);

        if ($telefoneDigitos === '') {
            $errors['telefone'] = 'Informe seu telefone.';
        } elseif (strlen($telefoneDigitos) < 10 || strlen($telefoneDigitos) > 11) {
            $errors['telefone'] = 'Informe um telefone com DDD (10 ou 11 dígitos).';
        }

        if (strlen($senha) < 6) {
            $errors['senha'] = 'A senha deve ter no mínimo 6 caracteres.';
        } elseif (strlen($senha) > 100) {
            $errors['senha'] = 'A senha deve ter no máximo 100 caracteres.';
        }

        if ($senhaConfirmar !== $senha) {
            $errors['senha_confirmar'] = 'As senhas não coincidem.';
        }

        $enderecoPreenchido = algum_preenchido([
            $endereco['logradouro'],
            $endereco['numero'],
            $endereco['complemento'],
            $endereco['bairro'],
            $endereco['cidade'],
            $endereco['uf'],
            $endereco['cep'],
        ]);

        if ($enderecoPreenchido) {
            foreach ([
                'logradouro' => 'Informe o endereço.',
                'numero' => 'Informe o número.',
                'bairro' => 'Informe o bairro.',
                'cidade' => 'Informe a cidade.',
                'uf' => 'Informe o estado (UF).',
            ] as $campo => $mensagem) {
                if ($endereco[$campo] === '') {
                    $errors['endereco'] = $mensagem;
                    break;
                }
            }

            if (!isset($errors['endereco']) && strlen($endereco['logradouro']) > 100) {
                $errors['endereco'] = 'A rua deve ter até 100 caracteres.';
            }

            if (!isset($errors['endereco']) && strlen($endereco['uf']) !== 2) {
                $errors['endereco'] = 'A UF deve ter 2 letras.';
            }

            if (!isset($errors['endereco']) && strlen($endereco['cep']) !== 8) {
                $errors['endereco'] = 'Informe um CEP com 8 dígitos.';
            }
        }

        foreach ($medidas as $campo => $valor) {
            if ($valor !== null && ($valor <= 0 || $valor > 300)) {
                $errors['medidas'] = 'As medidas devem ser valores positivos em centímetros.';
                break;
            }
        }

        if ($errors !== []) {
            Auth::setOld($old);
            Auth::setErrors($errors);
            Auth::flash('danger', 'Revise os campos destacados para concluir o cadastro.');
            redirect('/registro');
        }

        try {
            $usuarioId = $this->repository->criar([
                'nome' => $nome,
                'email' => $email,
                'senha_hash' => password_hash($senha, PASSWORD_DEFAULT),
                'telefone' => $telefoneDigitos,
                'medidas' => algum_preenchido($medidas) ? $medidas : [],
                'endereco' => $enderecoPreenchido ? $endereco : [],
            ]);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                Auth::setOld($old);
                Auth::setErrors(['email' => 'Este e-mail já está cadastrado.']);
                Auth::flash('danger', 'Não foi possível concluir o cadastro.');
                redirect('/registro');
            }

            throw $exception;
        }

        $usuario = $this->repository->buscarPorId($usuarioId);

        if ($usuario === null) {
            Auth::flash('danger', 'Cadastro criado, mas não foi possível iniciar sua sessão. Faça login.');
            redirect('/login');
        }

        Auth::login($usuario);
        Auth::flash('success', 'Cadastro realizado com sucesso. Bem-vinda à Bia Modas!');

        redirect('/perfil');
    }

    public function account(): void
    {
        $sessao = Auth::user();

        if ($sessao === null) {
            Auth::flash('info', 'Entre na sua conta para continuar.');
            redirect('/login');
        }

        $usuario = $this->repository->buscarPorId((int) $sessao['id_usuario']);

        if ($usuario === null) {
            Auth::logout();
            Auth::flash('danger', 'Sua conta não foi encontrada. Entre novamente.');
            redirect('/login');
        }

        $flashes = Auth::takeFlashes();

        require __DIR__ . '/../views/conta.php';
    }

    public function logout(): void
    {
        if (!Auth::validCsrf($_POST['_csrf'] ?? null)) {
            Auth::flash('danger', 'Sessão expirada. Tente novamente.');
            redirect('/');
        }

        Auth::logout();
        Auth::flash('success', 'Sessão encerrada com sucesso.');

        redirect('/login');
    }

    private function decimalOuNull(mixed $value): ?float
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $value = str_replace(',', '.', $value);

        return is_numeric($value) ? (float) $value : -1.0;
    }

    private function primeiroNome(string $nome): string
    {
        $partes = preg_split('/\s+/u', trim($nome)) ?: [];

        return $partes[0] ?? $nome;
    }
}
