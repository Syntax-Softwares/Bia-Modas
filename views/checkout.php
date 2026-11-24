<?php
$resumo = $data['resumo'];
$erros = $data['erros'] ?? [];
$dados = $data['dados'] ?? [];
$usuario = $data['usuario'] ?? null;
$enderecos = $data['enderecos'] ?? [];
$favoritosIds = $data['favoritosIds'] ?? [];
$favoritosTotal = $data['favoritosTotal'] ?? 0;
$quantidadeCarrinho = $data['quantidadeCarrinho'] ?? 0;

$enderecoSelecionado = (string) ($dados['endereco_id'] ?? '');
$novoEnderecoComErro = isset($erros['endereco_cep'])
    || isset($erros['endereco_logradouro'])
    || isset($erros['endereco_numero'])
    || isset($erros['endereco_complemento'])
    || isset($erros['endereco_bairro'])
    || isset($erros['endereco_cidade'])
    || isset($erros['endereco_uf']);
$usandoNovoEndereco = $enderecoSelecionado === 'novo' || $enderecos === [] || $novoEnderecoComErro;
$valorNovoEndereco = static function (string $campo) use ($dados, $enderecoSelecionado): string {
    return $enderecoSelecionado === 'novo' ? (string) ($dados[$campo] ?? '') : '';
};

$nomeUsuario = trim((string) ($usuario['nome'] ?? '')) !== ''
    ? trim((string) $usuario['nome'])
    : trim((string) ($dados['destinatario'] ?? ''));
