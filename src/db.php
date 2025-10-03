<?php
// src/db.php

// Configurações do banco de dados
$host = 'db';  // Nome do serviço no docker-compose
$port = '5432';
$dbname = 'semeadordb';
$user = 'semeadoruser';
$password = 'semearv2_2025';

$dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    error_log("Erro de conexão: " . $e->getMessage());
    die("Erro ao conectar com o banco de dados. Verifique as configurações.");
}