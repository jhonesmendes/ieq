<?php
/**
 * Teste BÁSICO - Apenas verifica se a API consegue responder
 */

// Teste 1: Verificar se conseguimos executar a API diretamente
echo "=== TESTE 1: Incluir a API diretamente ===\n\n";

// Simular uma requisição POST
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['email'] = 'test@example.com';
$_POST['csrf_token'] = 'teste';
$_GET['acao'] = 'solicitar_recuperacao_senha';

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "Incluindo /api/index.php...\n";
echo "---\n";

ob_start();
try {
    include __DIR__ . '/../api/index.php';
    $output = ob_get_clean();
    echo $output;
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ ERRO ao incluir API:\n";
    echo $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString();
}

echo "\n---\n";
?>
