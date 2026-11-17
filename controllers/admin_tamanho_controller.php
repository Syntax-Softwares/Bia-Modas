<?php

require_once __DIR__ . '/admin_controller.php';
require_once __DIR__ . '/../repositories/tamanho_repository.php';

class AdminTamanhoController extends AdminController
{
    private TamanhoRepository $repository;

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);

        $this->repository = new TamanhoRepository($pdo);
    }

    public function index(): void
    {
        $tamanhos = $this->repository->listar();
        $flashes = Auth::takeFlashes();
        $admin = $this->admin;

        $titulo = 'Tamanhos e medidas';
        $ativo = 'tamanhos';

        ob_start();
        require __DIR__ . '/../views/admin/tamanhos/listar.php';
        $conteudo = (string) ob_get_clean();

        require __DIR__ . '/../views/admin/layout.php';
    }

    public function novo(): void
    {
        $valores = array_merge(
            ['tamanho' => '', 'busto' => '', 'cintura' => '', 'quadril' => ''],
            Auth::takeOld()
        );
        $errors = Auth::takeErrors();
        $flashes = Auth::takeFlashes();
        $admin = $this->admin;

        $titulo = 'Novo tamanho';
        $ativo = 'tamanhos';

        ob_start();
        require __DIR__ . '/../views/admin/tamanhos/form.php';
        $conteudo = (string) ob_get_clean();

        require __DIR__ . '/../views/admin/layout.php';
    }

    public function criar(): void
    {
        $this->validarCsrf('/admin/tamanhos/novo');

        [$dados, $errors, $old] = $this->validar(null);

        if ($errors !== []) {
            $this->voltarComErros(
                '/admin/tamanhos/novo',
                $old,
                $errors,
                'Revise os campos destacados para criar o tamanho.'
            );
        }

        $this->repository->criar($dados);
        Auth::flash('success', 'Tamanho criado com sucesso.');
        redirect('/admin/tamanhos');
    }

    public function editar(string $id): void
    {
        $tamanho = $this->repository->getPorId((int) $id);

        if ($tamanho === null) {
            $this->naoEncontrado();
        }

        $valores = array_merge($tamanho, Auth::takeOld());
        $errors = Auth::takeErrors();
        $flashes = Auth::takeFlashes();
        $admin = $this->admin;

        $titulo = 'Editar tamanho';
        $ativo = 'tamanhos';

        ob_start();
        require __DIR__ . '/../views/admin/tamanhos/form.php';
        $conteudo = (string) ob_get_clean();

        require __DIR__ . '/../views/admin/layout.php';
    }

    public function atualizar(string $id): void
    {
        $tamanho = $this->repository->getPorId((int) $id);

        if ($tamanho === null) {
            $this->naoEncontrado();
        }

        $destino = '/admin/tamanhos/' . (int) $id . '/editar';
        $this->validarCsrf($destino);

        [$dados, $errors, $old] = $this->validar((int) $id);

        if ($errors !== []) {
            $this->voltarComErros($destino, $old, $errors, 'Revise os campos destacados para salvar o tamanho.');
        }

        $this->repository->atualizar((int) $id, $dados);
        Auth::flash('success', 'Tamanho atualizado com sucesso.');
        redirect('/admin/tamanhos');
    }

    public function excluir(string $id): void
    {
        $id = (int) $id;
        $this->validarCsrf('/admin/tamanhos');

        if ($this->repository->getPorId($id) === null) {
            Auth::flash('danger', 'Tamanho não encontrado.');
            redirect('/admin/tamanhos');
        }

        if ($this->repository->emUso($id)) {
            Auth::flash('danger', 'Não é possível excluir: existem produtos usando este tamanho.');
            redirect('/admin/tamanhos');
        }

        $this->repository->excluir($id);
        Auth::flash('success', 'Tamanho excluído com sucesso.');
        redirect('/admin/tamanhos');
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, string>, 2: array<string, mixed>}
     */
    private function validar(?int $id): array
    {
        $tamanho = $this->texto('tamanho');
        $errors = [];
        $medidas = [];
        $oldMedidas = [];

        if (mb_strlen($tamanho) < 1 || mb_strlen($tamanho) > 5) {
            $errors['tamanho'] = 'Informe o tamanho com até 5 caracteres (ex.: P, M, GG, 38).';
        } elseif ($this->repository->tamanhoEmUso($tamanho, $id)) {
            $errors['tamanho'] = 'Já existe um tamanho com esta descrição.';
        }

        foreach (['busto', 'cintura', 'quadril'] as $campo) {
            $bruto = $this->texto($campo);
            $oldMedidas[$campo] = $bruto;

            if ($bruto === '') {
                $medidas[$campo] = null;
                continue;
            }

            $valor = $this->decimal($campo);

            if ($valor === null || $valor <= 0 || $valor > 300) {
                $errors[$campo] = 'Informe uma medida positiva de até 300 cm.';
                continue;
            }

            $medidas[$campo] = $valor;
        }

        $dados = [
            'tamanho' => $tamanho,
            'busto' => $medidas['busto'],
            'cintura' => $medidas['cintura'],
            'quadril' => $medidas['quadril'],
        ];

        $old = array_merge(['tamanho' => $tamanho], $oldMedidas);

        return [$dados, $errors, $old];
    }
}
