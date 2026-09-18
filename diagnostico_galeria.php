<?php
/**
 * Teste de Diagnóstico - Galeria e Células
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir configs
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'config/seguranca.php';
require_once 'config/permissoes.php';

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Diagnóstico - Galeria e Células</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; margin: 10px 0; border-left: 4px solid #667eea; border-radius: 4px; }
        .error { border-left-color: #f44336; background: #ffebee; }
        .success { border-left-color: #4caf50; background: #f1f8e9; }
        h2 { margin-top: 0; }
        code { background: #e0e0e0; padding: 2px 5px; border-radius: 3px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 10px; border-bottom: 1px solid #ddd; }
        th { background: #f5f5f5; font-weight: bold; }
    </style>
</head>
<body>
    <h1>🔍 Diagnóstico - Galeria e Células</h1>";

// 1. Verificar ses são de test user
$box_class = (isset($_SESSION['user_id']) && $_SESSION['user_funcao'] === 'lider') ? 'success' : 'error';
echo "<div class='box {$box_class}'><h2>1. Sessão de Usuário</h2>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'NÃO LOGADO') . "<br>";
echo "Função: <code>" . ($_SESSION['user_funcao'] ?? 'N/A') . "</code><br>";
echo "</div>";

if (!isset($_SESSION['user_id'])) {
    echo "<p style='color: red;'><strong>❌ Erro:</strong> Usuário não logado. <a href='?page=login'>Fazer login</a></p>";
    echo "</body></html>";
    exit;
}

// 2. Verificar células do líder
echo "<div class='box'><h2>2. Células do Líder</h2>";

if (function_exists('obter_celulas_do_lider')) {
    $celula_ids = obter_celulas_do_lider($_SESSION['user_id']);
    
    if (!empty($celula_ids)) {
        echo "✓ " . count($celula_ids) . " célula(s) encontrada(s)<br>";
        
        // Buscar detalhes de cada célula
        $db = getDB();
        echo "<table>";
        echo "<tr><th>ID</th><th>Nome</th><th>Membros</th></tr>";
        
        $celulas = [];
        foreach ($celula_ids as $celula_id) {
            // Usar SELECT com * para conseguir todas as colunas
            $stmt = $db->prepare("SELECT * FROM celulas WHERE id = ?");
            $stmt->execute([$celula_id]);
            $celula = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($celula) {
                // Verificar qual coluna contém o nome
                $nome_celula = $celula['titulo'] ?? $celula['descricao'] ?? $celula['nome'] ?? 'Célula #' . $celula['id'];
                $celulas[] = $celula;
                
                // Contar membros
                $stmt_membros = $db->prepare("SELECT COUNT(*) as total FROM membros WHERE celula_id = ? AND status = 'ativo'");
                $stmt_membros->execute([$celula_id]);
                $total = $stmt_membros->fetchColumn();
                
                echo "<tr>";
                echo "<td>" . $celula['id'] . "</td>";
                echo "<td>" . htmlspecialchars($nome_celula) . "</td>";
                echo "<td>" . $total . "</td>";
                echo "</tr>";
            }
        }
        echo "</table>";
    } else {
        echo "❌ Nenhuma célula encontrada para este líder";
    }
} else {
    echo "❌ Função <code>obter_celulas_do_lider()</code> não existe!";
}

echo "</div>";

// 3. Verificar membros de cada célula
if (!empty($celulas)) {
    echo "<div class='box'><h2>3. Membros por Célula</h2>";
    
    $db = getDB();
    
    foreach ($celulas as $celula) {
        $nome_celula = $celula['titulo'] ?? $celula['descricao'] ?? $celula['nome'] ?? 'Célula #' . $celula['id'];
        
        $stmt = $db->prepare("
            SELECT m.id, u.nome, m.status, m.usuario_id 
            FROM membros m
            LEFT JOIN usuarios u ON m.usuario_id = u.id
            WHERE m.celula_id = ? 
            ORDER BY u.nome
        ");
        $stmt->execute([$celula['id']]);
        $membros = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>📍 " . htmlspecialchars($nome_celula) . " (" . count($membros) . " membros)</h3>";
        
        if (!empty($membros)) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Nome</th><th>Status</th></tr>";
            foreach ($membros as $membro) {
                echo "<tr>";
                echo "<td>" . $membro['id'] . "</td>";
                echo "<td>" . htmlspecialchars($membro['nome']) . "</td>";
                echo "<td><code>" . $membro['status'] . "</code></td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: orange;'>⚠ Nenhum membro nesta célula</p>";
        }
    }
    
    echo "</div>";
}

// 4. Verificar fotos/reuniões
if (!empty($celulas)) {
    echo "<div class='box'><h2>4. Reuniões com Fotos</h2>";
    
    $db = getDB();
    
    foreach ($celulas as $celula) {
        $nome_celula = $celula['titulo'] ?? $celula['descricao'] ?? $celula['nome'] ?? 'Célula #' . $celula['id'];
        
        $stmt = $db->prepare("SELECT id, data_reuniao, foto_url, observacoes FROM reunioes_celula WHERE celula_id = ? ORDER BY data_reuniao DESC LIMIT 5");
        $stmt->execute([$celula['id']]);
        $reunioes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>📸 " . htmlspecialchars($nome_celula) . " (" . count($reunioes) . " reuniões)</h3>";
        
        if (!empty($reunioes)) {
            echo "<table>";
            echo "<tr><th>Data</th><th>Foto</th><th>Observações</th></tr>";
            
            foreach ($reunioes as $reuniao) {
                echo "<tr>";
                echo "<td>" . substr($reuniao['data_reuniao'], 0, 10) . "</td>";
                echo "<td>" . (!empty($reuniao['foto_url']) ? "✓ Tem foto" : "✗ Sem foto") . "</td>";
                echo "<td>" . substr($reuniao['observacoes'] ?? '', 0, 50) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: orange;'>⚠ Nenhuma reunião registrada</p>";
        }
    }
    
    echo "</div>";
}

// 5. Verificar permissões
echo "<div class='box'><h2>5. Permissões</h2>";
echo "is_lider(): " . (function_exists('is_lider') && is_lider() ? "✓ Sim" : "✗ Não") . "<br>";
echo "is_admin(): " . (function_exists('is_admin') && is_admin() ? "✓ Sim" : "✗ Não") . "<br>";
echo "is_pastor(): " . (function_exists('is_pastor') && is_pastor() ? "✓ Sim" : "✗ Não") . "<br>";
echo "pode_acessar_modulo('galeria'): " . (function_exists('pode_acessar_modulo') && pode_acessar_modulo('galeria') ? "✓ Sim" : "✗ Não") . "<br>";
echo "</div>";

echo "</body></html>";
?>
