<?php

require_once __DIR__ . '/admin_controller.php';
require_once __DIR__ . '/../repositories/admin_produto_repository.php';

class AdminEstoqueController extends AdminController
{
    private const POR_PAGINA = 20;
    private const ESTOQUE_BAIXO = 5;

    private AdminProdutoRepository $repository;

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);

        $this->repository = new AdminProdutoRepository($pdo);
    }

    public function index(): void
    {
        $pagina = $this->paginaAtual();
        $busca = trim((string) ($_GET['q'] ?? ''));
        $somenteBaixo = ($_GET['baixo'] ?? '') === '1';

        $resultado = $this->repository->listarParaEstoque(
            $busca,
            $somenteBaixo,
            $pagina,
            self::POR_PAGINA,
            self::ESTOQUE_BAIXO
        );

        $totalPaginas = max(1, (int) ceil($resultado['total'] / self::POR_PAGINA));
        $flashes = Auth::takeFlashes();
        $admin = $this->admin;

        $titulo = 'Estoque';
        $ativo = 'estoque';

        ob_start();
        require __DIR__ . '/../views/admin/estoque/listar.php';
        $conteudo = (string) ob_get_clean();

        require __DIR__ . '/../views/admin/layout.php';
    }

    public function atualizar(string $id): void
    {
        $id = (int) $id;
        $this->validarCsrf('/admin/estoque');

        $estoque = $this->inteiro('estoque');

        if ($estoque === null || $estoque < 0 || $estoque > 99999) {
            Auth::flash('danger', 'Informe um estoque entre 0 e 99.999.');
            $this->redirecionarComFiltros();
        }

        if (!$this->repository->atualizarEstoque($id, $estoque)) {
            Auth::flash('danger', 'Produto não encontrado ou já excluído.');
            $this->redirecionarComFiltros();
        }

        Auth::flash('success', 'Estoque atualizado com sucesso.');
        $this->redirecionarComFiltros();
    }

    private function redirecionarComFiltros(): never
    {
        $query = array_filter([
            'q' => trim((string) ($_POST['q'] ?? '')),
            'baixo' => ($_POST['baixo'] ?? '') === '1' ? '1' : '',
        ]);

        redirect(url_com_query('/admin/estoque', $query));
    }
}
