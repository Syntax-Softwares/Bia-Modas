<?php

require_once __DIR__ . '/../core/sessao.php';
require_once __DIR__ . '/../core/comprador.php';
require_once __DIR__ . '/../repositories/carrinho_repository.php';
require_once __DIR__ . '/../repositories/favorito_repository.php';
require_once __DIR__ . '/../repositories/pedido_repository.php';
require_once __DIR__ . '/../repositories/usuario_repository.php';

class CheckoutController
{
    private CarrinhoRepository $carrinho;
    private FavoritoRepository $favoritos;
    private PedidoRepository $pedidos;
    private UsuarioRepository $usuarios;

    private const FORMAS_PAGAMENTO = ['pix', 'cartao', 'boleto'];

    public function __construct(private PDO $pdo)
    {
        $this->carrinho = new CarrinhoRepository($pdo);
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

        $dados = $this->validar($_POST);

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
     * Dados iniciais do checkout: nome, telefone e endereco principal do
     * cadastro. O cliente ainda pode ajustar antes de confirmar; o pedido
     * guarda uma copia (snapshot) do que foi enviado.
     */
    private function dadosDoCadastro(int $usuarioId): array
    {
        $dados = $this->dadosVazios();
        $usuario = $this->usuarios->buscarPorId($usuarioId);

        if ($usuario === null) {
            return $dados;
        }

        $dados['destinatario'] = (string) ($usuario['nome'] ?? '');
        $dados['telefone'] = (string) ($usuario['telefone'] ?? '');
        $dados['endereco_cep'] = (string) ($usuario['endereco_cep'] ?? '');
        $dados['endereco_logradouro'] = (string) ($usuario['endereco_logradouro'] ?? '');
        $dados['endereco_numero'] = (string) ($usuario['endereco_numero'] ?? '');
        $dados['endereco_complemento'] = (string) ($usuario['endereco_complemento'] ?? '');
        $dados['endereco_bairro'] = (string) ($usuario['endereco_bairro'] ?? '');
        $dados['endereco_cidade'] = (string) ($usuario['endereco_cidade'] ?? '');
        $dados['endereco_uf'] = (string) ($usuario['endereco_uf'] ?? '');

        return $dados;
    }

    private function validar(array $entrada): array
    {
        $dados = [
            'destinatario' => trim((string) ($entrada['destinatario'] ?? '')),
            'telefone' => preg_replace('/\D+/', '', (string) ($entrada['telefone'] ?? '')),
            'endereco_cep' => preg_replace('/\D+/', '', (string) ($entrada['endereco_cep'] ?? '')),
            'endereco_logradouro' => trim((string) ($entrada['endereco_logradouro'] ?? '')),
            'endereco_numero' => trim((string) ($entrada['endereco_numero'] ?? '')),
            'endereco_complemento' => trim((string) ($entrada['endereco_complemento'] ?? '')),
            'endereco_bairro' => trim((string) ($entrada['endereco_bairro'] ?? '')),
            'endereco_cidade' => trim((string) ($entrada['endereco_cidade'] ?? '')),
            'endereco_uf' => strtoupper(trim((string) ($entrada['endereco_uf'] ?? ''))),
            'forma_pagamento' => (string) ($entrada['forma_pagamento'] ?? ''),
        ];

        $erros = [];

        if (tamanho_texto($dados['destinatario']) < 3 || tamanho_texto($dados['destinatario']) > 255) {
            $erros['destinatario'] = 'Informe o nome de quem vai receber.';
        }

        $tamanhoTelefone = strlen($dados['telefone']);
        if ($tamanhoTelefone < 10 || $tamanhoTelefone > 11) {
            $erros['telefone'] = 'Informe um telefone com DDD.';
        }

        if (strlen($dados['endereco_cep']) !== 8) {
            $erros['endereco_cep'] = 'Informe um CEP válido (8 dígitos).';
        }

        $tamanhoLogradouro = tamanho_texto($dados['endereco_logradouro']);
        if ($tamanhoLogradouro < 3) {
            $erros['endereco_logradouro'] = 'Informe a rua ou avenida.';
        } elseif ($tamanhoLogradouro > 255) {
            $erros['endereco_logradouro'] = 'A rua/avenida deve ter até 255 caracteres.';
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

        if (!in_array($dados['forma_pagamento'], self::FORMAS_PAGAMENTO, true)) {
            $erros['forma_pagamento'] = 'Escolha a forma de pagamento.';
        }

        return [
            'dados' => $dados,
            'erros' => $erros,
        ];
    }

    private function dadosVazios(): array
    {
        return [
            'destinatario' => '',
            'telefone' => '',
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
            'favoritosIds' => $favoritosIds,
            'favoritosTotal' => count($favoritosIds),
            'carrinhoIds' => array_column($resumo['itens'], 'produto_id'),
            'quantidadeCarrinho' => $resumo['quantidade'],
            'temEnderecoPrincipal' => $usuario !== null && !empty($usuario['endereco_logradouro']),
            'flashes' => Sessao::flashes(),
        ];

        require __DIR__ . '/../views/checkout.php';
    }
}
