<?php

/**
 * MVC06 - perfil e enderecos do usuario.
 *
 * Escopo: dados cadastrais, CRUD/selecao de enderecos e troca de senha.
 * Medidas, preferencias e pedidos ficam para os MVCs seguintes.
 */
final class PerfilController
{
    private const MAX_ENDERECOS = 20;

    private const LIMITE_REQ_IP = 120;        // requisicoes por minuto por IP
    private const LIMITE_POST = 30;           // POSTs por minuto por usuario
    private const JANELA_TENTATIVAS = 900;    // 15 minutos
    private const MAX_TENTATIVAS_IP = 5;      // por usuario + IP
    private const MAX_TENTATIVAS_USUARIO = 20;

    private PerfilRepository $perfil;
    private EnderecoRepository $enderecos;

    public function __construct(private PDO $pdo)
    {
        $this->perfil = new PerfilRepository($pdo);
        $this->enderecos = new EnderecoRepository($pdo);
    }

    public function index(): void
    {
        $this->renderizar('dados');
    }

    public function enderecos(): void
    {
        $this->renderizar('enderecos');
    }

    public function seguranca(): void
    {
        $this->renderizar('seguranca');
    }

    public function atualizarDados(): void
    {
        $sessao = $this->protegerPost();
        $usuarioId = (int) $sessao['id_usuario'];
        $atual = $this->perfil->buscarPorId($usuarioId);

        if ($atual === null) {
            $this->contaAusente();
        }

        $nome = Validacao::texto($_POST['nome'] ?? '');
        $email = strtolower(Validacao::texto($_POST['email'] ?? ''));
        $telefone = Validacao::somenteDigitos($_POST['telefone'] ?? '');
        $cpf = Validacao::somenteDigitos($_POST['cpf'] ?? '');
        $dataNascimento = Validacao::texto($_POST['data_nascimento'] ?? '');
        $senhaAtual = (string) ($_POST['senha_atual'] ?? '');

        $erros = [];

        if (strlen($nome) < 3) {
            $erros['nome'] = 'Informe seu nome completo (minimo 3 caracteres).';
        } elseif (strlen($nome) > 255) {
            $erros['nome'] = 'O nome deve ter no maximo 255 caracteres.';
        }

        if (!Validacao::email($email)) {
            $erros['email'] = 'Informe um e-mail valido.';
        } elseif ($this->perfil->emailEmUsoPorOutro($email, $usuarioId)) {
            $erros['email'] = 'Este e-mail ja esta cadastrado.';
        }

        if (!Validacao::telefone($telefone)) {
            $erros['telefone'] = 'Informe um telefone com DDD (10 ou 11 digitos).';
        }

        if ($cpf !== '' && !Validacao::cpf($cpf)) {
            $erros['cpf'] = 'Informe um CPF valido.';
        } elseif ($cpf !== '' && $this->perfil->cpfEmUsoPorOutro($cpf, $usuarioId)) {
            $erros['cpf'] = 'Este CPF ja esta cadastrado.';
        }

        if ($dataNascimento !== '' && !Validacao::dataNascimento($dataNascimento)) {
            $erros['data_nascimento'] = 'Informe uma data de nascimento valida.';
        }

        $emailMudou = strcasecmp($email, (string) $atual['email']) !== 0;

        if ($emailMudou) {
            if ($this->bloqueadoPorSenha($usuarioId)) {
                $this->responderErro(
                    429,
                    'Muitas tentativas',
                    'Aguarde 15 minutos antes de tentar alterar o e-mail novamente.',
                    900
                );
            }

            if ($senhaAtual === '') {
                $erros['senha_atual'] = 'Informe sua senha atual para alterar o e-mail.';
            } elseif (!$this->senhaConfere($usuarioId, $senhaAtual)) {
                $this->registrarFalhaSenha($usuarioId);
                $erros['senha_atual'] = 'Senha atual incorreta.';
            } else {
                $this->limparFalhasSenha($usuarioId);
            }
        }

        if ($erros !== []) {
            Sessao::setOld([
                'nome' => $nome,
                'email' => $email,
                'telefone' => $telefone,
                'cpf' => $cpf,
                'data_nascimento' => $dataNascimento,
            ]);
            Sessao::setErros($erros);
            Sessao::flash('danger', 'Revise os campos destacados.');
            Sessao::redirecionar('/perfil');
        }

        try {
            $this->perfil->atualizarDados($usuarioId, [
                'nome' => $nome,
                'email' => $email,
                'telefone' => $telefone,
                'cpf' => $cpf === '' ? null : $cpf,
                'data_nascimento' => $dataNascimento === '' ? null : $dataNascimento,
            ]);
        } catch (PDOException $excecao) {
            if ($excecao->getCode() === '23000') {
                // Mostra o erro apenas no campo que colidiu, sem revelar o
                // valor duplicado nem detalhes do banco.
                $erros = str_contains($excecao->getMessage(), 'usuarios_cpf_uk')
                    ? ['cpf' => 'Este CPF nao esta disponivel.']
                    : ['email' => 'Este e-mail nao esta disponivel.'];

                Sessao::setOld([
                    'nome' => $nome,
                    'email' => $email,
                    'telefone' => $telefone,
                    'cpf' => $cpf,
                    'data_nascimento' => $dataNascimento,
                ]);
                Sessao::setErros($erros);
                Sessao::flash('danger', 'Nao foi possivel salvar os dados.');
                Sessao::redirecionar('/perfil');
            }

            error_log('[perfil] erro ao atualizar dados usuario=' . $usuarioId . ' codigo=' . $excecao->getCode());
            $this->responderErro(500, 'Erro interno', 'Nao foi possivel salvar os dados agora.');
        }

        Sessao::atualizarUsuario(['nome' => $nome, 'email' => $email]);

        if ($emailMudou) {
            Sessao::regenerar();
        }

        Sessao::flash('success', 'Dados atualizados com sucesso.');
        Sessao::redirecionar('/perfil');
    }

