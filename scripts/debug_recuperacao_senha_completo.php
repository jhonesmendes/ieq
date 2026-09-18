<?php
/**
 * Página de Debug - Teste Completo de Recuperação de Senha
 */

session_start();

// Incluir config
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/email.php';

// Habilitar todos os erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>Debug - Recuperação de Senha</title>";
echo "<style>";
echo "body { font-family: monospace; margin: 20px; background: #f5f5f5; }";
echo ".test { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 5px solid #667eea; }";
echo ".ok { border-left-color: #4caf50; }";
echo ".erro { border-left-color: #f44336; background: #fff5f5; }";
echo ".titulo { font-size: 18px; font-weight: bold; margin-bottom: 10px; }";
echo ".conteudo { font-size: 13px; line-height: 1.6; color: #333; }";
echo ".codigo { background: #f0f0f0; padding: 10px; border-radius: 4px; overflow-x: auto; }";
echo "pre { margin: 0; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<h1>🔐 Debug - Recuperação de Senha</h1>";

// Teste 1: Banco de dados
echo "<div class='test ok'>";
echo "<div class='titulo'>✅ Teste 1: Banco de Dados</div>";
try {
    $db = getDB();
    echo "<div class='conteudo'>Conectado com sucesso<br>";
    echo "Driver: " . DB_DRIVER . "<br>";
    echo "Host: " . DB_HOST . "</div>";
} catch (Exception $e) {
    echo "<div class='test erro'>";
    echo "<div class='conteudo'>";
    echo "❌ Erro: " . $e->getMessage();
    echo "</div></div>";
    die();
}
echo "</div>";

