<?php
// src/dashboard.php (VERSÃO COMPLETA COM TODOS OS CAMPOS DO BANCO)

session_start();
require_once 'db.php';

// Protege a página
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

$beneficiario_id = $_SESSION['usuario_id'];

// Busca listas para os dropdowns
$municipios_permitidos = [];
$estados = [];
try {
    // Municípios ativos
    $stmtMunicipios = $pdo->prepare("SELECT id, nome FROM municipios_permitidos WHERE ativo = TRUE ORDER BY nome ASC");
    $stmtMunicipios->execute();
    $municipios_permitidos = $stmtMunicipios->fetchAll(PDO::FETCH_ASSOC);
    
    // Estados
    $stmtEstados = $pdo->prepare("SELECT id, sigla, nome FROM estados ORDER BY nome ASC");
    $stmtEstados->execute();
    $estados = $stmtEstados->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao carregar dados: " . $e->getMessage());
}

// Busca os dados do cadastro e os arquivos já enviados
$cadastro = null;
$arquivos_enviados = [];
try {
    $stmtCadastro = $pdo->prepare("SELECT * FROM cadastros WHERE beneficiario_id = :beneficiario_id");
    $stmtCadastro->execute(['beneficiario_id' => $beneficiario_id]);
    $cadastro = $stmtCadastro->fetch(PDO::FETCH_ASSOC);

    if ($cadastro) {
        $stmtArquivos = $pdo->prepare("SELECT tipo_documento, nome_original FROM arquivos_cadastro WHERE cadastro_id = :cadastro_id");
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
    <style>
        .section-title {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            margin-top: 30px;
            margin-bottom: 20px;
        }
        .form-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="#">Tempo de Semear - 2ª Edição</a>
        <div class="d-flex">
            <span class="navbar-text me-3 text-white">Olá, <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></span>
            <a href="logout.php" class="btn btn-danger">Sair</a>
        </div>
    </div>
</nav>

<div class="container py-5">
    <div class="row">
        <div class="col-md-11 mx-auto">
            
            <?php if (isset($_GET['status']) && $_GET['status'] === 'salvo_sucesso'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>Sucesso!</strong> Seus dados foram salvos com sucesso.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($cadastro && $cadastro['protocolo']): ?>
                <div class="alert alert-info">
                    <strong>Protocolo:</strong> <?= htmlspecialchars($cadastro['protocolo']) ?>
                    | <strong>Status:</strong> <?= htmlspecialchars($cadastro['status']) ?>
                </div>
            <?php endif; ?>
            
            <h2>Formulário de Inscrição - Tempo de Semear</h2>
            <p class="lead">Preencha seus dados abaixo. Você pode salvar como rascunho e continuar depois.</p>
            
            <?php if (!$form_editavel): ?>
                <div class="alert alert-warning" role="alert">
                    <strong>Atenção!</strong> Seu cadastro já foi enviado e não pode mais ser editado. As informações abaixo são apenas para consulta.
                </div>
            <?php endif; ?>

            <form action="processa_formulario_principal.php" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                
                <!-- SEÇÃO 1: DADOS BÁSICOS -->
                <div class="section-title">
                    <h4 class="mb-0">1. Dados Básicos</h4>
                </div>
                <div class="form-section">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="caf" class="form-label">CAF - Código de Acesso Familiar</label>
                            <input type="text" class="form-control" id="caf" name="caf" 
                                   value="<?= htmlspecialchars($cadastro['caf'] ?? '') ?>" 
                                   placeholder="Ex: 12345678901234"
                                   <?= $form_editavel ? '' : 'readonly' ?>>
                            <div class="form-text">Informe seu número do CAF (CadÚnico)</div>
                        </div>
                        <div class="col-md-6">
                            <label for="situacao" class="form-label">Parentesco no CAF</label>
                            <select class="form-select" id="situacao" name="situacao" <?= $form_editavel ? '' : 'disabled' ?>>
                                <option value="">Selecione...</option>
                                <option value="Pessoa responsável pela UFPA (declarante)" <?= ($cadastro['situacao'] ?? '') == 'Pessoa responsável pela UFPA (declarante)' ? 'selected' : '' ?>>Pessoa responsável pela UFPA (declarante)</option>
                                <option value="Filho(a)" <?= ($cadastro['situacao'] ?? '') == 'Filho(a)' ? 'selected' : '' ?>>Filho(a)</option>
                                <option value="Cônjuge ou companheiro(a)" <?= ($cadastro['situacao'] ?? '') == 'Cônjuge ou companheiro(a)' ? 'selected' : '' ?>>Cônjuge ou companheiro(a)</option>
                                <option value="Parentesco" <?= ($cadastro['situacao'] ?? '') == 'Parentesco' ? 'selected' : '' ?>>Parentesco</option>
                                <option value="Enteado(a)" <?= ($cadastro['situacao'] ?? '') == 'Enteado(a)' ? 'selected' : '' ?>>Enteado(a)</option>
                                <option value="Não parente" <?= ($cadastro['situacao'] ?? '') == 'Não parente' ? 'selected' : '' ?>>Não parente</option>
                                <option value="Neto(a) ou bisneto(a)" <?= ($cadastro['situacao'] ?? '') == 'Neto(a) ou bisneto(a)' ? 'selected' : '' ?>>Neto(a) ou bisneto(a)</option>
                                <option value="Outro parente" <?= ($cadastro['situacao'] ?? '') == 'Outro parente' ? 'selected' : '' ?>>Outro parente</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- SEÇÃO 2: IDENTIFICAÇÃO PESSOAL -->
                <div class="section-title">
                    <h4 class="mb-0">2. Identificação Pessoal</h4>
                </div>
                <div class="form-section">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="nome" class="form-label">Nome Completo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nome" name="nome" 
                                   value="<?= htmlspecialchars($cadastro['nome'] ?? '') ?>" 
                                   <?= $form_editavel ? 'required' : 'readonly' ?>>
                        </div>
                        <div class="col-md-6">
                            <label for="nome_social" class="form-label">Nome Social (Opcional)</label>
                            <input type="text" class="form-control" id="nome_social" name="nome_social" 
                                   value="<?= htmlspecialchars($cadastro['nome_social'] ?? '') ?>" 
                                   <?= $form_editavel ? '' : 'readonly' ?>>
                        </div>
                        <div class="col-md-4">
                            <label for="data_nascimento" class="form-label">Data de Nascimento <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="data_nascimento" name="data_nascimento" 
                                   value="<?= htmlspecialchars($cadastro['data_nascimento'] ?? '') ?>" 
                                   <?= $form_editavel ? 'required' : 'readonly' ?>>
                        </div>
                        <div class="col-md-4">
                            <label for="sexo" class="form-label">Sexo <span class="text-danger">*</span></label>
                            <select class="form-select" id="sexo" name="sexo" <?= $form_editavel ? 'required' : 'disabled' ?>>
                                <option value="">Selecione...</option>
                                <option value="M" <?= ($cadastro['sexo'] ?? '') == 'M' ? 'selected' : '' ?>>Masculino</option>
                                <option value="F" <?= ($cadastro['sexo'] ?? '') == 'F' ? 'selected' : '' ?>>Feminino</option>
                                <option value="Outro" <?= ($cadastro['sexo'] ?? '') == 'Outro' ? 'selected' : '' ?>>Outro</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="nacionalidade" class="form-label">Nacionalidade</label>
                            <input type="text" class="form-control" id="nacionalidade" name="nacionalidade" 
                                   value="<?= htmlspecialchars($cadastro['nacionalidade'] ?? 'Brasileira') ?>" 
                                   <?= $form_editavel ? '' : 'readonly' ?>>
                        </div>
                    </div>
                </div>

                <!-- SEÇÃO 3: FILIAÇÃO -->
                <div class="section-title">
                    <h4 class="mb-0">3. Filiação</h4>
                </div>
                <div class="form-section">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="nome_mae" class="form-label">Nome da Mãe <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nome_mae" name="nome_mae" 
                                   value="<?= htmlspecialchars($cadastro['nome_mae'] ?? '') ?>" 
                                   <?= $form_editavel ? 'required' : 'readonly' ?>>
                        </div>
                        <div class="col-md-6">
                            <label for="nome_pai" class="form-label">Nome do Pai (Opcional)</label>
                            <input type="text" class="form-control" id="nome_pai" name="nome_pai" 
                                   value="<?= htmlspecialchars($cadastro['nome_pai'] ?? '') ?>" 
                                   <?= $form_editavel ? '' : 'readonly' ?>>
                        </div>
                    </div>
                </div>

                <!-- SEÇÃO 4: DOCUMENTOS -->
                <div class="section-title">
                    <h4 class="mb-0">4. Documentos</h4>
                </div>
                <div class="form-section">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="rg" class="form-label">RG <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="rg" name="rg" 
                                   value="<?= htmlspecialchars($cadastro['rg'] ?? '') ?>" 
                                   <?= $form_editavel ? 'required' : 'readonly' ?>>
                        </div>
                        <div class="col-md-4">
                            <label for="orgao_emissor" class="form-label">Órgão Emissor <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="orgao_emissor" name="orgao_emissor" 
                                   value="<?= htmlspecialchars($cadastro['orgao_emissor'] ?? '') ?>" 
                                   placeholder="Ex: SSP, PC, etc."
                                   <?= $form_editavel ? 'required' : 'readonly' ?>>
                        </div>
                        <div class="col-md-4">
                            <label for="uf_documento" class="form-label">UF do Documento</label>
                            <select class="form-select" id="uf_documento" name="uf_documento" <?= $form_editavel ? '' : 'disabled' ?>>
                                <option value="">Selecione...</option>
                                <?php foreach ($estados as $estado): ?>
                                    <option value="<?= $estado['sigla'] ?>" <?= ($cadastro['uf_documento'] ?? '') == $estado['sigla'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($estado['sigla']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- SEÇÃO 5: LOCALIZAÇÃO -->
                <div class="section-title">
                    <h4 class="mb-0">5. Localização</h4>
                </div>
                <div class="form-section">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="estado_id" class="form-label">Estado</label>
                            <select class="form-select" id="estado_id" name="estado_id" <?= $form_editavel ? '' : 'disabled' ?>>
                                <option value="">Selecione...</option>
                                <?php foreach ($estados as $estado): ?>
                                    <option value="<?= $estado['id'] ?>" <?= ($cadastro['estado_id'] ?? '') == $estado['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($estado['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="municipio_id" class="form-label">Município de Residência <span class="text-danger">*</span></label>
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
                </div>

                <!-- SEÇÃO 6: CONTATO -->
                <div class="section-title">
                    <h4 class="mb-0">6. Contato</h4>
                </div>
                <div class="form-section">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="telefone1" class="form-label">Telefone Principal <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="telefone1" name="telefone1" 
                                   value="<?= htmlspecialchars($cadastro['telefone1'] ?? '') ?>" 
                                   placeholder="(00) 00000-0000"
                                   <?= $form_editavel ? 'required' : 'readonly' ?>>
                        </div>
                        <div class="col-md-6">
                            <label for="telefone2" class="form-label">Telefone Secundário (Opcional)</label>
                            <input type="tel" class="form-control" id="telefone2" name="telefone2" 
                                   value="<?= htmlspecialchars($cadastro['telefone2'] ?? '') ?>" 
                                   placeholder="(00) 00000-0000"
                                   <?= $form_editavel ? '' : 'readonly' ?>>
                        </div>
                    </div>
                </div>

                <!-- SEÇÃO 7: ENDEREÇO -->
                <div class="section-title">
                    <h4 class="mb-0">7. Endereço Completo</h4>
                </div>
                <div class="form-section">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="cep" class="form-label">CEP</label>
                            <input type="text" class="form-control" id="cep" name="cep" 
                                   value="<?= htmlspecialchars($cadastro['cep'] ?? '') ?>" 
                                   placeholder="00000-000"
                                   maxlength="9"
                                   <?= $form_editavel ? '' : 'readonly' ?>>
                        </div>
                        <div class="col-md-7">
                            <label for="logradouro" class="form-label">Logradouro (Rua, Avenida)</label>
                            <input type="text" class="form-control" id="logradouro" name="logradouro" 
                                   value="<?= htmlspecialchars($cadastro['logradouro'] ?? '') ?>" 
                                   <?= $form_editavel ? '' : 'readonly' ?>>
                        </div>
                        <div class="col-md-2">
                            <label for="numero" class="form-label">Número</label>
                            <input type="text" class="form-control" id="numero" name="numero" 
                                   value="<?= htmlspecialchars($cadastro['numero'] ?? '') ?>" 
                                   <?= $form_editavel ? '' : 'readonly' ?>>
                        </div>
                        <div class="col-md-6">
                            <label for="complemento" class="form-label">Complemento (Opcional)</label>
                            <input type="text" class="form-control" id="complemento" name="complemento" 
                                   value="<?= htmlspecialchars($cadastro['complemento'] ?? '') ?>" 
                                   placeholder="Ex: Apto 101, Casa B"
                                   <?= $form_editavel ? '' : 'readonly' ?>>
                        </div>
                        <div class="col-md-6">
                            <label for="bairro" class="form-label">Bairro</label>
                            <input type="text" class="form-control" id="bairro" name="bairro" 
                                   value="<?= htmlspecialchars($cadastro['bairro'] ?? '') ?>" 
                                   <?= $form_editavel ? '' : 'readonly' ?>>
                        </div>
                        <div class="col-12">
                            <label for="ponto_referencia" class="form-label">Ponto de Referência (Opcional)</label>
                            <textarea class="form-control" id="ponto_referencia" name="ponto_referencia" 
                                      rows="2" placeholder="Ex: Próximo ao mercado, ao lado da igreja..."
                                      <?= $form_editavel ? '' : 'readonly' ?>><?= htmlspecialchars($cadastro['ponto_referencia'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- SEÇÃO 8: DOCUMENTOS DIGITAIS -->
                <div class="section-title">
                    <h4 class="mb-0">8. Envio de Documentos</h4>
                </div>
                <div class="form-section">
                    <p class="text-muted mb-3">Envie cópias digitalizadas ou fotos dos documentos solicitados (máx. 10MB por arquivo)</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="doc_cpf" class="form-label">Cópia do CPF</label>
                            <input class="form-control" type="file" id="doc_cpf" name="documentos[cpf]" 
                                   accept="image/*,application/pdf"
                                   <?= $form_editavel ? '' : 'disabled' ?>>
                            <?php if (isset($arquivos_enviados['cpf'])): ?>
                                <div class="mt-2">
                                    <small class="text-success">✓ Arquivo enviado:</small> 
                                    <a href="ver_arquivo.php?tipo=cpf" target="_blank">
                                        <?= htmlspecialchars($arquivos_enviados['cpf']['nome_original']) ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label for="doc_rg" class="form-label">Cópia do RG</label>
                            <input class="form-control" type="file" id="doc_rg" name="documentos[rg]" 
                                   accept="image/*,application/pdf"
                                   <?= $form_editavel ? '' : 'disabled' ?>>
                            <?php if (isset($arquivos_enviados['rg'])): ?>
                                <div class="mt-2">
                                    <small class="text-success">✓ Arquivo enviado:</small> 
                                    <a href="ver_arquivo.php?tipo=rg" target="_blank">
                                        <?= htmlspecialchars($arquivos_enviados['rg']['nome_original']) ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label for="doc_comprovante_residencia" class="form-label">Comprovante de Residência</label>
                            <input class="form-control" type="file" id="doc_comprovante_residencia" 
                                   name="documentos[comprovante_residencia]" 
                                   accept="image/*,application/pdf"
                                   <?= $form_editavel ? '' : 'disabled' ?>>
                            <?php if (isset($arquivos_enviados['comprovante_residencia'])): ?>
                                <div class="mt-2">
                                    <small class="text-success">✓ Arquivo enviado:</small> 
                                    <a href="ver_arquivo.php?tipo=comprovante_residencia" target="_blank">
                                        <?= htmlspecialchars($arquivos_enviados['comprovante_residencia']['nome_original']) ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label for="doc_foto_3x4" class="form-label">Foto 3x4</label>
                            <input class="form-control" type="file" id="doc_foto_3x4" name="documentos[foto_3x4]" 
                                   accept="image/*"
                                   <?= $form_editavel ? '' : 'disabled' ?>>
                            <?php if (isset($arquivos_enviados['foto_3x4'])): ?>
                                <div class="mt-2">
                                    <small class="text-success">✓ Arquivo enviado:</small> 
                                    <a href="ver_arquivo.php?tipo=foto_3x4" target="_blank">
                                        <?= htmlspecialchars($arquivos_enviados['foto_3x4']['nome_original']) ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label for="doc_caf" class="form-label">Comprovante CAF (CadÚnico)</label>
                            <input class="form-control" type="file" id="doc_caf" name="documentos[caf]" 
                                   accept="image/*,application/pdf"
                                   <?= $form_editavel ? '' : 'disabled' ?>>
                            <?php if (isset($arquivos_enviados['caf'])): ?>
                                <div class="mt-2">
                                    <small class="text-success">✓ Arquivo enviado:</small> 
                                    <a href="ver_arquivo.php?tipo=caf" target="_blank">
                                        <?= htmlspecialchars($arquivos_enviados['caf']['nome_original']) ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label for="doc_outros" class="form-label">Outros Documentos (Opcional)</label>
                            <input class="form-control" type="file" id="doc_outros" name="documentos[outros]" 
                                   accept="image/*,application/pdf"
                                   <?= $form_editavel ? '' : 'disabled' ?>>
                            <?php if (isset($arquivos_enviados['outros'])): ?>
                                <div class="mt-2">
                                    <small class="text-success">✓ Arquivo enviado:</small> 
                                    <a href="ver_arquivo.php?tipo=outros" target="_blank">
                                        <?= htmlspecialchars($arquivos_enviados['outros']['nome_original']) ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <?php if ($form_editavel): ?>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button class="btn btn-secondary btn-lg px-5" type="submit" name="acao" value="rascunho">
                            💾 Salvar Rascunho
                        </button>
                        <button class="btn btn-primary btn-lg px-5" type="submit" name="acao" value="enviar">
                            📨 Enviar Cadastro Definitivamente
                        </button>
                    </div>
                    <p class="text-muted text-end mt-2">
                        <small>* Após o envio definitivo, você não poderá mais editar seus dados.</small>
                    </p>
                <?php else: ?>
                    <div class="alert alert-success">
                        <strong>✓ Cadastro enviado!</strong> Seus dados estão em análise.
                    </div>
                <?php endif; ?>

            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Validação do formulário Bootstrap
(function () {
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

// Máscara para CEP
document.getElementById('cep')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length > 5) {
        value = value.replace(/^(\d{5})(\d)/, '$1-$2');
    }
    e.target.value = value;
});
</script>

</body>
</html>