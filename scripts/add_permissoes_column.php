<?php
/**
 * Script para adicionar coluna de permissões à tabela usuarios
 * Execute: php scripts/add_permissoes_column.php
 */

require_once __DIR__ . '/../config/database.php';

echo "===================================\n";
echo "  Adicionar Coluna de Permissões\n";
echo "===================================\n\n";

try {
    $db = getDB();
    
    // Verificar se a coluna já existe
    if (DB_DRIVER === 'mysql') {
        $stmt = $db->query("SHOW COLUMNS FROM usuarios LIKE 'permissoes'");
        $columnExists = $stmt->fetch();
    } else {
        $stmt = $db->query("PRAGMA table_info(usuarios)");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $columnExists = false;
        foreach ($columns as $column) {
            if ($column['name'] === 'permissoes') {
                $columnExists = true;
                break;
            }
        }
    }
    
    if ($columnExists) {
        echo "⚠️  Coluna 'permissoes' já existe na tabela usuarios.\n";
        echo "Nenhuma alteração necessária.\n\n";
        exit(0);
    }
    
    // Adicionar coluna
    echo "Adicionando coluna 'permissoes'...\n";
    if (DB_DRIVER === 'mysql') {
        $db->exec("ALTER TABLE usuarios ADD COLUMN permissoes TEXT DEFAULT NULL");
    } else {
        $db->exec("ALTER TABLE usuarios ADD COLUMN permissoes TEXT DEFAULT NULL");
    }
    
    echo "✅ Coluna 'permissoes' adicionada com sucesso!\n\n";
    
    // Definir permissões padrão para admin existente
    echo "Configurando permissões padrão para administradores...\n";
    $permissoesAdmin = json_encode(['*']); // * = todas as permissões
    $db->exec("UPDATE usuarios SET permissoes = '$permissoesAdmin' WHERE funcao = 'admin'");
    
    echo "✅ Permissões configuradas!\n\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Administradores têm acesso total.\n";
    echo "Configure permissões específicas para outros usuários.\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
