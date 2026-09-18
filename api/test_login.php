<?php
// Teste rápido de API com muito logging  
session_start();

header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../config/seguranca.php';
require_once '../config/auth.php';

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['email'] = 'admin@ieq.com';
$_POST['senha'] = 'admin123';

// Gera um token válido nesta sessão
$token_valido = obter_csrf_token();
$_POST['csrf_token'] = $token_valido;

initDatabase();

echo json_encode([
    'debug' => [
        'session_id' => session_id(),
        'token_recebido' => substr($_POST['csrf_token'], 0, 20),
        'token_esperado' => substr($_SESSION['csrf_token'] ?? 'NONE', 0, 20),
        'csrf_valido' => validar_csrf_token($_POST['csrf_token']),
        'email' => $_POST['email'],
        'senha_chars' => strlen($_POST['senha'])
    ]
]);
?>
