<?php

require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/mailer.php';
require_once __DIR__ . '/../repositories/usuario_repository.php';
require_once __DIR__ . '/../repositories/codigo_recuperacao_repository.php';

class RecuperacaoSenhaController
{
    private const EXPIRACAO_MINUTOS = 10;
    private const MAX_TENTATIVAS = 5;
    private const INTERVALO_REENVIO = 60;
    private const CHAVE_SESSAO = 'recuperacao';

    private UsuarioRepository $usuarios;
    private CodigoRecuperacaoRepository $codigos;
    private Mailer $mailer;

    public function __construct(private PDO $pdo, ?Mailer $mailer = null)
    {
        $this->usuarios = new UsuarioRepository($pdo);
        $this->codigos = new CodigoRecuperacaoRepository($pdo);
        $this->mailer = $mailer ?? Mailer::fromEnv();
    }

    public function showSolicitar(): void
    {
        $old = Auth::takeOld();
        $errors = Auth::takeErrors();
        $flashes = Auth::takeFlashes();

        require __DIR__ . '/../views/recuperar_senha.php';
    }

    public function solicitar(): void
    {
        $this->exigirCsrf('/recuperar-senha');

        $email = strtolower(trim((string) ($_POST['email'] ?? '')));

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            Auth::setOld(['email' => $email]);
            Auth::setErrors(['email' => 'Informe um e-mail válido.']);
            redirect('/recuperar-senha');
        }

        $usuario = $this->usuarios->buscarPorEmail($email);

        // A resposta é a mesma exista ou não a conta (anti-enumeração).
        unset($_SESSION[self::CHAVE_SESSAO]);
        $_SESSION[self::CHAVE_SESSAO] = [
            'usuario_id' => $usuario !== null ? (int) $usuario['id_usuario'] : null,
            'email' => $email,
            'expira_em' => time() + self::EXPIRACAO_MINUTOS * 60,
            'ultima_solicitacao' => time(),
        ];

        if ($usuario !== null) {
            $this->enviarCodigo($usuario);
        }

        Auth::flash('info', 'Se este e-mail estiver cadastrado, enviaremos um código de verificação.');

