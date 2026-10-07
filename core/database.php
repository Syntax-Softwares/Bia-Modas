<?php
class Database{
    static public function conenct():PDO
    {
        $pdo = new PDO(

            "mysql:host=db;dbname=biamodas;charset=utf8mb4",
            "root",
            "root"
        
        );

        $pdo->setAttribute(
            PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION
        );

        return $pdo;
    }
}
?>