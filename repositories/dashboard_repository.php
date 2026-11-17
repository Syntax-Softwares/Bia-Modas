<?php

/**
 * Consultas agregadas para o painel inicial do admin.
 */
class DashboardRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return array<string, int>
     */
    public function resumo(int $limiteEstoqueBaixo = 5): array
    {
        return [
            'produtos_ativos' => $this->contar(
                'SELECT COUNT(*) FROM produtos WHERE ativo = TRUE AND deletado_em IS NULL'
            ),
            'estoque_baixo' => $this->contar(
                'SELECT COUNT(*) FROM produtos
                 WHERE ativo = TRUE
                   AND deletado_em IS NULL
                   AND estoque <= :limite',
                [':limite' => $limiteEstoqueBaixo]
            ),
            'clientes' => $this->contar(
                'SELECT COUNT(*) FROM usuarios WHERE is_admin = FALSE AND deletado_em IS NULL'
            ),
            'ofertas_ativas' => $this->contar(
                "SELECT COUNT(*) FROM ofertas
                 WHERE ativo = TRUE
                   AND deletado_em IS NULL
                   AND (inicio_em IS NULL OR inicio_em <= NOW())
                   AND (fim_em IS NULL OR fim_em >= NOW())"
            ),
            'cupons_ativos' => $this->contar(
                'SELECT COUNT(*) FROM cupons WHERE deletado_em IS NULL'
            ),
            'categorias' => $this->contar(
                'SELECT COUNT(*) FROM categorias WHERE deletado_em IS NULL'
            ),
        ];
    }

    public function ultimosProdutos(int $limite = 5): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id_produto, nome, sku, valor, estoque, ativo, criado_em
             FROM produtos
             WHERE deletado_em IS NULL
             ORDER BY criado_em DESC, id_produto DESC
             LIMIT :limite'
        );
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function ultimosUsuarios(int $limite = 5): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id_usuario, nome, email, is_admin, criado_em
             FROM usuarios
             WHERE deletado_em IS NULL
             ORDER BY criado_em DESC, id_usuario DESC
             LIMIT :limite'
        );
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    private function contar(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $nome => $valor) {
            $stmt->bindValue($nome, $valor, PDO::PARAM_INT);
        }

        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }
}
