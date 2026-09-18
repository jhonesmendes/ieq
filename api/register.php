<?php
/**
 * API de Registro
 * Processa criação de novas contas
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

// CORS Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// Responder a preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

require_once '../config/database.php';
require_once '../config/auth.php';

try {
    initDatabase();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'Método não permitido. Use POST.'
        ]);
        exit;
    }
    
    // Obter dados do POST
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $senha = trim($_POST['senha'] ?? '');
    
    error_log("Registro attempt: email=$email");
    
    // Validação
    if (empty($nome) || empty($email) || empty($senha)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'Nome, email e senha são obrigatórios'
        ]);
        exit;
    }
    
    // Validar email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'Email inválido'
        ]);
        exit;
    }
    
    // Validar comprimento da senha
    if (strlen($senha) < 6) {
        http_response_code(400);
        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'A senha deve ter no mínimo 6 caracteres'
        ]);
        exit;
    }
    
    // Verificar se email já existe em ambas as tabelas
    $db = getDB();
    
    // Verificar em usuarios
    $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'Este email já está cadastrado'
        ]);
        exit;
    }
    
    // Verificar em cadastros_pendentes
    $stmt = $db->prepare("SELECT id FROM cadastros_pendentes WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'Este email já está aguardando aprovação'
        ]);
        exit;
    }
    
    // Registrar novo usuário - SALVA A SENHA CADASTRADA
    $senha_hash = password_hash($senha, PASSWORD_BCRYPT);
    
    try {
        $db->beginTransaction();
        
        // Determinar a sintaxe correta para data/hora conforme o driver
        $timestamp_sql = (DB_DRIVER === 'mysql') ? 'NOW()' : "datetime('now')";
        
        // Inserir em cadastros_pendentes (aguarda aprovação admin) - INCLUI SENHA
        $sql = "
            INSERT INTO cadastros_pendentes (nome, email, senha, foto_url, status, criado_em, atualizado_em)
            VALUES (?, ?, ?, ?, 'pendente', " . $timestamp_sql . ", " . $timestamp_sql . ")
        ";
        $stmt = $db->prepare($sql);
        
        if (!$stmt->execute([$nome, $email, $senha_hash, null])) {
            throw new Exception("Erro ao criar cadastro pendente: " . implode(', ', $stmt->errorInfo()));
        }
        
        $cadastro_id = $db->lastInsertId();
        
        $db->commit();
        
        error_log("New user registered (awaiting approval): email=$email, cadastro_id=$cadastro_id");
        
        http_response_code(201);
        echo json_encode([
            'status' => 'sucesso',
            'mensagem' => 'Conta criada com sucesso! Aguarde a aprovação do administrador.',
            'dados' => [
                'cadastro_id' => $cadastro_id,
                'email' => $email
            ]
        ]);
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error registering user: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'Erro ao criar conta. Tente novamente.'
        ]);
        exit;
    }
} catch (Exception $e) {
    error_log("Register error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Erro ao processar registro'
    ]);
    exit;
}
?>
