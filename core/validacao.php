<?php

/**
 * Validacoes de entrada compartilhadas pelo perfil.
 *
 * Todas as regras espelham os limites do db.sql e do RF01.
 */
final class Validacao
{
    private const UFS = [
        'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS',
        'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC',
        'SP', 'SE', 'TO',
    ];

    public static function texto(mixed $valor): string
    {
        $texto = (string) $valor;
        // Remove caracteres de controle (inclui NUL) sem mexer em acentos.
        $texto = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $texto) ?? '';

        return trim($texto);
    }

    public static function somenteDigitos(mixed $valor): string
    {
        return preg_replace('/\D+/', '', (string) $valor) ?? '';
    }

    public static function email(string $email): bool
    {
        return $email !== ''
            && strlen($email) <= 255
            && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function telefone(string $telefone): bool
    {
        $digitos = self::somenteDigitos($telefone);

        return strlen($digitos) >= 10
            && strlen($digitos) <= 11
            && $digitos[0] !== '0';
    }

    public static function cep(string $cep): bool
    {
        return strlen(self::somenteDigitos($cep)) === 8;
    }

    public static function uf(string $uf): bool
    {
        return in_array(strtoupper($uf), self::UFS, true);
    }

    /**
     * Valida CPF pelos digitos verificadores (nao apenas o tamanho).
     */
    public static function cpf(string $cpf): bool
    {
        $cpf = self::somenteDigitos($cpf);

        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf) === 1) {
            return false;
        }

        for ($posicao = 9; $posicao < 11; $posicao++) {
            $soma = 0;

            for ($i = 0; $i < $posicao; $i++) {
                $soma += (int) $cpf[$i] * (($posicao + 1) - $i);
            }

            $digito = ((10 * $soma) % 11) % 10;

            if ((int) $cpf[$posicao] !== $digito) {
                return false;
            }
        }

        return true;
    }

    /**
     * Aceita apenas datas reais no passado (formato Y-m-d, vindo do input date).
     */
    public static function dataNascimento(string $data): bool
    {
        $objeto = DateTime::createFromFormat('!Y-m-d', $data);

        if ($objeto === false || $objeto->format('Y-m-d') !== $data) {
            return false;
        }

        $ano = (int) $objeto->format('Y');
        $hoje = new DateTime('today');

        return $ano >= 1900 && $objeto <= $hoje;
    }

    /**
     * Politica de senha do perfil: minimo 8, maiuscula, minuscula, numero e simbolo.
     *
     * @return list<string> mensagens de erro (vazio quando a senha e valida)
     */
    public static function senha(string $senha): array
    {
        $erros = [];

        if (strlen($senha) < 8) {
            $erros[] = 'A senha deve ter no minimo 8 caracteres.';
        }

        // bcrypt ignora bytes apos 72; recusar evita senhas ambíguas.
        if (strlen($senha) > 72) {
            $erros[] = 'A senha deve ter no maximo 72 caracteres.';
        }

        if (preg_match('/[a-z]/', $senha) !== 1) {
            $erros[] = 'A senha deve conter ao menos uma letra minuscula.';
        }

        if (preg_match('/[A-Z]/', $senha) !== 1) {
            $erros[] = 'A senha deve conter ao menos uma letra maiuscula.';
        }

        if (preg_match('/\d/', $senha) !== 1) {
            $erros[] = 'A senha deve conter ao menos um numero.';
        }

        if (preg_match('/[^A-Za-z0-9]/', $senha) !== 1) {
            $erros[] = 'A senha deve conter ao menos um simbolo.';
        }

        return $erros;
    }
}
