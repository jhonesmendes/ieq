<?php
/**
 * Script para criar registros de membro faltantes
 */

try {
    $db = new PDO('sqlite:' . __DIR__ . '/../data/ieq.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║  Corrigindo: Criar Registros de Membro Faltantes        ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

// Encontrar usuários sem registro de membro
$sql = "
    SELECT u.id, u.nome, u.email, u.status_aprovacao, u.funcao
    FROM usuarios u
    WHERE u.id NOT IN (SELECT usuario_id FROM membros)
    ORDER BY u.id
";

try {
    $stmt = $db->query($sql);
    $usuarios_sem_membro = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($usuarios_sem_membro)) {
        echo "✓ Nenhum usuário faltando registro de membro!\n\n";
        exit(0);
    }
    
    echo "📋 Usuários sem registro de membro:\n";
    foreach ($usuarios_sem_membro as $idx => $user) {
        echo "  " . ($idx + 1) . ". ID {$user['id']} - {$user['nome']} ({$user['email']}) - Status: {$user['status_aprovacao']}\n";
    }
    
    echo "\n⏳ Criando registros de membro...\n";
    
    $criados = 0;
    $erros = [];
    
    foreach ($usuarios_sem_membro as $user) {
        try {
            $stmt_membro = $db->prepare("
                INSERT INTO membros (usuario_id, status)
                VALUES (?, 'ativo')
            ");
            
            if ($stmt_membro->execute([$user['id']])) {
                echo "  ✓ Membro criado para: {$user['nome']} (ID: {$user['id']})\n";
                $criados++;
            } else {
                $erro = implode(', ', $stmt_membro->errorInfo());
                $erros[] = "ID {$user['id']}: " . $erro;
                echo "  ✗ Erro ao criar membro para {$user['nome']}: " . $erro . "\n";
            }
        } catch (Exception $e) {
            $erros[] = "ID {$user['id']}: " . $e->getMessage();
            echo "  ✗ Exceção ao criar membro para {$user['nome']}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n";
    echo "╔══════════════════════════════════════════════════════════╗\n";
    echo "║  RESUMO DA OPERAÇÃO                                     ║\n";
    echo "╚══════════════════════════════════════════════════════════╝\n";
    echo "✓ Registros criados com sucesso: " . $criados . " / " . count($usuarios_sem_membro) . "\n";
    
    if (!empty($erros)) {
        echo "\n⚠️  Erros encontrados:\n";
        foreach ($erros as $erro) {
            echo "   - " . $erro . "\n";
        }
    }
    
    echo "\n✓ MIGRAÇÃO CONCLUÍDA!\n\n";
    
} catch (Exception $e) {
    echo "✗ Erro fatal: " . $e->getMessage() . "\n";
    exit(1);
}
?>
?>
