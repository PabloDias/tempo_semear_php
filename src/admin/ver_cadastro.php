<?php
// src/admin/ver_cadastro.php (VERSÃO COMPLETA)

session_start();
require_once '../db.php';

if (!isset($_SESSION['admin_usuario_id'])) {
    header('Location: index.php');
    exit();
}

$cadastro_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($cadastro_id === 0) {
    header('Location: dashboard.php?error=id_invalido');
    exit();
}

try {
    // Buscar todos os dados do cadastro
    $sql = "SELECT 
                c.*,
                m.nome AS nome_municipio,
                e.nome AS nome_estado,
                e.sigla AS sigla_estado,
                ui.nome AS nome_analisador,
                ui_criador.nome AS nome_criador_alteracao
            FROM cadastros c
            JOIN municipios_permitidos m ON c.municipio_id = m.id
            LEFT JOIN estados e ON c.estado_id = e.id
            LEFT JOIN usuarios_internos ui ON c.analisado_por = ui.id
            LEFT JOIN usuarios_internos ui_criador ON c.atualizado_por = ui_criador.id
            WHERE c.id = :cadastro_id";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['cadastro_id' => $cadastro_id]);
    $cadastro = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cadastro) {
        header('Location: dashboard.php?error=cadastro_nao_encontrado');
        exit();
    }
    
    // Buscar os arquivos associados
    $stmt_arquivos = $pdo->prepare("SELECT id, tipo_documento, nome_original, tamanho_bytes, criado_em FROM arquivos_cadastro WHERE cadastro_id = :cadastro_id ORDER BY tipo_documento");
    $stmt_arquivos->execute(['cadastro_id' => $cadastro_id]);
    $arquivos = $stmt_arquivos->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar histórico de edições
    $stmt_historico = $pdo->prepare("
        SELECT h.*, ui.nome AS nome_usuario 
        FROM historico_edicoes h
        LEFT JOIN usuarios_internos ui ON h.usuario_id = ui.id
        WHERE h.cadastro_id = :cadastro_id
        ORDER BY h.criado_em DESC
    ");
    $stmt_historico->execute(['cadastro_id' => $cadastro_id]);
    $historico = $stmt_historico->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erro ao buscar os detalhes do cadastro: " . $e->getMessage());
}

// Função helper para formatar data
function formatarData($data) {
    return $data ? date('d/m/Y', strtotime($data)) : '-';
}

function formatarDataHora($data) {
    return $data ? date('d/m/Y H:i', strtotime($data)) : '-';
}

