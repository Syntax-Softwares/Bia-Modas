<?php

require_once __DIR__ . '/../core/admin.php';
require_once __DIR__ . '/../core/sessao.php';
require_once __DIR__ . '/../repositories/admin_repository.php';

class AdminController
{
    private AdminRepository $repository;

    public function __construct(private PDO $pdo)
    {
        Admin::exigir();
        $this->repository = new AdminRepository($pdo);
    }

    public function index(): void
    {
        $resumo = $this->repository->resumo();
        $ultimosPedidos = $this->repository->ultimosPedidos(6);
        $estoqueBaixo = $this->repository->produtosEstoqueBaixo(6);
        $flashes = Sessao::mensagensFlash();

        $tituloAdmin = 'Dashboard';
        $abaAdmin = 'dashboard';

        require __DIR__ . '/../views/admin/dashboard.php';
    }
}
