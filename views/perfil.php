<?php
/** @var string $aba */
/** @var array $usuario */
/** @var array $enderecos */
/** @var array|null $editando */
/** @var array|null $excluindo */
/** @var array $flash */
/** @var array $erros */
/** @var array $old */

$valorDados = static function (string $campo, string $padrao = '') use ($old, $usuario): string {
    if (array_key_exists($campo, $old)) {
        return (string) $old[$campo];
    }

    $valor = $usuario[$campo] ?? $padrao;

    return $valor === null ? '' : (string) $valor;
};

$valorEndereco = static function (string $campo, string $padrao = '') use ($old, $editando): string {
    if (array_key_exists($campo, $old)) {
        return (string) $old[$campo];
    }

    if ($editando !== null) {
        $valor = $editando[$campo] ?? $padrao;

        return $valor === null ? '' : (string) $valor;
    }

    return $padrao;
};

$campoErro = static function (string $campo) use ($erros): string {
    return isset($erros[$campo])
        ? '<span class="field-error">' . Html::e($erros[$campo]) . '</span>'
        : '';
};

$abas = [
    ['id' => 'dados', 'rotulo' => 'Dados Pessoais', 'href' => '/perfil'],
    ['id' => 'enderecos', 'rotulo' => 'Endereços', 'href' => '/perfil/enderecos'],
    ['id' => 'medidas', 'rotulo' => 'Medidas', 'desabilitada' => true],
    ['id' => 'preferencias', 'rotulo' => 'Preferências', 'desabilitada' => true],
    ['id' => 'seguranca', 'rotulo' => 'Segurança', 'href' => '/perfil/seguranca'],
];

$formAction = $editando !== null ? '/perfil/enderecos/atualizar' : '/perfil/enderecos/criar';
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Bia Modas - Minha Conta.">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="<?= Html::e(Sessao::csrfToken()) ?>">
    <title>Minha Conta | Bia Modas</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lucide-static@latest/font/lucide.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/css/base.css">
    <link rel="stylesheet" href="/css/layout.css">
    <link rel="stylesheet" href="/css/components.css">
    <link rel="stylesheet" href="/css/responsive.css">
    <link rel="stylesheet" href="/css/auth.css">
    <link rel="stylesheet" href="/css/usuario.css">
    <link rel="stylesheet" href="/css/carrinho.css">
