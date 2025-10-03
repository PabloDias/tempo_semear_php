<?php
// src/admin/processa_login_admin.php

// Inicia a sessão. É crucial que esta seja a primeira linha.
session_start();

// O arquivo db.php está um nível acima (fora da pasta /admin), então usamos '../' para voltar.
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Sanitiza o CPF, removendo caracteres não numéricos
    $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf']);
    $senha = $_POST['senha'];

    if (empty($cpf) || empty($senha)) {
        header('Location: index.php?error=credenciais_invalidas');
        exit();
    }

    try {
        // Busca o usuário interno pelo CPF
        $stmt = $pdo->prepare(
            "SELECT id, nome, senha_hash, perfil, ativo 
             FROM usuarios_internos 
             WHERE cpf = :cpf"
        );
        $stmt->execute(['cpf' => $cpf]);
        $usuario_interno = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verifica três coisas:
        // 1. Se o usuário foi encontrado.
        // 2. Se a senha está correta.
        // 3. Se o usuário está ATIVO no sistema.
        if ($usuario_interno && password_verify($senha, $usuario_interno['senha_hash']) && $usuario_interno['ativo']) {
            
            // Login bem-sucedido.
            session_regenerate_id(true);

            // Guarda informações específicas do admin na sessão.
            // Usamos chaves diferentes (ex: 'admin_usuario_id') para não confundir com a sessão do beneficiário.
            $_SESSION['admin_usuario_id'] = $usuario_interno['id'];
            $_SESSION['admin_usuario_nome'] = $usuario_interno['nome'];
            $_SESSION['admin_usuario_perfil'] = $usuario_interno['perfil']; // 'admin' ou 'consulta'

            // Redireciona para o painel administrativo
            header('Location: dashboard.php');
            exit();

        } else {
            // Se qualquer uma das verificações falhar, o login é inválido.
            header('Location: index.php?error=credenciais_invalidas');
            exit();
        }

    } catch (PDOException $e) {
        // Em caso de erro de banco, redireciona com um erro genérico
        header('Location: index.php?error=db_error');
        exit();
    }
} else {
    // Se não for um POST, redireciona para a tela de login
    header('Location: index.php');
    exit();
}