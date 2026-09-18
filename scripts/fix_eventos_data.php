<?php
/**
 * Script para atualizar datas dos eventos para 2026
 */

try {
    $db = new PDO('sqlite:' . __DIR__ . '/../data/ieq.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║  Atualizando datas dos eventos para 2026                ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

// Buscar eventos atuais
$sql = "SELECT id, nome, data_evento FROM eventos ORDER BY data_evento";
$stmt = $db->query($sql);
$eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "📋 Eventos encontrados: " . count($eventos) . "\n";
foreach ($eventos as $idx => $evento) {
    echo "  " . ($idx + 1) . ". " . $evento['nome'] . " - " . $evento['data_evento'] . "\n";
}

echo "\n⏳ Atualizando datas para 2026...\n";

$atualizados = 0;
foreach ($eventos as $evento) {
    $data_antiga = new DateTime($evento['data_evento']);
    
    // Criar nova data em 2026 mantendo mês e dia
    $data_nova = new DateTime('2026-' . $data_antiga->format('m-d H:i:s'));
    
    $stmt_update = $db->prepare("
        UPDATE eventos 
        SET data_evento = ? 
        WHERE id = ?
    ");
    
    if ($stmt_update->execute([$data_nova->format('Y-m-d H:i:s'), $evento['id']])) {
        echo "  ✓ " . $evento['nome'] . " → " . $data_nova->format('d/m/Y H:i') . "\n";
        $atualizados++;
    } else {
        echo "  ✗ Erro ao atualizar " . $evento['nome'] . "\n";
    }
}

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║  RESULTADO                                              ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n";
echo "✓ Eventos atualizados: " . $atualizados . " / " . count($eventos) . "\n";
echo "\n✓ ATUALIZAÇÃO CONCLUÍDA!\n\n";
?>
