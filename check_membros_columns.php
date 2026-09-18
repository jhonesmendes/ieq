<?php
require_once 'config/database.php';
$db = getDB();

// Verificar colunas da tabela membros
$result = $db->query('DESC membros');
$colunas = $result->fetchAll(PDO::FETCH_ASSOC);

echo "=== Estrutura da tabela MEMBROS ===\n";
foreach($colunas as $col) {
    echo $col['Field'] . ' (' . $col['Type'] . ')\n';
}

echo "\n=== Verificar celulas ===\n";
$result = $db->query('DESC celulas');
$colunas = $result->fetchAll(PDO::FETCH_ASSOC);
foreach($colunas as $col) {
    echo $col['Field'] . ' (' . $col['Type'] . ')\n';
}
?>
