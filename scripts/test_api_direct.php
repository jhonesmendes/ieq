<?php
/**
 * Teste direto da API - para ver o que ela está retornando
 */

header('Content-Type: application/json; charset=utf-8');

// Testar com email vazio (como faz o teste)
echo "=== TESTE 1: Email vazio ===\n";
echo "Fazendo POST para /api/index.php?acao=solicitar_recuperacao_senha\n";
echo "Dados: email=&csrf_token=\n\n";

$url = 'http://localhost/api/index.php?acao=solicitar_recuperacao_senha';
$data = http_build_query(['email' => '', 'csrf_token' => '']);

$options = [
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => $data,
        'ignore_errors' => true
    ]
];

$context = stream_context_create($options);
$response = @file_get_contents($url, false, $context);

echo "Resposta recebida:\n";
echo "---\n";
echo $response;
echo "\n---\n\n";

// Testar com email válido
echo "=== TESTE 2: Email válido ===\n";
echo "Fazendo POST para /api/index.php?acao=solicitar_recuperacao_senha\n";
echo "Dados: email=test@example.com&csrf_token=teste\n\n";

$data2 = http_build_query(['email' => 'test@example.com', 'csrf_token' => 'teste']);
$context2 = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => $data2,
        'ignore_errors' => true
    ]
]);

$response2 = @file_get_contents($url, false, $context2);

echo "Resposta recebida:\n";
echo "---\n";
echo $response2;
echo "\n---\n";

// Tentar fazer parse JSON
echo "\n=== ANÁLISE ===\n";
$json = json_decode($response2, true);
if ($json) {
    echo "JSON válido! Status: " . ($json['status'] ?? 'indefinido') . "\n";
    echo "Mensagem: " . ($json['mensagem'] ?? 'indefinida') . "\n";
} else {
    echo "JSON INVÁLIDO ou vazio!\n";
    echo "Comprimento da resposta: " . strlen($response2) . " bytes\n";
    if (empty($response2)) {
        echo "⚠️ A API retornou VAZIO - isso significa que a requisição não chegou corretamente\n";
    }
}
?>
