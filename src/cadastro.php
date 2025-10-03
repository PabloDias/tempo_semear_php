<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Tempo de Semear</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container">
    <main>
        <div class="py-5 text-center">
            <h2>Tempo de Semear - 2ª Edição</h2>
            <p class="lead">Crie sua conta para iniciar seu cadastro. Todos os campos com * são obrigatórios.</p>
        </div>

        <div class="row g-5">
            <div class="col-md-7 col-lg-8 mx-auto">
               
                <?php
                // Pega o parâmetro 'error' da URL, se existir
                if (isset($_GET['error'])) {
                    $mensagem = '';
                    switch ($_GET['error']) {
                        case 'dados_invalidos':
                            $mensagem = 'Por favor, preencha todos os campos corretamente.';
                            break;
                        case 'usuario_existente':
                            $mensagem = 'O CPF ou e-mail informado já está cadastrado.';
                            break;
                        case 'db_error':
                            $mensagem = 'Ocorreu um erro ao processar seu cadastro. Tente novamente.';
                            break;
                    }
                    // Exibe a mensagem de erro em um alerta
                    if ($mensagem) {
                        echo '<div class="alert alert-danger" role="alert">' . htmlspecialchars($mensagem) . '</div>';
                    }
                }
                ?>
                
                <form action="processa_cadastro.php" method="POST" class="needs-validation" novalidate>
                <h4 class="mb-3">Informações de Acesso</h4>
                
                <form action="processa_cadastro.php" method="POST" class="needs-validation" novalidate>
                    <div class="row g-3">
                        
                        <div class="col-12">
                            <label for="nome_completo" class="form-label">Nome Completo *</label>
                            <input type="text" class="form-control" id="nome_completo" name="nome_completo" required>
                            <div class="invalid-feedback">
                                Por favor, insira seu nome completo.
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="cpf" class="form-label">CPF *</label>
                            <input type="text" class="form-control" id="cpf" name="cpf" placeholder="Apenas números" required pattern="\d{11}">
                            <div class="invalid-feedback">
                                Por favor, insira um CPF válido com 11 dígitos.
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="voce@exemplo.com" required>
                            <div class="invalid-feedback">
                                Por favor, insira um endereço de e-mail válido.
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="senha" class="form-label">Senha *</label>
                            <input type="password" class="form-control" id="senha" name="senha" required minlength="8">
                            <div class="invalid-feedback">
                                A senha precisa ter no mínimo 8 caracteres.
                            </div>
                        </div>

                    </div>

                    <hr class="my-4">

                    <button class="w-100 btn btn-primary btn-lg" type="submit">Criar Conta</button>
                </form>
            </div>
        </div>
    </main>
</div>

<script>
    // Script para ativar a validação de formulário do Bootstrap
    (() => {
        'use strict'
        const forms = document.querySelectorAll('.needs-validation')
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
    })()
</script>

</body>
</html>