<?php
/**
 * Teste da API - Versão simplificada com logs
 */

header('Content-Type: application/json; charset=utf-8');

error_log("=== API TESTE INICIADO ===");

try {
    error_log("1. Iniciando sessão");
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    error_log("2. Compilando .env");
    
    // ============ CARREGAR .ENV ============
    $env_file = __DIR__ . '/../.env';
    if (file_exists($env_file)) {
        $env_lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($env_lines as $line) {
            if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }
    error_log("3. .env carregado");
    
    error_log("4. Incluindo configs");
    require_once '../config/database.php';
    error_log("   ✅ database.php");
    
    require_once '../config/seguranca.php';
    error_log("   ✅ seguranca.php");
    
    require_once '../config/auth.php';
    error_log("   ✅ auth.php");
    
    error_log("5. Inicializando BD");
    initDatabase();
    error_log("   ✅ BD inicializado");
    
    error_log("6. Obtendo ação");
    $acao = $_GET['acao'] ?? $_POST['acao'] ?? 'teste';
    error_log("   Ação: $acao");
    
    error_log("7. Obtendo método");
    $method = $_SERVER['REQUEST_METHOD'];
    error_log("   Método: $method");
    
    if ($acao === 'solicitar_recuperacao_senha' && $method === 'POST') {
        error_log("8. Processando recuperação de senha");
        
        error_log("   a. Verificar rate limit");
        verificar_rate_limit('recuperacao_senha');
        error_log("   ✅ Rate limit OK");
        
        error_log("   b. Obter email");
        $email = $_POST['email'] ?? '';
        error_log("   Email: $email");
        
        if (empty($email)) {
            echo json_encode(['status' => 'erro', 'mensagem' => 'Email vazio']);
            exit;
        }
        
        error_log("   c. Validar email");
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['status' => 'erro', 'mensagem' => 'Email inválido']);
            exit;
        }
        error_log("   ✅ Email válido");
        
        error_log("   d. Buscar usuário");
        $db = getDB();
        $stmt = $db->prepare("SELECT id, nome FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        error_log("   Usuário encontrado: " . ($usuario ? 'SIM' : 'NÃO'));
        
        error_log("   e. Responder");
        echo json_encode([
            'status' => 'sucesso',
            'mensagem' => 'Email processado com sucesso',
            'usuario_encontrado' => !empty($usuario)
        ]);
        exit;
    }
    
    error_log("9. Retornando resposta padrão");
    echo json_encode([
        'status' => 'ok',
        'mensagem' => 'API funcionando',
        'acao' => $acao,
        'metodo' => $method
    ]);
    
} catch (Throwable $e) {
    error_log("❌ ERRO: " . $e->getMessage());
    error_log("   Arquivo: " . $e->getFile());
    error_log("   Linha: " . $e->getLine());
    
    http_response_code(500);
    echo json_encode([
        'status' => 'erro',
        'mensagem' => $e->getMessage(),
        'arquivo' => $e->getFile(),
        'linha' => $e->getLine()
    ]);
}

error_log("=== FIM ===\n");
?>
