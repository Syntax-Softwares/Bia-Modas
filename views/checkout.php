<?php
$resumo = $data['resumo'];
$erros = $data['erros'] ?? [];
$dados = $data['dados'] ?? [];
$favoritosIds = $data['favoritosIds'] ?? [];
$favoritosTotal = $data['favoritosTotal'] ?? 0;
$quantidadeCarrinho = $data['quantidadeCarrinho'] ?? 0;
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
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
            <p>Confirme seus dados e escolha como pagar</p>
        </div>
    </header>

    <section class="products-section">
        <div class="container">
            <?php require __DIR__ . '/partials/alerts.php'; ?>

            <?php if (!empty($data['temEnderecoPrincipal'])): ?>
                <div class="alerta alerta-info" role="status">
                    Nome, telefone e endereço preenchidos com os dados do seu cadastro.
                    <a href="/perfil/enderecos">Gerenciar endereços</a>
                </div>
            <?php else: ?>
                <div class="alerta alerta-info" role="status">
                    Você ainda não tem um endereço principal no cadastro. Preencha os dados de entrega abaixo ou
                    <a href="/perfil/enderecos">cadastre um endereço</a>.
                </div>
            <?php endif; ?>

            <?php if (isset($erros['geral'])): ?>
                <div class="alerta alerta-erro" role="alert"><?= e($erros['geral']) ?></div>
            <?php endif; ?>

            <div class="checkout-layout">
                <form class="checkout-form" method="post" action="/checkout" novalidate data-endereco-form>
                    <?= Sessao::csrfField() ?>

                    <fieldset class="checkout-bloco">
                        <legend><span class="checkout-passo">1</span> Quem recebe</legend>

                        <div class="checkout-grade">
                            <div class="campo campo-cheio">
                                <label for="destinatario">Nome completo</label>
                                <input type="text" id="destinatario" name="destinatario"
                                       value="<?= e($dados['destinatario'] ?? '') ?>"
                                       autocomplete="name" maxlength="255"
                                       class="<?= isset($erros['destinatario']) ? 'tem-erro' : '' ?>">
                                <?php if (isset($erros['destinatario'])): ?>
                                    <span class="campo-erro"><?= e($erros['destinatario']) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="campo">
                                <label for="telefone">Telefone / WhatsApp</label>
                                <input type="tel" id="telefone" name="telefone" inputmode="numeric"
                                       value="<?= e($dados['telefone'] ?? '') ?>"
                                       placeholder="(14) 99999-9999" autocomplete="tel" maxlength="15"
                                       data-mask="telefone"
                                       class="<?= isset($erros['telefone']) ? 'tem-erro' : '' ?>">
                                <?php if (isset($erros['telefone'])): ?>
                                    <span class="campo-erro"><?= e($erros['telefone']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="checkout-bloco">
                        <legend><span class="checkout-passo">2</span> Endereço de entrega</legend>

                        <div class="checkout-grade">
                            <div class="campo">
                                <label for="endereco_cep">CEP</label>
                                <input type="text" id="endereco_cep" name="endereco_cep" inputmode="numeric"
                                       value="<?= e($dados['endereco_cep'] ?? '') ?>"
                                       placeholder="17201-320" autocomplete="postal-code" maxlength="9"
                                       class="<?= isset($erros['endereco_cep']) ? 'tem-erro' : '' ?>">
                                <?php if (isset($erros['endereco_cep'])): ?>
                                    <span class="campo-erro"><?= e($erros['endereco_cep']) ?></span>
                                <?php endif; ?>
                                <small class="endereco-feedback" data-cep-feedback hidden></small>
                            </div>

                            <div class="campo">
                                <?php $semNumeroCheckout = strtoupper(trim((string) ($dados['endereco_numero'] ?? ''))) === 'S/N'; ?>
                                <label for="endereco_numero">Número</label>
                                <div class="numero-linha">
                                    <input type="text" id="endereco_numero" name="endereco_numero"
                                           value="<?= e($dados['endereco_numero'] ?? '') ?>"
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
                                       value="<?= e($dados['endereco_logradouro'] ?? '') ?>"
                                       autocomplete="address-line1" maxlength="100"
                                       class="<?= isset($erros['endereco_logradouro']) ? 'tem-erro' : '' ?>">
                                <?php if (isset($erros['endereco_logradouro'])): ?>
                                    <span class="campo-erro"><?= e($erros['endereco_logradouro']) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="campo">
                                <label for="endereco_complemento">Complemento <span class="campo-opcional">(opcional)</span></label>
                                <input type="text" id="endereco_complemento" name="endereco_complemento"
                                       value="<?= e($dados['endereco_complemento'] ?? '') ?>"
                                       maxlength="60"
                                       class="<?= isset($erros['endereco_complemento']) ? 'tem-erro' : '' ?>">
                                <?php if (isset($erros['endereco_complemento'])): ?>
                                    <span class="campo-erro"><?= e($erros['endereco_complemento']) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="campo">
                                <label for="endereco_bairro">Bairro</label>
                                <input type="text" id="endereco_bairro" name="endereco_bairro"
                                       value="<?= e($dados['endereco_bairro'] ?? '') ?>"
                                       autocomplete="address-level3" maxlength="60"
                                       class="<?= isset($erros['endereco_bairro']) ? 'tem-erro' : '' ?>">
                                <?php if (isset($erros['endereco_bairro'])): ?>
                                    <span class="campo-erro"><?= e($erros['endereco_bairro']) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="campo">
                                <label for="endereco_cidade">Cidade</label>
                                <input type="text" id="endereco_cidade" name="endereco_cidade"
                                       value="<?= e($dados['endereco_cidade'] ?? '') ?>"
                                       autocomplete="address-level2" maxlength="60"
                                       class="<?= isset($erros['endereco_cidade']) ? 'tem-erro' : '' ?>">
                                <?php if (isset($erros['endereco_cidade'])): ?>
                                    <span class="campo-erro"><?= e($erros['endereco_cidade']) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="campo">
                                <label for="endereco_uf">UF</label>
                                <input type="text" id="endereco_uf" name="endereco_uf" maxlength="2"
                                       value="<?= e($dados['endereco_uf'] ?? '') ?>"
                                       autocomplete="address-level1" placeholder="SP"
                                       class="campo-uf <?= isset($erros['endereco_uf']) ? 'tem-erro' : '' ?>">
                                <?php if (isset($erros['endereco_uf'])): ?>
                                    <span class="campo-erro"><?= e($erros['endereco_uf']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="checkout-bloco">
                        <legend><span class="checkout-passo">3</span> Pagamento</legend>

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
                    <h2>Resumo do pedido</h2>

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

                    <div class="carrinho-total">
                        <span>Total</span>
                        <span><?= money($resumo['subtotal']) ?></span>
                    </div>
                    <p class="checkout-nota">Frete combinado no atendimento.</p>
                </aside>
            </div>
        </div>
    </section>

    <?php require __DIR__ . '/partials/site_footer.php'; ?>
</body>
</html>
