<?php
/**
 * Teste simples para verificar se os scripts estão acessíveis
 */

echo json_encode([
    'status' => 'ok',
    'mensagem' => 'Scripts estão acessíveis! 🎉',
    'data_hora' => date('Y-m-d H:i:s'),
    'servidor' => $_SERVER['SERVER_NAME'] ?? 'localhost',
    'php_version' => phpversion()
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
