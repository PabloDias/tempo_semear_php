<?php
// src/admin/exportar_xlsx.php (VERSÃO COMPLETA E FUNCIONAL)

session_start();
require_once '../db.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

// Verifica permissão
if (!isset($_SESSION['admin_usuario_id']) || $_SESSION['admin_usuario_perfil'] !== 'admin') {
    http_response_code(403);
    die("Acesso negado.");
}

try {
    // Lógica de filtro (mesma do dashboard)
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

    if ($filtro_status) { // ADICIONAR
        $where_conditions[] = "c.status = :status";
        $params[':status'] = $filtro_status;
    }

    $where_sql = "";
    if (count($where_conditions) > 0) {
        $where_sql = " WHERE " . implode(" AND ", $where_conditions);
    }

    // Busca TODOS os dados com JOINs
    $sql = "SELECT 
                c.*,
                m.nome AS nome_municipio,
                e.nome AS nome_estado,
                ui.nome AS nome_analisador
            FROM cadastros c
            JOIN municipios_permitidos m ON c.municipio_id = m.id
            LEFT JOIN estados e ON c.estado_id = e.id
            LEFT JOIN usuarios_internos ui ON c.analisado_por = ui.id"
            . $where_sql . 
            " ORDER BY c.protocolo ASC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $cadastros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Cria o spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Cadastros Tempo de Semear');
    
    // Define o cabeçalho (TODOS OS CAMPOS)
    $cabecalho = [
        'Protocolo',
        'Status',
        'CAF',
        'Situação',
        'Nome Completo',
        'Nome Social',
        'CPF',
        'Data Nascimento',
        'Idade',
        'Sexo',
        'RG',
        'Órgão Emissor',
        'UF Documento',
        'Nome da Mãe',
        'Nome do Pai',
        'Nacionalidade',
        'Estado',
        'Município',
        'Email',
        'Telefone 1',
        'Telefone 2',
        'CEP',
        'Logradouro',
        'Número',
        'Complemento',
        'Bairro',
        'Ponto de Referência',
        'Data de Envio',
        'Data de Análise',
        'Data de Conclusão',
        'Analisado Por',
        'Observações da Análise',
        'Motivo de Rejeição',
        'Editável',
        'Criado Em',
        'Atualizado Em'
    ];
    
    // Escreve o cabeçalho na primeira linha
    $sheet->fromArray($cabecalho, NULL, 'A1');
    
    // Estiliza o cabeçalho
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
            'size' => 12
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '4472C4']
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ]
    ];
    
    $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray($headerStyle);
    
    // Define altura da linha do cabeçalho
    $sheet->getRowDimension(1)->setRowHeight(25);
    
    // Preenche os dados
    $linha = 2;
    foreach ($cadastros as $cadastro) {
        // Calcula idade
        $idade = '';
        if ($cadastro['data_nascimento']) {
            $dataNasc = new DateTime($cadastro['data_nascimento']);
            $hoje = new DateTime();
            $idade = $hoje->diff($dataNasc)->y;
        }
        
        // Formata datas
        $data_envio = $cadastro['data_envio'] ? date('d/m/Y H:i', strtotime($cadastro['data_envio'])) : '';
        $data_analise = $cadastro['data_analise'] ? date('d/m/Y H:i', strtotime($cadastro['data_analise'])) : '';
        $data_conclusao = $cadastro['data_conclusao'] ? date('d/m/Y H:i', strtotime($cadastro['data_conclusao'])) : '';
        $criado_em = date('d/m/Y H:i', strtotime($cadastro['criado_em']));
        $atualizado_em = date('d/m/Y H:i', strtotime($cadastro['atualizado_em']));
        
        $dados_linha = [
            $cadastro['protocolo'] ?? '',
            $cadastro['status'] ?? '',
            $cadastro['caf'] ?? '',
            $cadastro['situacao'] ?? '',
            $cadastro['nome'] ?? '',
            $cadastro['nome_social'] ?? '',
            $cadastro['cpf'] ?? '',
            $cadastro['data_nascimento'] ? date('d/m/Y', strtotime($cadastro['data_nascimento'])) : '',
            $idade,
            $cadastro['sexo'] ?? '',
            $cadastro['rg'] ?? '',
            $cadastro['orgao_emissor'] ?? '',
            $cadastro['uf_documento'] ?? '',
            $cadastro['nome_mae'] ?? '',
            $cadastro['nome_pai'] ?? '',
            $cadastro['nacionalidade'] ?? '',
            $cadastro['nome_estado'] ?? '',
            $cadastro['nome_municipio'] ?? '',
            $cadastro['email'] ?? '',
            $cadastro['telefone1'] ?? '',
            $cadastro['telefone2'] ?? '',
            $cadastro['cep'] ?? '',
            $cadastro['logradouro'] ?? '',
            $cadastro['numero'] ?? '',
            $cadastro['complemento'] ?? '',
            $cadastro['bairro'] ?? '',
            $cadastro['ponto_referencia'] ?? '',
            $data_envio,
            $data_analise,
            $data_conclusao,
            $cadastro['nome_analisador'] ?? '',
            $cadastro['observacoes_analise'] ?? '',
            $cadastro['motivo_rejeicao'] ?? '',
            $cadastro['editavel'] ? 'Sim' : 'Não',
            $criado_em,
            $atualizado_em
        ];
        
        $sheet->fromArray($dados_linha, NULL, 'A' . $linha);
        $linha++;
    }
    
    // Auto-dimensiona as colunas
    foreach (range('A', $sheet->getHighestColumn()) as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Aplica bordas em toda a tabela
    $styleArray = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'CCCCCC']
            ]
        ]
    ];
    $sheet->getStyle('A1:' . $sheet->getHighestColumn() . ($linha - 1))->applyFromArray($styleArray);
    
    // Congela a primeira linha
    $sheet->freezePane('A2');
    
    // Nome do arquivo com filtros aplicados
    $nome_arquivo = 'cadastros_tempo_de_semear_' . date('Y-m-d_His');
    if ($filtro_municipio_id) {
        $nome_arquivo .= '_municipio_' . $filtro_municipio_id;
    }
    $nome_arquivo .= '.xlsx';
    
    // Configura headers para download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $nome_arquivo . '"');
    header('Cache-Control: max-age=0');
    header('Cache-Control: max-age=1');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Cache-Control: cache, must-revalidate');
    header('Pragma: public');

    // Gera e envia o arquivo
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();

} catch (Exception $e) {
    http_response_code(500);
    die("Erro ao gerar o arquivo XLSX: " . $e->getMessage());
}