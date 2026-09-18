<?php
/**
 * TESTE SIMPLES - Validar que callback_google.php é acessível
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Teste de Acesso ao Callback</h2>";

// Teste 1: Verificar se o arquivo existe
echo "<h3>1. Arquivo callback_google.php existe?</h3>";
if (file_exists('callback_google.php')) {
    echo "✅ SIM<br>";
} else {
    echo "❌ NÃO<br>";
}

// Teste 2: Verificar se pode incluir google_oauth.php
echo "<h3>2. Arquivo google_oauth.php pode ser incluído?</h3>";
try {
    require_once '../config/google_oauth.php';
    if (function_exists('gerar_url_login_google')) {
        echo "✅ SIM - Função gerar_url_login_google disponível<br>";
    } else {
        echo "❌ Funções não encontradas<br>";
    }
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "<br>";
}

// Teste 3: Simular callback com parâmetros do Google
echo "<h3>3. Teste de Callback Simulado</h3>";
echo "<p>Acesse este URL para simular um callback do Google:</p>";
echo '<a href="callback_google.php?code=test_code_123&scope=openid+email+profile&authuser=0&prompt=none" target="_blank">';
echo 'https://ieqroo.com.br/pages/callback_google.php?code=test_code_123&scope=...';
echo '</a><br>';

// Teste 4: Mostrar GOOGLE_REDIRECT_URI configurado
echo "<h3>4. GOOGLE_REDIRECT_URI Configurado</h3>";
if (defined('GOOGLE_REDIRECT_URI')) {
    echo "✅ " . GOOGLE_REDIRECT_URI . "<br>";
} else {
    echo "❌ Não definido<br>";
}

// Teste 5: Verificar headers
echo "<h3>5. Headers Enviados</h3>";
echo "Content-Type: " . (isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : 'Não definido') . "<br>";
echo "Content-Length: " . (isset($_SERVER['CONTENT_LENGTH']) ? $_SERVER['CONTENT_LENGTH'] : 'Não definido') . "<br>";

?>