// Teste 2: Tabela recuperacao_senha
echo "<div class='test'>";
echo "<div class='titulo'>✅ Teste 2: Tabela recuperacao_senha</div>";
try {
    $db = getDB();
    
    // Verificar estrutura da tabela
    if (DB_DRIVER === 'mysql') {
        $stmt = $db->query("DESCRIBE recuperacao_senha");
        $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $db->query("PRAGMA table_info(recuperacao_senha)");
        $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo "<div class='conteudo'>";
    echo "Tabela existe com " . count($colunas) . " colunas:<br>";
    echo "<div class='codigo'><pre>";
    foreach ($colunas as $col) {
        if (DB_DRIVER === 'mysql') {
            echo $col['Field'] . " (" . $col['Type'] . ")\n";
        } else {
            echo $col['name'] . " (" . $col['type'] . ")\n";
        }
    }
    echo "</pre></div>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div class='test erro'>";
    echo "<div class='conteudo'>";
    echo "❌ Erro: " . $e->getMessage();
    echo "</div></div>";
}
echo "</div>";

// Teste 3: Funções
echo "<div class='test ok'>";
echo "<div class='titulo'>✅ Teste 3: Funções</div>";
echo "<div class='conteudo'>";
echo "gerar_token_recuperacao: " . (function_exists('gerar_token_recuperacao') ? "✅ OK" : "❌ NÃO EXISTE") . "<br>";
echo "validar_token_recuperacao: " . (function_exists('validar_token_recuperacao') ? "✅ OK" : "❌ NÃO EXISTE") . "<br>";
echo "redefinir_senha: " . (function_exists('redefinir_senha') ? "✅ OK" : "❌ NÃO EXISTE") . "<br>";
echo "enviar_email_recuperacao_senha: " . (function_exists('enviar_email_recuperacao_senha') ? "✅ OK" : "❌ NÃO EXISTE") . "<br>";
echo "</div>";
echo "</div>";

// Teste 4: Email
echo "<div class='test ok'>";
echo "<div class='titulo'>✅ Teste 4: Configuração de Email</div>";
echo "<div class='conteudo'>";
$config = testar_configuracoes_email();
echo "SMTP Host: " . SMTP_HOST . "<br>";
echo "SMTP Port: " . SMTP_PORT . "<br>";
echo "SMTP User: " . SMTP_USER . "<br>";
echo "Email Configurado: " . ($config['smtp_configurado'] ? "✅ SIM" : "❌ NÃO") . "<br>";
echo "PHPMailer Disponível: " . ($config['phpmailer_disponivel'] ? "✅ SIM" : "⚠️ NÃO") . "<br>";
echo "mail() Disponível: " . ($config['mail_nativo_disponivel'] ? "✅ SIM" : "❌ NÃO") . "<br>";
echo "</div>";
echo "</div>";

// Teste 5: Testar gerar token
echo "<div class='test ok'>";
echo "<div class='titulo'>✅ Teste 5: Gerar Token de Teste</div>";
echo "<div class='conteudo'>";

// Verificar se há usuários
$db = getDB();
$stmt = $db->prepare("SELECT id, nome, email FROM usuarios LIMIT 1");
$stmt->execute();
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if ($usuario) {
    echo "Usuário encontrado: " . $usuario['nome'] . " (" . $usuario['email'] . ")<br><br>";
    
    try {
        // Tentar gerar token
        $resultado = gerar_token_recuperacao($usuario['email']);
        
        if ($resultado && isset($resultado['token'])) {
            echo "✅ Token gerado com sucesso!<br>";
            echo "<div class='codigo'><pre>";
            echo "Token: " . substr($resultado['token'], 0, 20) . "... (comprimento: " . strlen($resultado['token']) . ")\n";
            echo "Email: " . $resultado['email'] . "\n";
            echo "Usuario ID: " . $resultado['usuario_id'] . "\n";
            echo "Expira em: " . $resultado['expira_em'] . "\n";
            echo "</pre></div>";
        } else {
            echo "❌ Erro ao gerar token";
        }
    } catch (Exception $e) {
        echo "❌ Erro: " . $e->getMessage();
        echo "<div class='codigo'><pre>" . $e->getTraceAsString() . "</pre></div>";
    }
} else {
    echo "❌ Nenhum usuário encontrado no banco de dados";
}

echo "</div>";
echo "</div>";

// Teste 6: Verificar arquivos
echo "<div class='test ok'>";
echo "<div class='titulo'>✅ Teste 6: Arquivos Criados</div>";
echo "<div class='conteudo'>";
$arquivos = [
    'config/email.php' => __DIR__ . '/../config/email.php',
    'pages/recuperar_senha.php' => __DIR__ . '/../pages/recuperar_senha.php',
    'pages/redefinir_senha.php' => __DIR__ . '/../pages/redefinir_senha.php',
];

foreach ($arquivos as $nome => $caminho) {
    if (file_exists($caminho)) {
        $tamanho = filesize($caminho);
        echo "✅ $nome ($tamanho bytes)<br>";
    } else {
        echo "❌ $nome (NÃO EXISTE)<br>";
    }
}

echo "</div>";
echo "</div>";

// Teste 7: Log de segurança
echo "<div class='test ok'>";
echo "<div class='titulo'>✅ Teste 7: Arquivo de Log</div>";
echo "<div class='conteudo'>";
$log_file = __DIR__ . '/../data/seguranca.log';
if (file_exists($log_file)) {
    $tamanho = filesize($log_file);
    $linhas = count(file($log_file));
    echo "✅ Arquivo de log existe<br>";
    echo "Tamanho: " . human_filesize($tamanho) . "<br>";
    echo "Linhas: " . $linhas . "<br>";
    echo "<br>Últimas 5 linhas:<br>";
    echo "<div class='codigo'><pre>";
    $ultimasLinhas = array_slice(file($log_file), -5);
    echo htmlspecialchars(implode('', $ultimasLinhas));
    echo "</pre></div>";
} else {
    echo "❌ Arquivo de log não existe ainda (será criado ao tentar enviar email)";
}

function human_filesize($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB');
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    return round($bytes, $precision) . ' ' . $units[$i];
}

echo "</div>";
echo "</div>";

// Resumo
echo "<div class='test ok'>";
echo "<div class='titulo'>📋 Resumo e Próximos Passos</div>";
echo "<div class='conteudo'>";
echo "1. Se todos os testes passaram: O sistema está funcionando corretamente<br>";
echo "2. Abrir: <strong>/?page=recuperar_senha</strong><br>";
echo "3. Digitar email de um usuário<br>";
echo "4. Clicar 'Enviar Link de Recuperação'<br>";
echo "5. Verificar inbox de email<br>";
echo "6. Clicar link do email para redefinir senha<br>";
echo "</div>";
echo "</div>";

echo "</body>";
echo "</html>";
?>
