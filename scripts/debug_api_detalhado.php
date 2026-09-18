<?php
/**
 * Debug detalhado - LOG todas as linhas da API
 */

header('Content-Type: text/plain; charset=utf-8');

// Registrar TUDO
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Criar arquivo de log para capturar output
$log_file = __DIR__ . '/../data/debug_api.log';
ini_set('error_log', $log_file);

// Funcao para logar
function log_debug($msg) {
    global $log_file;
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - " . $msg . "\n", FILE_APPEND);
    echo $msg . "\n";
}

log_debug("=== INICIANDO DEBUG ===");

// Simular requisição
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['email'] = 'test@example.com';
$_POST['csrf_token'] = 'teste';
$_GET['acao'] = 'solicitar_recuperacao_senha';

log_debug("Requisição simulada: email=test@example.com");

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    log_debug("Iniciando sessão...");
    session_start();
    log_debug("✅ Sessão iniciada");
}

// Incluir com erro handler
try {
    log_debug("Incluindo /api/index.php...");
    
    // Usar output buffering para capturar output
    ob_start();
    
    include __DIR__ . '/../api/index.php';
    
    $output = ob_get_clean();
    
    log_debug("✅ API incluída com sucesso");
    log_debug("Output da API: " . substr($output, 0, 200));
    echo $output;
    
} catch (ParseError $e) {
    log_debug("❌ PARSE ERROR: " . $e->getMessage());
    log_debug("Arquivo: " . $e->getFile());
    log_debug("Linha: " . $e->getLine());
    ob_end_clean();
} catch (Throwable $e) {
    log_debug("❌ ERRO: " . $e->getMessage());
    log_debug("Classe: " . get_class($e));
    log_debug("Arquivo: " . $e->getFile());
    log_debug("Linha: " . $e->getLine());
    ob_end_clean();
}

log_debug("=== FIM DEBUG ===\n");

echo "\n\n=== CONTEÚDO DO LOG ===\n";
if (file_exists($log_file)) {
    echo file_get_contents($log_file);
}
?>
