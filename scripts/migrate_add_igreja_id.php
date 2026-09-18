<?php
/**
 * Script de migração para adicionar campo igreja_id na tabela celulas
 */

require_once __DIR__ . '/../config/database.php';

echo "=== Iniciando migração: Adicionar igreja_id na tabela celulas ===\n\n";

try {
    $db = getDB();
    
    // Verificar se a coluna já existe
    if (DB_DRIVER === 'sqlite') {
        $result = $db->query("PRAGMA table_info(celulas)");
        $columns = $result->fetchAll(PDO::FETCH_ASSOC);
        
        $colunaExiste = false;
        foreach ($columns as $column) {
            if ($column['name'] === 'igreja_id') {
                $colunaExiste = true;
                break;
            }
        }
        
        if ($colunaExiste) {
            echo "✓ Coluna 'igreja_id' já existe na tabela celulas.\n";
        } else {
            echo "→ Adicionando coluna 'igreja_id' na tabela celulas...\n";
            $db->exec("ALTER TABLE celulas ADD COLUMN igreja_id INTEGER REFERENCES igrejas(id)");
            echo "✓ Coluna 'igreja_id' adicionada com sucesso!\n";
        }
        
    } else {
        // MySQL
        $result = $db->query("SHOW COLUMNS FROM celulas LIKE 'igreja_id'");
        
        if ($result->rowCount() > 0) {
            echo "✓ Coluna 'igreja_id' já existe na tabela celulas.\n";
        } else {
            echo "→ Adicionando coluna 'igreja_id' na tabela celulas...\n";
            $db->exec("ALTER TABLE celulas ADD COLUMN igreja_id INT NULL AFTER nome, ADD FOREIGN KEY (igreja_id) REFERENCES igrejas(id)");
            echo "✓ Coluna 'igreja_id' adicionada com sucesso!\n";
        }
    }
    
    echo "\n=== Migração concluída com sucesso! ===\n";
    
} catch (PDOException $e) {
    echo "\n✗ ERRO na migração: " . $e->getMessage() . "\n";
    exit(1);
}
?>
