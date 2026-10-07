<?php
class ProdutoRepository{
    public function __construct(
        private PDO $pdo
    )
    {}

    public function get_novidades(){
        $stmt = $this->pdo->query(
        "
            SELECT * FROM produtos
            WHERE deletado_em IS NULL
            ORDER BY criado_em DESC
            LIMIT 15
        "
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>