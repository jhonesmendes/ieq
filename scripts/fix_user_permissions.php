<?php
/**
 * Script para corrigir permissões de usuários existentes
 * Execute: php scripts/fix_user_permissions.php
 */

require_once __DIR__ . '/../config/database.php';

echo "===================================\n";
echo "  Corrigir Permissões de Usuários\n";
echo "===================================\n\n";

try {
    $db = getDB();
    
    // Buscar todos os usuários
    $stmt = $db->query("SELECT id, nome, email, funcao, permissoes FROM usuarios ORDER BY id");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($usuarios)) {
        echo "⚠️  Nenhum usuário encontrado.\n";
        exit(0);
    }
    
    echo "Usuários encontrados: " . count($usuarios) . "\n\n";
    
    foreach ($usuarios as $usuario) {
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "👤 {$usuario['nome']} ({$usuario['email']})\n";
        echo "   Função: {$usuario['funcao']}\n";
        echo "   Permissões atuais: " . ($usuario['permissoes'] ?? 'NULL') . "\n";
        
        // Se é admin, garantir permissões totais
        if ($usuario['funcao'] === 'admin') {
            $novas_permissoes = json_encode(['*']);
            $stmt = $db->prepare("UPDATE usuarios SET permissoes = ? WHERE id = ?");
            $stmt->execute([$novas_permissoes, $usuario['id']]);
            echo "   ✅ Atualizado para: ['*'] (acesso total)\n";
        }
        // Se não tem permissões, perguntar quais dar
        elseif (empty($usuario['permissoes']) || $usuario['permissoes'] === 'null') {
            echo "\n   ⚠️  Usuário sem permissões!\n";
            echo "   Deseja configurar permissões agora? (s/n): ";
            $resposta = trim(fgets(STDIN));
            
            if (strtolower($resposta) === 's') {
                echo "\n   Módulos disponíveis:\n";
                echo "   1. dashboard\n";
                echo "   2. igrejas\n";
                echo "   3. membros\n";
                echo "   4. celulas\n";
                echo "   5. presenca\n";
                echo "   6. visitantes\n";
                echo "   7. cursos\n";
                echo "   8. eventos\n";
                echo "   9. novo_convertido\n";
                echo "   10. configuracoes\n";
                echo "   * (asterisco) = TODAS\n\n";
                echo "   Digite os números separados por vírgula (ex: 1,7,8) ou * para todas: ";
                $escolha = trim(fgets(STDIN));
                
                if ($escolha === '*') {
                    $permissoes = ['*'];
                } else {
                    $modulos = [
                        '1' => 'dashboard',
                        '2' => 'igrejas',
                        '3' => 'membros',
                        '4' => 'celulas',
                        '5' => 'presenca',
                        '6' => 'visitantes',
                        '7' => 'cursos',
                        '8' => 'eventos',
                        '9' => 'novo_convertido',
                        '10' => 'configuracoes'
                    ];
                    
                    $numeros = explode(',', $escolha);
                    $permissoes = [];
                    foreach ($numeros as $num) {
                        $num = trim($num);
                        if (isset($modulos[$num])) {
                            $permissoes[] = $modulos[$num];
                        }
                    }
                }
                
                if (!empty($permissoes)) {
                    $permissoes_json = json_encode($permissoes);
                    $stmt = $db->prepare("UPDATE usuarios SET permissoes = ? WHERE id = ?");
                    $stmt->execute([$permissoes_json, $usuario['id']]);
                    echo "   ✅ Permissões atualizadas: " . implode(', ', $permissoes) . "\n";
                } else {
                    echo "   ⚠️  Nenhuma permissão válida selecionada.\n";
                }
            } else {
                echo "   ⏭️  Pulado\n";
            }
        } else {
            echo "   ✅ Já tem permissões configuradas\n";
        }
    }
    
    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ Processo concluído!\n\n";
    
} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
