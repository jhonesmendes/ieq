<?php
/**
 * Logs de Email - Verificar se o email foi enviado
 */

header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/config/database.php';

echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <title>Logs de Email</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; }
        .section { margin: 20px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #667eea; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #667eea; color: white; }
        code { background: #f0f0f0; padding: 2px 5px; border-radius: 3px; font-family: monospace; }
        .log-file { background: #f0f0f0; padding: 15px; border-radius: 4px; white-space: pre-wrap; font-family: monospace; font-size: 12px; max-height: 300px; overflow-y: auto; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>📋 Logs do Sistema de Email</h1>";

// 1. Verificar tokens de recuperação criados
echo "<div class='section'>
    <h2>🔐 Tokens de Recuperação Criados</h2>";

try {
    $db = getDB();
    
    // Contar tokens
    $stmt = $db->query("SELECT COUNT(*) as total FROM recuperacao_senha");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total = $result['total'] ?? 0;
    
    echo "<p><strong>Total de tokens:</strong> $total</p>";
    
    if ($total > 0) {
        echo "<table>
            <thead><tr>
                <th>Token</th>
                <th>Email</th>
                <th>Criado em</th>
                <th>Expira em</th>
                <th>Usado?</th>
            </tr></thead>
            <tbody>";
        
        $stmt = $db->query("SELECT token, email, criado_em, expira_em, usado FROM recuperacao_senha ORDER BY criado_em DESC LIMIT 20");
        $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($tokens as $t) {
            $usado = $t['usado'] ? '✅ SIM' : '❌ NÃO';
            echo "<tr>
                <td><code>" . substr($t['token'], 0, 16) . "...</code></td>
                <td>{$t['email']}</td>
                <td>{$t['criado_em']}</td>
                <td>{$t['expira_em']}</td>
                <td>{$usado}</td>
            </tr>";
        }
        
        echo "</tbody></table>";
    } else {
        echo "<p style='color: orange;'><strong>⚠️</strong> Nenhum token de recuperação foi criado ainda.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>❌ Erro:</strong> " . $e->getMessage() . "</p>";
}

echo "</div>";

// 2. Verificar logs de segurança
echo "<div class='section'>
    <h2>🔒 Logs de Segurança</h2>";

$log_file = __DIR__ . '/data/seguranca.log';
if (file_exists($log_file)) {
    $linhas = file($log_file);
    $ultimas = array_slice($linhas, -20);
    
    echo "<p><strong>Últimas 20 linhas:</strong></p>";
    echo "<div class='log-file'>" . htmlspecialchars(implode('', $ultimas)) . "</div>";
} else {
    echo "<p style='color: orange;'><strong>⚠️</strong> Arquivo de log não encontrado: $log_file</p>";
}

echo "</div>";

// 3. Verificar arquivo de erro PHP
echo "<div class='section'>
    <h2>❌ Erros do PHP</h2>";

$log_php = __DIR__ . '/data/php_errors.log';
if (file_exists($log_php)) {
    $linhas = file($log_php);
    $ultimas = array_slice($linhas, -20);
    
    echo "<p><strong>Últimas 20 linhas:</strong></p>";
    echo "<div class='log-file'>" . htmlspecialchars(implode('', $ultimas)) . "</div>";
} else {
    echo "<p style='color: green;'><strong>✅</strong> Nenhum erro registrado</p>";
}

echo "</div>";

echo "</div></body></html>";
?>
