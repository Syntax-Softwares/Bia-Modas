<?php

class Database
{
    static public function connect(): PDO
    {
        $pdo = new PDO(
            "mysql:host=db;dbname=biamodas;charset=utf8mb4",
            "root",
            "root"
        );

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $pdo;
    }

    static public function conenct(): PDO
    {
        return self::connect();
    }
}
