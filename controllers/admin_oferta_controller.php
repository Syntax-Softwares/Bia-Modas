<?php

require_once __DIR__ . '/admin_controller.php';
require_once __DIR__ . '/../repositories/oferta_repository.php';

class AdminOfertaController extends AdminController
{
    private OfertaRepository $repository;

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);

        $this->repository = new OfertaRepository($pdo);
    }

    public function index(): void
    {
        $ofertas = $this->repository->listar();
        $flashes = Auth::takeFlashes();
        $admin = $this->admin;

        $titulo = 'Ofertas';
        $ativo = 'ofertas';

        ob_start();
        require __DIR__ . '/../views/admin/ofertas/listar.php';
        $conteudo = (string) ob_get_clean();

        require __DIR__ . '/../views/admin/layout.php';
    }

    public function novo(): void
    {
        $valores = array_merge(
            [
                'percentual' => '',
                'inicio_em' => date('Y-m-d\TH:i'),
                'fim_em' => '',
                'ativo' => 1,
            ],
            Auth::takeOld()
        );
        $errors = Auth::takeErrors();
        $flashes = Auth::takeFlashes();
        $admin = $this->admin;

        $titulo = 'Nova oferta';
        $ativo = 'ofertas';

        ob_start();
        require __DIR__ . '/../views/admin/ofertas/form.php';
        $conteudo = (string) ob_get_clean();

        require __DIR__ . '/../views/admin/layout.php';
    }

    public function criar(): void
    {
        $this->validarCsrf('/admin/ofertas/novo');

        [$dados, $errors, $old] = $this->validar();

        if ($errors !== []) {
            $this->voltarComErros('/admin/ofertas/novo', $old, $errors, 'Revise os campos destacados para criar a oferta.');
        }

        $this->repository->criar($dados);
        Auth::flash('success', 'Oferta criada com sucesso.');
        redirect('/admin/ofertas');
    }

    public function editar(string $id): void
    {
        $oferta = $this->repository->getPorId((int) $id);

        if ($oferta === null) {
            $this->naoEncontrado();
        }

        $valores = array_merge(
            [
                'percentual' => (int) round((float) $oferta['percentual'] * 100),
                'inicio_em' => $this->paraInputDatetime($oferta['inicio_em']),
                'fim_em' => $this->paraInputDatetime($oferta['fim_em']),
                'ativo' => $oferta['ativo'],
            ],
            Auth::takeOld()
        );
        $errors = Auth::takeErrors();
        $flashes = Auth::takeFlashes();
        $admin = $this->admin;

        $titulo = 'Editar oferta';
        $ativo = 'ofertas';

        ob_start();
        require __DIR__ . '/../views/admin/ofertas/form.php';
        $conteudo = (string) ob_get_clean();

        require __DIR__ . '/../views/admin/layout.php';
    }

    public function atualizar(string $id): void
    {
        $id = (int) $id;
        $destino = '/admin/ofertas/' . $id . '/editar';
        $this->validarCsrf($destino);

        if ($this->repository->getPorId($id) === null) {
            $this->naoEncontrado();
        }

        [$dados, $errors, $old] = $this->validar();

        if ($errors !== []) {
            $this->voltarComErros($destino, $old, $errors, 'Revise os campos destacados para salvar a oferta.');
        }

        $this->repository->atualizar($id, $dados);
        Auth::flash('success', 'Oferta atualizada com sucesso.');
        redirect('/admin/ofertas');
    }

    public function excluir(string $id): void
    {
        $id = (int) $id;
        $this->validarCsrf('/admin/ofertas');

        if ($this->repository->getPorId($id) === null) {
            Auth::flash('danger', 'Oferta não encontrada.');
            redirect('/admin/ofertas');
        }

        $this->repository->excluir($id);
        Auth::flash('success', 'Oferta excluída e desvinculada dos produtos.');
        redirect('/admin/ofertas');
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, string>, 2: array<string, mixed>}
     */
    private function validar(): array
    {
        $percentualBruto = $this->texto('percentual');
        $inicioBruto = $this->texto('inicio_em');
        $fimBruto = $this->texto('fim_em');
        $ativo = isset($_POST['ativo']);

        $errors = [];

        $percentual = null;

        if (!preg_match('/^\d{1,2}$/', $percentualBruto) || (int) $percentualBruto < 1 || (int) $percentualBruto > 99) {
            $errors['percentual'] = 'Informe um percentual inteiro entre 1 e 99.';
        } else {
            $percentual = (int) $percentualBruto;
        }

        $inicio = $this->normalizarDatetime($inicioBruto);

        if ($inicio === null) {
            $errors['inicio_em'] = 'Informe a data e hora de início.';
        }

        $fim = $fimBruto === '' ? null : $this->normalizarDatetime($fimBruto);

        if ($fimBruto !== '' && $fim === null) {
            $errors['fim_em'] = 'Informe uma data e hora de fim válida.';
        } elseif ($inicio !== null && $fim !== null && $fim < $inicio) {
            $errors['fim_em'] = 'A data de fim deve ser posterior à data de início.';
        }

        $dados = [
            'percentual' => $percentual !== null ? $percentual / 100 : null,
            'inicio_em' => $inicio,
            'fim_em' => $fim,
            'ativo' => $ativo,
        ];

        $old = [
            'percentual' => $percentualBruto,
            'inicio_em' => $inicioBruto,
            'fim_em' => $fimBruto,
            'ativo' => $ativo ? 1 : 0,
        ];

        return [$dados, $errors, $old];
    }

    private function normalizarDatetime(string $valor): ?string
    {
        foreach (['Y-m-d\TH:i', 'Y-m-d\TH:i:s', 'Y-m-d H:i:s', 'Y-m-d H:i'] as $formato) {
            $data = DateTime::createFromFormat($formato, $valor);

            if ($data instanceof DateTime && $data->format($formato) === $valor) {
                return $data->format('Y-m-d H:i:s');
            }
        }

        return null;
    }

    private function paraInputDatetime(?string $valor): string
    {
        if ($valor === null || $valor === '') {
            return '';
        }

        $timestamp = strtotime($valor);

        return $timestamp === false ? '' : date('Y-m-d\TH:i', $timestamp);
    }
}
