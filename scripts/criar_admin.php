<?php
/**
 * Script para criar usuário administrador no banco de dados
 * Execute: php scripts/criar_admin.php
 */

require_once __DIR__ . '/../config/database.php';

echo "===================================\n";
echo "  Criar Usuário Administrador\n";
echo "===================================\n\n";

// Dados do admin padrão
$nome = "Administrador";
$email = "admin@ieq.com";
$senha = "admin123"; // Senha padrão - MUDAR após primeiro login
$funcao = "admin";

try {
    $db = getDB();
    
    // Verificar se já existe um admin com este email
    $stmt = $db->prepare("SELECT id, nome FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuarioExistente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usuarioExistente) {
        echo "⚠️  Usuário já existe!\n";
        echo "Nome: {$usuarioExistente['nome']}\n";
        echo "Email: $email\n\n";
        
        echo "Deseja resetar a senha deste usuário? (s/n): ";
        $resposta = trim(fgets(STDIN));
        
        if (strtolower($resposta) === 's') {
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE usuarios SET senha = ?, funcao = ? WHERE email = ?");
            $stmt->execute([$senhaHash, $funcao, $email]);
            
            echo "\n✅ Senha resetada com sucesso!\n";
            echo "Email: $email\n";
            echo "Senha: $senha\n";
            echo "\n⚠️  IMPORTANTE: Altere a senha após o primeiro login!\n";
        } else {
            echo "\n❌ Operação cancelada.\n";
        }
    } else {
        // Criar novo usuário admin
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("
            INSERT INTO usuarios (nome, email, senha, funcao, criado_em) 
            VALUES (?, ?, ?, ?, datetime('now'))
        ");
        
        $stmt->execute([$nome, $email, $senhaHash, $funcao]);
        
        echo "✅ Usuário administrador criado com sucesso!\n\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "📧 Email: $email\n";
        echo "🔑 Senha: $senha\n";
        echo "👑 Função: $funcao\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        echo "⚠️  IMPORTANTE: Altere a senha após o primeiro login!\n";
        echo "Acesse: http://localhost/\n\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Erro ao criar usuário: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";