</head>
<body data-page="perfil">
    <?php require __DIR__ . '/partials/perfil_header.php'; ?>

    <main class="products-section profile-section">
        <div class="container">
            <div class="section-header">
                <h2>Minha Conta</h2>
                <div class="line"></div>
                <p class="mt-3">Gerencie seus dados, endereços e segurança</p>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="profile-card">
                        <div class="text-center mb-4">
                            <div id="profile-avatar"><?= Html::e(Html::inicial($usuario['nome'])) ?></div>
                            <h3 class="mt-3 mb-1" id="profile-name"><?= Html::e($usuario['nome']) ?></h3>
                            <p class="mb-0" id="profile-email"><?= Html::e($usuario['email']) ?></p>
                        </div>

                        <nav class="profile-tabs" aria-label="Seções da conta">
                            <?php foreach ($abas as $item): ?>
                                <?php if (!empty($item['desabilitada'])): ?>
                                    <span class="profile-tab disabled" aria-disabled="true" title="Disponível em breve">
                                        <?= Html::e($item['rotulo']) ?>
                                    </span>
                                <?php else: ?>
                                    <a href="<?= Html::e($item['href']) ?>"
                                       class="profile-tab<?= $aba === $item['id'] ? ' active' : '' ?>"
                                       <?= $aba === $item['id'] ? 'aria-current="page"' : '' ?>>
                                        <?= Html::e($item['rotulo']) ?>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </nav>

                        <?php require __DIR__ . '/partials/perfil_alerts.php'; ?>

                        <?php if ($aba === 'dados'): ?>
                            <div class="profile-tab-content active" id="tab-dados">
                                <form class="auth-form" method="post" action="/perfil/dados" autocomplete="off" novalidate>
                                    <?= Sessao::csrfCampo() ?>

                                    <div class="row">
                                        <div class="col-md-6 form-group">
                                            <label for="pf-nome">Nome Completo</label>
                                            <input type="text" class="form-control" id="pf-nome" name="nome"
                                                   value="<?= Html::e($valorDados('nome')) ?>"
                                                   required minlength="3" maxlength="255" autocomplete="name">
                                            <?= $campoErro('nome') ?>
                                        </div>

                                        <div class="col-md-6 form-group">
                                            <label for="pf-email">E-mail</label>
                                            <input type="email" class="form-control" id="pf-email" name="email"
                                                   value="<?= Html::e($valorDados('email')) ?>"
                                                   required maxlength="255" autocomplete="email">
                                            <?= $campoErro('email') ?>
                                        </div>

                                        <div class="col-md-6 form-group">
                                            <label for="pf-telefone">Telefone</label>
                                            <input type="tel" class="form-control" id="pf-telefone" name="telefone"
                                                   value="<?= Html::e(Html::telefone($valorDados('telefone'))) ?>"
                                                   required maxlength="15" autocomplete="tel">
                                            <?= $campoErro('telefone') ?>
                                        </div>

                                        <div class="col-md-6 form-group">
                                            <label for="pf-cpf">CPF</label>
                                            <input type="text" class="form-control" id="pf-cpf" name="cpf"
                                                   value="<?= Html::e(Html::cpf($valorDados('cpf'))) ?>"
                                                   maxlength="14" inputmode="numeric" autocomplete="off"
                                                   placeholder="Opcional">
                                            <?= $campoErro('cpf') ?>
                                        </div>

                                        <div class="col-md-6 form-group">
                                            <label for="pf-nascimento">Data de Nascimento</label>
                                            <input type="date" class="form-control" id="pf-nascimento" name="data_nascimento"
                                                   value="<?= Html::e($valorDados('data_nascimento')) ?>"
                                                   min="1900-01-01" max="<?= date('Y-m-d') ?>" autocomplete="bday">
                                            <?= $campoErro('data_nascimento') ?>
                                        </div>

                                        <div class="col-md-6 form-group">
                                            <label for="pf-senha-atual">Senha atual</label>
                                            <input type="password" class="form-control" id="pf-senha-atual" name="senha_atual"
                                                   maxlength="72" autocomplete="current-password"
                                                   placeholder="Obrigatória apenas para alterar o e-mail">
                                            <span class="form-hint">Confirme a senha atual somente se for trocar o e-mail.</span>
                                            <?= $campoErro('senha_atual') ?>
                                        </div>
                                    </div>

                                    <div class="text-center mt-3">
                                        <button type="submit" class="btn-primary-solid">
                                            <i class="icon-check me-2"></i>Salvar Alterações
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>

                        <?php if ($aba === 'enderecos'): ?>
                            <div class="profile-tab-content active" id="tab-enderecos">
                                <?php if ($excluindo !== null): ?>
                                    <div class="confirm-box">
                                        <p class="mb-1"><strong>Excluir o endereço "<?= Html::e($excluindo['nome_endereco']) ?>"?</strong></p>
                                        <p class="mb-3">O endereço será removido da sua conta.</p>
                                        <form method="post" action="/perfil/enderecos/excluir" class="confirm-actions">
                                            <?= Sessao::csrfCampo() ?>
                                            <input type="hidden" name="id_endereco" value="<?= (int) $excluindo['id_endereco'] ?>">
                                            <button type="submit" class="btn-primary-solid btn-logout">Sim, excluir</button>
                                            <a href="/perfil/enderecos" class="btn-outline-account">Cancelar</a>
                                        </form>
                                    </div>
                                <?php endif; ?>

                                <?php if ($enderecos === []): ?>
                                    <p class="account-empty">Nenhum endereço cadastrado ainda.</p>
                                <?php else: ?>
                                    <div class="address-list">
                                        <?php foreach ($enderecos as $endereco): ?>
                                            <?php $principal = (int) $endereco['principal'] === 1; ?>
                                            <article class="address-card<?= $principal ? ' is-primary' : '' ?>">
                                                <div class="address-card-header">
                                                    <h3><?= Html::e($endereco['nome_endereco']) ?></h3>
                                                    <?php if ($principal): ?>
                                                        <span class="badge-primary">Principal</span>
                                                    <?php endif; ?>
                                                </div>

                                                <address class="account-address">
                                                    <?= Html::e($endereco['logradouro']) ?>, <?= Html::e($endereco['numero']) ?>
                                                    <?php if (!empty($endereco['complemento'])): ?>
                                                        — <?= Html::e($endereco['complemento']) ?>
                                                    <?php endif; ?>
                                                    <br>
                                                    <?= Html::e($endereco['bairro']) ?> — <?= Html::e($endereco['cidade']) ?>/<?= Html::e($endereco['uf']) ?>
                                                    <br>
                                                    CEP <?= Html::e(Html::cep($endereco['cep'])) ?>
                                                </address>

                                                <div class="address-actions">
                                                    <a href="/perfil/enderecos?editar=<?= (int) $endereco['id_endereco'] ?>" class="btn-outline-account">
                                                        <i class="icon-pencil me-1"></i>Editar
                                                    </a>

                                                    <?php if (!$principal): ?>
                                                        <form method="post" action="/perfil/enderecos/principal">
                                                            <?= Sessao::csrfCampo() ?>
                                                            <input type="hidden" name="id_endereco" value="<?= (int) $endereco['id_endereco'] ?>">
                                                            <button type="submit" class="btn-outline-account">
                                                                <i class="icon-star me-1"></i>Tornar principal
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>

                                                    <a href="/perfil/enderecos?excluir=<?= (int) $endereco['id_endereco'] ?>" class="btn-outline-account is-danger">
                                                        <i class="icon-trash-2 me-1"></i>Excluir
                                                    </a>
                                                </div>
                                            </article>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <h3 class="profile-subtitle">
                                    <?= $editando !== null ? 'Editar endereço' : 'Adicionar endereço' ?>
                                </h3>

                                <form class="auth-form" method="post" action="<?= Html::e($formAction) ?>" autocomplete="off" novalidate>
                                    <?= Sessao::csrfCampo() ?>

                                    <?php if ($editando !== null): ?>
                                        <input type="hidden" name="id_endereco" value="<?= (int) $editando['id_endereco'] ?>">
                                    <?php endif; ?>

                                    <div class="row">
                                        <div class="col-md-4 form-group">
                                            <label for="pf-nome-endereco">Nome do endereço</label>
                                            <input type="text" class="form-control" id="pf-nome-endereco" name="nome_endereco"
                                                   value="<?= Html::e($valorEndereco('nome_endereco')) ?>"
                                                   required maxlength="30" placeholder="Casa, trabalho...">
                                            <?= $campoErro('nome_endereco') ?>
                                        </div>

                                        <div class="col-md-4 form-group">
                                            <label for="pf-cep">CEP</label>
                                            <input type="text" class="form-control" id="pf-cep" name="cep"
                                                   value="<?= Html::e(Html::cep($valorEndereco('cep'))) ?>"
                                                   required maxlength="9" inputmode="numeric" autocomplete="postal-code">
                                            <?= $campoErro('cep') ?>
                                        </div>

                                        <div class="col-md-8 form-group">
                                            <label for="pf-logradouro">Endereço</label>
                                            <input type="text" class="form-control" id="pf-logradouro" name="logradouro"
                                                   value="<?= Html::e($valorEndereco('logradouro')) ?>"
                                                   required maxlength="255" autocomplete="address-line1">
                                            <?= $campoErro('logradouro') ?>
                                        </div>

                                        <div class="col-md-4 form-group">
                                            <label for="pf-numero">Número</label>
                                            <input type="text" class="form-control" id="pf-numero" name="numero"
                                                   value="<?= Html::e($valorEndereco('numero')) ?>"
                                                   required maxlength="10" autocomplete="address-line2">
                                            <?= $campoErro('numero') ?>
                                        </div>

                                        <div class="col-md-8 form-group">
                                            <label for="pf-complemento">Complemento</label>
                                            <input type="text" class="form-control" id="pf-complemento" name="complemento"
                                                   value="<?= Html::e($valorEndereco('complemento')) ?>"
                                                   maxlength="60" placeholder="Apartamento, bloco...">
                                            <?= $campoErro('complemento') ?>
                                        </div>

                                        <div class="col-md-8 form-group">
                                            <label for="pf-bairro">Bairro</label>
                                            <input type="text" class="form-control" id="pf-bairro" name="bairro"
                                                   value="<?= Html::e($valorEndereco('bairro')) ?>"
                                                   required maxlength="60" autocomplete="address-level3">
                                            <?= $campoErro('bairro') ?>
                                        </div>

                                        <div class="col-md-8 form-group">
                                            <label for="pf-cidade">Cidade</label>
                                            <input type="text" class="form-control" id="pf-cidade" name="cidade"
                                                   value="<?= Html::e($valorEndereco('cidade')) ?>"
                                                   required maxlength="60" autocomplete="address-level2">
                                            <?= $campoErro('cidade') ?>
                                        </div>

                                        <div class="col-md-4 form-group">
                                            <label for="pf-uf">Estado (UF)</label>
                                            <input type="text" class="form-control" id="pf-uf" name="uf"
                                                   value="<?= Html::e(strtoupper($valorEndereco('uf'))) ?>"
                                                   required maxlength="2" autocomplete="address-level1">
                                            <?= $campoErro('uf') ?>
                                        </div>
                                    </div>

                                    <?php if ($editando !== null): ?>
                                        <p class="form-hint">
                                            O endereço principal não muda ao editar. Use "Tornar principal" na lista.
                                        </p>
                                    <?php endif; ?>

                                    <div class="text-center mt-3">
                                        <button type="submit" class="btn-primary-solid">
                                            <i class="icon-check me-2"></i>
                                            <?= $editando !== null ? 'Salvar Endereço' : 'Adicionar Endereço' ?>
                                        </button>

                                        <?php if ($editando !== null): ?>
                                            <a href="/perfil/enderecos" class="btn-outline-account">Cancelar edição</a>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>

                        <?php if ($aba === 'seguranca'): ?>
                            <div class="profile-tab-content active" id="tab-seguranca">
                                <form class="auth-form" method="post" action="/perfil/senha" autocomplete="off" novalidate>
                                    <?= Sessao::csrfCampo() ?>

                                    <div class="row justify-content-center">
                                        <div class="col-md-6 form-group">
                                            <label for="pf-senha-atual">Senha atual</label>
                                            <input type="password" class="form-control" id="pf-senha-atual" name="senha_atual"
                                                   required maxlength="72" autocomplete="current-password">
                                            <?= $campoErro('senha_atual') ?>
                                        </div>
                                    </div>

                                    <div class="row justify-content-center">
                                        <div class="col-md-6 form-group">
                                            <label for="pf-senha-nova">Nova senha</label>
                                            <input type="password" class="form-control" id="pf-senha-nova" name="senha_nova"
                                                   required minlength="8" maxlength="72" autocomplete="new-password">
                                            <span class="form-hint">Mínimo 8 caracteres, com maiúscula, minúscula, número e símbolo.</span>
                                            <?= $campoErro('senha_nova') ?>
                                        </div>

                                        <div class="col-md-6 form-group">
                                            <label for="pf-senha-confirmar">Confirmar nova senha</label>
                                            <input type="password" class="form-control" id="pf-senha-confirmar" name="senha_confirmar"
                                                   required minlength="8" maxlength="72" autocomplete="new-password">
                                            <?= $campoErro('senha_confirmar') ?>
                                        </div>
                                    </div>

                                    <div class="text-center mt-3">
                                        <button type="submit" class="btn-primary-solid">
                                            <i class="icon-shield-check me-2"></i>Atualizar Senha
                                        </button>
                                    </div>
                                </form>

                                <div class="logout-section">
                                    <p>Deseja sair da sua conta?</p>
                                    <form method="post" action="/perfil/sair">
                                        <?= Sessao::csrfCampo() ?>
                                        <button type="submit" class="btn-primary-solid btn-logout">
                                            <i class="icon-log-out me-2"></i>Sair da Conta
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php require __DIR__ . '/partials/perfil_footer.php'; ?>
</body>
</html>
