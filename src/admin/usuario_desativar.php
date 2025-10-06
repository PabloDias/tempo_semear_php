<?php
// src/admin/usuario_desativar.php

session_start();
require_once '../db.php';

if (!isset($_SESSION['admin_usuario_id']) || $_SESSION['admin_usuario_perfil'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

$usuario_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($usuario_id === 0 || $usuario_id === $_SESSION['admin_usuario_id']) {
    header('Location: usuarios.php');
    exit();
}

try {
    // Busca o status atual
    $stmt = $pdo->prepare("SELECT ativo, nome FROM usuarios_internos WHERE id = :id");
    $stmt->execute(['id' => $usuario_id]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        header('Location: usuarios.php');
        exit();
    }
    
    // Inverte o status (usa operador booleano correto)
    $novo_status = !$usuario['ativo'];
    
    // Bind com tipo PDO::PARAM_BOOL
    $stmt = $pdo->prepare("UPDATE usuarios_internos SET ativo = :ativo, atualizado_em = CURRENT_TIMESTAMP WHERE id = :id");
    $stmt->bindValue(':ativo', $novo_status, PDO::PARAM_BOOL);
    $stmt->bindValue(':id', $usuario_id, PDO::PARAM_INT);
    $stmt->execute();
    
    // Registrar no log
    $acao = $novo_status ? 'ativado' : 'desativado';
    $pdo->prepare(
        "INSERT INTO logs_sistema (nivel, modulo, acao, descricao, usuario_tipo, usuario_id)
         VALUES ('INFO', 'usuarios_internos', 'Alteração de status', :descricao, 'interno', :admin_id)"
    )->execute([
        'descricao' => "Admin {$_SESSION['admin_usuario_nome']} {$acao} o usuário {$usuario['nome']} (ID: {$usuario_id})",
        'admin_id' => $_SESSION['admin_usuario_id']
    ]);
    
    header('Location: usuarios.php?success=1');
    exit();
    
} catch (Exception $e) {
    die("Erro ao alterar status: " . $e->getMessage());
}