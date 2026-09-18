<?php
// Script de teste de conexão com banco de dados
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Teste de Conexão com Banco de Dados</h2>";

// Carregar configurações
require_once 'config/database.php';

echo "<pre>";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "CONFIGURAÇÕES CARREGADAS:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "DB_DRIVER: " . DB_DRIVER . "\n";
echo "DB_HOST: " . DB_HOST . "\n";
echo "DB_PORT: " . DB_PORT . "\n";
echo "DB_NAME: " . DB_NAME . "\n";
echo "DB_USER: " . DB_USER . "\n";
echo "DB_PASS: " . (DB_PASS === '' ? '(vazio)' : '(preenchido)') . "\n\n";

// Teste de conexão
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TESTANDO CONEXÃO:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

try {
    if (DB_DRIVER === 'mysql') {
        // Teste 1: Conectar sem banco de dados
        echo "✓ Teste 1: Conectando ao servidor MySQL...\n";
        $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', DB_HOST, DB_PORT);
        $db = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);
        echo "✓ Conexão com servidor MySQL bem-sucedida!\n\n";
        
        // Teste 2: Listar bancos de dados
        echo "✓ Teste 2: Listando bancos de dados...\n";
        $stmt = $db->query("SHOW DATABASES");
        $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "Bancos disponíveis: " . implode(', ', $databases) . "\n\n";
        
        // Teste 3: Verificar se banco 'ieq' existe
        echo "✓ Teste 3: Procurando banco '" . DB_NAME . "'...\n";
        if (in_array(DB_NAME, $databases)) {
            echo "✓ Banco '" . DB_NAME . "' encontrado!\n\n";
            
            // Teste 4: Selecionar e testar tabelas
            echo "✓ Teste 4: Acessando banco '" . DB_NAME . "'...\n";
            $db->exec("USE `" . DB_NAME . "`");
            
            $stmt = $db->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "Tabelas existentes: " . (count($tables) > 0 ? implode(', ', $tables) : 'Nenhuma') . "\n\n";
            
            if (count($tables) === 0) {
                echo "⚠️ AVISO: Banco de dados vazio! Precisa executar inicDatabase()\n";
                echo "You should run: initDatabase() to create tables\n";
            }
        } else {
            echo "❌ Banco '" . DB_NAME . "' NÃO ENCONTRADO!\n";
            echo "Você precisa criar o banco. Execute:\n";
            echo "CREATE DATABASE `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n\n";
            
            echo "✓ Tentando criar automaticamente...\n";
            $db->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "✓ Banco criado com sucesso!\n";
        }
    } else {
        // SQLite
        echo "Tipo: SQLite\n";
        echo "Caminho: " . DB_PATH . "\n";
        if (file_exists(DB_PATH)) {
            echo "✓ Arquivo SQLite encontrado\n";
        } else {
            echo "❌ Arquivo SQLite não encontrado\n";
        }
    }
    
    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ TESTE CONCLUÍDO COM SUCESSO!\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
} catch (PDOException $e) {
    echo "\n❌ ERRO NA CONEXÃO:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo $e->getMessage() . "\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
} catch (Exception $e) {
    echo "\n❌ ERRO GERAL:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo $e->getMessage() . "\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
}

echo "</pre>";
?>
