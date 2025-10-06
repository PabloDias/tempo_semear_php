<?php
// src/admin/dashboard.php

session_start();
require_once '../db.php';

if (!isset($_SESSION['admin_usuario_id'])) {
    header('Location: index.php');
    exit();
}

// Lógica de Filtro
$filtro_municipio_id = isset($_GET['municipio_id']) && !empty($_GET['municipio_id']) ? (int)$_GET['municipio_id'] : null;
$filtro_cpf = isset($_GET['cpf']) && !empty($_GET['cpf']) ? preg_replace('/[^0-9]/', '', $_GET['cpf']) : null;
$filtro_status = isset($_GET['status']) && !empty($_GET['status']) ? $_GET['status'] : null;

$where_conditions = [];
$params = [];

if ($filtro_municipio_id) {
    $where_conditions[] = "c.municipio_id = :municipio_id";
    $params[':municipio_id'] = $filtro_municipio_id;
}
if ($filtro_cpf) {
    $where_conditions[] = "c.cpf LIKE :cpf";
    $params[':cpf'] = $filtro_cpf . '%';
}
if ($filtro_status) {
    $where_conditions[] = "c.status = :status";
    $params[':status'] = $filtro_status;
}

$where_sql = "";
if (count($where_conditions) > 0) {
    $where_sql = " WHERE " . implode(" AND ", $where_conditions);
}

// Lógica de Paginação
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$registros_por_pagina = 15;
$offset = ($pagina_atual - 1) * $registros_por_pagina;

