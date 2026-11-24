<?php

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/sessao.php';
require_once __DIR__ . '/../repositories/avaliacao_repository.php';
require_once __DIR__ . '/../repositories/produto_repository.php';

class AvaliacaoController
{
    private AvaliacaoRepository $avaliacoes;
    private ProdutoRepository $produtos;

    public function __construct(private PDO $pdo)
    {
        $this->avaliacoes = new AvaliacaoRepository($pdo);
        $this->produtos = new ProdutoRepository($pdo);
    }

    /**
     * Cria ou atualiza a avaliacao do usuario logado.
     */
    public function salvar(): void
    {
        $usuario = Sessao::usuario();

        if ($usuario === null) {
            Sessao::flash('warning', 'Entre na sua conta para avaliar um produto.');
            redirect('/login');
        }

        $produtoId = post_int('produto_id');

        if ($produtoId === null || $produtoId <= 0) {
            Sessao::flash('danger', 'Produto inválido.');
            redirect('/categoria');
        }

        $voltar = '/produto?id=' . $produtoId . '#avaliacoes';
        $this->exigirCsrf($voltar);

        if ($this->produtos->buscarPorId($produtoId) === null) {
            Sessao::flash('danger', 'Produto não encontrado.');
            redirect('/categoria');
        }

        $nota = post_int('nota');
        $comentario = trim((string) ($_POST['comentario'] ?? ''));

        $erros = [];

        if ($nota === null || $nota < 1 || $nota > 5) {
            $erros['nota'] = 'Escolha uma nota de 1 a 5 estrelas.';
        }

        if (tamanho_texto($comentario) > 500) {
            $erros['comentario'] = 'O comentário pode ter no máximo 500 caracteres.';
        }

        if ($erros !== []) {
            Sessao::setErros($erros);
            Sessao::setOld([
                'nota' => $_POST['nota'] ?? '',
                'comentario' => $comentario,
            ]);
            Sessao::flash('danger', 'Não foi possível salvar sua avaliação.');
            redirect($voltar);
        }

        $this->avaliacoes->salvar(
            (int) $usuario['id_usuario'],
            $produtoId,
            (int) $nota,
            $comentario === '' ? null : $comentario
        );

        Sessao::flash('success', 'Avaliação publicada. Obrigada por compartilhar!');
        redirect($voltar);
    }

    /**
     * Remove (soft delete) a avaliacao do usuario logado.
     */
    public function remover(): void
    {
        $usuario = Sessao::usuario();

        if ($usuario === null) {
            Sessao::flash('warning', 'Entre na sua conta para remover uma avaliação.');
            redirect('/login');
        }

        $produtoId = post_int('produto_id');

        if ($produtoId === null || $produtoId <= 0) {
            Sessao::flash('danger', 'Produto inválido.');
            redirect('/categoria');
        }

        $voltar = '/produto?id=' . $produtoId . '#avaliacoes';
        $this->exigirCsrf($voltar);

        $removeu = $this->avaliacoes->remover((int) $usuario['id_usuario'], $produtoId);

        Sessao::flash(
            $removeu ? 'success' : 'warning',
            $removeu ? 'Sua avaliação foi removida.' : 'Você não tem avaliação neste produto.'
        );

        redirect($voltar);
    }

    private function exigirCsrf(string $voltar): void
    {
        if (!Sessao::validarCsrf($_POST['_csrf'] ?? null)) {
            Sessao::flash('danger', 'Sessão expirada. Recarregue a página e tente novamente.');
            redirect($voltar);
        }
    }
}