    public function alterarSenha(): void
    {
        $sessao = $this->protegerPost();
        $usuarioId = (int) $sessao['id_usuario'];

        $senhaAtual = (string) ($_POST['senha_atual'] ?? '');
        $senhaNova = (string) ($_POST['senha_nova'] ?? '');
        $confirmar = (string) ($_POST['senha_confirmar'] ?? '');

        if ($this->bloqueadoPorSenha($usuarioId)) {
            Sessao::flash('danger', 'Muitas tentativas. Aguarde 15 minutos antes de tentar novamente.');
            Sessao::redirecionar('/perfil/seguranca');
        }

        $erros = [];

        if ($senhaAtual === '') {
            $erros['senha_atual'] = 'Informe sua senha atual.';
        }

        if ($senhaNova === '') {
            $erros['senha_nova'] = 'Informe a nova senha.';
        } else {
            $regras = Validacao::senha($senhaNova);

            if ($regras !== []) {
                $erros['senha_nova'] = implode(' ', $regras);
            }
        }

        if ($senhaNova !== $confirmar) {
            $erros['senha_confirmar'] = 'As senhas nao coincidem.';
        }

        if ($senhaAtual !== '' && $senhaNova !== '' && $senhaAtual === $senhaNova) {
            $erros['senha_nova'] = 'A nova senha deve ser diferente da atual.';
        }

        if (!isset($erros['senha_atual']) && $senhaAtual !== '' && !$this->senhaConfere($usuarioId, $senhaAtual)) {
            $this->registrarFalhaSenha($usuarioId);
            $erros['senha_atual'] = 'Senha atual incorreta.';
        }

        if ($erros !== []) {
            Sessao::setErros($erros);
            Sessao::flash('danger', 'Nao foi possivel alterar a senha.');
            Sessao::redirecionar('/perfil/seguranca');
        }

        $this->limparFalhasSenha($usuarioId);
        $this->perfil->atualizarSenha($usuarioId, password_hash($senhaNova, PASSWORD_DEFAULT));
        Sessao::regenerar();

        Sessao::flash('success', 'Senha alterada com sucesso.');
        Sessao::redirecionar('/perfil/seguranca');
    }