try {
    // Busca municípios ativos
    $stmtMunicipios = $pdo->query("SELECT id, nome FROM municipios_permitidos WHERE ativo = TRUE ORDER BY nome ASC");
    $lista_municipios = $stmtMunicipios->fetchAll(PDO::FETCH_ASSOC);

    // Contar total de registros
    $total_registros_stmt = $pdo->prepare("SELECT COUNT(*) FROM cadastros c" . $where_sql);
    $total_registros_stmt->execute($params);
    $total_registros = $total_registros_stmt->fetchColumn();
    $total_paginas = ceil($total_registros / $registros_por_pagina);

    // Buscar registros da página atual
    $sql = "SELECT c.id, c.protocolo, c.nome, c.cpf, c.status, c.data_envio, m.nome AS nome_municipio
            FROM cadastros c
            JOIN municipios_permitidos m ON c.municipio_id = m.id"
           . $where_sql .
           " ORDER BY c.data_envio DESC NULLS LAST, c.criado_em DESC
            LIMIT :limit OFFSET :offset";
            
    $stmt = $pdo->prepare($sql);
    
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    
    $stmt->bindValue(':limit', $registros_por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $cadastros = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erro ao buscar os cadastros: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - Cadastros</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="#">Admin - Tempo de Semear</a>
        <div class="d-flex text-white align-items-center">
            <?php if ($_SESSION['admin_usuario_perfil'] === 'admin'): ?>
                <a href="usuarios.php" class="btn btn-outline-light btn-sm me-3">Gerenciar Usuários</a>
            <?php endif; ?>
            <span class="navbar-text me-3">
                Usuário: <?= htmlspecialchars($_SESSION['admin_usuario_nome']); ?>
                (<?= htmlspecialchars($_SESSION['admin_usuario_perfil']); ?>)
            </span>
            <a href="logout_admin.php" class="btn btn-danger">Sair</a>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Lista de Cadastros</h1>
        <?php if (isset($_SESSION['admin_usuario_perfil']) && $_SESSION['admin_usuario_perfil'] === 'admin'):
            $query_export = http_build_query([
                'municipio_id' => $filtro_municipio_id, 
                'cpf' => $filtro_cpf,
                'status' => $filtro_status
            ]);
        ?>
            <a href="exportar_xlsx.php?<?= $query_export ?>" class="btn btn-success">
                Exportar para Excel
            </a>
        <?php endif; ?>
    </div>

    <div class="card card-body mb-4">
        <form method="GET" action="dashboard.php" class="row g-3 align-items-center">
            <div class="col-md-4">
                <label for="municipio_id" class="form-label">Filtrar por Município</label>
                <select name="municipio_id" id="municipio_id" class="form-select">
                    <option value="">Todos os municípios</option>
                    <?php foreach($lista_municipios as $municipio): ?>
                        <option value="<?= $municipio['id'] ?>" <?= $filtro_municipio_id == $municipio['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($municipio['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Filtrar por Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="">Todos os status</option>
                    <option value="rascunho" <?= $filtro_status === 'rascunho' ? 'selected' : '' ?>>Rascunho</option>
                    <option value="enviado" <?= $filtro_status === 'enviado' ? 'selected' : '' ?>>Enviado</option>
                    <option value="em_analise" <?= $filtro_status === 'em_analise' ? 'selected' : '' ?>>Em Análise</option>
                    <option value="aprovado" <?= $filtro_status === 'aprovado' ? 'selected' : '' ?>>Aprovado</option>
                    <option value="rejeitado" <?= $filtro_status === 'rejeitado' ? 'selected' : '' ?>>Rejeitado</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="cpf" class="form-label">Buscar por CPF</label>
                <input type="text" name="cpf" id="cpf" class="form-control" placeholder="Apenas números" value="<?= htmlspecialchars($filtro_cpf ?? '') ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <div class="d-grid gap-2 w-100">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="dashboard.php" class="btn btn-secondary">Limpar</a>
                </div>
            </div>
        </form>
    </div>
    
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Protocolo</th>
                    <th>Nome</th>
                    <th>CPF</th>
                    <th>Município</th>
                    <th>Data de Envio</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($cadastros) > 0): ?>
                    <?php foreach ($cadastros as $cadastro): ?>
                        <tr>
                            <td>
                                <?php if ($cadastro['protocolo']): ?>
                                    <?= htmlspecialchars($cadastro['protocolo']) ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($cadastro['nome']); ?></td>
                            <td><?= htmlspecialchars($cadastro['cpf']); ?></td>
                            <td><?= htmlspecialchars($cadastro['nome_municipio']); ?></td>
                            <td><?= $cadastro['data_envio'] ? date('d/m/Y H:i', strtotime($cadastro['data_envio'])) : 'N/A'; ?></td>
                            <td>
                                <?php
                                $badge_class = [
                                    'rascunho' => 'secondary',
                                    'enviado' => 'primary',
                                    'em_analise' => 'warning',
                                    'aprovado' => 'success',
                                    'rejeitado' => 'danger'
                                ];
                                $class = $badge_class[$cadastro['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $class ?>">
                                    <?= strtoupper(str_replace('_', ' ', $cadastro['status'])) ?>
                                </span>
                            </td>
                            <td>
                                <a href="ver_cadastro.php?id=<?= $cadastro['id']; ?>" class="btn btn-sm btn-primary">Ver Detalhes</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">Nenhum cadastro encontrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_paginas > 1): ?>
        <nav aria-label="Navegação de página">
            <ul class="pagination justify-content-center">
                <?php
                $query_params = http_build_query([
                    'municipio_id' => $filtro_municipio_id, 
                    'cpf' => $filtro_cpf,
                    'status' => $filtro_status
                ]);
                ?>
                <li class="page-item <?= $pagina_atual <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?pagina=<?= $pagina_atual - 1; ?>&<?= $query_params ?>">Anterior</a>
                </li>
                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <li class="page-item <?= $i == $pagina_atual ? 'active' : '' ?>">
                        <a class="page-link" href="?pagina=<?= $i; ?>&<?= $query_params ?>"><?= $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= $pagina_atual >= $total_paginas ? 'disabled' : '' ?>">
                    <a class="page-link" href="?pagina=<?= $pagina_atual + 1; ?>&<?= $query_params ?>">Próximo</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>

</div>

</body>
</html>