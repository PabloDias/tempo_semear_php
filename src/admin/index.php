<?php
// src/admin/index.php (Tela de Login do Admin)
session_start();

// Se o admin já estiver logado, redireciona para o painel principal
if (isset($_SESSION['admin_usuario_id'])) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Administrativo - Tempo de Semear</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-white">

<div class="container">
    <main>
        <div class="py-5 text-center">
            <h2>Área Administrativa</h2>
            <p class="lead">Acesso restrito para usuários internos.</p>
        </div>

        <div class="row">
            <div class="col-md-6 col-lg-4 mx-auto">
                
                <?php
                if (isset($_GET['error'])) {
                    echo '<div class="alert alert-danger" role="alert">CPF ou senha inválidos.</div>';
                }
                ?>

                <form action="processa_login_admin.php" method="POST" class="bg-light p-4 rounded text-dark">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="cpf" name="cpf" placeholder="CPF" required>
                        <label for="cpf">CPF</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="senha" name="senha" placeholder="Senha" required>
                        <label for="senha">Senha</label>
                    </div>
                    <button class="w-100 btn btn-lg btn-primary" type="submit">Entrar</button>
                </form>
            </div>
        </div>
    </main>
</div>

</body>
</html>