    public function criarEndereco(): void
    {
        $sessao = $this->protegerPost();
        $usuarioId = (int) $sessao['id_usuario'];

        if ($this->enderecos->contarPorUsuario($usuarioId) >= self::MAX_ENDERECOS) {
            Sessao::flash('danger', 'Limite de ' . self::MAX_ENDERECOS . ' enderecos atingido.');
            Sessao::redirecionar('/perfil/enderecos');
        }

        $dados = $this->dadosEndereco($_POST);
        $erros = $this->validarEndereco($dados);

        if ($erros !== []) {
            Sessao::setOld($dados);
            Sessao::setErros($erros);
            Sessao::flash('danger', 'Revise os campos do endereco.');
            Sessao::redirecionar('/perfil/enderecos');
        }

        try {
            $this->enderecos->criar($usuarioId, $dados);
        } catch (Throwable $excecao) {
            error_log('[perfil] erro ao criar endereco usuario=' . $usuarioId);
            $this->responderErro(500, 'Erro interno', 'Nao foi possivel salvar o endereco agora.');
        }

        Sessao::flash('success', 'Endereco adicionado com sucesso.');
        Sessao::redirecionar('/perfil/enderecos');
    }

    public function atualizarEndereco(): void
    {
        $sessao = $this->protegerPost();
        $usuarioId = (int) $sessao['id_usuario'];
        $idEndereco = $this->idEnderecoPost();

        if ($idEndereco === null) {
            Sessao::flash('danger', 'Endereco nao encontrado.');
            Sessao::redirecionar('/perfil/enderecos');
        }

        $dados = $this->dadosEndereco($_POST);
        $erros = $this->validarEndereco($dados);

        if ($erros !== []) {
            Sessao::setOld($dados);
            Sessao::setErros($erros);
            Sessao::flash('danger', 'Revise os campos do endereco.');
            Sessao::redirecionar('/perfil/enderecos?editar=' . $idEndereco);
        }

        try {
            $atualizou = $this->enderecos->atualizar($idEndereco, $usuarioId, $dados);
        } catch (Throwable $excecao) {
            error_log('[perfil] erro ao atualizar endereco usuario=' . $usuarioId);
            $this->responderErro(500, 'Erro interno', 'Nao foi possivel salvar o endereco agora.');
        }

        if (!$atualizou) {
            Sessao::flash('danger', 'Endereco nao encontrado.');
            Sessao::redirecionar('/perfil/enderecos');
        }

        Sessao::flash('success', 'Endereco atualizado com sucesso.');
        Sessao::redirecionar('/perfil/enderecos');
    }

    public function excluirEndereco(): void
    {
        $sessao = $this->protegerPost();
        $usuarioId = (int) $sessao['id_usuario'];
        $idEndereco = $this->idEnderecoPost();

        if ($idEndereco === null || !$this->enderecos->excluir($idEndereco, $usuarioId)) {
            Sessao::flash('danger', 'Endereco nao encontrado.');
            Sessao::redirecionar('/perfil/enderecos');
        }

        Sessao::flash('success', 'Endereco removido.');
        Sessao::redirecionar('/perfil/enderecos');
    }

    public function definirPrincipal(): void
    {
        $sessao = $this->protegerPost();
        $usuarioId = (int) $sessao['id_usuario'];
        $idEndereco = $this->idEnderecoPost();

        if ($idEndereco === null || !$this->enderecos->definirPrincipal($idEndereco, $usuarioId)) {
            Sessao::flash('danger', 'Endereco nao encontrado.');
            Sessao::redirecionar('/perfil/enderecos');
        }

        Sessao::flash('success', 'Endereco principal atualizado.');
        Sessao::redirecionar('/perfil/enderecos');
    }

