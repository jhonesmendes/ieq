<?php
require_once 'config/database.php';
$db = getDB();

echo "=== Estrutura da tabela REUNIOES_CELULA ===\n";
$result = $db->query('DESC reunioes_celula');
$colunas = $result->fetchAll(PDO::FETCH_ASSOC);
foreach($colunas as $col) {
    echo $col['Field'] . ' (' . $col['Type'] . ')\n';
}
?>
