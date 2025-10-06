<?php
// src/admin/usuario_novo.php

session_start();
require_once '../db.php';

if (!isset($_SESSION['admin_usuario_id']) || $_SESSION['admin_usuario_perfil'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $perfil = $_POST['perfil'];
    
    try {
        // Verifica se já existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios_internos WHERE cpf = :cpf OR email = :email");
        $stmt->execute(['cpf' => $cpf, 'email' => $email]);
        
        if ($stmt->fetch()) {
            $erro = "CPF ou email já cadastrado.";
        } else {
            $senha_hash = password_hash($senha, PASSWORD_ARGON2ID);
            
            $stmt = $pdo->prepare(
                "INSERT INTO usuarios_internos (nome, cpf, email, senha_hash, perfil, ativo, criado_por)
                 VALUES (:nome, :cpf, :email, :senha_hash, :perfil, TRUE, :criado_por)"
            );
            
            $stmt->execute([
                'nome' => $nome,
                'cpf' => $cpf,
                'email' => $email,
                'senha_hash' => $senha_hash,
                'perfil' => $perfil,
                'criado_por' => $_SESSION['admin_usuario_id']
            ]);
            
            header('Location: usuarios.php?success=1');
            exit();
        }
    } catch (Exception $e) {
        $erro = "Erro ao criar usuário: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Usuário Interno</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">Admin - Tempo de Semear</a>
        <div class="d-flex">
            <a href="usuarios.php" class="btn btn-secondary me-2">Voltar</a>
            <a href="logout_admin.php" class="btn btn-danger">Sair</a>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <h2>Criar Novo Usuário Interno</h2>
            
            <?php if (isset($erro)): ?>
                <div class="alert alert-danger"><?= $erro ?></div>
            <?php endif; ?>
            
            <form method="POST" class="mt-4">
                <div class="mb-3">
                    <label for="nome" class="form-label">Nome Completo</label>
                    <input type="text" class="form-control" id="nome" name="nome" required>
                </div>
                
                <div class="mb-3">
                    <label for="cpf" class="form-label">CPF</label>
                    <input type="text" class="form-control" id="cpf" name="cpf" 
                           placeholder="Apenas números" maxlength="11" required>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                
                <div class="mb-3">
                    <label for="senha" class="form-label">Senha</label>
                    <input type="password" class="form-control" id="senha" name="senha" 
                           minlength="8" required>
                    <div class="form-text">Mínimo 8 caracteres</div>
                </div>
                
                <div class="mb-3">
                    <label for="perfil" class="form-label">Perfil</label>
                    <select class="form-select" id="perfil" name="perfil" required>
                        <option value="">Selecione...</option>
                        <option value="admin">Administrador</option>
                        <option value="supervisor">Supervisor</option>
                    </select>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="usuarios.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Criar Usuário</button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>