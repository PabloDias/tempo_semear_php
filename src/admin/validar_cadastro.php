<?php
// src/admin/validar_cadastro.php

session_start();
require_once '../db.php';

if (!isset($_SESSION['admin_usuario_id'])) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit();
}

$cadastro_id = (int)$_POST['cadastro_id'];
$novo_status = $_POST['status']; // enviado, em_analise, validado, rejeitado
$observacoes = trim($_POST['observacoes'] ?? '');
$admin_id = $_SESSION['admin_usuario_id'];

// Validar status
$status_permitidos = ['enviado', 'em_analise', 'validado', 'rejeitado'];
if (!in_array($novo_status, $status_permitidos)) {
    header('Location: ver_cadastro.php?id=' . $cadastro_id . '&error=status_invalido');
    exit();
}

try {
    $pdo->beginTransaction();
    
    // Atualizar status
    $sql = "UPDATE cadastros SET 
                status = :status,
                analisado_por = :admin_id,
                data_analise = CURRENT_TIMESTAMP,
                observacoes_analise = :observacoes
            WHERE id = :cadastro_id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'status' => $novo_status,
        'admin_id' => $admin_id,
        'observacoes' => $observacoes,
        'cadastro_id' => $cadastro_id
    ]);
    
    // Registrar no log
    $pdo->prepare(
        "INSERT INTO logs_sistema (nivel, modulo, acao, descricao, usuario_tipo, usuario_id, cadastro_id)
         VALUES ('INFO', 'cadastros', 'Mudança de status', :descricao, 'interno', :usuario_id, :cadastro_id)"
    )->execute([
        'descricao' => "Status alterado para: {$novo_status}. Observações: {$observacoes}",
        'usuario_id' => $admin_id,
        'cadastro_id' => $cadastro_id
    ]);
    
    $pdo->commit();
    
    header('Location: ver_cadastro.php?id=' . $cadastro_id . '&status=validado_sucesso');
    exit();
    
} catch (Exception $e) {
    $pdo->rollBack();
    header('Location: ver_cadastro.php?id=' . $cadastro_id . '&error=' . urlencode($e->getMessage()));
    exit();
}