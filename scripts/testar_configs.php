<?php
/**
 * Testar cada config individualmente
 */

header('Content-Type: text/plain; charset=utf-8');

// Configurar error reporting para máximo
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== TESTANDO CADA CONFIG ===\n\n";

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$configs = [
    'database' => '../config/database.php',
    'seguranca' => '../config/seguranca.php',
    'auth' => '../config/auth.php',
    'membros' => '../config/membros.php',
    'celulas' => '../config/celulas.php',
    'presenca' => '../config/presenca.php',
    'eventos' => '../config/eventos.php',
    'cursos' => '../config/cursos.php',
    'igrejas' => '../config/igrejas.php',
    'visitantes' => '../config/visitantes.php',
    'permissoes' => '../config/permissoes.php',
    'upload' => '../config/upload.php',
];

foreach ($configs as $nome => $caminho) {
    $arquivo_completo = __DIR__ . '/' . $caminho;
    echo "Testando: $nome ($arquivo_completo)\n";
    
    if (!file_exists($arquivo_completo)) {
        echo "  ❌ ARQUIVO NÃO EXISTE!\n";
        continue;
    }
    
    try {
        require_once $arquivo_completo;
        echo "  ✅ Incluído com sucesso\n";
    } catch (Throwable $e) {
        echo "  ❌ ERRO: " . $e->getMessage() . "\n";
        echo "  No arquivo: " . $e->getFile() . "\n";
        echo "  Linha: " . $e->getLine() . "\n";
        break; // Parar no primeiro erro
    }
}

echo "\n✅ Todos os configs foram testados\n";

// Agora testar se a API pode ser incluída
echo "\n=== TESTANDO API ===\n";

// Simular requisição POST
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['email'] = 'test@example.com';
$_POST['csrf_token'] = 'teste';
$_GET['acao'] = 'solicitar_recuperacao_senha';

try {
    // Incluir a API
    include __DIR__ . '/../api/index.php';
    echo "✅ API incluída com sucesso\n";
} catch (Throwable $e) {
    echo "❌ ERRO ao incluir API:\n";
    echo "Mensagem: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
}
?>
