<?php
/**
 * Teste simples da API via GET (apenas verificação)
 */

header('Content-Type: application/json; charset=utf-8');

// GET simples não requer autenticação
echo json_encode([
    'status' => 'teste',
    'mensagem' => 'Se você vê isso, a API básica está funcionando',
    'data' => date('Y-m-d H:i:s'),
    'timestamp' => time()
]);
?>
