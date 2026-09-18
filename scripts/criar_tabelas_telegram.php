<?php
/**
 * Script para criar tabelas necessárias para o Telegram
 * Execute uma única vez no banco de dados
 */

require_once __DIR__ . '/database.php';

$db = getDB();

try {
    // Tabela de configurações
    $db->exec("CREATE TABLE IF NOT EXISTS configuracoes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        chave VARCHAR(255) UNIQUE NOT NULL,
        valor LONGTEXT,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_chave (chave)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Tabela de log de erros
    $db->exec("CREATE TABLE IF NOT EXISTS log_erros (
        id INT AUTO_INCREMENT PRIMARY KEY,
        titulo VARCHAR(255) NOT NULL,
        mensagem LONGTEXT,
        arquivo VARCHAR(500),
        linha INT,
        usuario_id INT,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_usuario (usuario_id),
        INDEX idx_data (criado_em),
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Tabela de log de notificações Telegram
    $db->exec("CREATE TABLE IF NOT EXISTS log_telegran (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tipo VARCHAR(100) NOT NULL,
        titulo VARCHAR(255),
        mensagem LONGTEXT,
        enviado TINYINT(1) DEFAULT 0,
        resposta JSON,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_tipo (tipo),
        INDEX idx_enviado (enviado)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    echo "✅ Tabelas criadas com sucesso!\n";
    echo "   - configuracoes\n";
    echo "   - log_erros\n";
    echo "   - log_telegran\n";
    
} catch (Exception $e) {
    echo "❌ Erro ao criar tabelas: " . $e->getMessage() . "\n";
}
?>