    public function sair(): void
    {
        $this->protegerPost();
        Sessao::sair();
        Sessao::redirecionar('/');
    }

    private function renderizar(string $aba): void
    {
        $sessao = $this->proteger();
        $usuarioId = (int) $sessao['id_usuario'];

        $usuario = $this->perfil->buscarPorId($usuarioId);

        if ($usuario === null) {
            $this->contaAusente();
        }

        $enderecos = [];
        $editando = null;
        $excluindo = null;

        if ($aba === 'enderecos') {
            $enderecos = $this->enderecos->listarPorUsuario($usuarioId);

            $editarId = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT);

            if (is_int($editarId) && $editarId > 0) {
                $editando = $this->enderecos->buscarDoUsuario($editarId, $usuarioId);

                if ($editando === null) {
                    Sessao::flash('danger', 'Endereco nao encontrado.');
                    Sessao::redirecionar('/perfil/enderecos');
                }
            }

            $excluirId = filter_input(INPUT_GET, 'excluir', FILTER_VALIDATE_INT);

            if (is_int($excluirId) && $excluirId > 0) {
                $excluindo = $this->enderecos->buscarDoUsuario($excluirId, $usuarioId);

                if ($excluindo === null) {
                    Sessao::flash('danger', 'Endereco nao encontrado.');
                    Sessao::redirecionar('/perfil/enderecos');
                }
            }
        }

        $flash = Sessao::mensagensFlash();
        $erros = Sessao::erros();
        $old = Sessao::old();

