<?php
// src/admin/ver_arquivo_admin.php

session_start();
require_once '../db.php';

// 1. Verifica se o usuário ADMIN está logado e se um ID de arquivo foi solicitado
if (!isset($_SESSION['admin_usuario_id']) || !isset($_GET['id'])) {
    http_response_code(403); // Proibido
    die("Acesso negado.");
}

$arquivo_id = (int)$_GET['id'];

try {
    // 2. Busca o caminho do arquivo no banco de dados pelo seu ID único
    $stmt = $pdo->prepare("SELECT caminho_arquivo, nome_original, mime_type FROM arquivos_cadastro WHERE id = :id");
    $stmt->execute(['id' => $arquivo_id]);
    $arquivo = $stmt->fetch(PDO::FETCH_ASSOC);

    // 3. Se o arquivo for encontrado, entrega ao navegador
    if ($arquivo && file_exists($arquivo['caminho_arquivo'])) {
        header('Content-Type: ' . $arquivo['mime_type']);
        header('Content-Disposition: inline; filename="' . basename($arquivo['nome_original']) . '"');
        readfile($arquivo['caminho_arquivo']);
        exit();
    } else {
        http_response_code(404); // Não encontrado
        die("Arquivo não encontrado.");
    }

} catch (PDOException $e) {
    http_response_code(500); // Erro interno do servidor
    die("Erro no servidor ao tentar acessar o arquivo.");
}