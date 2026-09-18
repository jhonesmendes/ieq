<?php
require_once __DIR__ . '/../config/database.php';

$db = getDB();
$stmt = $db->query("SELECT id, nome, email, funcao, permissoes FROM usuarios ORDER BY id DESC LIMIT 1");
$user = $stmt->fetch(PDO::FETCH_ASSOC);

echo "=== ÚLTIMO USUÁRIO CRIADO ===\n";
echo "ID: " . $user['id'] . "\n";
echo "Nome: " . $user['nome'] . "\n";
echo "Email: " . $user['email'] . "\n";
echo "Função: " . $user['funcao'] . "\n";
echo "Permissões (RAW): " . $user['permissoes'] . "\n";
echo "Permissões (TIPO): " . gettype($user['permissoes']) . "\n";

if ($user['permissoes']) {
    $perms = json_decode($user['permissoes'], true);
    echo "Permissões (DECODIFICADO): " . print_r($perms, true) . "\n";
} else {
    echo "Permissões: NULL ou vazio\n";
}
