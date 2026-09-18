<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== TESTE DE REQUIRES ===\n\n";

$requires = [
    '../config/database.php',
    '../config/seguranca.php',
    '../config/auth.php',
    '../config/membros.php',
    '../config/celulas.php',
    '../config/presenca.php',
    '../config/eventos.php',
    '../config/cursos.php',
    '../config/igrejas.php',
    '../config/visitantes.php',
    '../config/permissoes.php',
    '../config/upload.php',
];

$cwd = __DIR__ . '/api';

try {
    foreach ($requires as $file) {
        $path = $cwd . '/' . $file;
        echo "Testando: $file ... ";
        if (!file_exists($path)) {
            echo "❌ NÃO EXISTE\n";
            continue;
        }
        require_once $path;
        echo "✅ OK\n";
    }
    echo "\nTodos os requires foram carregados com sucesso!\n";
} catch (Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "Stack:\n";
    echo $e->getTraceAsString();
}
?>
