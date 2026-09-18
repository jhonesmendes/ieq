<?php
/**
 * Teste de arquivo e carregamento de config
 */

header('Content-Type: text/plain; charset=utf-8');

echo "=== DIAGNÓSTICO DE ARQUIVO ===\n\n";

// Teste 1: Arquivo existe?
$api_file = __DIR__ . '/../api/index.php';
echo "1. Arquivo API: $api_file\n";
echo "   Existe? " . (file_exists($api_file) ? "✅ SIM" : "❌ NÃO") . "\n";
if (file_exists($api_file)) {
    echo "   Tamanho: " . filesize($api_file) . " bytes\n";
    echo "   Legível? " . (is_readable($api_file) ? "✅ SIM" : "❌ NÃO") . "\n";
}

echo "\n2. Arquivo .env: ";
$env_file = __DIR__ . '/../.env';
echo $env_file . "\n";
echo "   Existe? " . (file_exists($env_file) ? "✅ SIM" : "❌ NÃO") . "\n";
if (file_exists($env_file)) {
    $env_content = file_get_contents($env_file);
    echo "   Tamanho: " . strlen($env_content) . " bytes\n";
    echo "   Primeiras linhas:\n";
    $lines = explode("\n", $env_content);
    foreach (array_slice($lines, 0, 3) as $line) {
        echo "   > " . trim($line) . "\n";
    }
}

echo "\n3. Config database: ";
$db_file = __DIR__ . '/../config/database.php';
echo $db_file . "\n";
echo "   Existe? " . (file_exists($db_file) ? "✅ SIM" : "❌ NÃO") . "\n";

echo "\n4. Config auth: ";
$auth_file = __DIR__ . '/../config/auth.php';
echo $auth_file . "\n";
echo "   Existe? " . (file_exists($auth_file) ? "✅ SIM" : "❌ NÃO") . "\n";

echo "\n5. Config email: ";
$email_file = __DIR__ . '/../config/email.php';
echo $email_file . "\n";
echo "   Existe? " . (file_exists($email_file) ? "✅ SIM" : "❌ NÃO") . "\n";

echo "\n=== TESTE: Tentar conectar ao BD ===\n\n";
try {
    require_once __DIR__ . '/../config/database.php';
    $db = getDB();
    echo "✅ Banco de dados conectado!\n";
    
    // Tentar query simples
    $result = $db->query("SELECT 1")->fetchAll();
    echo "✅ Query simples funcionou\n";
    
} catch (Exception $e) {
    echo "❌ Erro ao conectar:\n";
    echo $e->getMessage() . "\n";
}

echo "\n=== TESTE: Verificar função de recuperação ===\n\n";
try {
    require_once __DIR__ . '/../config/auth.php';
    if (function_exists('gerar_token_recuperacao')) {
        echo "✅ Função gerar_token_recuperacao existe\n";
    } else {
        echo "❌ Função gerar_token_recuperacao NÃO existe\n";
    }
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}

?>
