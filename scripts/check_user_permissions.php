<?php
/**
 * Script para verificar permissões de um usuário específico
 * Execute: php scripts/check_user_permissions.php EMAIL
 */

require_once __DIR__ . '/../config/database.php';

$email = $argv[1] ?? null;

if (!$email) {
    echo "Uso: php scripts/check_user_permissions.php EMAIL\n";
    echo "Exemplo: php scripts/check_user_permissions.php jhones.mendes.ti@gmail.com\n";
    exit(1);
}

try {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT id, nome, email, funcao, permissoes FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        echo "❌ Usuário não encontrado: $email\n";
        exit(1);
    }
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "👤 Informações do Usuário\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "ID: {$usuario['id']}\n";
    echo "Nome: {$usuario['nome']}\n";
    echo "Email: {$usuario['email']}\n";
    echo "Função: {$usuario['funcao']}\n";
    echo "\n";
    
    echo "📋 Permissões no Banco (RAW):\n";
    echo $usuario['permissoes'] ?? 'NULL';
    echo "\n\n";
    
    if ($usuario['permissoes']) {
        $permissoes = json_decode($usuario['permissoes'], true);
        
        if (is_array($permissoes)) {
            echo "✅ Permissões Decodificadas:\n";
            foreach ($permissoes as $perm) {
                echo "  - $perm\n";
            }
            echo "\n";
            
            // Mapear para nomes legíveis
            $modulos = [
                'dashboard' => '📊 Dashboard',
                'igrejas' => '⛪ Igrejas',
                'membros' => '👥 Membros',
                'celulas' => '📍 Células',
                'presenca' => '✓ Presença',
                'visitantes' => '👥 Visitantes',
                'cursos' => '📚 Cursos',
                'eventos' => '🎉 Eventos',
                'novo_convertido' => '✨ Novo Convertido',
                'configuracoes' => '⚙️ Configurações'
            ];
            
            echo "🔓 Acesso Permitido a:\n";
            if (in_array('*', $permissoes)) {
                echo "  ⭐ TODAS AS PÁGINAS (Admin)\n";
            } else {
                foreach ($permissoes as $perm) {
                    $nome = $modulos[$perm] ?? $perm;
                    echo "  ✓ $nome\n";
                }
            }
        } else {
            echo "❌ Erro ao decodificar permissões\n";
        }
    } else {
        echo "⚠️  Nenhuma permissão configurada!\n";
    }
    
    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
