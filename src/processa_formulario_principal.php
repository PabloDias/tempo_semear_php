<?php
// src/processa_formulario_principal.php (VERSÃO COMPLETA)

session_start();
require_once 'db.php';

if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . (isset($_SESSION['usuario_id']) ? 'dashboard.php' : 'login.php'));
    exit();
}

// Inicia uma transação
$pdo->beginTransaction();

try {
    $beneficiario_id = $_SESSION['usuario_id'];
    $acao = $_POST['acao'] ?? 'rascunho';

    // Coleta TODOS os dados do formulário
    $dados_cadastro = [
        // Dados básicos
        'caf' => trim($_POST['caf'] ?? ''),
        'situacao' => trim($_POST['situacao'] ?? ''),
        
        // Identificação pessoal
        'nome' => trim($_POST['nome']),
        'nome_social' => trim($_POST['nome_social'] ?? ''),
        'data_nascimento' => $_POST['data_nascimento'],
        'sexo' => $_POST['sexo'],
        'nacionalidade' => trim($_POST['nacionalidade'] ?? 'Brasileira'),
        
        // Filiação
        'nome_mae' => trim($_POST['nome_mae']),
        'nome_pai' => trim($_POST['nome_pai'] ?? ''),
        
        // Documentos
        'rg' => trim($_POST['rg']),
        'orgao_emissor' => trim($_POST['orgao_emissor']),
        'uf_documento' => $_POST['uf_documento'] ?? null,
        
        // Localização
        'estado_id' => !empty($_POST['estado_id']) ? (int)$_POST['estado_id'] : null,
        'municipio_id' => (int)$_POST['municipio_id'],
        
        // Contato
        'telefone1' => trim($_POST['telefone1']),
        'telefone2' => trim($_POST['telefone2'] ?? ''),
        
        // Endereço completo
        'cep' => preg_replace('/[^0-9]/', '', $_POST['cep'] ?? ''),
        'logradouro' => trim($_POST['logradouro'] ?? ''),
        'numero' => trim($_POST['numero'] ?? ''),
        'complemento' => trim($_POST['complemento'] ?? ''),
        'bairro' => trim($_POST['bairro'] ?? ''),
        'ponto_referencia' => trim($_POST['ponto_referencia'] ?? '')
    ];

    // Validação básica
    if (empty($dados_cadastro['nome']) || empty($dados_cadastro['data_nascimento']) || 
        empty($dados_cadastro['sexo']) || empty($dados_cadastro['nome_mae']) ||
        empty($dados_cadastro['rg']) || empty($dados_cadastro['orgao_emissor']) ||
        empty($dados_cadastro['telefone1']) || empty($dados_cadastro['municipio_id'])) {
        throw new Exception("Por favor, preencha todos os campos obrigatórios.");
    }

    // Define o status baseado na ação
    if ($acao === 'enviar') {
        $dados_cadastro['status'] = 'enviado';
        $dados_cadastro['editavel'] = false;
        $dados_cadastro['data_envio'] = date('Y-m-d H:i:s');
    } else {
        $dados_cadastro['status'] = 'rascunho';
        $dados_cadastro['editavel'] = true;
        $dados_cadastro['data_envio'] = null;
    }

    // Busca informações do beneficiário
    $stmtUser = $pdo->prepare("SELECT cpf, email FROM beneficiarios WHERE id = :id");
    $stmtUser->execute(['id' => $beneficiario_id]);
    $beneficiario_info = $stmtUser->fetch(PDO::FETCH_ASSOC);
    
    if (!$beneficiario_info) {
        throw new Exception("Usuário não encontrado.");
    }

    // Verifica se já existe cadastro
    $stmt = $pdo->prepare("SELECT id, editavel FROM cadastros WHERE beneficiario_id = :beneficiario_id");
    $stmt->execute(['beneficiario_id' => $beneficiario_id]);
    $cadastro_existente = $stmt->fetch();

    if ($cadastro_existente) {
        // ATUALIZAÇÃO
        
        // Verifica se o cadastro é editável
        if (!$cadastro_existente['editavel']) {
            throw new Exception("Este cadastro não pode mais ser editado.");
        }
        
        $cadastro_id = $cadastro_existente['id'];
        
        $sql = "UPDATE cadastros SET
                    caf = :caf,
                    situacao = :situacao,
                    nome = :nome,
                    nome_social = :nome_social,
                    data_nascimento = :data_nascimento,
                    sexo = :sexo,
                    nacionalidade = :nacionalidade,
                    nome_mae = :nome_mae,
                    nome_pai = :nome_pai,
                    rg = :rg,
                    orgao_emissor = :orgao_emissor,
                    uf_documento = :uf_documento,
                    estado_id = :estado_id,
                    municipio_id = :municipio_id,
                    telefone1 = :telefone1,
                    telefone2 = :telefone2,
                    cep = :cep,
                    logradouro = :logradouro,
                    numero = :numero,
                    complemento = :complemento,
                    bairro = :bairro,
                    ponto_referencia = :ponto_referencia,
                    status = :status,
                    editavel = :editavel,
                    data_envio = :data_envio,
                    atualizado_em = CURRENT_TIMESTAMP
                WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        
        // Bind dos parâmetros
        foreach ($dados_cadastro as $key => $value) {
            if ($key === 'editavel') {
                $stmt->bindValue(':' . $key, $value, PDO::PARAM_BOOL);
            } elseif (is_int($value)) {
                $stmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
            } elseif ($value === null) {
                $stmt->bindValue(':' . $key, null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
            }
        }
        $stmt->bindValue(':id', $cadastro_id, PDO::PARAM_INT);
        
        $stmt->execute();
        
    } else {
        // INSERÇÃO
        
        // Adiciona campos adicionais para insert
        $dados_cadastro['beneficiario_id'] = $beneficiario_id;
        $dados_cadastro['cpf'] = $beneficiario_info['cpf'];
        $dados_cadastro['email'] = $beneficiario_info['email'];
        $dados_cadastro['protocolo'] = null; // Será gerado pelo trigger se status = enviado
        $dados_cadastro['ip_cadastro'] = $_SERVER['REMOTE_ADDR'] ?? null;
        $dados_cadastro['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? null;

        $sql = "INSERT INTO cadastros (
                    beneficiario_id, protocolo, caf, situacao,
                    nome, nome_social, cpf, data_nascimento, sexo, nacionalidade,
                    nome_mae, nome_pai, rg, orgao_emissor, uf_documento,
                    estado_id, municipio_id, email, telefone1, telefone2,
                    cep, logradouro, numero, complemento, bairro, ponto_referencia,
                    status, editavel, data_envio, ip_cadastro, user_agent
                ) VALUES (
                    :beneficiario_id, :protocolo, :caf, :situacao,
                    :nome, :nome_social, :cpf, :data_nascimento, :sexo, :nacionalidade,
                    :nome_mae, :nome_pai, :rg, :orgao_emissor, :uf_documento,
                    :estado_id, :municipio_id, :email, :telefone1, :telefone2,
                    :cep, :logradouro, :numero, :complemento, :bairro, :ponto_referencia,
                    :status, :editavel, :data_envio, :ip_cadastro, :user_agent
                )";
        
        $stmt = $pdo->prepare($sql);
        
        // Bind dos parâmetros
        foreach ($dados_cadastro as $key => $value) {
            if ($key === 'editavel') {
                $stmt->bindValue(':' . $key, $value, PDO::PARAM_BOOL);
            } elseif ($key === 'beneficiario_id' || $key === 'estado_id' || $key === 'municipio_id') {
                $stmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
            } elseif ($value === null || $value === '') {
                $stmt->bindValue(':' . $key, null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
            }
        }
        
        $stmt->execute();
        $cadastro_id = $pdo->lastInsertId('cadastros_id_seq');
    }

    // =============================
    // UPLOAD DE ARQUIVOS
    // =============================
    
    $pasta_uploads = '/var/www/uploads/';
    
    // Cria a pasta se não existir
    if (!is_dir($pasta_uploads)) {
        mkdir($pasta_uploads, 0755, true);
    }
    
    $documentos_enviados = $_FILES['documentos'] ?? [];
    $tipos_permitidos = ['cpf', 'rg', 'comprovante_residencia', 'foto_3x4', 'caf', 'outros'];
    
    foreach ($tipos_permitidos as $tipo_documento) {
        if (isset($documentos_enviados['name'][$tipo_documento]) && 
            !empty($documentos_enviados['name'][$tipo_documento]) &&
            $documentos_enviados['error'][$tipo_documento] === UPLOAD_ERR_OK) {
            
            $nome_original = $documentos_enviados['name'][$tipo_documento];
            $tamanho = $documentos_enviados['size'][$tipo_documento];
            $tmp_name = $documentos_enviados['tmp_name'][$tipo_documento];
            $mime_type = mime_content_type($tmp_name);
            
            // Validação do tamanho (máx 10MB)
            if ($tamanho > 10485760) {
                throw new Exception("O arquivo {$nome_original} excede o tamanho máximo de 10MB.");
            }
            
            // Validação do tipo MIME
            $mimes_permitidos = [
                'image/jpeg', 'image/png', 'image/jpg', 'image/gif',
                'application/pdf'
            ];
            
            if (!in_array($mime_type, $mimes_permitidos)) {
                throw new Exception("O arquivo {$nome_original} não é um formato válido.");
            }
            
            // Gera nome único para o arquivo
            $extensao = pathinfo($nome_original, PATHINFO_EXTENSION);
            $nome_arquivo = $cadastro_id . '_' . $tipo_documento . '_' . time() . '.' . $extensao;
            $caminho_completo = $pasta_uploads . $nome_arquivo;
            
            // Move o arquivo
            if (!move_uploaded_file($tmp_name, $caminho_completo)) {
                throw new Exception("Erro ao salvar o arquivo {$nome_original}.");
            }
            
            // Calcula hash SHA256 do arquivo
            $hash_arquivo = hash_file('sha256', $caminho_completo);
            
            // Verifica se já existe arquivo deste tipo para este cadastro
            $stmtCheckArquivo = $pdo->prepare(
                "SELECT id FROM arquivos_cadastro 
                 WHERE cadastro_id = :cadastro_id AND tipo_documento = :tipo_documento"
            );
            $stmtCheckArquivo->execute([
                'cadastro_id' => $cadastro_id,
                'tipo_documento' => $tipo_documento
            ]);
            
            if ($arquivo_antigo = $stmtCheckArquivo->fetch()) {
                // Atualiza o registro existente
                $stmtUpdateArquivo = $pdo->prepare(
                    "UPDATE arquivos_cadastro SET
                        nome_arquivo = :nome_arquivo,
                        nome_original = :nome_original,
                        caminho_arquivo = :caminho_arquivo,
                        tamanho_bytes = :tamanho_bytes,
                        mime_type = :mime_type,
                        hash_arquivo = :hash_arquivo,
                        atualizado_em = CURRENT_TIMESTAMP
                     WHERE id = :id"
                );
                $stmtUpdateArquivo->execute([
                    'nome_arquivo' => $nome_arquivo,
                    'nome_original' => $nome_original,
                    'caminho_arquivo' => $caminho_completo,
                    'tamanho_bytes' => $tamanho,
                    'mime_type' => $mime_type,
                    'hash_arquivo' => $hash_arquivo,
                    'id' => $arquivo_antigo['id']
                ]);
            } else {
                // Insere novo registro
                $stmtArquivo = $pdo->prepare(
                    "INSERT INTO arquivos_cadastro (
                        cadastro_id, tipo_documento, nome_arquivo, nome_original,
                        caminho_arquivo, tamanho_bytes, mime_type, hash_arquivo
                    ) VALUES (
                        :cadastro_id, :tipo_documento, :nome_arquivo, :nome_original,
                        :caminho_arquivo, :tamanho_bytes, :mime_type, :hash_arquivo
                    )"
                );
                $stmtArquivo->execute([
                    'cadastro_id' => $cadastro_id,
                    'tipo_documento' => $tipo_documento,
                    'nome_arquivo' => $nome_arquivo,
                    'nome_original' => $nome_original,
                    'caminho_arquivo' => $caminho_completo,
                    'tamanho_bytes' => $tamanho,
                    'mime_type' => $mime_type,
                    'hash_arquivo' => $hash_arquivo
                ]);
            }
        }
    }

    // Confirma a transação
    $pdo->commit();

    // Redireciona com mensagem de sucesso
    if ($acao === 'enviar') {
        header('Location: dashboard.php?status=salvo_sucesso&enviado=true');
    } else {
        header('Location: dashboard.php?status=salvo_sucesso');
    }
    exit();

} catch (Exception $e) {
    // Desfaz a transação em caso de erro
    $pdo->rollBack();
    
    // Log do erro (em produção, salvar em arquivo de log)
    error_log("Erro no processamento do formulário: " . $e->getMessage());
    
    // Redireciona com mensagem de erro
    header('Location: dashboard.php?erro=' . urlencode($e->getMessage()));
    exit();
}