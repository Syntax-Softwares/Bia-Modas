<?php

require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../repositories/usuario_repository.php';

/**
 * Base de todos os controllers administrativos.
 *
 * Revalida o papel de administrador no banco a cada requisição: esconder o
 * menu não é autorização. Usuário deslogado vai para o login; usuário logado
 * sem permissão recebe 403.
 */
abstract class AdminController
{
    /** @var array{id_usuario: int, nome: string, email: string, is_admin: bool} */
    protected array $admin;

    public function __construct(protected PDO $pdo)
    {
        $sessao = Auth::user();

        if ($sessao === null) {
            Auth::flash('info', 'Entre com uma conta administrativa para continuar.');
            redirect('/login');
        }

        $usuarios = new UsuarioRepository($pdo);
        $usuario = $usuarios->buscarAdminAtivo((int) $sessao['id_usuario']);

        if ($usuario === null) {
            Auth::logout();
            Auth::flash('danger', 'Sua conta não está mais disponível. Entre novamente.');
            redirect('/login');
        }

        // Mantém a sessão coerente com o banco (promoção/rebaixamento).
        Auth::sync($usuario);

        if (!$usuario['is_admin']) {
            http_response_code(403);
            require __DIR__ . '/../views/403.php';
            exit;
        }

        $this->admin = $usuario;
    }

    /**
     * Valida o token CSRF de qualquer POST administrativo.
     */
    protected function validarCsrf(string $redirecionarPara): void
    {
        if (!Auth::validCsrf($_POST['_csrf'] ?? null)) {
            Auth::flash('danger', 'Sessão expirada. Tente novamente.');
            redirect($redirecionarPara);
        }
    }

    protected function paginaAtual(): int
    {
        $pagina = filter_input(INPUT_GET, 'pagina', FILTER_VALIDATE_INT);

        return $pagina !== false && $pagina !== null && $pagina > 0 ? (int) $pagina : 1;
    }

    protected function texto(string $campo): string
    {
        return trim((string) ($_POST[$campo] ?? ''));
    }

    /**
     * Converte "1.234,56" ou "1234.56" em float; retorna null se vazio/inválido.
     */
    protected function decimal(string $campo): ?float
    {
        $valor = trim((string) ($_POST[$campo] ?? ''));

        if ($valor === '') {
            return null;
        }

        if (str_contains($valor, ',')) {
            // Formato brasileiro: ponto é separador de milhar.
            $valor = str_replace('.', '', $valor);
            $valor = str_replace(',', '.', $valor);
        } elseif (substr_count($valor, '.') > 1) {
            // Vários pontos: o último é o separador decimal.
            $partes = explode('.', $valor);
            $decimal = array_pop($partes);
            $valor = implode('', $partes) . '.' . $decimal;
        }

        return is_numeric($valor) ? (float) $valor : null;
    }

    protected function inteiro(string $campo): ?int
    {
        $valor = trim((string) ($_POST[$campo] ?? ''));

        if ($valor === '' || !preg_match('/^-?\d+$/', $valor)) {
            return null;
        }

        return (int) $valor;
    }

    /**
     * Redireciona de volta ao formulário preservando valores e erros.
     */
    protected function voltarComErros(string $destino, array $old, array $errors, string $mensagem): never
    {
        Auth::setOld($old);
        Auth::setErrors($errors);
        Auth::flash('danger', $mensagem);
        redirect($destino);
    }

    protected function naoEncontrado(): never
    {
        http_response_code(404);
        require __DIR__ . '/../views/404.php';
        exit;
    }
}
