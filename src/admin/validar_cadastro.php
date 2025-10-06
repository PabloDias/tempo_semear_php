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
$novo_status = $_POST['status'];
$observacoes = trim($_POST['observacoes'] ?? '');
$admin_id = $_SESSION['admin_usuario_id'];

// Validar status
$status_permitidos = ['enviado', 'em_analise', 'aprovado', 'rejeitado'];
if (!in_array($novo_status, $status_permitidos)) {
    header('Location: ver_cadastro.php?id=' . $cadastro_id . '&error=status_invalido');
    exit();
}

if (empty($observacoes)) {
    header('Location: ver_cadastro.php?id=' . $cadastro_id . '&error=observacoes_obrigatorias');
    exit();
}

try {
    $pdo->beginTransaction();
    
    // Busca o status atual ANTES de atualizar
    $stmt_antigo = $pdo->prepare("SELECT status, observacoes_analise FROM cadastros WHERE id = :id");
    $stmt_antigo->execute(['id' => $cadastro_id]);
    $dados_antigos = $stmt_antigo->fetch(PDO::FETCH_ASSOC);
    
    if (!$dados_antigos) {
        throw new Exception("Cadastro não encontrado.");
    }
    
    $status_anterior = $dados_antigos['status'];
    
    // Atualizar status do cadastro
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
    
    // REGISTRAR NO HISTÓRICO DE EDIÇÕES
    $stmt_historico = $pdo->prepare(
        "INSERT INTO historico_edicoes (cadastro_id, usuario_id, campo_alterado, valor_anterior, valor_novo, justificativa, ip_alteracao)
         VALUES (:cadastro_id, :usuario_id, :campo, :anterior, :novo, :justificativa, :ip)"
    );
    
    $stmt_historico->execute([
        'cadastro_id' => $cadastro_id,
        'usuario_id' => $admin_id,
        'campo' => 'status',
        'anterior' => $status_anterior,
        'novo' => $novo_status,
        'justificativa' => $observacoes,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null
    ]);
    
    // Registrar no log do sistema
    $pdo->prepare(
        "INSERT INTO logs_sistema (nivel, modulo, acao, descricao, usuario_tipo, usuario_id, cadastro_id)
         VALUES ('INFO', 'cadastros', 'Mudança de status', :descricao, 'interno', :usuario_id, :cadastro_id)"
    )->execute([
        'descricao' => "Status alterado de '{$status_anterior}' para '{$novo_status}'. Observações: {$observacoes}",
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