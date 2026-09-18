<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: monospace; background: #f0f0f0; margin: 20px; }
        .box { background: white; padding: 20px; margin: 10px 0; border-left: 4px solid #667eea; }
        .error { border-left-color: #f44336; color: #f44336; }
        .success { border-left-color: #4caf50; color: #4caf50; }
        .info { border-left-color: #2196f3; color: #2196f3; }
        h2 { color: #333; margin-top: 0; }
        code { background: #e0e0e0; padding: 2px 5px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>🔧 Diagnóstico - Recuperação de Senha</h1>";

$errors = [];
$info = [];

// 1. Verificar SESSION
$box_class = 'info';
if (!isset($_SESSION)) {
    $errors[] = 'SESSION não iniciada';
    $box_class = 'error';
} else {
    $info[] = 'SESSION ok';
}
echo "<div class='box {$box_class}'><h2>1. SESSION</h2><p>" . (empty($errors) ? 'OK' : implode(', ', $errors)) . "</p></div>";
$errors = [];

// 2. Verificar arquivos de config
echo "<div class='box info'><h2>2. Arquivos de Configuração</h2>";
$configs = ['database.php', 'seguranca.php', 'auth.php', 'email.php'];
foreach ($configs as $config) {
    $path = __DIR__ . '/config/' . $config;
    if (file_exists($path)) {
        echo "✓ <code>{$config}</code> existe<br>";
    } else {
        echo "✗ <code>{$config}</code> FALTANDO<br>";
    }
}
echo "</div>";

// 3. Verificar funções
echo "<div class='box info'><h2>3. Funções Necessárias</h2>";
require_once 'config/database.php';
require_once 'config/seguranca.php';
require_once 'config/auth.php';

$funcoes = ['validar_token_recuperacao', 'redefinir_senha', 'gerar_token_recuperacao'];
foreach ($funcoes as $func) {
    if (function_exists($func)) {
        echo "✓ <code>{$func}()</code> existe<br>";
    } else {
        echo "✗ <code>{$func}()</code> FALTANDO<br>";
    }
}
echo "</div>";

// 4. Verificar Token
echo "<div class='box info'><h2>4. Token Fornecido</h2>";
$token = isset($_GET['token']) ? trim($_GET['token']) : '';
if (!empty($token)) {
    echo "Token: <code>" . substr($token, 0, 20) . "...</code><br><br>";
    
    // Tentar validar
    $resultado = validar_token_recuperacao($token);
    echo "<strong>Resultado:</strong><br>";
    echo "<pre>";
    var_dump($resultado);
    echo "</pre>";
    
    if ($resultado['valido']) {
        echo "<p style='color: green; font-weight: bold;'>✓ Token é VÁLIDO!</p>";
        echo "Email: <code>" . htmlspecialchars($resultado['email']) . "</code><br>";
        echo "Usuario ID: <code>" . $resultado['usuario_id'] . "</code>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>✗ Token é INVÁLIDO</p>";
        echo "Motivo: <code>" . $resultado['error_tipo'] . "</code>";
        if (!empty($resultado['mensagem'])) {
            echo "<br>Mensagem: <code>" . htmlspecialchars($resultado['mensagem']) . "</code>";
        }
    }
} else {
    echo "<p style='color: orange;'>⚠ Token não fornecido como GET</p>";
    echo "Use: <code>?token=XXXX</code>";
}
echo "</div>";

// 5. Teste de inclusão da página
echo "<div class='box info'><h2>5. Teste de Inclusão da Página</h2>";
echo "<p>Tentando incluir <code>pages/redefinir_senha.php</code>...</p>";
echo "<pre>";
try {
    ob_start();
    include 'pages/redefinir_senha.php';
    $output = ob_get_clean();
    echo "✓ Inclusão bem-sucedida<br>";
    echo "Output length: " . strlen($output) . " bytes<br>";
    echo "First 200 chars: " . htmlspecialchars(substr($output, 0, 200)) . "...";
} catch (Exception $e) {
    echo "✗ ERRO: " . $e->getMessage();
}
echo "</pre>";
echo "</div>";

echo "</body></html>";
?>
