<?php

require_once __DIR__ . '/../core/sessao.php';
require_once __DIR__ . '/../core/comprador.php';
require_once __DIR__ . '/../repositories/carrinho_repository.php';
require_once __DIR__ . '/../repositories/endereco_repository.php';
require_once __DIR__ . '/../repositories/favorito_repository.php';
require_once __DIR__ . '/../repositories/pedido_repository.php';
require_once __DIR__ . '/../repositories/usuario_repository.php';

class CheckoutController
{
    private CarrinhoRepository $carrinho;
    private EnderecoRepository $enderecos;
    private FavoritoRepository $favoritos;
    private PedidoRepository $pedidos;
    private UsuarioRepository $usuarios;

    private const FORMAS_PAGAMENTO = ['pix', 'cartao', 'boleto'];

    public function __construct(private PDO $pdo)
    {
        $this->carrinho = new CarrinhoRepository($pdo);
        $this->enderecos = new EnderecoRepository($pdo);
        $this->favoritos = new FavoritoRepository($pdo);
        $this->pedidos = new PedidoRepository($pdo);
        $this->usuarios = new UsuarioRepository($pdo);
    }

    public function index(): void
    {
        $usuarioId = $this->exigirLogin();
        $resumo = $this->carrinho->resumoDe(Comprador::atual());

        if ($resumo['itens'] === []) {
            Sessao::flash('aviso', 'Adicione produtos ao carrinho antes de finalizar.');
            redirecionar('/carrinho');
        }

        $this->renderizar($resumo, [], $this->dadosDoCadastro($usuarioId));
    }

    public function confirmar(): void
    {
        $usuarioId = $this->exigirLogin();

        $token = $_POST['_csrf'] ?? null;

        if (!Sessao::validarCsrf(is_string($token) ? $token : null)) {
            Sessao::flash('erro', 'Sessão expirada. Preencha os dados novamente.');
            redirecionar('/checkout');
        }

        $resumo = $this->carrinho->resumoDe(Comprador::atual());

        if ($resumo['itens'] === []) {
            Sessao::flash('aviso', 'Seu carrinho está vazio.');
            redirecionar('/carrinho');
        }

        $dados = $this->validar($_POST, $usuarioId);

        if ($dados['erros'] !== []) {
            $this->renderizar($resumo, $dados['erros'], $dados['dados']);
            return;
        }

        $carrinhoId = $this->carrinho->obter(Comprador::atual());
        $resultado = $this->pedidos->criar($usuarioId, $carrinhoId, $dados['dados']);

        if (!$resultado['ok']) {
            $this->renderizar($resumo, ['geral' => $resultado['mensagem']], $dados['dados']);
            return;
        }

        redirecionar('/pedido?numero=' . $resultado['numero_pedido'] . '&novo=1');
    }

    /**
     * Dados iniciais do checkout.
     *
     * Nome, telefone e e-mail vem do cadastro e nao sao mais digitados no
     * checkout. O endereco vem da lista de enderecos salvos: o principal ja
     * fica selecionado, mas o cliente pode trocar por outro ou informar um
     * endereco avulso. O pedido guarda uma copia (snapshot) do que foi usado.
     */
    private function dadosDoCadastro(int $usuarioId): array
    {
        $dados = $this->dadosVazios();
        $usuario = $this->usuarios->buscarPorId($usuarioId);

        if ($usuario === null) {
            $dados['endereco_id'] = 'novo';

            return $dados;
        }

        $dados['destinatario'] = trim((string) ($usuario['nome'] ?? ''));
        $dados['telefone'] = preg_replace('/\D+/', '', (string) ($usuario['telefone'] ?? ''));

        $enderecoId = (int) ($usuario['endereco_id'] ?? 0);
        $dados['endereco_id'] = $enderecoId > 0 ? (string) $enderecoId : 'novo';

        return $dados;
    }

    private function validar(array $entrada, int $usuarioId): array
    {
        $dados = $this->dadosDoCadastro($usuarioId);
        $dados['endereco_id'] = trim((string) ($entrada['endereco_id'] ?? ''));
        $dados['forma_pagamento'] = (string) ($entrada['forma_pagamento'] ?? '');

        $erros = [];

        if (
            tamanho_texto($dados['destinatario']) < 3
            || tamanho_texto($dados['destinatario']) > 255
            || strlen($dados['telefone']) < 10
            || strlen($dados['telefone']) > 11
        ) {
            $erros['contato'] = 'Complete seu nome e telefone no cadastro para finalizar a compra.';
        }

        if ($dados['endereco_id'] === 'novo') {
            $this->validarNovoEndereco($entrada, $dados, $erros);
        } elseif ($dados['endereco_id'] === '') {
            $erros['endereco_id'] = 'Escolha o endereço de entrega.';
        } else {
            $endereco = ctype_digit($dados['endereco_id'])
                ? $this->enderecos->buscarDoUsuario((int) $dados['endereco_id'], $usuarioId)
                : null;

            if ($endereco === null) {
                $erros['endereco_id'] = 'Escolha um endereço de entrega válido.';
                $dados['endereco_id'] = '';
            } else {
                $dados['endereco_id'] = (string) $endereco['id_endereco'];
                $dados['endereco_cep'] = (string) $endereco['cep'];
                $dados['endereco_logradouro'] = (string) $endereco['logradouro'];
                $dados['endereco_numero'] = (string) $endereco['numero'];
                $dados['endereco_complemento'] = (string) ($endereco['complemento'] ?? '');
                $dados['endereco_bairro'] = (string) $endereco['bairro'];
                $dados['endereco_cidade'] = (string) $endereco['cidade'];
                $dados['endereco_uf'] = (string) $endereco['uf'];
            }
        }

        if (!in_array($dados['forma_pagamento'], self::FORMAS_PAGAMENTO, true)) {
            $erros['forma_pagamento'] = 'Escolha a forma de pagamento.';
        }

        return [
            'dados' => $dados,
            'erros' => $erros,
        ];
    }

