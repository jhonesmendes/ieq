<?php
// Teste simples da página de redefinição

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Carregar configurações
require_once 'config/database.php';
require_once 'config/seguranca.php';
require_once 'config/auth.php';

echo "<!DOCTYPE html>";
echo "<html>";
echo "<head><meta charset='UTF-8'><title>Teste</title></head>";
echo "<body>";

echo "<h2>Teste de Redefinição de Senha</h2>";

// Simular GET
$_GET['token'] = '5461a731854eaeb6c4545b38fdb9a4f50c556f4abb574465b9a2c93d953f57fc';
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

echo "<p>Token: " . htmlspecialchars($token) . "</p>";

if (!empty($token)) {
    echo "<p>Validando token...</p>";
    
    if (function_exists('validar_token_recuperacao')) {
        $resultado = validar_token_recuperacao($token);
        
        echo "<pre>";
        echo "Resultado da validação:\n";
        var_dump($resultado);
        echo "</pre>";
        
        if ($resultado['valido']) {
            echo "<p style='color: green;'>✓ Token válido!</p>";
            echo "<p>Email: " . htmlspecialchars($resultado['email']) . "</p>";
        } else {
            echo "<p style='color: red;'>✗ Token inválido!</p>";
            echo "<p>Motivo: " . $resultado['error_tipo'] . "</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Função validar_token_recuperacao não existe!</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Token não fornecido!</p>";
}

echo "</body></html>";
?>
