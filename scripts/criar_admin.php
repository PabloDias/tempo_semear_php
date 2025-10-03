<?php
/**
 * Script para criar o primeiro usuário administrador
 * Executar: php scripts/criar_admin.php
 */

require_once __DIR__ . '/../src/db.php';

echo "==============================================\n";
echo "  CRIAÇÃO DE USUÁRIO ADMINISTRADOR\n";
echo "==============================================\n\n";

// Solicita dados do administrador
echo "Nome completo: ";
$nome = trim(fgets(STDIN));

echo "CPF (apenas números): ";
$cpf = trim(fgets(STDIN));

echo "Email: ";
$email = trim(fgets(STDIN));

echo "Matrícula (opcional): ";
$matricula = trim(fgets(STDIN));

echo "Senha: ";
$senha = trim(fgets(STDIN));

echo "Confirmar senha: ";
$senha_confirmar = trim(fgets(STDIN));

// Validações
if (empty($nome) || empty($cpf) || empty($email) || empty($senha)) {
    die("\n❌ Erro: Todos os campos obrigatórios devem ser preenchidos.\n");
}

if ($senha !== $senha_confirmar) {
    die("\n❌ Erro: As senhas não conferem.\n");
}

if (strlen($senha) < 8) {
    die("\n❌ Erro: A senha deve ter no mínimo 8 caracteres.\n");
}

if (strlen($cpf) !== 11 || !ctype_digit($cpf)) {
    die("\n❌ Erro: CPF deve ter 11 dígitos numéricos.\n");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("\n❌ Erro: Email inválido.\n");
}

try {
    // Verifica se já existe usuário com este CPF ou email
    $stmt = $pdo->prepare("SELECT id FROM usuarios_internos WHERE cpf = :cpf OR email = :email");
    $stmt->execute(['cpf' => $cpf, 'email' => $email]);
    
    if ($stmt->fetch()) {
        die("\n❌ Erro: Já existe um usuário com este CPF ou email.\n");
    }
    
    // Gera hash da senha usando Argon2id (mais seguro)
    echo "\nGerando hash da senha...\n";
    $senha_hash = password_hash($senha, PASSWORD_ARGON2ID);
    
    // Insere o usuário
    $stmt = $pdo->prepare(
        "INSERT INTO usuarios_internos (
            nome, cpf, email, senha_hash, perfil, matricula,
            ativo, pode_exportar, criado_em
        ) VALUES (
            :nome, :cpf, :email, :senha_hash, 'admin', :matricula,
            TRUE, TRUE, CURRENT_TIMESTAMP
        ) RETURNING id"
    );
    
    $stmt->execute([
        'nome' => $nome,
        'cpf' => $cpf,
        'email' => $email,
        'senha_hash' => $senha_hash,
        'matricula' => $matricula ?: null
    ]);
    
    $usuario_id = $stmt->fetchColumn();
    
    echo "\n✅ SUCESSO! Usuário administrador criado.\n";
    echo "==============================================\n";
    echo "ID: $usuario_id\n";
    echo "Nome: $nome\n";
    echo "CPF: $cpf\n";
    echo "Email: $email\n";
    echo "Perfil: admin\n";
    echo "==============================================\n";
    echo "\n⚠️  IMPORTANTE: Guarde estas credenciais em local seguro!\n";
    echo "⚠️  Altere a senha após o primeiro login.\n\n";
    
    // Registra no log
    $pdo->prepare(
        "INSERT INTO logs_sistema (nivel, modulo, acao, descricao, usuario_tipo, usuario_id)
         VALUES ('INFO', 'usuarios_internos', 'Criação de admin via script', :descricao, 'interno', :usuario_id)"
    )->execute([
        'descricao' => "Usuário administrador criado: $nome (CPF: $cpf)",
        'usuario_id' => $usuario_id
    ]);
    
} catch (PDOException $e) {
    echo "\n❌ Erro ao criar usuário: " . $e->getMessage() . "\n";
    exit(1);
}