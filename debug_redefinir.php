<?php
// Debug da página de redefinição

// Mostrar todos os erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

echo "<h2>Debug - Redefinir Senha</h2>";
echo "<pre>";

// Verificar GET
echo "=== GET ===\n";
var_dump($_GET);

// Verificar SESSION
echo "\n=== SESSION ===\n";
var_dump($_SESSION);

// Tentar incluir as configurações
echo "\n=== Testando Inclusões ===\n";

try {
    require_once __DIR__ . '/config/database.php';
    echo "✓ database.php incluído\n";
} catch (Exception $e) {
    echo "✗ Erro em database.php: " . $e->getMessage() . "\n";
}

try {
    require_once __DIR__ . '/config/auth.php';
    echo "✓ auth.php incluído\n";
} catch (Exception $e) {
    echo "✗ Erro em auth.php: " . $e->getMessage() . "\n";
}

// Testar validação de token
echo "\n=== Testando Token ===\n";
$token = isset($_GET['token']) ? trim($_GET['token']) : '';
echo "Token recebido: " . $token . "\n";

if (!empty($token) && function_exists('validar_token_recuperacao')) {
    $resultado = validar_token_recuperacao($token);
    echo "Resultado da validação:\n";
    var_dump($resultado);
} else {
    echo "Token vazio ou função não existe\n";
}

echo "</pre>";
?>
