<?php
/**
 * Script de migração para criar tabela igrejas
 */

require_once __DIR__ . '/../config/database.php';

echo "=== Iniciando migração: Criar tabela igrejas ===\n\n";

try {
    $db = getDB();
    
    if (DB_DRIVER === 'mysql') {
        // Verificar se a tabela existe
        $result = $db->query("SHOW TABLES LIKE 'igrejas'");
        
        if ($result->rowCount() > 0) {
            echo "✓ Tabela 'igrejas' já existe.\n";
        } else {
            echo "→ Criando tabela 'igrejas'...\n";
            $db->exec("CREATE TABLE igrejas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome TEXT NOT NULL,
                endereco TEXT,
                bairro VARCHAR(150),
                cidade VARCHAR(150),
                telefone VARCHAR(50),
                email VARCHAR(255),
                pastor_presidente_id INT,
                pastor_auxiliar_id INT,
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (pastor_presidente_id) REFERENCES usuarios(id),
                FOREIGN KEY (pastor_auxiliar_id) REFERENCES usuarios(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            echo "✓ Tabela 'igrejas' criada com sucesso!\n";
        }
    } else {
        // SQLite
        $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='igrejas'");
        
        if ($result->rowCount() > 0) {
            echo "✓ Tabela 'igrejas' já existe.\n";
        } else {
            echo "→ Criando tabela 'igrejas'...\n";
            $db->exec("CREATE TABLE igrejas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nome TEXT NOT NULL,
                endereco TEXT,
                bairro TEXT,
                cidade TEXT,
                telefone TEXT,
                email TEXT,
                pastor_presidente_id INTEGER,
                pastor_auxiliar_id INTEGER,
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (pastor_presidente_id) REFERENCES usuarios(id),
                FOREIGN KEY (pastor_auxiliar_id) REFERENCES usuarios(id)
            )");
            echo "✓ Tabela 'igrejas' criada com sucesso!\n";
        }
    }
    
    echo "\n=== Migração concluída com sucesso! ===\n";
    
} catch (PDOException $e) {
    echo "\n✗ ERRO na migração: " . $e->getMessage() . "\n";
    exit(1);
}
?>
