<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/permissoes.php';

echo "<h2>Debug de Permissões - Usuário Logado</h2>";

if (!isset($_SESSION['user_id'])) {
    echo "<p>❌ Nenhum usuário logado</p>";
    exit;
}

echo "<h3>Informações da Sessão:</h3>";
echo "<pre>";
print_r([
    'user_id' => $_SESSION['user_id'],
    'user_nome' => $_SESSION['user_nome'],
    'user_funcao' => $_SESSION['user_funcao']
]);
echo "</pre>";

echo "<h3>Dados do Banco:</h3>";
$db = getDB();
$stmt = $db->prepare("SELECT id, nome, email, funcao, permissoes FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<pre>";
print_r($usuario);
echo "</pre>";

echo "<h3>Permissões Decodificadas:</h3>";
$permissoes = get_permissoes_usuario();
echo "<pre>";
print_r($permissoes);
echo "</pre>";

echo "<h3>Módulos Permitidos:</h3>";
$modulos_permitidos = get_modulos_permitidos();
echo "<pre>";
print_r($modulos_permitidos);
echo "</pre>";

echo "<h3>Teste de Acesso aos Módulos:</h3>";
$modulos = get_modulos_disponiveis();
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Módulo</th><th>Pode Acessar?</th></tr>";
foreach ($modulos as $key => $label) {
    $pode = pode_acessar_modulo($key) ? '✅ SIM' : '❌ NÃO';
    echo "<tr><td>$key ($label)</td><td>$pode</td></tr>";
}
echo "</table>";
