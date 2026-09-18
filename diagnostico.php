<?php
/**
 * DIAGNÓSTICO DE CONFIGURAÇÃO DO SERVIDOR
 * Execute em: https://jhonescosta.com/ieq/diagnostico.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 0);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico - IEQ</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #0066cc;
            padding-bottom: 10px;
        }
        h2 {
            color: #0066cc;
            margin-top: 30px;
        }
        .section {
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-left: 4px solid #0066cc;
            border-radius: 4px;
        }
        .success {
            color: #28a745;
            font-weight: bold;
        }
        .error {
            color: #dc3545;
            font-weight: bold;
        }
        .warning {
            color: #ffc107;
            font-weight: bold;
        }
        .info {
            color: #17a2b8;
            font-weight: bold;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        table td:first-child {
            font-weight: bold;
            width: 40%;
            background: #f0f0f0;
        }
        pre {
            background: #f4f4f4;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Diagnóstico de Configuração - IEQ</h1>
        <p><small>Gerado em: <?php echo date('d/m/Y H:i:s'); ?></small></p>

        <?php
        try {
            // ════════════════════════════════════════════════════════════
            // 1. CARREGAR CONFIGURAÇÕES
            // ════════════════════════════════════════════════════════════
            
            require_once 'config/database.php';
            
            echo '<h2>📋 Configuração Carregada</h2>';
            echo '<div class="section">';
            echo '<table>';
            echo '<tr><td>DB_DRIVER</td><td><code>' . DB_DRIVER . '</code></td></tr>';
            echo '<tr><td>DB_HOST</td><td><code>' . DB_HOST . '</code></td></tr>';
            echo '<tr><td>DB_PORT</td><td><code>' . DB_PORT . '</code></td></tr>';
            echo '<tr><td>DB_NAME</td><td><code>' . DB_NAME . '</code></td></tr>';
            echo '<tr><td>DB_USER</td><td><code>' . DB_USER . '</code></td></tr>';
            echo '<tr><td>DB_PASS</td><td>' . (DB_PASS === '' ? '<span class="warning">(vazio)</span>' : '<span class="info">(preenchido)</span>') . '</td></tr>';
            echo '<tr><td>DB_PATH (SQLite)</td><td><code>' . DB_PATH . '</code></td></tr>';
            echo '</table>';
            
            if (DB_DRIVER === 'mysql') {
                echo '<p><span class="success">✓ Configurado para MySQL</span></p>';
            } else {
                echo '<p><span class="warning">⚠ Configurado para SQLite</span></p>';
            }
            
            echo '</div>';
            
            // ════════════════════════════════════════════════════════════
            // 2. VERIFICAR ARQUIVO .env
            // ════════════════════════════════════════════════════════════
            
            echo '<h2>🔑 Arquivo .env</h2>';
            echo '<div class="section">';
            
            $env_file = __DIR__ . '/.env';
            if (file_exists($env_file)) {
                echo '<p><span class="success">✓ Arquivo .env encontrado</span></p>';
                echo '<pre>' . htmlspecialchars(file_get_contents($env_file)) . '</pre>';
            } else {
                echo '<p><span class="warning">⚠ Arquivo .env NÃO encontrado</span></p>';
                echo '<p>Usando valores padrão do <code>config/database.php</code></p>';
            }
            
            echo '</div>';
            
            // ════════════════════════════════════════════════════════════
            // 3. TESTAR CONEXÃO COM BANCO
            // ════════════════════════════════════════════════════════════
            
            echo '<h2>🔌 Teste de Conexão</h2>';
            echo '<div class="section">';
            
            try {
                $db = getDB();
                echo '<p><span class="success">✓ Conexão bem-sucedida com ' . DB_DRIVER . ' </span></p>';
                
                // Se MySQL, listar bancos de dados
                if (DB_DRIVER === 'mysql') {
                    echo '<p><strong>Bancos de dados disponíveis:</strong></p>';
                    $stmt = $db->query("SHOW DATABASES");
                    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    echo '<pre>' . implode("\n", $databases) . '</pre>';
                    
                    // Listar tabelas
                    echo '<p><strong>Tabelas em "' . DB_NAME . '":</strong></p>';
                    $stmt = $db->query("SHOW TABLES");
                    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    if (count($tables) > 0) {
                        echo '<pre>' . implode(", ", $tables) . '</pre>';
                    } else {
                        echo '<p><span class="error">❌ Nenhuma tabela encontrada!</span></p>';
                    }
                } else {
                    // SQLite
                    echo '<p><strong>Banco SQLite:</strong></p>';
                    if (file_exists(DB_PATH)) {
                        echo '<p><span class="success">✓ Arquivo SQLite encontrado</span></p>';
                        $size = filesize(DB_PATH);
                        echo '<p>Tamanho: ' . ($size / 1024 / 1024) . ' MB</p>';
                        
                        // Listar tabelas
                        $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table'");
                        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        if (count($tables) > 0) {
                            echo '<p><strong>Tabelas:</strong></p>';
                            echo '<pre>' . implode(", ", $tables) . '</pre>';
                        }
                    } else {
                        echo '<p><span class="error">❌ Arquivo SQLite não encontrado em: ' . DB_PATH . '</span></p>';
                    }
                }
            } catch (Exception $e) {
                echo '<p><span class="error">❌ Erro na conexão:</span></p>';
                echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
            }
            
            echo '</div>';
            
            // ════════════════════════════════════════════════════════════
            // 4. ARQUIVO DE LOG
            // ════════════════════════════════════════════════════════════
            
            echo '<h2>📋 Log de Erros (últimas 20 linhas)</h2>';
            echo '<div class="section">';
            
            $error_log = __DIR__ . '/api/error_log';
            if (file_exists($error_log)) {
                $lines = file($error_log);
                $last_lines = array_slice($lines, max(0, count($lines) - 20));
                echo '<pre>' . htmlspecialchars(implode('', $last_lines)) . '</pre>';
            } else {
                echo '<p><span class="warning">⚠ Arquivo de log não encontrado</span></p>';
            }
            
            echo '</div>';
            
            // ════════════════════════════════════════════════════════════
            // 5. RECOMENDAÇÕES
            // ════════════════════════════════════════════════════════════
            
            echo '<h2>💡 Recomendações</h2>';
            echo '<div class="section">';
            
            if (DB_DRIVER === 'sqlite') {
                echo '<p><span class="warning">⚠ AVISO: Seu servidor está usando SQLite, mas o código foi alterado para MySQL!</span></p>';
                echo '<p><strong>Soluções possíveis:</strong></p>';
                echo '<ol>';
                echo '<li><strong>Opção 1 (Recomendada):</strong> Criar arquivo <code>.env</code> na raiz com:<br>';
                echo '<pre>DB_DRIVER=mysql
DB_HOST=localhost
DB_PORT=3306
DB_NAME=ieq
DB_USER=root
DB_PASS=</pre>';
                echo '<p>E criar o banco MySQL ainda se não existir.</p>';
                echo '</li>';
                echo '<li><strong>Opção 2:</strong> Voltar para SQLite (menos recomendado) alterando:<br>';
                echo '<code>define(\'DB_DRIVER\', \'sqlite\')</code><br>em <code>config/database.php</code>';
                echo '</li>';
                echo '</ol>';
            } else if (DB_DRIVER === 'mysql') {
                echo '<p><span class="success">✓ Configurado para MySQL - Está correto!</span></p>';
            }
            
            echo '</div>';
            
        } catch (Exception $e) {
            echo '<div class="section">';
            echo '<p><span class="error">ERRO CRÍTICO:</span></p>';
            echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>
