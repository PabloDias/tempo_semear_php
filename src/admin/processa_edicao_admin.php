<?php
// src/admin/processa_edicao_admin.php (VERSÃO COMPLETA)

session_start();
require_once '../db.php';

if (!isset($_SESSION['admin_usuario_id']) || $_SESSION['admin_usuario_perfil'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?error=acesso_negado');
    exit();
}

$cadastro_id = (int)$_POST['cadastro_id'];
$justificativa = trim($_POST['justificativa']);
$admin_id = $_SESSION['admin_usuario_id'];

if (empty($cadastro_id) || empty($justificativa) || strlen($justificativa) < 10) {
    header('Location: editar_cadastro.php?id=' . $cadastro_id . '&error=justificativa_invalida');
    exit();
}

$pdo->beginTransaction();

try {
    // Busca dados antigos
    $stmt_antigo = $pdo->prepare("SELECT * FROM cadastros WHERE id = :id");
    $stmt_antigo->execute(['id' => $cadastro_id]);
    $dados_antigos = $stmt_antigo->fetch(PDO::FETCH_ASSOC);

    if (!$dados_antigos) {
        throw new Exception("Cadastro não encontrado.");
    }
    
    // Coleta os novos dados do formulário
    $dados_novos = [
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
        'uf_documento' => !empty($_POST['uf_documento']) ? $_POST['uf_documento'] : null,
        
        // Localização
        'estado_id' => !empty($_POST['estado_id']) ? (int)$_POST['estado_id'] : null,
        'municipio_id' => (int)$_POST['municipio_id'],
        
        // Contato
        'telefone1' => trim($_POST['telefone1']),
        'telefone2' => trim($_POST['telefone2'] ?? ''),
        
        // Endereço
        'cep' => preg_replace('/[^0-9]/', '', $_POST['cep'] ?? ''),
        'logradouro' => trim($_POST['logradouro'] ?? ''),
        'numero' => trim($_POST['numero'] ?? ''),
        'complemento' => trim($_POST['complemento'] ?? ''),
        'bairro' => trim($_POST['bairro'] ?? ''),
        'ponto_referencia' => trim($_POST['ponto_referencia'] ?? ''),
        
        // Auditoria
        'atualizado_por' => $admin_id
    ];

    // Validação básica
    if (empty($dados_novos['nome']) || empty($dados_novos['data_nascimento']) || 
        empty($dados_novos['sexo']) || empty($dados_novos['nome_mae']) ||
        empty($dados_novos['rg']) || empty($dados_novos['orgao_emissor']) ||
        empty($dados_novos['telefone1']) || empty($dados_novos['municipio_id'])) {
        throw new Exception("Por favor, preencha todos os campos obrigatórios.");
    }

    // Compara dados e registra no histórico apenas o que mudou
    $stmt_historico = $pdo->prepare(
        "INSERT INTO historico_edicoes (cadastro_id, usuario_id, campo_alterado, valor_anterior, valor_novo, justificativa, ip_alteracao)
         VALUES (:cadastro_id, :usuario_id, :campo, :anterior, :novo, :justificativa, :ip)"
    );

    $campos_modificados = 0;
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;

    foreach ($dados_novos as $campo => $valor_novo) {
        // Ignora o campo atualizado_por na comparação
        if ($campo === 'atualizado_por') {
            continue;
        }
        
        // Verifica se o campo existe nos dados antigos e se mudou
        if (array_key_exists($campo, $dados_antigos)) {
            $valor_antigo = $dados_antigos[$campo];
            
            // Compara valores (considerando NULL)
            if (($valor_antigo !== $valor_novo) && 
                !(empty($valor_antigo) && empty($valor_novo))) {
                
                $stmt_historico->execute([
                    'cadastro_id' => $cadastro_id,
                    'usuario_id' => $admin_id,
                    'campo' => $campo,
                    'anterior' => $valor_antigo,
                    'novo' => $valor_novo,
                    'justificativa' => $justificativa,
                    'ip' => $ip
                ]);
                
                $campos_modificados++;
            }
        }
    }

    // Se nenhum campo foi modificado, não precisa atualizar
    if ($campos_modificados === 0) {
        $pdo->rollBack();
        header('Location: ver_cadastro.php?id=' . $cadastro_id . '&status=sem_alteracoes');
        exit();
    }

    // Atualiza o cadastro na tabela principal
    $sql_update = "UPDATE cadastros SET
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
                       atualizado_por = :atualizado_por,
                       atualizado_em = CURRENT_TIMESTAMP
                   WHERE id = :cadastro_id";
    
    $stmt_update = $pdo->prepare($sql_update);
    
    // Bind dos parâmetros
    foreach ($dados_novos as $key => $value) {
        if (is_int($value) && $key !== 'atualizado_por') {
            $stmt_update->bindValue(':' . $key, $value, PDO::PARAM_INT);
        } elseif ($value === null || $value === '') {
            $stmt_update->bindValue(':' . $key, null, PDO::PARAM_NULL);
        } else {
            $stmt_update->bindValue(':' . $key, $value, PDO::PARAM_STR);
        }
    }
    $stmt_update->bindValue(':cadastro_id', $cadastro_id, PDO::PARAM_INT);
    
    $stmt_update->execute();

    // Registra no log do sistema
    $pdo->prepare(
        "INSERT INTO logs_sistema (nivel, modulo, acao, descricao, usuario_tipo, usuario_id, cadastro_id, ip_origem)
         VALUES ('WARNING', 'cadastros', 'Edição de cadastro por admin', :descricao, 'interno', :usuario_id, :cadastro_id, :ip)"
    )->execute([
        'descricao' => "Admin {$_SESSION['admin_usuario_nome']} editou {$campos_modificados} campo(s) do cadastro {$dados_antigos['protocolo']}. Justificativa: {$justificativa}",
        'usuario_id' => $admin_id,
        'cadastro_id' => $cadastro_id,
        'ip' => $ip
    ]);

    $pdo->commit();

    header('Location: ver_cadastro.php?id=' . $cadastro_id . '&status=edicao_sucesso&campos=' . $campos_modificados);
    exit();

} catch (Exception $e) {
    $pdo->rollBack();
    
    // Log do erro
    error_log("Erro ao editar cadastro {$cadastro_id}: " . $e->getMessage());
    
    header('Location: editar_cadastro.php?id=' . $cadastro_id . '&error=' . urlencode($e->getMessage()));
    exit();
}