<?php
// src/admin/processa_edicao_admin.php (VERSÃO CORRIGIDA FINAL)

session_start();
require_once '../db.php';

if (!isset($_SESSION['admin_usuario_id']) || $_SESSION['admin_usuario_perfil'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?error=acesso_negado');
    exit();
}

$cadastro_id = (int)$_POST['cadastro_id'];
$justificativa = trim($_POST['justificativa']);
$admin_id = $_SESSION['admin_usuario_id']; // ID do admin logado

if (empty($cadastro_id) || empty($justificativa)) {
    header('Location: editar_cadastro.php?id=' . $cadastro_id . '&error=justificativa_obrigatoria');
    exit();
}

$pdo->beginTransaction();

try {
    $stmt_antigo = $pdo->prepare("SELECT * FROM cadastros WHERE id = :id");
    $stmt_antigo->execute(['id' => $cadastro_id]);
    $dados_antigos = $stmt_antigo->fetch(PDO::FETCH_ASSOC);

    if (!$dados_antigos) {
        throw new Exception("Cadastro não encontrado.");
    }
    
    // Coleta os novos dados do formulário
    $dados_novos = [
        'nome' => trim($_POST['nome']),
        'nome_social' => trim($_POST['nome_social']),
        'municipio_id' => (int)$_POST['municipio_id'],
        'rg' => trim($_POST['rg']),
        'orgao_emissor' => trim($_POST['orgao_emissor']),
        'atualizado_por' => $admin_id // <-- ADIÇÃO IMPORTANTE
    ];

    // Compara dados e registra no histórico
    $stmt_historico = $pdo->prepare(
        "INSERT INTO historico_edicoes (cadastro_id, usuario_id, campo_alterado, valor_anterior, valor_novo, justificativa)
         VALUES (:cadastro_id, :usuario_id, :campo, :anterior, :novo, :justificativa)"
    );

    foreach ($dados_novos as $campo => $valor_novo) {
        if (array_key_exists($campo, $dados_antigos) && $dados_antigos[$campo] != $valor_novo) {
            $stmt_historico->execute([
                'cadastro_id' => $cadastro_id,
                'usuario_id' => $admin_id,
                'campo' => $campo,
                'anterior' => $dados_antigos[$campo],
                'novo' => $valor_novo,
                'justificativa' => $justificativa
            ]);
        }
    }

    // Atualiza o cadastro na tabela principal
    $sql_update = "UPDATE cadastros SET
                       nome = :nome,
                       nome_social = :nome_social,
                       municipio_id = :municipio_id,
                       rg = :rg,
                       orgao_emissor = :orgao_emissor,
                       atualizado_por = :atualizado_por -- <-- ADIÇÃO IMPORTANTE
                   WHERE id = :cadastro_id";
    
    $stmt_update = $pdo->prepare($sql_update);
    
    $dados_novos['cadastro_id'] = $cadastro_id;
    
    $stmt_update->execute($dados_novos);

    $pdo->commit();

    header('Location: ver_cadastro.php?id=' . $cadastro_id . '&status=edicao_sucesso');
    exit();

} catch (Exception $e) {
    $pdo->rollBack();
    die("Erro ao salvar as alterações: " . $e->getMessage());
}