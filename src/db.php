<?php
// src/db.php

$host = 'db';
$port = '5432';
$dbname = 'semeadordb';
$user = 'semeadoruser';
$password = 'semearv2_2025'; 

$dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

try {
    // Cria uma instância do PDO, que representa a conexão com o banco
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION // Habilita o lançamento de exceções em caso de erro
    ]);
} catch (PDOException $e) {
    // Se a conexão falhar, exibe uma mensagem de erro genérica e termina o script
    die("Erro ao conectar com o banco de dados: " . $e->getMessage());
}