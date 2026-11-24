<?php

/**
 * Conexao PDO com o banco da aplicacao.
 *
 * As credenciais vem de variaveis de ambiente para nao ficarem fixas no
 * codigo. Os valores padrao mantem o funcionamento do docker-compose atual.
 */
class Database
{
    /**
     * Mantido com o nome original para nao quebrar o codigo existente.
     */
    public static function conenct(): PDO
    {
        return self::connect();
    }

    public static function connect(): PDO
    {
        $host = getenv('DB_HOST') ?: 'db';
        $port = getenv('DB_PORT') ?: '3306';
        $nome = getenv('DB_NAME') ?: 'biamodas';
        $usuario = getenv('DB_USER') ?: 'root';
        $senha = getenv('DB_PASSWORD') ?: 'root';

        $pdo = new PDO(
            "mysql:host={$host};port={$port};dbname={$nome};charset=utf8mb4",
            $usuario,
            $senha,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                // Prepared statements reais no servidor: bloqueia SQL injection
                // por concatenacao e impede a emulacao de placeholders.
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            ]
        );

        return $pdo;
    }
}
