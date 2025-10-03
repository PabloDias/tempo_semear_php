<?php
// src/ver_arquivo.php

session_start();
require_once 'db.php';

// 1. Verifica se o usuário está logado e se um tipo de documento foi solicitado
if (!isset($_SESSION['usuario_id']) || !isset($_GET['tipo'])) {
    http_response_code(403); // Proibido
    die("Acesso negado.");
}

$beneficiario_id = $_SESSION['usuario_id'];
$tipo_documento_solicitado = $_GET['tipo'];

try {
    // 2. Busca o caminho do arquivo no banco de dados, garantindo que pertence ao usuário logado
    $sql = "SELECT ac.caminho_arquivo, ac.nome_original, ac.mime_type
            FROM arquivos_cadastro ac
            JOIN cadastros c ON ac.cadastro_id = c.id
            WHERE c.beneficiario_id = :beneficiario_id 
            AND ac.tipo_documento = :tipo_documento";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'beneficiario_id' => $beneficiario_id,
        'tipo_documento' => $tipo_documento_solicitado
    ]);
    
    $arquivo = $stmt->fetch(PDO::FETCH_ASSOC);

    // 3. Se o arquivo for encontrado, entrega ao navegador
    if ($arquivo && file_exists($arquivo['caminho_arquivo'])) {
        // Define o cabeçalho com o tipo de conteúdo do arquivo
        header('Content-Type: ' . $arquivo['mime_type']);
        // Define o cabeçalho para o navegador exibir o arquivo, em vez de baixar
        header('Content-Disposition: inline; filename="' . basename($arquivo['nome_original']) . '"');
        
        // Lê o arquivo e o envia para a saída
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