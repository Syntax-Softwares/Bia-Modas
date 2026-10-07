<?php 

require_once __DIR__ . "/" . "../repositories/produto_repository.php";

class HomeController{

    private ProdutoRepository $repository;

    public function  __construct(private PDO $pdo)
    {
        $this->repository = new ProdutoRepository($pdo);
    }
    
    public function index(){
        
        $data = [
            "novidades" => $this->repository->get_novidades()
        ];
        
        require __DIR__ . "/" ."../views/index.php";
    }
}
?>