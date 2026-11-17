<?php

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../repositories/avaliacao_repository.php';

class AvaliacaoController
{
    private AvaliacaoRepository $repository;

    public function __construct(private PDO $pdo)
    {
        $this->repository = new AvaliacaoRepository($pdo);
    }

    /**
     * Cria ou atualiza a avaliação do usuário logado.
     */
    public function salvar(): void
    {
        $usuario = $this->exigirLogin();

        $produtoId = post_int('produto_id');
        if ($produtoId === null || $produtoId <= 0) {
            Auth::flash('danger', 'Produto inválido.');
            redirect('/categoria');
        }

        $voltar = '/produto?id=' . $produtoId . '#avaliacoes';
        $this->exigirCsrf($voltar);

        $nota = post_int('nota');
        $comentario = trim((string) ($_POST['comentario'] ?? ''));

        $erros = [];

        if ($nota === null || $nota < 1 || $nota > 5) {
            $erros['nota'] = 'Escolha uma nota de 1 a 5 estrelas.';
        }

        if (mb_strlen($comentario) > 500) {
            $erros['comentario'] = 'O comentário pode ter no máximo 500 caracteres.';
        }

        if ($erros !== []) {
            Auth::setErrors($erros);
            Auth::setOld([
                'nota' => $_POST['nota'] ?? '',
                'comentario' => $comentario,
            ]);
            Auth::flash('danger', 'Não foi possível salvar sua avaliação.');
            redirect($voltar);
        }

        $this->repository->salvar(
            (int) $usuario['id_usuario'],
            $produtoId,
            (int) $nota,
            $comentario === '' ? null : $comentario
        );

        Auth::flash('success', 'Avaliação publicada. Obrigada por compartilhar!');
        redirect($voltar);
    }

    /**
     * Remove (soft delete) a avaliação do usuário logado.
     */
    public function remover(): void
    {
        $usuario = $this->exigirLogin();

        $produtoId = post_int('produto_id');
        if ($produtoId === null || $produtoId <= 0) {
            Auth::flash('danger', 'Produto inválido.');
            redirect('/categoria');
        }

        $voltar = '/produto?id=' . $produtoId . '#avaliacoes';
        $this->exigirCsrf($voltar);

        $removeu = $this->repository->remover((int) $usuario['id_usuario'], $produtoId);

        Auth::flash(
            $removeu ? 'success' : 'warning',
            $removeu ? 'Sua avaliação foi removida.' : 'Você não tem avaliação neste produto.'
        );

        redirect($voltar);
    }

    /**
     * @return array<string, mixed>
     */
    private function exigirLogin(): array
    {
        $usuario = Auth::user();

        if ($usuario === null) {
            Auth::flash('warning', 'Entre na sua conta para avaliar um produto.');
            redirect('/login');
        }

        return $usuario;
    }

    private function exigirCsrf(string $voltar): void
    {
        $token = $_POST['_csrf'] ?? null;

        if (!Auth::validCsrf(is_string($token) ? $token : null)) {
            Auth::flash('danger', 'Sessão expirada. Recarregue a página e tente novamente.');
            redirect($voltar);
        }
    }
}
