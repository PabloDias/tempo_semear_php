<?php
// src/admin/usuarios.php

session_start();
require_once '../db.php';

// Apenas admin pode acessar
if (!isset($_SESSION['admin_usuario_id']) || $_SESSION['admin_usuario_perfil'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

try {
    $stmt = $pdo->query("SELECT id, nome, cpf, email, perfil, ativo, criado_em FROM usuarios_internos ORDER BY nome ASC");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao buscar usuários: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários Internos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">Admin - Tempo de Semear</a>
        <div class="d-flex">
            <a href="dashboard.php" class="btn btn-secondary me-2">Voltar</a>
            <a href="logout_admin.php" class="btn btn-danger">Sair</a>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Usuários Internos</h1>
        <a href="usuario_novo.php" class="btn btn-success">Novo Usuário</a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            Operação realizada com sucesso!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Nome</th>
                    <th>CPF</th>
                    <th>Email</th>
                    <th>Perfil</th>
                    <th>Status</th>
                    <th>Criado em</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td><?= htmlspecialchars($usuario['nome']) ?></td>
                        <td><?= htmlspecialchars($usuario['cpf']) ?></td>
                        <td><?= htmlspecialchars($usuario['email']) ?></td>
                        <td>
                            <span class="badge bg-<?= $usuario['perfil'] === 'admin' ? 'danger' : 'primary' ?>">
                                <?= strtoupper($usuario['perfil']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-<?= $usuario['ativo'] ? 'success' : 'secondary' ?>">
                                <?= $usuario['ativo'] ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </td>
                        <td><?= date('d/m/Y', strtotime($usuario['criado_em'])) ?></td>
                        <td>
                            <a href="usuario_editar.php?id=<?= $usuario['id'] ?>" class="btn btn-sm btn-warning">Editar</a>
                            <?php if ($usuario['id'] != $_SESSION['admin_usuario_id']): ?>
                                <a href="usuario_desativar.php?id=<?= $usuario['id'] ?>" 
                                   class="btn btn-sm btn-<?= $usuario['ativo'] ? 'secondary' : 'success' ?>"
                                   onclick="return confirm('Confirma a alteração de status?')">
                                    <?= $usuario['ativo'] ? 'Desativar' : 'Ativar' ?>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>