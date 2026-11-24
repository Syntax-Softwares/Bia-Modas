<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SessaoTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testUsuarioExigeIdNaSessao(): void
    {
        self::assertNull(Sessao::usuario());
        self::assertNull(Sessao::id());

        $_SESSION['usuario'] = ['nome' => 'Sem id'];
        self::assertNull(Sessao::usuario());

        $_SESSION['usuario'] = ['id_usuario' => '12', 'nome' => 'Maria'];
        self::assertSame(['id_usuario' => '12', 'nome' => 'Maria'], Sessao::usuario());
        self::assertSame(12, Sessao::id());
    }

    public function testAtualizarUsuarioAlteraSomenteNomeEEmail(): void
    {
        $_SESSION['usuario'] = [
            'id_usuario' => 1,
            'nome' => 'Antigo',
            'email' => 'antigo@exemplo.com',
            'is_admin' => true,
        ];

        Sessao::atualizarUsuario([
            'nome' => 'Novo',
            'email' => 'novo@exemplo.com',
            'is_admin' => false,
            'id_usuario' => 99,
        ]);

        self::assertSame([
            'id_usuario' => 1,
            'nome' => 'Novo',
            'email' => 'novo@exemplo.com',
            'is_admin' => true,
        ], $_SESSION['usuario']);
    }

    public function testAtualizarUsuarioNaoCriaSessaoQuandoNaoExiste(): void
    {
        Sessao::atualizarUsuario(['nome' => 'Maria']);

        self::assertArrayNotHasKey('usuario', $_SESSION);
    }

    public function testVisitanteIdEhGeradoEMantido(): void
    {
        $id = Sessao::visitanteId();

        self::assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $id);
        self::assertSame($id, Sessao::visitanteId());
        self::assertSame($id, Sessao::visitanteIdAtual());
    }

    public function testVisitanteIdAtualNaoCriaNovoIdentificador(): void
    {
        self::assertNull(Sessao::visitanteIdAtual());
        self::assertArrayNotHasKey('visitante_id', $_SESSION);
    }

    public function testDestinoAposLoginAceitaSomenteCaminhoInterno(): void
    {
        Sessao::definirDestinoAposLogin('//evil.com');
        self::assertNull(Sessao::consumirDestinoAposLogin());

        Sessao::definirDestinoAposLogin('https://evil.com');
        self::assertNull(Sessao::consumirDestinoAposLogin());

        Sessao::definirDestinoAposLogin('checkout');
        self::assertNull(Sessao::consumirDestinoAposLogin());

        Sessao::definirDestinoAposLogin('/checkout?cupom=BIA10');
        self::assertSame('/checkout?cupom=BIA10', Sessao::consumirDestinoAposLogin());
        self::assertNull(Sessao::consumirDestinoAposLogin());
    }

    public function testConsumirDestinoDescartaValorInvalidoGravadoNaSessao(): void
    {
        $_SESSION['destino_apos_login'] = '//evil.com';

        self::assertNull(Sessao::consumirDestinoAposLogin());
        self::assertArrayNotHasKey('destino_apos_login', $_SESSION);
    }

    public function testCsrfTokenPossuiTamanhoMinimoERegeneraTokenCurto(): void
    {
        $_SESSION['csrf_token'] = 'curto';

        $token = Sessao::csrfToken();

        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
        self::assertNotSame('curto', $token);
        self::assertSame($token, Sessao::csrfToken());
    }

    public function testCsrfCampoEValidacao(): void
    {
        $token = Sessao::csrfToken();

        self::assertSame(
            '<input type="hidden" name="_csrf" value="' . $token . '">',
            Sessao::csrfCampo()
        );
        self::assertSame(Sessao::csrfCampo(), Sessao::csrfField());
        self::assertTrue(Sessao::validarCsrf($token));
        self::assertTrue(Sessao::validCsrf($token));
        self::assertFalse(Sessao::validarCsrf('outro'));
        self::assertFalse(Sessao::validarCsrf(null));
    }

    public function testFlashMantemAsDuasNomenclaturas(): void
    {
        Sessao::flash('success', 'Tudo certo.');

        self::assertSame([
            [
                'type' => 'success',
                'message' => 'Tudo certo.',
                'tipo' => 'success',
                'mensagem' => 'Tudo certo.',
            ],
        ], $_SESSION['flash']);
    }

    public function testMensagensFlashSaoConsumidasUmaVez(): void
    {
        Sessao::flash('info', 'Mensagem.');

        self::assertCount(1, Sessao::mensagensFlash());
        self::assertSame([], Sessao::flashes());
    }

    public function testSetOldRemoveSenhasEToken(): void
    {
        Sessao::setOld([
            'nome' => 'Maria',
            'senha' => 'a',
            'senha_atual' => 'b',
            'senha_nova' => 'c',
            'senha_confirmar' => 'd',
            '_csrf' => 'e',
        ]);

        self::assertSame(['nome' => 'Maria'], Sessao::old());
        self::assertSame([], Sessao::old());
    }

    public function testSetErrosEerros(): void
    {
        Sessao::setErros(['nome' => 'Obrigatorio.']);

        self::assertSame(['nome' => 'Obrigatorio.'], Sessao::erros());
        self::assertSame([], Sessao::erros());
    }
}
