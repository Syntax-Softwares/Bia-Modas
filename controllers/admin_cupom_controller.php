<?php

require_once __DIR__ . '/admin_controller.php';
require_once __DIR__ . '/../repositories/cupom_repository.php';

class AdminCupomController extends AdminController
{
    private CupomRepository $repository;

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);

        $this->repository = new CupomRepository($pdo);
    }

    public function index(): void
    {
        $cupons = $this->repository->listar();
        $flashes = Auth::takeFlashes();
        $admin = $this->admin;

        $titulo = 'Cupons';
        $ativo = 'cupons';

        ob_start();
        require __DIR__ . '/../views/admin/cupons/listar.php';
        $conteudo = (string) ob_get_clean();

        require __DIR__ . '/../views/admin/layout.php';
    }

    public function novo(): void
    {
        $valores = array_merge(
            [
                'codigo' => '',
                'desconto' => '',
                'val_min' => '0.00',
                'val_max' => '',
                'frete_gratis' => 0,
            ],
            Auth::takeOld()
        );
        $errors = Auth::takeErrors();
        $flashes = Auth::takeFlashes();
        $admin = $this->admin;

        $titulo = 'Novo cupom';
        $ativo = 'cupons';

        ob_start();
        require __DIR__ . '/../views/admin/cupons/form.php';
        $conteudo = (string) ob_get_clean();

        require __DIR__ . '/../views/admin/layout.php';
    }

    public function criar(): void
    {
        $this->validarCsrf('/admin/cupons/novo');

        [$dados, $errors, $old] = $this->validar(null);

        if ($errors !== []) {
            $this->voltarComErros('/admin/cupons/novo', $old, $errors, 'Revise os campos destacados para criar o cupom.');
        }

        try {
            $this->repository->criar($dados);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $this->voltarComErros(
                    '/admin/cupons/novo',
                    $old,
                    ['codigo' => 'Já existe um cupom com este código.'],
                    'Não foi possível criar o cupom.'
                );
            }

            throw $exception;
        }

        Auth::flash('success', 'Cupom criado com sucesso.');
        redirect('/admin/cupons');
    }

    public function editar(string $id): void
    {
        $cupom = $this->repository->getPorId((int) $id);

        if ($cupom === null) {
            $this->naoEncontrado();
        }

        $valores = array_merge(
            [
                'codigo' => $cupom['codigo'],
                'desconto' => (int) round((float) $cupom['desconto'] * 100),
                'val_min' => $cupom['val_min'],
                'val_max' => $cupom['val_max'],
                'frete_gratis' => $cupom['frete_gratis'],
            ],
            Auth::takeOld()
        );
        $errors = Auth::takeErrors();
        $flashes = Auth::takeFlashes();
        $admin = $this->admin;

        $titulo = 'Editar cupom';
        $ativo = 'cupons';

        ob_start();
        require __DIR__ . '/../views/admin/cupons/form.php';
        $conteudo = (string) ob_get_clean();

        require __DIR__ . '/../views/admin/layout.php';
    }

    public function atualizar(string $id): void
    {
        $id = (int) $id;
        $destino = '/admin/cupons/' . $id . '/editar';
        $this->validarCsrf($destino);

        if ($this->repository->getPorId($id) === null) {
            $this->naoEncontrado();
        }

        [$dados, $errors, $old] = $this->validar($id);

        if ($errors !== []) {
            $this->voltarComErros($destino, $old, $errors, 'Revise os campos destacados para salvar o cupom.');
        }

        try {
            $this->repository->atualizar($id, $dados);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $this->voltarComErros(
                    $destino,
                    $old,
                    ['codigo' => 'Já existe um cupom com este código.'],
                    'Não foi possível salvar o cupom.'
                );
            }

            throw $exception;
        }

        Auth::flash('success', 'Cupom atualizado com sucesso.');
        redirect('/admin/cupons');
    }

    public function excluir(string $id): void
    {
        $id = (int) $id;
        $this->validarCsrf('/admin/cupons');

        if ($this->repository->getPorId($id) === null) {
            Auth::flash('danger', 'Cupom não encontrado.');
            redirect('/admin/cupons');
        }

        $this->repository->excluir($id);
        Auth::flash('success', 'Cupom excluído com sucesso.');
        redirect('/admin/cupons');
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, string>, 2: array<string, mixed>}
     */
    private function validar(?int $id): array
    {
        $codigo = strtoupper($this->texto('codigo'));
        $descontoBruto = $this->texto('desconto');
        $valMin = $this->decimal('val_min');
        $valMax = $this->decimal('val_max');
        $freteGratis = isset($_POST['frete_gratis']);

        $errors = [];

        if (mb_strlen($codigo) < 3 || mb_strlen($codigo) > 50) {
            $errors['codigo'] = 'Informe um código entre 3 e 50 caracteres.';
        } elseif (!preg_match('/^[A-Z0-9_-]+$/', $codigo)) {
            $errors['codigo'] = 'Use apenas letras, números, hífen ou sublinhado.';
        } elseif ($this->repository->codigoEmUso($codigo, $id)) {
            $errors['codigo'] = 'Já existe um cupom com este código.';
        }

        $desconto = null;

        if (!preg_match('/^\d{1,2}$/', $descontoBruto) || (int) $descontoBruto < 1 || (int) $descontoBruto > 99) {
            $errors['desconto'] = 'Informe um percentual inteiro entre 1 e 99.';
        } else {
            $desconto = (int) $descontoBruto;
        }

        if ($valMin === null || $valMin < 0) {
            $errors['val_min'] = 'Informe um valor mínimo maior ou igual a zero.';
        }

        if ($valMax === null || $valMax <= 0) {
            $errors['val_max'] = 'Informe um valor máximo maior que zero.';
        } elseif ($valMin !== null && $valMin >= 0 && $valMax < $valMin) {
            $errors['val_max'] = 'O valor máximo deve ser maior ou igual ao mínimo.';
        }

        $dados = [
            'codigo' => $codigo,
            'desconto' => $desconto !== null ? $desconto / 100 : null,
            'val_min' => $valMin,
            'val_max' => $valMax,
            'frete_gratis' => $freteGratis,
        ];

        $old = [
            'codigo' => $codigo,
            'desconto' => $descontoBruto,
            'val_min' => $this->texto('val_min'),
            'val_max' => $this->texto('val_max'),
            'frete_gratis' => $freteGratis ? 1 : 0,
        ];

        return [$dados, $errors, $old];
    }
}
