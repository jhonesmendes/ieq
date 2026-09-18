<?php
/**
 * Listar todos os usuários cadastrados
 */

header('Content-Type: text/html; charset=utf-8');

// Conectar ao BD
require_once __DIR__ . '/config/database.php';

echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <title>Usuários Cadastrados</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #667eea; color: white; }
        tr:hover { background: #f5f5f5; }
        .copy-btn { cursor: pointer; color: blue; text-decoration: underline; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>👥 Usuários Cadastrados</h1>";

try {
    $db = getDB();
    
    // Contar usuários
    $stmt = $db->query("SELECT COUNT(*) as total FROM usuarios");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total = $result['total'] ?? 0;
    
    echo "<p><strong>Total de usuários: $total</strong></p>";
    
    if ($total > 0) {
        echo "<table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Telefone</th>
                    <th>Função</th>
                    <th>Cadastrado em</th>
                </tr>
            </thead>
            <tbody>";
        
        $stmt = $db->query("SELECT id, nome, email, telefone, funcao, criado_em FROM usuarios ORDER BY criado_em DESC LIMIT 50");
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($usuarios as $user) {
            $email = htmlspecialchars($user['email']);
            echo "<tr>
                <td>{$user['id']}</td>
                <td>{$user['nome']}</td>
                <td title='Clique para copiar'><span class='copy-btn' onclick=\"navigator.clipboard.writeText('$email').then(() => alert('Email copiado!'))\">{$email}</span></td>
                <td>{$user['telefone']}</td>
                <td>{$user['funcao']}</td>
                <td>{$user['criado_em']}</td>
            </tr>";
        }
        
        echo "</tbody></table>";
    } else {
        echo "<p style='color: red;'><strong>⚠️ Nenhum usuário cadastrado!</strong></p>";
        echo "<p>Você precisa ter pelo menos um usuário no banco para testar a recuperação de senha.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>❌ Erro:</strong> " . $e->getMessage() . "</p>";
}

echo "</div></body></html>";
?>
