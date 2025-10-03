<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Tempo de Semear</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container">
    <main>
        <div class="py-5 text-center">
            <h2>Tempo de Semear - 2ª Edição</h2>
            <p class="lead">Acesse sua conta para continuar seu cadastro ou consultar seus dados.</p>
        </div>

        <div class="row">
            <div class="col-md-6 col-lg-5 mx-auto">
                
                <?php
                // Exibe mensagem de SUCESSO após o cadastro
                if (isset($_GET['success']) && $_GET['success'] === 'cadastro_realizado') {
                    echo '<div class="alert alert-success" role="alert">Cadastro realizado com sucesso! Faça o login para continuar.</div>';
                }

                // Exibe mensagem de ERRO de credenciais
                if (isset($_GET['error']) && $_GET['error'] === 'credenciais_invalidas') {
                    echo '<div class="alert alert-danger" role="alert">CPF/E-mail ou senha inválidos.</div>';
                }
                ?>

                <form action="processa_login.php" method="POST">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="cpf_email" name="cpf_email" placeholder="CPF ou E-mail" required>
                        <label for="cpf_email">CPF ou E-mail</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="senha" name="senha" placeholder="Senha" required>
                        <label for="senha">Senha</label>
                    </div>

                    <button class="w-100 btn btn-lg btn-primary" type="submit">Entrar</button>
                    
                    <div class="text-center mt-3">
                        <a href="cadastro.php">Ainda não tem uma conta? Cadastre-se</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

</body>
</html>