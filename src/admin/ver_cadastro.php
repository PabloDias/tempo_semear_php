<?php
// src/admin/ver_cadastro.php

session_start();
require_once '../db.php';

// 1. Proteger a página e validar a entrada
if (!isset($_SESSION['admin_usuario_id'])) {
    header('Location: index.php');
    exit();
}

// Pega o ID do cadastro da URL e garante que é um número
$cadastro_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($cadastro_id === 0) {
    header('Location: dashboard.php?error=id_invalido');
    exit();
}

try {
    // 2. Buscar todos os dados do cadastro e informações relacionadas em uma única consulta
    $sql = "SELECT 
                c.*, -- Todos os campos da tabela cadastros
                m.nome AS nome_municipio,
                e.nome AS nome_estado
            FROM 
                cadastros c
            JOIN 
                municipios_permitidos m ON c.municipio_id = m.id
            LEFT JOIN
                estados e ON c.estado_id = e.id
            WHERE 
                c.id = :cadastro_id";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['cadastro_id' => $cadastro_id]);
    $cadastro = $stmt->fetch(PDO::FETCH_ASSOC);

    // Se nenhum cadastro for encontrado com esse ID, volta para o dashboard
    if (!$cadastro) {
        header('Location: dashboard.php?error=cadastro_nao_encontrado');
        exit();
    }
    
    // 3. Buscar os arquivos associados a este cadastro
    $stmt_arquivos = $pdo->prepare("SELECT id, tipo_documento, nome_original FROM arquivos_cadastro WHERE cadastro_id = :cadastro_id");
    $stmt_arquivos->execute(['cadastro_id' => $cadastro_id]);
    $arquivos = $stmt_arquivos->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erro ao buscar os detalhes do cadastro: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Cadastro - <?= htmlspecialchars($cadastro['protocolo']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">Admin - Tempo de Semear</a>
        <div class="d-flex text-white">
            <a href="dashboard.php" class="btn btn-secondary me-3">Voltar para a Lista</a>
            <a href="../logout_admin.php" class="btn btn-danger">Sair</a>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0">Detalhes do Cadastro - Protocolo: <?= htmlspecialchars($cadastro['protocolo']); ?></h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5>Dados Pessoais</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item"><strong>Nome Completo:</strong> <?= htmlspecialchars($cadastro['nome']); ?></li>
                        <li class="list-group-item"><strong>Nome Social:</strong> <?= htmlspecialchars($cadastro['nome_social'] ?: 'Não informado'); ?></li>
                        <li class="list-group-item"><strong>CPF:</strong> <?= htmlspecialchars($cadastro['cpf']); ?></li>
                        <li class="list-group-item"><strong>Data de Nascimento:</strong> <?= date('d/m/Y', strtotime($cadastro['data_nascimento'])); ?></li>
                        <li class="list-group-item"><strong>Sexo:</strong> <?= htmlspecialchars($cadastro['sexo']); ?></li>
                        <li class="list-group-item"><strong>Nome da Mãe:</strong> <?= htmlspecialchars($cadastro['nome_mae']); ?></li>
                        <li class="list-group-item"><strong>Nome do Pai:</strong> <?= htmlspecialchars($cadastro['nome_pai'] ?: 'Não informado'); ?></li>
                    </ul>

                    <h5 class="mt-4">Documentos</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item"><strong>RG:</strong> <?= htmlspecialchars($cadastro['rg']); ?></li>
                        <li class="list-group-item"><strong>Órgão Emissor:</strong> <?= htmlspecialchars($cadastro['orgao_emissor']); ?></li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h5>Localização e Contato</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item"><strong>Município:</strong> <?= htmlspecialchars($cadastro['nome_municipio']); ?></li>
                        <li class="list-group-item"><strong>CEP:</strong> <?= htmlspecialchars($cadastro['cep'] ?: 'Não informado'); ?></li>
                        <li class="list-group-item"><strong>Logradouro:</strong> <?= htmlspecialchars($cadastro['logradouro'] ?: 'Não informado'); ?></li>
                        <li class="list-group-item"><strong>Número:</strong> <?= htmlspecialchars($cadastro['numero'] ?: 'Não informado'); ?></li>
                        <li class="list-group-item"><strong>Bairro:</strong> <?= htmlspecialchars($cadastro['bairro'] ?: 'Não informado'); ?></li>
                        <li class="list-group-item"><strong>Telefone 1:</strong> <?= htmlspecialchars($cadastro['telefone1']); ?></li>
                        <li class="list-group-item"><strong>Telefone 2:</strong> <?= htmlspecialchars($cadastro['telefone2'] ?: 'Não informado'); ?></li>
                        <li class="list-group-item"><strong>Email:</strong> <?= htmlspecialchars($cadastro['email']); ?></li>
                    </ul>

                    <h5 class="mt-4">Arquivos Enviados</h5>
                    <div class="list-group">
                        <?php if (count($arquivos) > 0): ?>
                            <?php foreach ($arquivos as $arquivo): ?>
                                <a href="ver_arquivo_admin.php?id=<?= $arquivo['id']; ?>" target="_blank" class="list-group-item list-group-item-action">
                                    <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $arquivo['tipo_documento']))); ?>: 
                                    <small><?= htmlspecialchars($arquivo['nome_original']); ?></small>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="list-group-item">Nenhum arquivo foi enviado.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
         <div class="card-footer d-flex justify-content-between align-items-center">
            <span class="text-muted">
                Status do Cadastro: <strong><?= htmlspecialchars($cadastro['status']); ?></strong>
                | Data de Envio: <?= $cadastro['data_envio'] ? date('d/m/Y H:i', strtotime($cadastro['data_envio'])) : 'Ainda não enviado'; ?>
            </span>
            
            <?php 
            // Mostra o botão de editar apenas se o cadastro for editável E o usuário for 'admin'
            if ($_SESSION['admin_usuario_perfil'] === 'admin'): ?>
                <a href="editar_cadastro.php?id=<?= $cadastro['id']; ?>" class="btn btn-warning">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16"><path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/><path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5h6a.5.5 0 0 0 0-1h-6A1.5 1.5 0 0 0 1 2.5v11z"/></svg>
                    Editar Cadastro
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>