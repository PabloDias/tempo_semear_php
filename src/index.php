<?php

$host = 'db'; // O nome do serviço do banco de dados no docker-compose.yml
$port = '5432';
$dbname = 'semeadordb';
$user = 'semeadoruser';
$password = 'semearv2_2025'; // A mesma senha definida no docker-compose.yml

$dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

try {
    $pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "<h1>Conectado ao PostgreSQL com sucesso!</h1>";
} catch (PDOException $e) {
    echo "<h1 style='color: red;'>Erro ao conectar com o PostgreSQL:</h1>";
    die($e->getMessage());
}

echo "<hr>";
phpinfo();

?>