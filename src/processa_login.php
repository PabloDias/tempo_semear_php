<?php
// src/processa_login.php

// session_start() deve ser a PRIMEIRA coisa no seu script para que as sessões funcionem.
session_start();

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $cpf_email = trim($_POST['cpf_email']);
    $senha = $_POST['senha'];

    if (empty($cpf_email) || empty($senha)) {
        header('Location: login.php?error=credenciais_invalidas');
        exit();
    }

    try {
        // Prepara a consulta para buscar o usuário pelo CPF ou pelo E-mail
        $stmt = $pdo->prepare("SELECT id, nome_completo, senha_hash FROM beneficiarios WHERE cpf = :identifier OR email = :identifier");
        $stmt->execute(['identifier' => $cpf_email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verifica se o usuário foi encontrado E se a senha está correta
        if ($usuario && password_verify($senha, $usuario['senha_hash'])) {
            // Se a senha estiver correta, a autenticação foi um sucesso.

            // 1. Regenera o ID da sessão para segurança
            session_regenerate_id(true);

            // 2. Guarda informações do usuário na sessão
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome_completo'];

            // 3. Redireciona para a página principal do sistema (dashboard)
            header('Location: dashboard.php');
            exit();
        } else {
            // Se o usuário não existe ou a senha está errada, redireciona de volta para o login
            header('Location: login.php?error=credenciais_invalidas');
            exit();
        }

    } catch (PDOException $e) {
        // Em caso de erro de banco, redireciona para o login com um erro genérico
        header('Location: login.php?error=db_error');
        exit();
    }
} else {
    header('Location: login.php');
    exit();
}