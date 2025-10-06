<?php
// src/admin/usuario_editar.php

session_start();
require_once '../db.php';

if (!isset($_SESSION['admin_usuario_id']) || $_SESSION['admin_usuario_perfil'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

$usuario_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($usuario_id === 0) {
    header('Location: usuarios.php');
    exit();
}

// Buscar usuário
try {
    $stmt = $pdo->prepare("SELECT * FROM usuarios_internos WHERE id = :id");
    $stmt->execute(['id' => $usuario_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        header('Location: usuarios.php');
        exit();
    }
} catch (PDOException $e) {
    die("Erro ao buscar usuário: " . $e->getMessage());
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $perfil = $_POST['perfil'];
    $ativo = isset($_POST['ativo']) ? true : false;
    $nova_senha = trim($_POST['senha'] ?? '');
    
    try {
        $pdo->beginTransaction();
        
        // Verifica se email já está em uso por outro usuário
        $stmt = $pdo->prepare("SELECT id FROM usuarios_internos WHERE email = :email AND id != :id");
        $stmt->execute(['email' => $email, 'id' => $usuario_id]);
        
        if ($stmt->fetch()) {
            $erro = "Este email já está em uso por outro usuário.";
        } else {
            // Atualizar dados básicos
            $sql = "UPDATE usuarios_internos SET 
                        nome = :nome,
                        email = :email,
                        perfil = :perfil,
                        ativo = :ativo,
                        atualizado_em = CURRENT_TIMESTAMP
                    WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'nome' => $nome,
                'email' => $email,
                'perfil' => $perfil,
                'ativo' => $ativo,
                'id' => $usuario_id
            ]);
            
            // Atualizar senha se fornecida
            if (!empty($nova_senha)) {
                if (strlen($nova_senha) < 8) {
                    throw new Exception("A senha deve ter no mínimo 8 caracteres.");
                }
                
                $senha_hash = password_hash($nova_senha, PASSWORD_ARGON2ID);
                
                $stmt = $pdo->prepare("UPDATE usuarios_internos SET senha_hash = :senha_hash WHERE id = :id");
                $stmt->execute([
                    'senha_hash' => $senha_hash,
                    'id' => $usuario_id
                ]);
            }
            
            // Registrar no log
            $pdo->prepare(
                "INSERT INTO logs_sistema (nivel, modulo, acao, descricao, usuario_tipo, usuario_id)
                 VALUES ('INFO', 'usuarios_internos', 'Edição de usuário', :descricao, 'interno', :admin_id)"
            )->execute([
                'descricao' => "Admin {$_SESSION['admin_usuario_nome']} editou o usuário {$nome} (ID: {$usuario_id})",
                'admin_id' => $_SESSION['admin_usuario_id']
            ]);
            
            $pdo->commit();
            
            header('Location: usuarios.php?success=1');
            exit();
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $erro = "Erro ao atualizar usuário: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuário - <?= htmlspecialchars($usuario['nome']) ?></title>
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
            <h2>Editar Usuário Interno</h2>
            
            <?php if (isset($erro)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>
            
            <?php if ($usuario_id == $_SESSION['admin_usuario_id']): ?>
                <div class="alert alert-info">
                    Você está editando seu próprio usuário.
                </div>
            <?php endif; ?>
            
            <form method="POST" class="mt-4">
                <div class="mb-3">
                    <label for="nome" class="form-label">Nome Completo</label>
                    <input type="text" class="form-control" id="nome" name="nome" 
                           value="<?= htmlspecialchars($usuario['nome']) ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="cpf" class="form-label">CPF</label>
                    <input type="text" class="form-control" id="cpf" 
                           value="<?= htmlspecialchars($usuario['cpf']) ?>" disabled>
                    <div class="form-text">O CPF não pode ser alterado</div>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?= htmlspecialchars($usuario['email']) ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="senha" class="form-label">Nova Senha (deixe em branco para não alterar)</label>
                    <input type="password" class="form-control" id="senha" name="senha" 
                           minlength="8" placeholder="Digite apenas se quiser trocar a senha">
                    <div class="form-text">Mínimo 8 caracteres</div>
                </div>
                
                <div class="mb-3">
                    <label for="perfil" class="form-label">Perfil</label>
                    <select class="form-select" id="perfil" name="perfil" required
                            <?= $usuario_id == $_SESSION['admin_usuario_id'] ? 'disabled' : '' ?>>
                        <option value="admin" <?= $usuario['perfil'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
                        <option value="supervisor" <?= $usuario['perfil'] === 'supervisor' ? 'selected' : '' ?>>Supervisor</option>
                    </select>
                    <?php if ($usuario_id == $_SESSION['admin_usuario_id']): ?>
                        <input type="hidden" name="perfil" value="<?= $usuario['perfil'] ?>">
                        <div class="form-text">Você não pode alterar seu próprio perfil</div>
                    <?php endif; ?>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="ativo" name="ativo" 
                           <?= $usuario['ativo'] ? 'checked' : '' ?>
                           <?= $usuario_id == $_SESSION['admin_usuario_id'] ? 'disabled' : '' ?>>
                    <label class="form-check-label" for="ativo">
                        Usuário ativo
                    </label>
                    <?php if ($usuario_id == $_SESSION['admin_usuario_id']): ?>
                        <input type="hidden" name="ativo" value="1">
                        <div class="form-text">Você não pode se desativar</div>
                    <?php endif; ?>
                </div>
                
                <hr class="my-4">
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <small class="text-muted">
                            <strong>Criado em:</strong> 
                            <?= date('d/m/Y H:i', strtotime($usuario['criado_em'])) ?>
                        </small>
                    </div>
                    <div class="col-md-6 text-end">
                        <small class="text-muted">
                            <strong>Último acesso:</strong> 
                            <?= $usuario['ultimo_acesso'] ? date('d/m/Y H:i', strtotime($usuario['ultimo_acesso'])) : 'Nunca' ?>
                        </small>
                    </div>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="usuarios.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>