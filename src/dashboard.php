<?php
// src/dashboard.php (VERSÃO FINAL COM LISTA DE ARQUIVOS E BOTÕES CORRIGIDOS)

session_start();
require_once 'db.php';

// Protege a página
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

$beneficiario_id = $_SESSION['usuario_id'];

// Busca a lista de municípios ativos para o dropdown
$municipios_permitidos = [];
try {
    $stmtMunicipios = $pdo->prepare("SELECT id, nome FROM municipios_permitidos WHERE ativo = TRUE ORDER BY nome ASC");
    $stmtMunicipios->execute();
    $municipios_permitidos = $stmtMunicipios->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao carregar a lista de municípios: " . $e->getMessage());
}

// Busca os dados do cadastro e os arquivos já enviados
$cadastro = null;
$arquivos_enviados = [];
try {
    $stmtCadastro = $pdo->prepare("SELECT * FROM cadastros WHERE beneficiario_id = :beneficiario_id");
    $stmtCadastro->execute(['beneficiario_id' => $beneficiario_id]);
    $cadastro = $stmtCadastro->fetch(PDO::FETCH_ASSOC);

    if ($cadastro) {
        $stmtArquivos = $pdo->prepare("SELECT tipo_documento, nome_original, caminho_arquivo FROM arquivos_cadastro WHERE cadastro_id = :cadastro_id");
        $stmtArquivos->execute(['cadastro_id' => $cadastro['id']]);
        
        foreach ($stmtArquivos->fetchAll(PDO::FETCH_ASSOC) as $arquivo) {
            $arquivos_enviados[$arquivo['tipo_documento']] = $arquivo;
        }
    }
} catch (PDOException $e) {
    die("Erro ao carregar dados do cadastro: " . $e->getMessage());
}

