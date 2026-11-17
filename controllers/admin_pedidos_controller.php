<?php

require_once __DIR__ . '/../core/admin.php';
require_once __DIR__ . '/../core/sessao.php';
require_once __DIR__ . '/../repositories/admin_repository.php';

class AdminPedidosController
{
    private AdminRepository $repository;

    public function __construct(private PDO $pdo)
    {
        Admin::exigir();
        $this->repository = new AdminRepository($pdo);
    }

    public function index(): void
    {
        $pedidos = $this->repository->listarPedidos();
        $flashes = Sessao::mensagensFlash();

        $tituloAdmin = 'Pedidos';
        $abaAdmin = 'pedidos';

        require __DIR__ . '/../views/admin/pedidos.php';
    }

    public function ver(): void
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

        if (!is_int($id) || $id <= 0) {
            $this->naoEncontrado();
            return;
        }

        $pedido = $this->repository->buscarPedido($id);

        if ($pedido === null) {
            $this->naoEncontrado();
            return;
        }

        $itens = $this->repository->itensPedido($id);
        $flashes = Sessao::mensagensFlash();

        $tituloAdmin = 'Pedido #' . (int) $pedido['numero_pedido'];
        $abaAdmin = 'pedidos';

        require __DIR__ . '/../views/admin/pedido.php';
    }

    public function atualizar(): void
    {
        $this->exigirCsrf();

        $id = filter_var($_POST['id_compra'] ?? null, FILTER_VALIDATE_INT);
        $statusPagamento = (string) ($_POST['status_pagamento'] ?? 'pendente');

        if (!is_int($id) || $id <= 0) {
            Sessao::flash('danger', 'Pedido inválido.');
            redirect('/admin/pedidos');
        }

        if (!in_array($statusPagamento, ['pendente', 'pago', 'cancelado'], true)) {
            $statusPagamento = 'pendente';
        }

        $this->repository->atualizarStatusPedido($id, isset($_POST['concluido']), $statusPagamento);
        Sessao::flash('success', 'Status do pedido atualizado.');

        redirect('/admin/pedidos/ver?id=' . $id);
    }

    private function exigirCsrf(): void
    {
        if (!Sessao::validarCsrf($_POST['_csrf'] ?? null)) {
            Sessao::flash('danger', 'Sessão expirada. Tente novamente.');
            redirect('/admin/pedidos');
        }
    }

    private function naoEncontrado(): void
    {
        http_response_code(404);
        $codigo = 404;
        $titulo = 'Pedido não encontrado';
        $mensagem = 'O pedido informado não existe ou foi removido.';
        $voltar = '/admin/pedidos';

        require __DIR__ . '/../views/erro.php';
    }
}
