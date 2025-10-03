<?php
// src/processa_formulario_principal.php (VERSÃO FINAL E CORRIGIDA)

session_start();
require_once 'db.php';

if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . (isset($_SESSION['usuario_id']) ? 'dashboard.php' : 'login.php'));
    exit();
}

// Inicia uma transação para garantir que tudo seja salvo junto
$pdo->beginTransaction();

try {
    $beneficiario_id = $_SESSION['usuario_id'];
    $acao = $_POST['acao'] ?? 'rascunho';

    $dados_cadastro = [
        'municipio_id' => (int)$_POST['municipio_id'],
        'nome' => trim($_POST['nome']),
        'nome_social' => trim($_POST['nome_social']),
        'data_nascimento' => $_POST['data_nascimento'],
        'sexo' => $_POST['sexo'],
        'nome_mae' => trim($_POST['nome_mae']),
        'nome_pai' => trim($_POST['nome_pai']),
        'rg' => trim($_POST['rg']),
        'orgao_emissor' => trim($_POST['orgao_emissor']),
        'telefone1' => trim($_POST['telefone1']),
        'telefone2' => trim($_POST['telefone2']),
        'cep' => preg_replace('/[^0-9]/', '', $_POST['cep']),
        'logradouro' => trim($_POST['logradouro']),
        'numero' => trim($_POST['numero']),
        'bairro' => trim($_POST['bairro'])
    ];

    if ($acao === 'enviar') {
        $dados_cadastro['status'] = 'enviado';
        $dados_cadastro['editavel'] = false; // Valor booleano
        $dados_cadastro['data_envio'] = date('Y-m-d H:i:s');
    } else {
        $dados_cadastro['status'] = 'rascunho';
        $dados_cadastro['editavel'] = true; // Valor booleano
        $dados_cadastro['data_envio'] = null;
    }

    $stmt = $pdo->prepare("SELECT id FROM cadastros WHERE beneficiario_id = :beneficiario_id");
    $stmt->execute(['beneficiario_id' => $beneficiario_id]);
    $cadastro_existente = $stmt->fetch();

    if ($cadastro_existente) {
        $cadastro_id = $cadastro_existente['id'];
        $dados_cadastro['id'] = $cadastro_id;
        $sql = "UPDATE cadastros SET
                    municipio_id = :municipio_id, nome = :nome, nome_social = :nome_social, 
                    data_nascimento = :data_nascimento, sexo = :sexo, nome_mae = :nome_mae, 
                    nome_pai = :nome_pai, rg = :rg, orgao_emissor = :orgao_emissor, 
                    telefone1 = :telefone1, telefone2 = :telefone2, cep = :cep, 
                    logradouro = :logradouro, numero = :numero, bairro = :bairro,
                    status = :status, editavel = :editavel, data_envio = :data_envio
                WHERE id = :id";
    } else {
        $stmtUser = $pdo->prepare("SELECT cpf, email FROM beneficiarios WHERE id = :id");
        $stmtUser->execute(['id' => $beneficiario_id]);
        $beneficiario_info = $stmtUser->fetch(PDO::FETCH_ASSOC);

        $dados_cadastro['beneficiario_id'] = $beneficiario_id;
        $dados_cadastro['protocolo'] = 'TS' . date('Y') . '-' . str_pad($beneficiario_id, 6, '0', STR_PAD_LEFT);
        $dados_cadastro['cpf'] = $beneficiario_info['cpf'];
        $dados_cadastro['email'] = $beneficiario_info['email'];

        $sql = "INSERT INTO cadastros (
                    municipio_id, beneficiario_id, protocolo, nome, nome_social, data_nascimento, 
                    sexo, nome_mae, nome_pai, rg, orgao_emissor, telefone1, telefone2, 
                    cep, logradouro, numero, bairro, status, editavel, data_envio, cpf, email
                ) VALUES (
                    :municipio_id, :beneficiario_id, :protocolo, :nome, :nome_social, :data_nascimento,
                    :sexo, :nome_mae, :nome_pai, :rg, :orgao_emissor, :telefone1, :telefone2,
                    :cep, :logradouro, :numero, :bairro, :status, :editavel, :data_envio, :cpf, :email
                )";
    }
    
    // --- INÍCIO DA CORREÇÃO ---
    // Em vez de usar execute() diretamente no array,
    // nós preparamos a query e ligamos os parâmetros um a um para garantir a tipagem correta.
    $stmt = $pdo->prepare($sql);

    // Itera sobre os dados para ligar os parâmetros
    foreach ($dados_cadastro as $key => $value) {
        if ($key === 'editavel') {
            // Para o campo 'editavel', especificamos que é um booleano
            $stmt->bindValue(':' . $key, $value, PDO::PARAM_BOOL);
        } elseif (is_int($value)) {
            $stmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
        } else {
            // Para todos os outros, tratamos como string
            $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
        }
    }
    $stmt->execute();
    // --- FIM DA CORREÇÃO ---


    // Se o INSERT foi executado, pegamos o ID do novo cadastro
    if (!$cadastro_existente) {
        $cadastro_id = $pdo->lastInsertId('cadastros_id_seq');
    }

    // Lógica de Upload de Arquivos (sem alterações)
    $pasta_uploads = '/var/www/uploads/';
    $documentos_enviados = $_FILES['documentos'] ?? [];

    foreach ($documentos_enviados['name'] as $tipo_documento => $nome_original) {
        if ($documentos_enviados['error'][$tipo_documento] === UPLOAD_ERR_OK) {
            // ... (código de upload) ...
        }
    }

    // Se tudo deu certo até aqui, confirma a transação
    $pdo->commit();

    header('Location: dashboard.php?status=salvo_sucesso');
    exit();

} catch (Exception $e) {
    // Se algo deu errado, desfaz a transação
    $pdo->rollBack();
    die("<h1>ERRO AO SALVAR:</h1><pre>" . $e->getMessage() . "</pre>");
}