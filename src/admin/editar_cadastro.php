<?php
// src/admin/editar_cadastro.php

session_start();
require_once '../db.php';

// 1. Protege a página: Apenas usuários 'admin' podem editar
if (!isset($_SESSION['admin_usuario_id']) || $_SESSION['admin_usuario_perfil'] !== 'admin') {
    header('Location: index.php?error=acesso_negado');
    exit();
}

// Valida o ID do cadastro na URL
$cadastro_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($cadastro_id === 0) {
    header('Location: dashboard.php?error=id_invalido');
    exit();
}

try {
    // Busca os dados do cadastro a ser editado
    $stmt = $pdo->prepare("SELECT * FROM cadastros WHERE id = :id");
    $stmt->execute(['id' => $cadastro_id]);
    $cadastro = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cadastro) {
        header('Location: dashboard.php?error=cadastro_nao_encontrado');
        exit();
    }
    
     // Busca a lista de municípios ativos para o dropdown
    $stmtMunicipios = $pdo->query("SELECT id, nome FROM municipios_permitidos WHERE ativo = TRUE ORDER BY nome ASC");
    $municipios_permitidos = $stmtMunicipios->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erro ao carregar dados para edição: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Cadastro - <?= htmlspecialchars($cadastro['protocolo']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">Admin - Editar Cadastro</a>
        <a href="ver_cadastro.php?id=<?= $cadastro_id; ?>" class="btn btn-secondary">Cancelar e Voltar</a>
    </div>
</nav>

<div class="container py-5">
    <div class="row">
        <div class="col-md-10 mx-auto">
            <h2>Editando Cadastro de: <?= htmlspecialchars($cadastro['nome']); ?></h2>
            <p>Protocolo: <strong><?= htmlspecialchars($cadastro['protocolo']); ?></strong></p>
            
            <form action="processa_edicao_admin.php" method="POST">
                <input type="hidden" name="cadastro_id" value="<?= $cadastro['id']; ?>">

                <h4 class="mb-3 mt-4">1. Dados Pessoais</h4>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label for="nome" class="form-label">Nome Completo</label>
                        <input type="text" class="form-control" id="nome" name="nome" value="<?= htmlspecialchars($cadastro['nome'] ?? '') ?>" required>
                    </div>
                    <div class="col-sm-6">
                        <label for="nome_social" class="form-label">Nome Social (Opcional)</label>
                        <input type="text" class="form-control" id="nome_social" name="nome_social" value="<?= htmlspecialchars($cadastro['nome_social'] ?? '') ?>">
                    </div>
                    <div class="col-sm-6">
                        <label for="municipio_id" class="form-label">Município de Residência</label>
                        <select class="form-select" id="municipio_id" name="municipio_id" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($municipios_permitidos as $municipio): ?>
                                <option value="<?= $municipio['id'] ?>" <?= ($cadastro['municipio_id'] ?? '') == $municipio['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($municipio['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                     </div>

                <h4 class="mb-3 mt-4">2. Documentos</h4>
                <div class="row g-3">
                    <div class="col-sm-4">
                        <label for="rg" class="form-label">RG</label>
                        <input type="text" class="form-control" id="rg" name="rg" value="<?= htmlspecialchars($cadastro['rg'] ?? '') ?>" required>
                    </div>
                    <div class="col-sm-4">
                        <label for="orgao_emissor" class="form-label">Órgão Emissor</label>
                        <input type="text" class="form-control" id="orgao_emissor" name="orgao_emissor" value="<?= htmlspecialchars($cadastro['orgao_emissor'] ?? '') ?>" required>
                    </div>
                </div>

                <hr class="my-4">
                
                <div class="mb-3">
                    <label for="justificativa" class="form-label">Justificativa da Alteração *</label>
                    <textarea class="form-control" id="justificativa" name="justificativa" rows="3" required placeholder="Ex: Correção do nome da mãe a pedido do beneficiário."></textarea>
                    <div class="form-text">Qualquer alteração feita por um administrador deve ser justificada.</div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="ver_cadastro.php?id=<?= $cadastro_id; ?>" class="btn btn-secondary btn-lg">Cancelar</a>
                    <button class="btn btn-primary btn-lg" type="submit">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>