    private function validarNovoEndereco(array $entrada, array &$dados, array &$erros): void
    {
        $dados['endereco_cep'] = preg_replace('/\D+/', '', (string) ($entrada['endereco_cep'] ?? ''));
        $dados['endereco_logradouro'] = trim((string) ($entrada['endereco_logradouro'] ?? ''));
        $dados['endereco_numero'] = trim((string) ($entrada['endereco_numero'] ?? ''));
        $dados['endereco_complemento'] = trim((string) ($entrada['endereco_complemento'] ?? ''));
        $dados['endereco_bairro'] = trim((string) ($entrada['endereco_bairro'] ?? ''));
        $dados['endereco_cidade'] = trim((string) ($entrada['endereco_cidade'] ?? ''));
        $dados['endereco_uf'] = strtoupper(trim((string) ($entrada['endereco_uf'] ?? '')));

        if (strlen($dados['endereco_cep']) !== 8) {
            $erros['endereco_cep'] = 'Informe um CEP válido (8 dígitos).';
        }

        $tamanhoLogradouro = tamanho_texto($dados['endereco_logradouro']);
        if ($tamanhoLogradouro < 3) {
            $erros['endereco_logradouro'] = 'Informe a rua ou avenida.';
        } elseif ($tamanhoLogradouro > 100) {
            $erros['endereco_logradouro'] = 'A rua/avenida deve ter até 100 caracteres.';
        }

        if ($dados['endereco_numero'] === '' || tamanho_texto($dados['endereco_numero']) > 10) {
            $erros['endereco_numero'] = 'Informe o número do endereço.';
        }

        if (tamanho_texto($dados['endereco_complemento']) > 60) {
            $erros['endereco_complemento'] = 'O complemento deve ter até 60 caracteres.';
        }

        $tamanhoBairro = tamanho_texto($dados['endereco_bairro']);
        if ($tamanhoBairro < 2) {
            $erros['endereco_bairro'] = 'Informe o bairro.';
        } elseif ($tamanhoBairro > 60) {
            $erros['endereco_bairro'] = 'O bairro deve ter até 60 caracteres.';
        }

        $tamanhoCidade = tamanho_texto($dados['endereco_cidade']);
        if ($tamanhoCidade < 2) {
            $erros['endereco_cidade'] = 'Informe a cidade.';
        } elseif ($tamanhoCidade > 60) {
            $erros['endereco_cidade'] = 'A cidade deve ter até 60 caracteres.';
        }

        if (preg_match('/^[A-Z]{2}$/', $dados['endereco_uf']) !== 1) {
            $erros['endereco_uf'] = 'Informe a UF com 2 letras.';
        }
    }

    private function dadosVazios(): array
    {
        return [
            'destinatario' => '',
            'telefone' => '',
            'endereco_id' => '',
            'endereco_cep' => '',
            'endereco_logradouro' => '',
            'endereco_numero' => '',
            'endereco_complemento' => '',
            'endereco_bairro' => '',
            'endereco_cidade' => '',
            'endereco_uf' => '',
            'forma_pagamento' => '',
        ];
    }

    private function exigirLogin(): int
    {
        $usuarioId = Sessao::id();

        if ($usuarioId === null) {
            Sessao::definirDestinoAposLogin('/checkout');
            Sessao::flash('info', 'Entre na sua conta para finalizar a compra.');
            Sessao::redirecionar('/login');
        }

        return $usuarioId;
    }

    private function renderizar(array $resumo, array $erros, array $dados): void
    {
        $comprador = Comprador::atual();
        $favoritosIds = $this->favoritos->ids($comprador);
        $usuarioId = Sessao::id();
        $usuario = $usuarioId !== null ? $this->usuarios->buscarPorId($usuarioId) : null;

        $data = [
            'resumo' => $resumo,
            'erros' => $erros,
            'dados' => $dados,
            'usuario' => $usuario,
            'enderecos' => $usuarioId !== null ? $this->enderecos->listarPorUsuario($usuarioId) : [],
            'favoritosIds' => $favoritosIds,
            'favoritosTotal' => count($favoritosIds),
            'carrinhoIds' => array_column($resumo['itens'], 'produto_id'),
            'quantidadeCarrinho' => $resumo['quantidade'],
            'flashes' => Sessao::flashes(),
        ];

        require __DIR__ . '/../views/checkout.php';
    }
}
