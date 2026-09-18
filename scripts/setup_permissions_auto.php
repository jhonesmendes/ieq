<?php
/**
 * Script para configurar permissões automaticamente para todos os usuários
 * Execute: php scripts/setup_permissions_auto.php
 */

require_once __DIR__ . '/../config/database.php';

echo "===================================\n";
echo "  Configurar Permissões (Automático)\n";
echo "===================================\n\n";

try {
    $db = getDB();
    
    // Buscar todos os usuários
    $stmt = $db->query("SELECT id, nome, email, funcao FROM usuarios WHERE permissoes IS NULL OR permissoes = ''");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($usuarios)) {
        echo "✅ Todos os usuários já têm permissões configuradas.\n";
        exit(0);
    }
    
    echo "Usuários a configurar: " . count($usuarios) . "\n\n";
    
    $modulos_disponiveis = [
        'dashboard' => '📊 Dashboard',
        'igrejas' => '⛪ Igrejas',
        'membros' => '👥 Membros',
        'celulas' => '📍 Células',
        'presenca' => '✓ Presença',
        'galeria' => '📸 Galeria',
        'visitantes' => '👥 Visitantes',
        'cursos' => '📚 Cursos',
        'eventos' => '🎉 Eventos',
        'novo_convertido' => '✨ Novo Convertido',
        'configuracoes' => '⚙️ Configurações'
    ];
    
    $atualizado = 0;
    
    foreach ($usuarios as $usuario) {
        $permissoes = [];
        
        // Definir permissões baseado na função
        switch ($usuario['funcao']) {
            case 'admin':
                $permissoes = ['*']; // Acesso total
                break;
            case 'pastor':
                $permissoes = array_keys($modulos_disponiveis); // Todos os módulos
                break;
            case 'supervisor':
                $permissoes = ['dashboard', 'membros', 'celulas', 'presenca', 'visitantes', 'novo_convertido'];
                break;
            case 'lider':
                $permissoes = ['dashboard', 'membros', 'celulas', 'presenca', 'visitantes'];
                break;
            case 'membro':
                $permissoes = ['dashboard']; // Apenas dashboard
                break;
            default:
                $permissoes = ['dashboard'];
        }
        
        // Salvar permissões
        $permissoes_json = json_encode($permissoes);
        $update = $db->prepare("UPDATE usuarios SET permissoes = ? WHERE id = ?");
        $update->execute([$permissoes_json, $usuario['id']]);
        
        echo "✅ {$usuario['nome']} ({$usuario['funcao']}) - Permissões configuradas\n";
        $atualizado++;
    }
    
    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ {$atualizado} usuário(s) atualizado(s)!\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
?>