        redirect('/recuperar-senha/verificar');
    }

    public function showVerificar(): void
    {
        $sessao = $this->sessaoAtiva();

        if ($sessao === null) {
            Auth::flash('danger', 'Sua solicitação expirou. Recomece a recuperação.');
            redirect('/recuperar-senha');
        }

        $old = Auth::takeOld();
        $errors = Auth::takeErrors();
        $flashes = Auth::takeFlashes();
        $emailMascarado = $this->mascararEmail((string) $sessao['email']);

        require __DIR__ . '/../views/verificar_codigo.php';
    }

    public function verificar(): void
    {
        $this->exigirCsrf('/recuperar-senha/verificar');

        $sessao = $this->sessaoAtiva();

        if ($sessao === null) {
            Auth::flash('danger', 'Sua solicitação expirou. Recomece a recuperação.');
            redirect('/recuperar-senha');
        }

        $codigo = preg_replace('/\D+/', '', (string) ($_POST['codigo'] ?? '')) ?? '';
        $senha = (string) ($_POST['senha'] ?? '');
        $senhaConfirmar = (string) ($_POST['senha_confirmar'] ?? '');

        $errors = [];

        if (strlen($codigo) !== 6) {
            $errors['codigo'] = 'Informe o código de 6 dígitos.';
        }

        if (strlen($senha) < 6 || strlen($senha) > 100) {
            $errors['senha'] = 'A senha deve ter entre 6 e 100 caracteres.';
        }

        if ($senhaConfirmar !== $senha) {
            $errors['senha_confirmar'] = 'As senhas não coincidem.';
        }

        if ($errors !== []) {
            Auth::setOld(['codigo' => $codigo]);
            Auth::setErrors($errors);
            redirect('/recuperar-senha/verificar');
        }

        $usuarioId = $sessao['usuario_id'];

        $registro = $usuarioId !== null
            ? $this->codigos->buscarAtivoPorUsuario((int) $usuarioId)
            : null;

        if ($registro === null || !password_verify($codigo, (string) $registro['codigo_hash'])) {
            $this->registrarFalha($registro);

            Auth::setOld(['codigo' => $codigo]);
            Auth::setErrors(['codigo' => 'Código inválido ou expirado.']);
            redirect('/recuperar-senha/verificar');
        }

        $usuario = $this->usuarios->buscarPorId((int) $usuarioId);

        if ($usuario === null) {
            $this->codigos->invalidarTodosDoUsuario((int) $usuarioId);
            unset($_SESSION[self::CHAVE_SESSAO]);
            Auth::flash('danger', 'Não foi possível concluir a recuperação. Recomece o processo.');
            redirect('/recuperar-senha');
        }

        $this->usuarios->atualizarSenhaHash(
            (int) $usuarioId,
            password_hash($senha, PASSWORD_DEFAULT)
        );

        $this->codigos->invalidar((int) $registro['id_codigo']);
        $this->codigos->invalidarTodosDoUsuario((int) $usuarioId);

        unset($_SESSION[self::CHAVE_SESSAO]);
        Auth::logout();

        require __DIR__ . '/../views/recuperacao_concluida.php';
    }

    public function reenviar(): void
    {
        $this->exigirCsrf('/recuperar-senha/verificar');

        $sessao = $this->sessaoAtiva();

        if ($sessao === null) {
            Auth::flash('danger', 'Sua solicitação expirou. Recomece a recuperação.');
            redirect('/recuperar-senha');
        }

        $decorridos = time() - (int) ($sessao['ultima_solicitacao'] ?? 0);
        $restantes = self::INTERVALO_REENVIO - $decorridos;

        // O intervalo é controlado pela sessão (e não pelo banco) para que a
        // mensagem seja idêntica exista ou não a conta.
        if ($restantes > 0) {
            Auth::flash('warning', 'Aguarde ' . $restantes . ' segundo(s) para reenviar.');
            redirect('/recuperar-senha/verificar');
        }

        $_SESSION[self::CHAVE_SESSAO]['ultima_solicitacao'] = time();
        $_SESSION[self::CHAVE_SESSAO]['expira_em'] = time() + self::EXPIRACAO_MINUTOS * 60;

        if ($sessao['usuario_id'] !== null) {
            $usuario = $this->usuarios->buscarPorId((int) $sessao['usuario_id']);

            if ($usuario !== null) {
                $this->enviarCodigo($usuario);
            }
        }

        Auth::flash('info', 'Se este e-mail estiver cadastrado, enviaremos um novo código.');

        redirect('/recuperar-senha/verificar');
    }

    /**
     * Gera o código, grava apenas o hash e envia por e-mail.
     * Falha de SMTP é registrada no log e não é revelada ao usuário.
     */
    private function enviarCodigo(array $usuario): bool
    {
        $usuarioId = (int) $usuario['id_usuario'];

        if (!$this->codigos->podeSolicitar($usuarioId)) {
            return false;
        }

        $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->codigos->criar($usuarioId, password_hash($codigo, PASSWORD_DEFAULT));

        try {
            $this->mailer->enviarCodigo((string) $usuario['email'], (string) $usuario['nome'], $codigo);
        } catch (Throwable $erro) {
            error_log('Falha ao enviar código de recuperação: ' . $erro->getMessage());

            return false;
        }

        return true;
    }

    private function registrarFalha(?array $registro): void
    {
        if ($registro === null) {
            return;
        }

        $idCodigo = (int) $registro['id_codigo'];
        $tentativas = (int) $registro['tentativas'] + 1;

        $this->codigos->registrarTentativa($idCodigo);

        if ($tentativas >= self::MAX_TENTATIVAS) {
            $this->codigos->invalidar($idCodigo);
        }
    }

    private function sessaoAtiva(): ?array
    {
        $sessao = $_SESSION[self::CHAVE_SESSAO] ?? null;

        if (!is_array($sessao) || (int) ($sessao['expira_em'] ?? 0) < time()) {
            unset($_SESSION[self::CHAVE_SESSAO]);

            return null;
        }

        return $sessao;
    }

    private function mascararEmail(string $email): string
    {
        $partes = explode('@', $email, 2);

        if (count($partes) !== 2 || $partes[0] === '') {
            return $email;
        }

        return substr($partes[0], 0, 1) . '***@' . $partes[1];
    }

    private function exigirCsrf(string $destino): void
    {
        if (!Auth::validCsrf($_POST['_csrf'] ?? null)) {
            Auth::flash('danger', 'Sessão expirada. Tente novamente.');
            redirect($destino);
        }
    }
}
