<?php

class Produto
{
    public function __construct(
        public int $id_produto,
        public int $medida_produto_id,
        public ?int $oferta_id,
        public string $nome,
        public string $sku,
        public ?string $descricao,
        public int $estoque,
        public float $valor,
        public bool $ativo,
        public ?DateTime $criado_em,
        public ?DateTime $alterado_em,
        public ?DateTime $deletado_em
    ) {
    }
}
