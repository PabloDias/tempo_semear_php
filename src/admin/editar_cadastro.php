<?php
// src/admin/editar_cadastro.php (VERSÃO COMPLETA)

session_start();
require_once '../db.php';

// Apenas usuários 'internos' podem editar
$perfis_permitidos = ['admin', 'supervisor'];

if (!isset($_SESSION['admin_usuario_id']) || !in_array($_SESSION['admin_usuario_perfil'], $perfis_permitidos)) {
    header('Location: index.php?error=acesso_negado');
    exit();
}

$cadastro_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($cadastro_id === 0) {
    header('Location: dashboard.php?error=id_invalido');
    exit();
}

try {
    // Busca os dados do cadastro
    $stmt = $pdo->prepare("SELECT * FROM cadastros WHERE id = :id");
    $stmt->execute(['id' => $cadastro_id]);
    $cadastro = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cadastro) {
        header('Location: dashboard.php?error=cadastro_nao_encontrado');
        exit();
    }
    
    // Busca municípios ativos
    $stmtMunicipios = $pdo->query("SELECT id, nome FROM municipios_permitidos WHERE ativo = TRUE ORDER BY nome ASC");
    $municipios_permitidos = $stmtMunicipios->fetchAll(PDO::FETCH_ASSOC);
    
    // Busca estados
    $stmtEstados = $pdo->query("SELECT id, sigla, nome FROM estados ORDER BY nome ASC");
    $estados = $stmtEstados->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erro ao carregar dados para edição: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Cadastro - <?= htmlspecialchars($cadastro['protocolo']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .section-title {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            margin-top: 25px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">Admin - Editar Cadastro</a>
        <a href="ver_cadastro.php?id=<?= $cadastro_id; ?>" class="btn btn-secondary">Cancelar e Voltar</a>
    </div>
</nav>

<div class="container py-4">
    <div class="row">
        <div class="col-md-11 mx-auto">
            
            <div class="alert alert-info">
                <strong>Editando Cadastro:</strong> <?= htmlspecialchars($cadastro['nome']); ?><br>
                <strong>Protocolo:</strong> <?= htmlspecialchars($cadastro['protocolo']); ?>
            </div>
            
            <div class="alert alert-warning">
                <strong>⚠️ Atenção:</strong> Todas as alterações serão registradas no histórico e devem ser justificadas.
            </div>
            
            <form action="processa_edicao_admin.php" method="POST">
                <input type="hidden" name="cadastro_id" value="<?= $cadastro['id']; ?>">

                <!-- Dados Básicos -->
                <div class="section-title">
                    <h5 class="mb-0">1. Dados Básicos</h5>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="caf" class="form-label">CAF - Código de Acesso Familiar</label>
                        <input type="text" class="form-control" id="caf" name="caf" 
                               value="<?= htmlspecialchars($cadastro['caf'] ?? '') ?>" 
                               placeholder="Ex: 12345678901234">
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

                <!-- Identificação Pessoal -->
                <div class="section-title">
                    <h5 class="mb-0">2. Identificação Pessoal</h5>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="nome" class="form-label">Nome Completo *</label>
                        <input type="text" class="form-control" id="nome" name="nome" 
                               value="<?= htmlspecialchars($cadastro['nome'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="nome_social" class="form-label">Nome Social</label>
                        <input type="text" class="form-control" id="nome_social" name="nome_social" 
                               value="<?= htmlspecialchars($cadastro['nome_social'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="data_nascimento" class="form-label">Data de Nascimento *</label>
                        <input type="date" class="form-control" id="data_nascimento" name="data_nascimento" 
                               value="<?= htmlspecialchars($cadastro['data_nascimento'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="sexo" class="form-label">Sexo *</label>
                        <select class="form-select" id="sexo" name="sexo" required>
                            <option value="">Selecione...</option>
                            <option value="M" <?= ($cadastro['sexo'] ?? '') == 'M' ? 'selected' : '' ?>>Masculino</option>
                            <option value="F" <?= ($cadastro['sexo'] ?? '') == 'F' ? 'selected' : '' ?>>Feminino</option>
                            <option value="Outro" <?= ($cadastro['sexo'] ?? '') == 'Outro' ? 'selected' : '' ?>>Outro</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="nacionalidade" class="form-label">Nacionalidade</label>
                        <input type="text" class="form-control" id="nacionalidade" name="nacionalidade" 
                               value="<?= htmlspecialchars($cadastro['nacionalidade'] ?? 'Brasileira') ?>">
                    </div>
                </div>

                <!-- Filiação -->
                <div class="section-title">
                    <h5 class="mb-0">3. Filiação</h5>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="nome_mae" class="form-label">Nome da Mãe *</label>
                        <input type="text" class="form-control" id="nome_mae" name="nome_mae" 
                               value="<?= htmlspecialchars($cadastro['nome_mae'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="nome_pai" class="form-label">Nome do Pai</label>
                        <input type="text" class="form-control" id="nome_pai" name="nome_pai" 
                               value="<?= htmlspecialchars($cadastro['nome_pai'] ?? '') ?>">
                    </div>
                </div>

                <!-- Documentos -->
                <div class="section-title">
                    <h5 class="mb-0">4. Documentos</h5>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="rg" class="form-label">RG *</label>
                        <input type="text" class="form-control" id="rg" name="rg" 
                               value="<?= htmlspecialchars($cadastro['rg'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="orgao_emissor" class="form-label">Órgão Emissor *</label>
                        <input type="text" class="form-control" id="orgao_emissor" name="orgao_emissor" 
                               value="<?= htmlspecialchars($cadastro['orgao_emissor'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="uf_documento" class="form-label">UF do Documento</label>
                        <select class="form-select" id="uf_documento" name="uf_documento">
                            <option value="">Selecione...</option>
                            <?php foreach ($estados as $estado): ?>
                                <option value="<?= $estado['sigla'] ?>" <?= ($cadastro['uf_documento'] ?? '') == $estado['sigla'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($estado['sigla']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Localização -->
                <div class="section-title">
                    <h5 class="mb-0">5. Localização</h5>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="estado_id" class="form-label">Estado</label>
                        <select class="form-select" id="estado_id" name="estado_id">
                            <option value="">Selecione...</option>
                            <?php foreach ($estados as $estado): ?>
                                <option value="<?= $estado['id'] ?>" <?= ($cadastro['estado_id'] ?? '') == $estado['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($estado['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="municipio_id" class="form-label">Município *</label>
                        <select class="form-select" id="municipio_id" name="municipio_id" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($municipios_permitidos as $municipio): ?>
                                <option value="<?= $municipio['id'] ?>" <?= ($cadastro['municipio_id'] ?? '') == $municipio['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($municipio['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Contato -->
                <div class="section-title">
                    <h5 class="mb-0">6. Contato</h5>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="telefone1" class="form-label">Telefone Principal *</label>
                        <input type="tel" class="form-control" id="telefone1" name="telefone1" 
                               value="<?= htmlspecialchars($cadastro['telefone1'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="telefone2" class="form-label">Telefone Secundário</label>
                        <input type="tel" class="form-control" id="telefone2" name="telefone2" 
                               value="<?= htmlspecialchars($cadastro['telefone2'] ?? '') ?>">
                    </div>
                </div>

                <!-- Endereço -->
                <div class="section-title">
                    <h5 class="mb-0">7. Endereço Completo</h5>
                </div>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="cep" class="form-label">CEP</label>
                        <input type="text" class="form-control" id="cep" name="cep" 
                               value="<?= htmlspecialchars($cadastro['cep'] ?? '') ?>" 
                               placeholder="00000-000" maxlength="9">
                    </div>
                    <div class="col-md-7">
                        <label for="logradouro" class="form-label">Logradouro</label>
                        <input type="text" class="form-control" id="logradouro" name="logradouro" 
                               value="<?= htmlspecialchars($cadastro['logradouro'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="numero" class="form-label">Número</label>
                        <input type="text" class="form-control" id="numero" name="numero" 
                               value="<?= htmlspecialchars($cadastro['numero'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="complemento" class="form-label">Complemento</label>
                        <input type="text" class="form-control" id="complemento" name="complemento" 
                               value="<?= htmlspecialchars($cadastro['complemento'] ?? '') ?>" 
                               placeholder="Ex: Apto 101, Casa B">
                    </div>
                    <div class="col-md-6">
                        <label for="bairro" class="form-label">Bairro</label>
                        <input type="text" class="form-control" id="bairro" name="bairro" 
                               value="<?= htmlspecialchars($cadastro['bairro'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label for="ponto_referencia" class="form-label">Ponto de Referência</label>
                        <textarea class="form-control" id="ponto_referencia" name="ponto_referencia" 
                                  rows="2" placeholder="Ex: Próximo ao mercado, ao lado da igreja..."><?= htmlspecialchars($cadastro['ponto_referencia'] ?? '') ?></textarea>
                    </div>
                </div>

                <hr class="my-4">
                
                <!-- Justificativa (OBRIGATÓRIA) -->
                <div class="alert alert-warning">
                    <strong>⚠️ Justificativa Obrigatória</strong>
                </div>
                <div class="mb-4">
                    <label for="justificativa" class="form-label">
                        <strong>Justificativa da Alteração *</strong>
                    </label>
                    <textarea class="form-control" id="justificativa" name="justificativa" 
                              rows="4" required 
                              placeholder="Ex: Correção do nome da mãe a pedido do beneficiário conforme novo documento apresentado."></textarea>
                    <div class="form-text">
                        Explique detalhadamente o motivo das alterações realizadas. Esta informação ficará registrada no histórico.
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="ver_cadastro.php?id=<?= $cadastro_id; ?>" class="btn btn-secondary btn-lg px-5">
                        Cancelar
                    </a>
                    <button class="btn btn-primary btn-lg px-5" type="submit">
                        💾 Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Máscara para CEP
document.getElementById('cep')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length > 5) {
        value = value.replace(/^(\d{5})(\d)/, '$1-$2');
    }
    e.target.value = value;
});

// Validação antes de enviar
document.querySelector('form').addEventListener('submit', function(e) {
    const justificativa = document.getElementById('justificativa').value.trim();
    if (justificativa.length < 10) {
        e.preventDefault();
        alert('A justificativa deve ter pelo menos 10 caracteres.');
        document.getElementById('justificativa').focus();
    }
});
</script>

</body>
</html>