        require __DIR__ . '/../views/perfil.php';
    }

    private function proteger(): array
    {
        Sessao::cabecalhosSeguranca();

        if (!RateLimit::consumir('req_ip', RateLimit::ip(), self::LIMITE_REQ_IP, 60)) {
            $this->responderErro(429, 'Muitas requisicoes', 'Aguarde um instante e tente novamente.', 60);
        }

        return Sessao::exigirLogin();
    }

    private function protegerPost(): array
    {
        $sessao = $this->proteger();
        $usuarioId = (int) $sessao['id_usuario'];

        $chave = 'u:' . $usuarioId . '|ip:' . RateLimit::ip();

        if (!RateLimit::consumir('post_usuario', $chave, self::LIMITE_POST, 60)) {
            $this->responderErro(429, 'Muitas requisicoes', 'Aguarde um instante e tente novamente.', 60);
        }

        if (!Sessao::validarCsrf($_POST['_csrf'] ?? null)) {
            $this->responderErro(403, 'Sessao invalida', 'Recarregue a pagina e tente novamente.');
        }

        return $sessao;
    }

    private function dadosEndereco(array $post): array
    {
        $complemento = Validacao::texto($post['complemento'] ?? '');

        return [
            'nome_endereco' => Validacao::texto($post['nome_endereco'] ?? ''),
            'logradouro' => Validacao::texto($post['logradouro'] ?? ''),
            'numero' => Validacao::texto($post['numero'] ?? ''),
            'complemento' => $complemento === '' ? null : $complemento,
            'bairro' => Validacao::texto($post['bairro'] ?? ''),
            'cidade' => Validacao::texto($post['cidade'] ?? ''),
            'uf' => strtoupper(Validacao::texto($post['uf'] ?? '')),
            'cep' => Validacao::somenteDigitos($post['cep'] ?? ''),
        ];
    }

    private function validarEndereco(array $dados): array
    {
        $erros = [];

        if ($dados['nome_endereco'] === '' || strlen($dados['nome_endereco']) > 30) {
            $erros['nome_endereco'] = 'Informe um nome para o endereco (ate 30 caracteres).';
        }

        if ($dados['logradouro'] === '' || strlen($dados['logradouro']) > 100) {
            $erros['logradouro'] = 'Informe o endereco (ate 100 caracteres).';
        }

        if ($dados['numero'] === '' || strlen($dados['numero']) > 10) {
            $erros['numero'] = 'Informe o numero (ate 10 caracteres).';
        }

        if ($dados['complemento'] !== null && strlen($dados['complemento']) > 60) {
            $erros['complemento'] = 'O complemento deve ter no maximo 60 caracteres.';
        }

        if ($dados['bairro'] === '' || strlen($dados['bairro']) > 60) {
            $erros['bairro'] = 'Informe o bairro (ate 60 caracteres).';
        }

        if ($dados['cidade'] === '' || strlen($dados['cidade']) > 60) {
            $erros['cidade'] = 'Informe a cidade (ate 60 caracteres).';
        }

        if (!Validacao::uf($dados['uf'])) {
            $erros['uf'] = 'Informe uma UF valida.';
        }

        if (!Validacao::cep($dados['cep'])) {
            $erros['cep'] = 'Informe um CEP com 8 digitos.';
        }

        return $erros;
    }

    private function idEnderecoPost(): ?int
    {
        $id = filter_var($_POST['id_endereco'] ?? null, FILTER_VALIDATE_INT);

        return is_int($id) && $id > 0 ? $id : null;
    }

    private function senhaConfere(int $usuarioId, string $senha): bool
    {
        $hash = $this->perfil->buscarSenhaHash($usuarioId);

        if ($hash === null) {
            // Hash descartavel para manter tempo de resposta semelhante.
            password_verify($senha, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

            return false;
        }

        return password_verify($senha, $hash);
    }

    private function bloqueadoPorSenha(int $usuarioId): bool
    {
        $bloqueio = (int) ($_SESSION['_perfil_bloqueio'] ?? 0);

        if ($bloqueio > time()) {
            return true;
        }

        $chaveIp = 'senha|u:' . $usuarioId . '|ip:' . RateLimit::ip();
        $chaveUsuario = 'senha|u:' . $usuarioId;

        return RateLimit::contar('senha_falha', $chaveIp, self::JANELA_TENTATIVAS) >= self::MAX_TENTATIVAS_IP
            || RateLimit::contar('senha_falha', $chaveUsuario, self::JANELA_TENTATIVAS) >= self::MAX_TENTATIVAS_USUARIO;
    }

    private function registrarFalhaSenha(int $usuarioId): void
    {
        RateLimit::registrar(
            'senha_falha',
            'senha|u:' . $usuarioId . '|ip:' . RateLimit::ip(),
            self::JANELA_TENTATIVAS
        );
        RateLimit::registrar('senha_falha', 'senha|u:' . $usuarioId, self::JANELA_TENTATIVAS);

        $falhas = (int) ($_SESSION['_perfil_falhas'] ?? 0) + 1;
        $_SESSION['_perfil_falhas'] = $falhas;

        if ($falhas >= self::MAX_TENTATIVAS_IP) {
            $_SESSION['_perfil_bloqueio'] = time() + self::JANELA_TENTATIVAS;
        }
    }

    private function limparFalhasSenha(int $usuarioId): void
    {
        RateLimit::limpar('senha_falha', 'senha|u:' . $usuarioId . '|ip:' . RateLimit::ip());
        RateLimit::limpar('senha_falha', 'senha|u:' . $usuarioId);
        unset($_SESSION['_perfil_falhas'], $_SESSION['_perfil_bloqueio']);
    }

    private function contaAusente(): never
    {
        Sessao::sair();
        Sessao::redirecionar('/login');
    }

    private function responderErro(int $codigo, string $titulo, string $mensagem, ?int $retryAfter = null): never
    {
        http_response_code($codigo);

        if ($retryAfter !== null) {
            header('Retry-After: ' . $retryAfter);
        }

        require __DIR__ . '/../views/erro.php';
        exit;
    }
}
