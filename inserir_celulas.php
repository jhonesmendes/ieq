<?php
require_once 'config/database.php';

try {
    $db = getDB();
    
    // Verificar se há células
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM celulas");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['total'] == 0) {
        echo "Inserindo células de teste...\n";
        
        // Verificar usuario 1 (pastor)
        $stmt = $db->prepare("SELECT id FROM usuarios WHERE id = 1");
        $stmt->execute();
        $user1 = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user1) {
            echo "✗ Usuário 1 não encontrado!\n";
            exit;
        }
        
        // Células de exemplo
        $celulas = [
            ['Pescadores de Almas', 'Pinheiros', -23.5629, -46.7006, 1, 1, 'celula', 'Quarta-feira', '19:30', 'Rua dos Pinheiros, 123', 'Pinheiros', 'São Paulo'],
            ['Célula Jovens Vencedores', 'Moema', -23.6034, -46.6617, 2, 2, 'jovens', 'Sexta-feira', '20:00', 'Av. Ibirapuera, 456', 'Moema', 'São Paulo'],
            ['Célula Casais em Cristo', 'Vila Madalena', -23.5505, -46.6889, 3, 1, 'casais', 'Sábado', '18:00', 'Rua Harmonia, 789', 'Vila Madalena', 'São Paulo'],
            ['Célula Mulheres de Fé', 'Centro', -23.5431, -46.6291, 4, 2, 'mulheres', 'Terça-feira', '14:00', 'Rua São Bento, 321', 'Centro', 'São Paulo']
        ];
        
        $stmt = $db->prepare("INSERT INTO celulas (nome, localizacao, latitude, longitude, lider_id, supervisor_id, tipo, dia_semana, hora, endereco, bairro, cidade) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $count = 0;
        foreach ($celulas as $celula) {
            try {
                $stmt->execute($celula);
                $count++;
            } catch (Exception $e) {
                echo "Erro ao inserir célula: " . $e->getMessage() . "\n";
            }
        }
        
        echo "✓ $count células inseridas com sucesso!\n";
    } else {
        echo "✓ Já existem {$result['total']} células no banco\n";
    }
    
    // Listar células
    $stmt = $db->prepare("SELECT id, nome FROM celulas ORDER BY id");
    $stmt->execute();
    $celulas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nCélulas no banco:\n";
    foreach ($celulas as $c) {
        echo "  - ID {$c['id']}: {$c['nome']}\n";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>
