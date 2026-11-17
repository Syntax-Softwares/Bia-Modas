<?php

require_once __DIR__ . '/admin_controller.php';
require_once __DIR__ . '/../repositories/dashboard_repository.php';

class AdminDashboardController extends AdminController
{
    private DashboardRepository $repository;

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);

        $this->repository = new DashboardRepository($pdo);
    }

    public function index(): void
    {
        $resumo = $this->repository->resumo();
        $ultimosProdutos = $this->repository->ultimosProdutos();
        $ultimosUsuarios = $this->repository->ultimosUsuarios();

        $titulo = 'Painel administrativo';
        $ativo = 'painel';
        $flashes = Auth::takeFlashes();
        $admin = $this->admin;

        ob_start();
        require __DIR__ . '/../views/admin/dashboard.php';
        $conteudo = (string) ob_get_clean();

        require __DIR__ . '/../views/admin/layout.php';
    }
}
