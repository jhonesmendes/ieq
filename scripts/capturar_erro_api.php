<?php
/**
 * Capturar erro da API
 */

header('Content-Type: text/plain; charset=utf-8');

echo "=== CAPTURANDO ERRO DA API ===\n\n";

// Habilitar todos os erros
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Buffer para capturar output
ob_start();

// Simular requisição
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['email'] = 'test@example.com';
$_POST['csrf_token'] = 'teste';
$_GET['acao'] = 'solicitar_recuperacao_senha';

// Headers
header('Content-Type: application/json; charset=utf-8');

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "Incluindo API...\n";
echo "---\n";

try {
    include __DIR__ . '/../api/index.php';
} catch (Throwable $e) {
    echo "❌ EXCEÇÃO CAPTURADA:\n";
    echo "Mensagem: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString();
}

$output = ob_get_clean();

echo $output;

// Se tiver error log, mostrar
echo "\n---\n";
echo "\n=== VERIFICANDO ERROR LOG ===\n";

// Tentar encontrar onde está o error log
$possible_logs = [
    ini_get('error_log'),
    __DIR__ . '/../data/error.log',
    __DIR__ . '/../logs/error.log',
    sys_get_temp_dir() . '/php_errors.log',
    'C:/xampp02/apache/logs/error.log',
    'C:/xampp02/php/logs/error.log',
];

foreach ($possible_logs as $log_file) {
    if (!empty($log_file) && file_exists($log_file)) {
        echo "✅ Log encontrado: $log_file\n";
        echo "Últimas 10 linhas:\n";
        echo "---\n";
        $lines = file($log_file);
        $ultimas = array_slice($lines, -10);
        foreach ($ultimas as $line) {
            echo $line;
        }
        echo "---\n";
    }
}
?>
