<?php
/**
 * API de Login Simples
 * Processa autenticação sem CORS issues
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
    $email = trim($_POST['email'] ?? $_GET['email'] ?? '');
    $senha = trim($_POST['senha'] ?? $_GET['senha'] ?? '');
    
    // Se não houver dados em POST, tenta JSON
    if (empty($email) || empty($senha)) {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $email = trim($data['email'] ?? '');
        $senha = trim($data['senha'] ?? '');
    }
    
    error_log("Login attempt: email=$email");
    
    if (empty($email) || empty($senha)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'Email e senha são obrigatórios'
        ]);
        exit;
    }
    
    // Tentar autenticar
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        error_log("User not found: $email");
        http_response_code(401);
        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'Email ou senha inválidos'
        ]);
        exit;
    }
    
    // Verificar senha
    if (!password_verify($senha, $usuario['senha'])) {
        error_log("Password mismatch for: $email");
        http_response_code(401);
        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'Email ou senha inválidos'
        ]);
        exit;
    }
    
    // Verificar aprovação
    if ($usuario['status_aprovacao'] !== 'aprovado') {
        error_log("User not approved: $email (status: {$usuario['status_aprovacao']})");
        http_response_code(403);
        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'Sua conta ainda não foi aprovada pelo administrador'
        ]);
        exit;
    }
    
    // Fazer login
    $_SESSION['user_id'] = $usuario['id'];
    $_SESSION['user_nome'] = $usuario['nome'];
    $_SESSION['user_email'] = $usuario['email'];
    $_SESSION['user_funcao'] = $usuario['funcao'];
    
    error_log("Login success: {$usuario['email']} (ID: {$usuario['id']})");
    
    http_response_code(200);
    echo json_encode([
        'status' => 'sucesso',
        'mensagem' => 'Login realizado com sucesso',
        'dados' => [
            'user_id' => $usuario['id'],
            'user_nome' => $usuario['nome'],
            'user_email' => $usuario['email'],
            'user_funcao' => $usuario['funcao']
        ]
    ]);
    exit;
    
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Erro ao processar login'
    ]);
    exit;
}
?>
