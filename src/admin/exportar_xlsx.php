<?php
// src/admin/exportar_xlsx.php (VERSÃO FINAL)

session_start();
require_once '../db.php';
// O caminho agora está correto, pois vendor/ estará na raiz
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (!isset($_SESSION['admin_usuario_id']) || $_SESSION['admin_usuario_perfil'] !== 'admin') {
    http_response_code(403);
    die("Acesso negado.");
}

try {
    // Lógica de filtro
    $filtro_municipio_id = isset($_GET['municipio_id']) && !empty($_GET['municipio_id']) ? (int)$_GET['municipio_id'] : null;
    $filtro_cpf = isset($_GET['cpf']) && !empty($_GET['cpf']) ? preg_replace('/[^0-9]/', '', $_GET['cpf']) : null;
    
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

    $where_sql = "";
    if (count($where_conditions) > 0) {
        $where_sql = " WHERE " . implode(" AND ", $where_conditions);
    }

    $sql = "SELECT c.*, m.nome AS nome_municipio FROM cadastros c JOIN municipios_permitidos m ON c.municipio_id = m.id" . $where_sql . " ORDER BY c.nome ASC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $cadastros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Cadastros');
    
    $cabecalho = [ 'Protocolo', 'Status', 'Nome Completo', /* ... etc */ ];
    $sheet->fromArray($cabecalho, NULL, 'A1');

    $linha = 2;
    foreach ($cadastros as $cadastro) {
        $dados_linha = [ $cadastro['protocolo'], $cadastro['status'], /* ... etc */ ];
        $sheet->fromArray($dados_linha, NULL, 'A' . $linha);
        $linha++;
    }

    // Envia o arquivo para download
    $nome_arquivo = 'cadastros_' . date('Y-m-d') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $nome_arquivo . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();

} catch (Exception $e) {
    die("Erro ao gerar o arquivo XLSX: " . $e->getMessage());
}