$form_editavel = true;
if ($cadastro && !$cadastro['editavel']) {
    $form_editavel = false;
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Beneficiário - Tempo de Semear</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="#">Tempo de Semear</a>
        <div class="d-flex">
            <span class="navbar-text me-3">Olá, <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></span>
            <a href="logout.php" class="btn btn-danger">Sair</a>
        </div>
    </div>
</nav>

<div class="container py-5">
    <div class="row">
        <div class="col-md-10 mx-auto">
            <h2>Formulário de Inscrição</h2>
            <p>Preencha seus dados abaixo. Você pode salvar como rascunho e continuar depois.</p>
            
            <?php if (!$form_editavel): ?>
                <div class="alert alert-warning" role="alert">
                    Seu cadastro já foi enviado e não pode mais ser editado. As informações abaixo são apenas para consulta.
                </div>
            <?php endif; ?>

            <form action="processa_formulario_principal.php" method="POST" enctype="multipart/form-data">
                
                <h4 class="mb-3 mt-4">1. Dados Pessoais</h4>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label for="nome" class="form-label">Nome Completo</label>
                        <input type="text" class="form-control" id="nome" name="nome" value="<?= htmlspecialchars($cadastro['nome'] ?? '') ?>" <?= $form_editavel ? 'required' : 'readonly' ?>>
                    </div>
                    <div class="col-sm-6">
                        <label for="nome_social" class="form-label">Nome Social (Opcional)</label>
                        <input type="text" class="form-control" id="nome_social" name="nome_social" value="<?= htmlspecialchars($cadastro['nome_social'] ?? '') ?>" <?= $form_editavel ? '' : 'readonly' ?>>
                    </div>
                    <div class="col-sm-4">
                        <label for="data_nascimento" class="form-label">Data de Nascimento</label>
                        <input type="date" class="form-control" id="data_nascimento" name="data_nascimento" value="<?= htmlspecialchars($cadastro['data_nascimento'] ?? '') ?>" <?= $form_editavel ? 'required' : 'readonly' ?>>
                    </div>
                    <div class="col-sm-4">
                        <label for="sexo" class="form-label">Sexo</label>
                        <select class="form-select" id="sexo" name="sexo" <?= $form_editavel ? 'required' : 'disabled' ?>>
                            <option value="">Selecione...</option>
                            <option value="M" <?= ($cadastro['sexo'] ?? '') == 'M' ? 'selected' : '' ?>>Masculino</option>
                            <option value="F" <?= ($cadastro['sexo'] ?? '') == 'F' ? 'selected' : '' ?>>Feminino</option>
                            <option value="Outro" <?= ($cadastro['sexo'] ?? '') == 'Outro' ? 'selected' : '' ?>>Outro</option>
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label for="nome_mae" class="form-label">Nome da Mãe</label>
                        <input type="text" class="form-control" id="nome_mae" name="nome_mae" value="<?= htmlspecialchars($cadastro['nome_mae'] ?? '') ?>" <?= $form_editavel ? 'required' : 'readonly' ?>>
                    </div>
                     <div class="col-sm-6">
                        <label for="nome_pai" class="form-label">Nome do Pai (Opcional)</label>
                        <input type="text" class="form-control" id="nome_pai" name="nome_pai" value="<?= htmlspecialchars($cadastro['nome_pai'] ?? '') ?>" <?= $form_editavel ? '' : 'readonly' ?>>
                    </div>
                    <div class="col-sm-6">
                        <label for="municipio_id" class="form-label">Município de Residência</label>
                        <select class="form-select" id="municipio_id" name="municipio_id" <?= $form_editavel ? 'required' : 'disabled' ?>>
                            <option value="">Selecione seu município...</option>
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
                        <input type="text" class="form-control" id="rg" name="rg" value="<?= htmlspecialchars($cadastro['rg'] ?? '') ?>" <?= $form_editavel ? 'required' : 'readonly' ?>>
                    </div>
                    <div class="col-sm-4">
                        <label for="orgao_emissor" class="form-label">Órgão Emissor</label>
                        <input type="text" class="form-control" id="orgao_emissor" name="orgao_emissor" value="<?= htmlspecialchars($cadastro['orgao_emissor'] ?? '') ?>" <?= $form_editavel ? 'required' : 'readonly' ?>>
                    </div>
                </div>

                <h4 class="mb-3 mt-4">3. Contato</h4>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label for="telefone1" class="form-label">Telefone Principal</label>
                        <input type="tel" class="form-control" id="telefone1" name="telefone1" value="<?= htmlspecialchars($cadastro['telefone1'] ?? '') ?>" <?= $form_editavel ? 'required' : 'readonly' ?>>
                    </div>
                     <div class="col-sm-6">
                        <label for="telefone2" class="form-label">Telefone Secundário (Opcional)</label>
                        <input type="tel" class="form-control" id="telefone2" name="telefone2" value="<?= htmlspecialchars($cadastro['telefone2'] ?? '') ?>" <?= $form_editavel ? '' : 'readonly' ?>>
                    </div>
                </div>

                <h4 class="mb-3 mt-4">4. Endereço</h4>
                 <div class="row g-3">
                    <div class="col-sm-4">
                        <label for="cep" class="form-label">CEP</label>
                        <input type="text" class="form-control" id="cep" name="cep" value="<?= htmlspecialchars($cadastro['cep'] ?? '') ?>" <?= $form_editavel ? '' : 'readonly' ?>>
                    </div>
                    <div class="col-sm-8">
                        <label for="logradouro" class="form-label">Logradouro (Rua, Avenida)</label>
                        <input type="text" class="form-control" id="logradouro" name="logradouro" value="<?= htmlspecialchars($cadastro['logradouro'] ?? '') ?>" <?= $form_editavel ? '' : 'readonly' ?>>
                    </div>
                    <div class="col-sm-4">
                        <label for="numero" class="form-label">Número</label>
                        <input type="text" class="form-control" id="numero" name="numero" value="<?= htmlspecialchars($cadastro['numero'] ?? '') ?>" <?= $form_editavel ? '' : 'readonly' ?>>
                    </div>
                    <div class="col-sm-8">
                        <label for="bairro" class="form-label">Bairro</label>
                        <input type="text" class="form-control" id="bairro" name="bairro" value="<?= htmlspecialchars($cadastro['bairro'] ?? '') ?>" <?= $form_editavel ? '' : 'readonly' ?>>
                    </div>
                </div>

                <h4 class="mb-3 mt-4">5. Envio de Documentos</h4>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="doc_cpf" class="form-label">Cópia do CPF</label>
                        <input class="form-control" type="file" id="doc_cpf" name="documentos[cpf]" <?= $form_editavel ? '' : 'disabled' ?>>
                        <?php if (isset($arquivos_enviados['cpf'])): ?>
                            <div class="mt-2"><small class="text-success">Arquivo enviado:</small> <a href="ver_arquivo.php?tipo=cpf" target="_blank"><?= htmlspecialchars($arquivos_enviados['cpf']['nome_original']) ?></a></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label for="doc_comprovante_residencia" class="form-label">Comprovante de Residência</label>
                        <input class="form-control" type="file" id="doc_comprovante_residencia" name="documentos[comprovante_residencia]" <?= $form_editavel ? '' : 'disabled' ?>>
                        <?php if (isset($arquivos_enviados['comprovante_residencia'])): ?>
                            <div class="mt-2"><small class="text-success">Arquivo enviado:</small> <a href="ver_arquivo.php?tipo=comprovante_residencia" target="_blank"><?= htmlspecialchars($arquivos_enviados['comprovante_residencia']['nome_original']) ?></a></div>
                        <?php endif; ?>
                    </div>
                </div>

                <hr class="my-4">

                <?php if ($form_editavel): ?>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button class="btn btn-secondary btn-lg" type="submit" name="acao" value="rascunho">Salvar Rascunho</button>
                        <button class="btn btn-primary btn-lg" type="submit" name="acao" value="enviar">Enviar Cadastro Definitivamente</button>
                    </div>
                <?php endif; ?>

            </form> </div>
    </div>
</div>

</body>
</html>