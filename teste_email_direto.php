<?php
/**
 * Teste de Email - Enviar para usuário específico
 */

header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/email.php';

echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <title>Teste de Email</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; max-width: 600px; }
        button { background: #667eea; color: white; border: none; padding: 12px 20px; border-radius: 4px; cursor: pointer; font-size: 14px; }
        button:hover { background: #764ba2; }
        .log { background: #f0f0f0; padding: 15px; margin-top: 20px; border-radius: 4px; white-space: pre-wrap; font-family: monospace; font-size: 12px; max-height: 400px; overflow-y: auto; }
        .success { background: #e8f5e9; border-left: 4px solid #4caf50; }
        .error { background: #ffebee; border-left: 4px solid #f44336; }
        .info { background: #e3f2fd; border-left: 4px solid #2196f3; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>📧 Teste de Email</h1>";

// Email que vamos testar
$email_teste = 'mendes.jhones@gmail.com';

echo "<p><strong>Email a testar:</strong> $email_teste</p>";

echo "<button onclick=\"testarEmail()\">Enviar Email de Teste</button>";
echo "<button onclick=\"location.reload()\">Limpar</button>";

echo "<div id='resultado'></div>";

echo "<script>
function testarEmail() {
    const resultado = document.getElementById('resultado');
    resultado.innerHTML = '<div class=\"log info\">Enviando...</div>';
    
    fetch('/api/index.php?acao=solicitar_recuperacao_senha', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'email=$email_teste&csrf_token=teste'
    })
    .then(res => res.json())
    .then(data => {
        let classe = data.status === 'sucesso' ? 'success' : 'error';
        let html = '<div class=\"log ' + classe + '\">';
        html += '<strong>' + data.status.toUpperCase() + '</strong><br><br>';
        html += JSON.stringify(data, null, 2);
        html += '</div>';
        resultado.innerHTML = html;
        console.log('Resposta:', data);
    })
    .catch(err => {
        resultado.innerHTML = '<div class=\"log error\">❌ Erro: ' + err.message + '</div>';
        console.error(err);
    });
}
</script>";

echo "</div></body></html>";
?>