$emailUsuario = trim((string) ($usuario['email'] ?? ''));
$telefoneUsuario = preg_replace('/\D+/', '', (string) ($usuario['telefone'] ?? ($dados['telefone'] ?? '')));
$totalItens = array_sum(array_map(static fn (array $item): int => (int) $item['quantidade'], $resumo['itens']));
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <link rel="icon" href="<?= asset('/favicon.ico') ?>" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('/img/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= asset('/img/favicon-192x192.png') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= asset('/img/apple-touch-icon.png') ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Bia Modas - Finalizar compra." />
    <meta name="csrf-token" content="<?= e(Sessao::csrfToken()) ?>" />
    <title>Finalizar Compra | Bia Modas</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lucide-static@latest/font/lucide.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= asset('/css/base.css') ?>">
    <link rel="stylesheet" href="<?= asset('/css/layout.css') ?>">
    <link rel="stylesheet" href="<?= asset('/css/components.css') ?>">
    <link rel="stylesheet" href="<?= asset('/css/responsive.css') ?>">
    <link rel="stylesheet" href="<?= asset('/css/categoria.css') ?>">
    <link rel="stylesheet" href="<?= asset('/css/carrinho.css') ?>">
    <link rel="stylesheet" href="<?= asset('/css/checkout.css') ?>">
</head>
<body data-page="checkout">
    <?php require __DIR__ . '/partials/site_header.php'; ?>
    <?php require __DIR__ . '/partials/cart_offcanvas.php'; ?>

    <header class="page-header">
        <div class="container">
            <nav class="breadcrumb" aria-label="Navegação">
                <a href="/">Início</a>
                <span>/</span>
                <a href="/carrinho">Carrinho</a>
                <span>/</span>
                <span>Checkout</span>
            </nav>
            <h1>Finalizar compra</h1>
            <p>Revise seus dados e escolha como pagar</p>
        </div>
    </header>

    <section class="products-section">
        <div class="container">
            <?php require __DIR__ . '/partials/alerts.php'; ?>

            <div class="checkout-conta">
                <span class="checkout-conta-avatar" aria-hidden="true"><?= e(Html::inicial($nomeUsuario)) ?></span>
                <span class="checkout-conta-info">
                    <span class="checkout-conta-rotulo">Comprando como</span>
                    <strong class="checkout-conta-nome"><?= e($nomeUsuario !== '' ? $nomeUsuario : 'Sua conta') ?></strong>
                    <?php if ($emailUsuario !== '' || $telefoneUsuario !== ''): ?>
                        <span class="checkout-conta-contato">
                            <?php if ($emailUsuario !== ''): ?>
                                <span><?= e($emailUsuario) ?></span>
                            <?php endif; ?>
                            <?php if ($emailUsuario !== '' && $telefoneUsuario !== ''): ?>
                                <span class="checkout-conta-separador" aria-hidden="true">•</span>
                            <?php endif; ?>
                            <?php if ($telefoneUsuario !== ''): ?>
                                <span><?= e(Html::telefone($telefoneUsuario)) ?></span>
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>
                </span>
                <a href="/perfil" class="checkout-conta-link">Minha conta</a>
            </div>

            <?php if (isset($erros['contato'])): ?>
                <div class="alerta alerta-erro" role="alert">
                    <?= e($erros['contato']) ?>
                    <a href="/perfil">Atualizar meus dados</a>
                </div>
            <?php endif; ?>

            <?php if (isset($erros['geral'])): ?>
                <div class="alerta alerta-erro" role="alert"><?= e($erros['geral']) ?></div>
            <?php endif; ?>

            <?php if ($enderecos === []): ?>
                <div class="alerta alerta-aviso" role="status">
                    Você ainda não tem um endereço cadastrado. Informe o endereço de entrega abaixo ou
                    <a href="/perfil/enderecos">cadastre um endereço na sua conta</a>.
                </div>
            <?php endif; ?>

            <div class="checkout-layout">
                <form class="checkout-form" method="post" action="/checkout" novalidate data-checkout-form>
                    <?= Sessao::csrfField() ?>

                    <fieldset class="checkout-bloco">
                        <legend><span class="checkout-passo">1</span> Endereço de entrega</legend>

                        <?php if ($enderecos !== []): ?>
                            <div class="checkout-enderecos">
                                <?php foreach ($enderecos as $endereco): ?>
                                    <?php
                                    $idEndereco = (int) $endereco['id_endereco'];
                                    $selecionado = $enderecoSelecionado === (string) $idEndereco;
                                    $principal = (int) $endereco['principal'] === 1;
                                    $nomeEndereco = trim((string) $endereco['nome_endereco']);
                                    $mostrarTagPrincipal = $principal && strcasecmp($nomeEndereco, 'Principal') !== 0;
                                    ?>
                                    <label class="checkout-endereco-opcao<?= $selecionado ? ' is-selecionado' : '' ?>">
                                        <input type="radio" name="endereco_id" value="<?= $idEndereco ?>"
                                            <?= $selecionado ? 'checked' : '' ?>>
                                        <span class="checkout-endereco-conteudo">
                                            <span class="checkout-endereco-topo">
                                                <strong><?= e($nomeEndereco) ?></strong>
                                                <?php if ($mostrarTagPrincipal): ?>
                                                    <span class="checkout-endereco-tag">Principal</span>
                                                <?php endif; ?>
                                            </span>
                                            <span class="checkout-endereco-linha">
                                                <?= e($endereco['logradouro']) ?>, <?= e($endereco['numero']) ?>
                                                <?php if (!empty($endereco['complemento'])): ?>
                                                    — <?= e($endereco['complemento']) ?>
                                                <?php endif; ?>
                                            </span>
                                            <span class="checkout-endereco-linha">
                                                <?= e($endereco['bairro']) ?> · <?= e($endereco['cidade']) ?>/<?= e($endereco['uf']) ?>
                                            </span>
                                            <span class="checkout-endereco-linha checkout-endereco-cep">
                                                CEP <?= e(Html::cep($endereco['cep'])) ?>
                                            </span>
                                        </span>
                                    </label>
                                <?php endforeach; ?>

                                <label class="checkout-endereco-opcao checkout-endereco-opcao-nova<?= $usandoNovoEndereco ? ' is-selecionado' : '' ?>">
                                    <input type="radio" name="endereco_id" value="novo"
                                        <?= $usandoNovoEndereco ? 'checked' : '' ?> data-checkout-novo-endereco>
                                    <span class="checkout-endereco-conteudo">
                                        <span class="checkout-endereco-topo">
                                            <strong>Outro endereço</strong>
                                        </span>
                                        <span class="checkout-endereco-linha">
                                            Informar um endereço diferente só para este pedido
                                        </span>
                                    </span>
                                </label>
                            </div>

                            <?php if (isset($erros['endereco_id'])): ?>
                                <span class="campo-erro"><?= e($erros['endereco_id']) ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            <input type="hidden" name="endereco_id" value="novo">
                        <?php endif; ?>

                        <div class="checkout-endereco-novo" data-checkout-endereco-novo <?= $usandoNovoEndereco ? '' : 'hidden' ?>>
                            <div class="checkout-endereco-novo-topo">
                                <h3>Endereço para esta entrega</h3>
                                <p>Preencha os dados de quem vai receber.</p>
                            </div>

                            <div class="checkout-grade" data-endereco-form>
                                <div class="campo">
                                    <label for="endereco_cep">CEP</label>
                                    <input type="text" id="endereco_cep" name="endereco_cep" inputmode="numeric"
                                           value="<?= e($valorNovoEndereco('endereco_cep')) ?>"
                                           placeholder="17201-320" autocomplete="postal-code" maxlength="9"
                                           class="<?= isset($erros['endereco_cep']) ? 'tem-erro' : '' ?>">
                                    <?php if (isset($erros['endereco_cep'])): ?>
                                        <span class="campo-erro"><?= e($erros['endereco_cep']) ?></span>
                                    <?php endif; ?>
                                    <small class="endereco-feedback" data-cep-feedback hidden></small>
                                </div>

                                <div class="campo">
                                    <?php $semNumeroCheckout = strtoupper(trim($valorNovoEndereco('endereco_numero'))) === 'S/N'; ?>
                                    <label for="endereco_numero">Número</label>
                                    <div class="numero-linha">
                                        <input type="text" id="endereco_numero" name="endereco_numero"
                                               value="<?= e($valorNovoEndereco('endereco_numero')) ?>"
                                               autocomplete="address-line2" maxlength="10"
                                               <?= $semNumeroCheckout ? 'readonly aria-readonly="true"' : '' ?>
                                               class="<?= isset($erros['endereco_numero']) ? 'tem-erro ' : '' ?><?= $semNumeroCheckout ? 'is-readonly' : '' ?>">
                                        <label class="sem-numero-check" for="endereco-sem-numero">
                                            <input type="checkbox" id="endereco-sem-numero" data-sem-numero <?= $semNumeroCheckout ? 'checked' : '' ?>>
                                            <span>Este endereço não possui número</span>
                                        </label>
                                    </div>
                                    <?php if (isset($erros['endereco_numero'])): ?>
                                        <span class="campo-erro"><?= e($erros['endereco_numero']) ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="campo campo-cheio">
                                    <label for="endereco_logradouro">Rua / Avenida</label>
                                    <input type="text" id="endereco_logradouro" name="endereco_logradouro"
                                           value="<?= e($valorNovoEndereco('endereco_logradouro')) ?>"
                                           autocomplete="address-line1" maxlength="100"
                                           class="<?= isset($erros['endereco_logradouro']) ? 'tem-erro' : '' ?>">
                                    <?php if (isset($erros['endereco_logradouro'])): ?>
                                        <span class="campo-erro"><?= e($erros['endereco_logradouro']) ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="campo">
                                    <label for="endereco_complemento">Complemento <span class="campo-opcional">(opcional)</span></label>
                                    <input type="text" id="endereco_complemento" name="endereco_complemento"
                                           value="<?= e($valorNovoEndereco('endereco_complemento')) ?>"
                                           maxlength="60"
                                           class="<?= isset($erros['endereco_complemento']) ? 'tem-erro' : '' ?>">
                                    <?php if (isset($erros['endereco_complemento'])): ?>
                                        <span class="campo-erro"><?= e($erros['endereco_complemento']) ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="campo">
                                    <label for="endereco_bairro">Bairro</label>
                                    <input type="text" id="endereco_bairro" name="endereco_bairro"
                                           value="<?= e($valorNovoEndereco('endereco_bairro')) ?>"
                                           autocomplete="address-level3" maxlength="60"
                                           class="<?= isset($erros['endereco_bairro']) ? 'tem-erro' : '' ?>">
                                    <?php if (isset($erros['endereco_bairro'])): ?>
                                        <span class="campo-erro"><?= e($erros['endereco_bairro']) ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="campo">
                                    <label for="endereco_cidade">Cidade</label>
                                    <input type="text" id="endereco_cidade" name="endereco_cidade"
                                           value="<?= e($valorNovoEndereco('endereco_cidade')) ?>"
                                           autocomplete="address-level2" maxlength="60"
                                           class="<?= isset($erros['endereco_cidade']) ? 'tem-erro' : '' ?>">
                                    <?php if (isset($erros['endereco_cidade'])): ?>
                                        <span class="campo-erro"><?= e($erros['endereco_cidade']) ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="campo">
                                    <label for="endereco_uf">UF</label>
                                    <input type="text" id="endereco_uf" name="endereco_uf" maxlength="2"
                                           value="<?= e($valorNovoEndereco('endereco_uf')) ?>"
                                           autocomplete="address-level1" placeholder="SP"
                                           class="campo-uf <?= isset($erros['endereco_uf']) ? 'tem-erro' : '' ?>">
                                    <?php if (isset($erros['endereco_uf'])): ?>
                                        <span class="campo-erro"><?= e($erros['endereco_uf']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="checkout-endereco-rodape">
                            <a href="/perfil/enderecos" class="checkout-link-gerenciar">
                                <i class="icon-map-pin"></i> Gerenciar meus endereços
                            </a>
                        </div>
                    </fieldset>

                    <fieldset class="checkout-bloco">
                        <legend><span class="checkout-passo">2</span> Pagamento</legend>

                        <div class="checkout-pagamentos">
                            <label class="pagamento-opcao">
                                <input type="radio" name="forma_pagamento" value="pix"
                                    <?= ($dados['forma_pagamento'] ?? '') === 'pix' ? 'checked' : '' ?>>
                                <span class="pagamento-conteudo">
                                    <i class="icon-qr-code"></i>
                                    <strong>PIX</strong>
                                    <small>Pague pelo aplicativo do banco</small>
                                </span>
                            </label>

                            <label class="pagamento-opcao">
                                <input type="radio" name="forma_pagamento" value="cartao"
                                    <?= ($dados['forma_pagamento'] ?? '') === 'cartao' ? 'checked' : '' ?>>
                                <span class="pagamento-conteudo">
                                    <i class="icon-credit-card"></i>
                                    <strong>Cartão de crédito</strong>
                                    <small>Em até 12x, sujeito à análise da loja</small>
                                </span>
                            </label>

                            <label class="pagamento-opcao">
                                <input type="radio" name="forma_pagamento" value="boleto"
                                    <?= ($dados['forma_pagamento'] ?? '') === 'boleto' ? 'checked' : '' ?>>
                                <span class="pagamento-conteudo">
                                    <i class="icon-scan-barcode"></i>
                                    <strong>Boleto</strong>
                                    <small>Vencimento em 2 dias úteis</small>
                                </span>
                            </label>
                        </div>

                        <?php if (isset($erros['forma_pagamento'])): ?>
                            <span class="campo-erro"><?= e($erros['forma_pagamento']) ?></span>
                        <?php endif; ?>
                    </fieldset>

                    <button type="submit" class="btn-checkout">
                        <i class="icon-shopping-bag"></i>
                        Finalizar pedido
                    </button>
                    <a href="/carrinho" class="checkout-voltar">Voltar ao carrinho</a>
                </form>

                <aside class="carrinho-resumo checkout-resumo">
                    <div class="checkout-resumo-topo">
                        <h2>Resumo do pedido</h2>
                        <span><?= (int) $totalItens ?> <?= $totalItens === 1 ? 'item' : 'itens' ?></span>
                    </div>

                    <ul class="checkout-itens">
                        <?php foreach ($resumo['itens'] as $item): ?>
                            <li>
                                <img src="<?= e($item['imagem'] ?: '/img/logoBiaModas.png') ?>" alt="<?= e($item['nome']) ?>">
                                <div class="checkout-item-info">
                                    <span><?= e($item['nome']) ?></span>
                                    <small>Tamanho: <?= e($item['tamanho']) ?></small>
                                    <small><?= (int) $item['quantidade'] ?> x <?= money($item['valor_unitario']) ?></small>
                                </div>
                                <strong><?= money($item['subtotal']) ?></strong>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="checkout-resumo-entrega">
                        <span><i class="icon-truck"></i> Entrega</span>
                        <span>A combinar</span>
                    </div>

                    <div class="carrinho-total">
                        <span>Total</span>
                        <span><?= money($resumo['subtotal']) ?></span>
                    </div>
                    <p class="checkout-nota">O frete será combinado no atendimento.</p>
                </aside>
            </div>
        </div>
    </section>

    <?php require __DIR__ . '/partials/site_footer.php'; ?>
    <script src="<?= asset('/js/checkout.js') ?>"></script>
</body>
</html>
