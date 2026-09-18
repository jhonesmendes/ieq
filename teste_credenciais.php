<?php
/**
 * Teste de credenciais SMTP
 */

header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/config/email.php';

echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <title>Teste Credenciais SMTP</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; max-width: 700px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #667eea; color: white; }
        code { background: #f0f0f0; padding: 5px; border-radius: 3px; }
        .ok { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔐 Teste de Credenciais SMTP</h1>
        
        <h2>Variáveis de Ambiente</h2>
        <table>
            <tr>
                <th>Variável</th>
                <th>Valor</th>
                <th>Status</th>
            </tr>";

$vars = [
    'EMAIL_DE_ENDERECO' => EMAIL_DE_ENDERECO,
    'SMTP_HOST' => SMTP_HOST,
    'SMTP_PORT' => SMTP_PORT,
    'SMTP_USER' => SMTP_USER,
    'SMTP_PASS' => SMTP_PASS ? '***' . substr(SMTP_PASS, -4) : '(vazio)',
    'SMTP_SEGURANCA' => SMTP_SEGURANCA,
];

foreach ($vars as $nome => $valor) {
    $status = empty($valor) ? '<span class="error">❌ Vazio</span>' : '<span class="ok">✅ OK</span>';
    $display = $nome === 'SMTP_PASS' ? $valor : (is_string($valor) ? htmlspecialchars($valor) : $valor);
    echo "<tr><td><code>$nome</code></td><td>$display</td><td>$status</td></tr>";
}

echo "</table>";

// Teste de conexão
echo "<h2>Teste de Conexão Simples</h2>";

$protocol = (SMTP_PORT == 465) ? 'ssl' : 'tcp';
$context = stream_context_create([
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
    ]
]);

echo "<p>Conectando a <code>" . SMTP_HOST . ":" . SMTP_PORT . "</code> (" . $protocol . ")...</p>";

$smtp = @stream_socket_client(
    "$protocol://" . SMTP_HOST . ":" . SMTP_PORT,
    $errno, 
    $errstr, 
    5,
    STREAM_CLIENT_CONNECT,
    $context
);

if ($smtp) {
    echo "<p class='ok'>✅ Conectado com sucesso!</p>";
    
    // Ler resposta
    $response = fgets($smtp, 1024);
    echo "<p><code>Resposta: " . htmlspecialchars($response) . "</code></p>";
    
    // Enviar EHLO
    fputs($smtp, "EHLO localhost\r\n");
    stream_set_timeout($smtp, 5);
    
    $has_starttls = false;
    while ($line = fgets($smtp, 1024)) {
        if (strpos($line, 'STARTTLS') !== false) {
            $has_starttls = true;
        }
        if (substr($line, 3, 1) === ' ') break;
    }
    
    echo "<p>STARTTLS suportado: " . ($has_starttls ? '<span class=\"ok\">✅ SIM</span>' : '<span class=\"warning\">⚠️ NÃO</span>') . "</p>";
    
    // Testar base64
    echo "<h2>Teste de Base64</h2>";
    $user_encoded = base64_encode(SMTP_USER);
    $pass_encoded = base64_encode(SMTP_PASS);
    
    echo "<table>";
    echo "<tr><th>Tipo</th><th>Valor Original</th><th>Base64</th></tr>";
    echo "<tr><td>Usuário</td><td><code>" . htmlspecialchars(SMTP_USER) . "</code></td><td><code>" . htmlspecialchars($user_encoded) . "</code></td></tr>";
    echo "<tr><td>Senha</td><td><code>***</code></td><td><code>" . htmlspecialchars($pass_encoded) . "</code></td></tr>";
    echo "</table>";
    
    fclose($smtp);
} else {
    echo "<p class='error'>❌ Falha na conexão: $errstr ($errno)</p>";
}

echo "</div></body></html>";
?>
