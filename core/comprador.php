<?php

require_once __DIR__ . '/sessao.php';

/**
 * Identifica de quem sao o carrinho e os favoritos da requisicao atual.
 *
 * Usuario logado: o vinculo e feito pelo id_usuario.
 * Visitante: usa o identificador anonimo da sessao, que depois e migrado
 * para a conta no primeiro login/cadastro (ver AuthController).
 */
final class Comprador
{
    private function __construct(
        private ?int $idUsuario,
        private ?string $idSessao
    ) {
    }

    public static function atual(): self
    {
        $idUsuario = Sessao::id();

        if ($idUsuario !== null) {
            return new self($idUsuario, null);
        }

        return new self(null, Sessao::visitanteId());
    }

    public function idUsuario(): ?int
    {
        return $this->idUsuario;
    }

    public function idSessao(): string
    {
        if ($this->idSessao === null) {
            throw new LogicException('Comprador autenticado nao possui sessao anonima.');
        }

        return $this->idSessao;
    }
}
