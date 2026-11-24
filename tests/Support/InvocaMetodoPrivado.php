<?php

declare(strict_types=1);

/**
 * Acesso controlado a metodos privados de controllers.
 *
 * Alguns controllers concentram regras de validacao/normalizacao em metodos
 * privados. O trait permite testar essas regras sem alterar a API publica das
 * classes de producao.
 */
trait InvocaMetodoPrivado
{
    /**
     * @param array<int, mixed> $argumentos
     */
    private function invocarMetodoPrivado(object $objeto, string $metodo, array $argumentos = []): mixed
    {
        $reflexao = new ReflectionMethod($objeto, $metodo);

        return $reflexao->invokeArgs($objeto, $argumentos);
    }
}
