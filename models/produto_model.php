<?php
class Produto{
    public function __construct(
        public int $id_produto,
        public string $nome,
        public ?string $descricao,
        public int $preco, //centavos
        public bool $ativo,
        public ?DateTime $criado_em,
        public ?DateTime $alterado_em,
        public DateTime $deletado_em
    )
    {}

    
}
?>