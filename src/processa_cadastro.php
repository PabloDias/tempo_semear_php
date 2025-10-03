<?php
// src/processa_cadastro.php (Versão Final)

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $nome_completo = trim($_POST['nome_completo']);
    $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf']);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha'];

    if (empty($nome_completo) || empty($cpf) || empty($email) || empty($senha) || strlen($cpf) != 11 || strlen($senha) < 8) {
        header('Location: cadastro.php?error=dados_invalidos');
        exit();
    }

    try {
        $stmt = $pdo->prepare("SELECT id FROM beneficiarios WHERE cpf = :cpf OR email = :email");
        $stmt->execute(['cpf' => $cpf, 'email' => $email]);
        if ($stmt->fetch()) {
            header('Location: cadastro.php?error=usuario_existente');
            exit();
        }

        $senha_hash = password_hash($senha, PASSWORD_ARGON2ID);

        $stmt = $pdo->prepare(
            "INSERT INTO beneficiarios (nome_completo, cpf, email, senha_hash) VALUES (:nome, :cpf, :email, :senha)"
        );
        $stmt->execute([
            'nome' => $nome_completo,
            'cpf' => $cpf,
            'email' => $email,
            'senha' => $senha_hash
        ]);

        // Redireciona para a página de login com mensagem de sucesso
        header('Location: login.php?success=cadastro_realizado');
        exit();

    } catch (PDOException $e) {
        // Em caso de erro, redireciona com uma mensagem genérica
        header('Location: cadastro.php?error=db_error');
        exit();
    }

} else {
    header('Location: cadastro.php');
    exit();
}