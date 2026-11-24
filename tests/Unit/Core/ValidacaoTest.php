<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ValidacaoTest extends TestCase
{
    #[DataProvider('cpfsValidos')]
    public function testCpfAceitaDigitosVerificadoresValidos(string $cpf): void
    {
        self::assertTrue(Validacao::cpf($cpf));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function cpfsValidos(): array
    {
        return [
            'somente digitos' => ['52998224725'],
            'com mascara' => ['529.982.247-25'],
            'outro cpf valido' => ['11144477735'],
        ];
    }

    #[DataProvider('cpfsInvalidos')]
    public function testCpfRejeitaValoresInvalidos(string $cpf): void
    {
        self::assertFalse(Validacao::cpf($cpf));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function cpfsInvalidos(): array
    {
        return [
            'vazio' => [''],
            'curto' => ['123'],
            'digitos repetidos' => ['11111111111'],
            'digito verificador errado' => ['52998224724'],
            'letras' => ['5299822472a'],
        ];
    }

    #[DataProvider('emailsValidos')]
    public function testEmailAceitaEnderecosValidos(string $email): void
    {
        self::assertTrue(Validacao::email($email));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function emailsValidos(): array
    {
        return [
            'simples' => ['maria@biamodas.com.br'],
            'com ponto' => ['maria.silva@exemplo.com'],
            'com mais' => ['maria+loja@exemplo.com'],
        ];
    }

    #[DataProvider('emailsInvalidos')]
    public function testEmailRejeitaEnderecosInvalidos(string $email): void
    {
        self::assertFalse(Validacao::email($email));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function emailsInvalidos(): array
    {
        return [
            'vazio' => [''],
            'sem arroba' => ['maria.exemplo.com'],
            'sem dominio' => ['maria@'],
            'com espaco' => ['maria silva@exemplo.com'],
            'muito longo' => [str_repeat('a', 250) . '@x.com'],
        ];
    }

    #[DataProvider('telefonesValidos')]
    public function testTelefoneAceitaDddECelularOuFixo(string $telefone): void
    {
        self::assertTrue(Validacao::telefone($telefone));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function telefonesValidos(): array
    {
        return [
            'celular com mascara' => ['(14) 99999-9999'],
            'fixo com mascara' => ['(14) 3333-4444'],
            'somente digitos' => ['14999999999'],
        ];
    }

    #[DataProvider('telefonesInvalidos')]
    public function testTelefoneRejeitaValoresInvalidos(string $telefone): void
    {
        self::assertFalse(Validacao::telefone($telefone));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function telefonesInvalidos(): array
    {
        return [
            'vazio' => [''],
            'curto' => ['999999'],
            'comeca com zero' => ['0999999999'],
            'longo demais' => ['5514999999999'],
        ];
    }

    #[DataProvider('ceps')]
    public function testCepExigeOitoDigitos(string $cep, bool $esperado): void
    {
        self::assertSame($esperado, Validacao::cep($cep));
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function ceps(): array
    {
        return [
            'oito digitos' => ['17200000', true],
            'com mascara' => ['17200-000', true],
            'sete digitos' => ['1720000', false],
            'letras' => ['17200-00a', false],
        ];
    }

    #[DataProvider('ufs')]
    public function testUfAceitaSomenteEstadosBrasileiros(string $uf, bool $esperado): void
    {
        self::assertSame($esperado, Validacao::uf($uf));
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function ufs(): array
    {
        return [
            'maiuscula' => ['SP', true],
            'minuscula' => ['sp', true],
            'inexistente' => ['XX', false],
            'vazia' => ['', false],
        ];
    }

    #[DataProvider('datasDeNascimento')]
    public function testDataNascimentoAceitaSomenteDatasReaisNoPassado(string $data, bool $esperado): void
    {
        self::assertSame($esperado, Validacao::dataNascimento($data));
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function datasDeNascimento(): array
    {
        return [
            'data valida' => ['1990-05-10', true],
            'formato brasileiro' => ['10/05/1990', false],
            'dia inexistente' => ['2020-02-30', false],
            'ano futuro' => ['2999-01-01', false],
            'antes de 1900' => ['1899-12-31', false],
            'vazia' => ['', false],
        ];
    }

    public function testSenhaAceitaPoliticaCompleta(): void
    {
        self::assertSame([], Validacao::senha('BiaModas1!'));
    }

    public function testSenhaAcumulaTodasAsRegrasVioladas(): void
    {
        $erros = Validacao::senha('abc');

        self::assertCount(4, $erros);
        self::assertContains('A senha deve ter no minimo 8 caracteres.', $erros);
        self::assertContains('A senha deve conter ao menos uma letra maiuscula.', $erros);
        self::assertContains('A senha deve conter ao menos um numero.', $erros);
        self::assertContains('A senha deve conter ao menos um simbolo.', $erros);
    }

    public function testSenhaRejeitaMaisDe72CaracteresPorCausaDoBcrypt(): void
    {
        $erros = Validacao::senha(str_repeat('A', 70) . 'a1!');

        self::assertContains('A senha deve ter no maximo 72 caracteres.', $erros);
    }

    public function testTextoRemoveCaracteresDeControleEPreservaAcentos(): void
    {
        self::assertSame('Jaú', Validacao::texto("  Ja\x00ú\t "));
    }

    public function testSomenteDigitosRemoveMascara(): void
    {
        self::assertSame('14999999999', Validacao::somenteDigitos('(14) 99999-9999'));
    }
}