function formatarTamanho($bytes) {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' bytes';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Cadastro - <?= htmlspecialchars($cadastro['protocolo']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .info-card {
            background: #f8f9fa;
            border-left: 4px solid #0d6efd;
            padding: 15px;
            margin-bottom: 20px;
        }
        .section-title {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            margin-top: 20px;
            margin-bottom: 15px;
        }
        .data-label {
            font-weight: 600;
            color: #495057;
        }
        .badge-status {
            font-size: 0.9rem;
            padding: 8px 15px;
        }
        .timeline-item {
            border-left: 2px solid #dee2e6;
            padding-left: 20px;
            padding-bottom: 15px;
            position: relative;
        }
        .timeline-item::before {
            content: '';
            width: 12px;
            height: 12px;
            background: #0d6efd;
            border-radius: 50%;
            position: absolute;
            left: -7px;
            top: 5px;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">Admin - Tempo de Semear</a>
        <div class="d-flex">
            <a href="dashboard.php" class="btn btn-secondary me-3">Voltar para a Lista</a>
            <a href="logout_admin.php" class="btn btn-danger">Sair</a>
        </div>
    </div>
</nav>

<div class="container mt-4 mb-5">
    
    <!-- Cabeçalho com Protocolo e Status -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h3 class="mb-2">
                        <strong>Protocolo:</strong> <?= htmlspecialchars($cadastro['protocolo'] ?? 'Rascunho'); ?>
                    </h3>
                    <p class="mb-0 text-muted">
                        <strong>Cadastro ID:</strong> #<?= $cadastro['id'] ?>
                    </p>
                </div>
                <div class="col-md-6 text-end">
                    <?php
                    $status_colors = [
                        'rascunho' => 'secondary',
                        'enviado' => 'primary',
                        'em_analise' => 'warning',
                        'aprovado' => 'success',
                        'rejeitado' => 'danger'
                    ];
                    $color = $status_colors[$cadastro['status']] ?? 'secondary';
                    ?>
                    <span class="badge bg-<?= $color ?> badge-status">
                        <?= strtoupper(str_replace('_', ' ', $cadastro['status'])) ?>
                    </span>
                    <div class="mt-2">
                        <small class="text-muted">
                            Enviado em: <?= formatarDataHora($cadastro['data_envio']) ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            
            <!-- Dados Básicos -->
            <div class="section-title">
                <h5 class="mb-0">📋 Dados Básicos</h5>
            </div>
            <div class="info-card">
                <div class="row g-3">
                    <div class="col-md-6">
                        <span class="data-label">CAF:</span><br>
                        <?= htmlspecialchars($cadastro['caf'] ?: '-') ?>
                    </div>
                    <div class="col-md-6">
                        <span class="data-label">Situação:</span><br>
                        <?= htmlspecialchars($cadastro['situacao'] ?: '-') ?>
                    </div>
                </div>
            </div>

            <!-- Identificação Pessoal -->
            <div class="section-title">
                <h5 class="mb-0">👤 Identificação Pessoal</h5>
            </div>
            <div class="info-card">
                <div class="row g-3">
                    <div class="col-md-8">
                        <span class="data-label">Nome Completo:</span><br>
                        <strong><?= htmlspecialchars($cadastro['nome']) ?></strong>
                    </div>
                    <div class="col-md-4">
                        <span class="data-label">CPF:</span><br>
                        <?= htmlspecialchars($cadastro['cpf']) ?>
                    </div>
                    <div class="col-md-8">
                        <span class="data-label">Nome Social:</span><br>
                        <?= htmlspecialchars($cadastro['nome_social'] ?: '-') ?>
                    </div>
                    <div class="col-md-4">
                        <span class="data-label">Data de Nascimento:</span><br>
                        <?= formatarData($cadastro['data_nascimento']) ?>
                        <?php
                        $idade = date_diff(date_create($cadastro['data_nascimento']), date_create('today'))->y;
                        echo " <small class='text-muted'>($idade anos)</small>";
                        ?>
                    </div>
                    <div class="col-md-4">
                        <span class="data-label">Sexo:</span><br>
                        <?= htmlspecialchars($cadastro['sexo']) ?>
                    </div>
                    <div class="col-md-8">
                        <span class="data-label">Nacionalidade:</span><br>
                        <?= htmlspecialchars($cadastro['nacionalidade'] ?: 'Brasileira') ?>
                    </div>
                </div>
            </div>

            <!-- Filiação -->
            <div class="section-title">
                <h5 class="mb-0">👨‍👩‍👦 Filiação</h5>
            </div>
            <div class="info-card">
                <div class="row g-3">
                    <div class="col-md-6">
                        <span class="data-label">Nome da Mãe:</span><br>
                        <?= htmlspecialchars($cadastro['nome_mae']) ?>
                    </div>
                    <div class="col-md-6">
                        <span class="data-label">Nome do Pai:</span><br>
                        <?= htmlspecialchars($cadastro['nome_pai'] ?: 'Não informado') ?>
                    </div>
                </div>
            </div>

            <!-- Documentos -->
            <div class="section-title">
                <h5 class="mb-0">🪪 Documentos</h5>
            </div>
            <div class="info-card">
                <div class="row g-3">
                    <div class="col-md-4">
                        <span class="data-label">RG:</span><br>
                        <?= htmlspecialchars($cadastro['rg']) ?>
                    </div>
                    <div class="col-md-4">
                        <span class="data-label">Órgão Emissor:</span><br>
                        <?= htmlspecialchars($cadastro['orgao_emissor']) ?>
                    </div>
                    <div class="col-md-4">
                        <span class="data-label">UF:</span><br>
                        <?= htmlspecialchars($cadastro['uf_documento'] ?: '-') ?>
                    </div>
                </div>
            </div>

            <!-- Localização -->
            <div class="section-title">
                <h5 class="mb-0">📍 Localização</h5>
            </div>
            <div class="info-card">
                <div class="row g-3">
                    <div class="col-md-6">
                        <span class="data-label">Estado:</span><br>
                        <?= htmlspecialchars($cadastro['nome_estado'] ?: '-') ?>
                        <?= $cadastro['sigla_estado'] ? "({$cadastro['sigla_estado']})" : '' ?>
                    </div>
                    <div class="col-md-6">
                        <span class="data-label">Município:</span><br>
                        <strong><?= htmlspecialchars($cadastro['nome_municipio']) ?></strong>
                    </div>
                </div>
            </div>

            <!-- Contato -->
            <div class="section-title">
                <h5 class="mb-0">📞 Contato</h5>
            </div>
            <div class="info-card">
                <div class="row g-3">
                    <div class="col-md-6">
                        <span class="data-label">Email:</span><br>
                        <?= htmlspecialchars($cadastro['email']) ?>
                    </div>
                    <div class="col-md-3">
                        <span class="data-label">Telefone 1:</span><br>
                        <?= htmlspecialchars($cadastro['telefone1']) ?>
                    </div>
                    <div class="col-md-3">
                        <span class="data-label">Telefone 2:</span><br>
                        <?= htmlspecialchars($cadastro['telefone2'] ?: '-') ?>
                    </div>
                </div>
            </div>

            <!-- Endereço -->
            <div class="section-title">
                <h5 class="mb-0">🏠 Endereço</h5>
            </div>
            <div class="info-card">
                <div class="row g-3">
                    <div class="col-md-3">
                        <span class="data-label">CEP:</span><br>
                        <?= htmlspecialchars($cadastro['cep'] ?: '-') ?>
                    </div>
                    <div class="col-md-7">
                        <span class="data-label">Logradouro:</span><br>
                        <?= htmlspecialchars($cadastro['logradouro'] ?: '-') ?>
                    </div>
                    <div class="col-md-2">
                        <span class="data-label">Número:</span><br>
                        <?= htmlspecialchars($cadastro['numero'] ?: '-') ?>
                    </div>
                    <div class="col-md-6">
                        <span class="data-label">Complemento:</span><br>
                        <?= htmlspecialchars($cadastro['complemento'] ?: '-') ?>
                    </div>
                    <div class="col-md-6">
                        <span class="data-label">Bairro:</span><br>
                        <?= htmlspecialchars($cadastro['bairro'] ?: '-') ?>
                    </div>
                    <div class="col-12">
                        <span class="data-label">Ponto de Referência:</span><br>
                        <?= htmlspecialchars($cadastro['ponto_referencia'] ?: '-') ?>
                    </div>
                </div>
            </div>

        </div>

        <div class="col-md-4">
            
            <!-- Arquivos Enviados -->
            <div class="section-title">
                <h5 class="mb-0">📎 Documentos Anexados</h5>
            </div>
            <div class="card mb-4">
                <div class="list-group list-group-flush">
                    <?php if (count($arquivos) > 0): ?>
                        <?php foreach ($arquivos as $arquivo): ?>
                            <a href="ver_arquivo_admin.php?id=<?= $arquivo['id']; ?>" target="_blank" class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $arquivo['tipo_documento']))); ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($arquivo['nome_original']); ?></small><br>
                                        <small class="text-muted"><?= formatarTamanho($arquivo['tamanho_bytes']); ?></small>
                                    </div>
                                    <span class="badge bg-success">Ver</span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="list-group-item text-center text-muted">
                            Nenhum documento anexado
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Informações do Sistema -->
            <div class="section-title">
                <h5 class="mb-0">⚙️ Informações do Sistema</h5>
            </div>
            <div class="card mb-4">
                <div class="card-body">
                    <p><strong>Editável:</strong> <?= $cadastro['editavel'] ? 'Sim' : 'Não' ?></p>
                    <p><strong>Criado em:</strong><br><?= formatarDataHora($cadastro['criado_em']) ?></p>
                    <p><strong>Atualizado em:</strong><br><?= formatarDataHora($cadastro['atualizado_em']) ?></p>
                    <?php if ($cadastro['nome_criador_alteracao']): ?>
                        <p><strong>Alterado por:</strong><br><?= htmlspecialchars($cadastro['nome_criador_alteracao']) ?></p>
                    <?php endif; ?>
                    <?php if ($cadastro['nome_analisador']): ?>
                        <p><strong>Analisado por:</strong><br><?= htmlspecialchars($cadastro['nome_analisador']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($cadastro['status'] === 'enviado' || $cadastro['status'] === 'em_analise'): ?>
                <div class="card mb-3">
                    <div class="card-header bg-warning">
                        <strong>Validar Cadastro</strong>
                    </div>
                    <div class="card-body">
                        <form action="validar_cadastro.php" method="POST">
                            <input type="hidden" name="cadastro_id" value="<?= $cadastro['id'] ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Novo Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="">Selecione...</option>
                                    <option value="em_analise">Em Análise</option>
                                    <option value="validado">Validado</option>
                                    <option value="rejeitado">Rejeitado</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Observações</label>
                                <textarea name="observacoes" class="form-control" rows="3" placeholder="Motivo da decisão..."></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">Salvar Validação</button>
                        </form>
                    </div>
                </div>
            <?php elseif ($cadastro['status'] === 'validado'): ?>
                <div class="alert alert-success">
                    <strong>Cadastro Validado</strong><br>
                    <?php if ($cadastro['observacoes_analise']): ?>
                        <?= htmlspecialchars($cadastro['observacoes_analise']) ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($_SESSION['admin_usuario_perfil'] === 'admin'): ?>
                <a href="editar_cadastro.php?id=<?= $cadastro['id']; ?>" class="btn btn-warning w-100 mb-3">
                    ✏️ Editar Cadastro
                </a>
                <?php if (!$cadastro['editavel']): ?>
                    <small class="text-muted d-block mb-3">
                        ⚠️ Cadastro já enviado. Edição administrativa com justificativa.
                    </small>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Histórico de Edições -->
    <?php if (count($historico) > 0): ?>
        <div class="section-title">
            <h5 class="mb-0">📜 Histórico de Alterações</h5>
        </div>
        <div class="card">
            <div class="card-body">
                <?php foreach ($historico as $item): ?>
                    <div class="timeline-item">
                        <strong><?= ucfirst(str_replace('_', ' ', $item['campo_alterado'])) ?></strong>
                        <small class="text-muted d-block">
                            <?= formatarDataHora($item['criado_em']) ?> - 
                            <?= htmlspecialchars($item['nome_usuario'] ?? 'Sistema') ?>
                        </small>
                        <div class="mt-2">
                            <span class="badge bg-danger">De: <?= htmlspecialchars($item['valor_anterior'] ?: '-') ?></span>
                            <span class="badge bg-success">Para: <?= htmlspecialchars($item['valor_novo'] ?: '-') ?></span>
                        </div>
                        <?php if ($item['justificativa']): ?>
                            <div class="mt-2 small">
                                <strong>Justificativa:</strong> <?= htmlspecialchars($item['justificativa']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>