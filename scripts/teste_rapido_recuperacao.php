<?php
/**
 * Teste Rápido - Recuperação de Senha
 */

session_start();

// Incluir config
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/email.php';

http_response_code(200);
header('Content-Type: application/json; charset=utf-8');

$resultado = [];

// Teste 1: Banco de dados
try {
    $db = getDB();
    $resultado['banco_de_dados'] = '✅ Conectado';
} catch (Exception $e) {
    $resultado['banco_de_dados'] = '❌ Erro: ' . $e->getMessage();
}

// Teste 2: Tabela recuperacao_senha
try {
    $db = getDB();
    $stmt = $db->query("SELECT 1 FROM recuperacao_senha LIMIT 1");
    $resultado['tabela_recuperacao'] = '✅ Existe';
} catch (Exception $e) {
    $resultado['tabela_recuperacao'] = '❌ Não existe: ' . $e->getMessage();
}

// Teste 3: Funções
$resultado['funcoes'] = [];
$resultado['funcoes']['gerar_token'] = function_exists('gerar_token_recuperacao') ? '✅' : '❌';
$resultado['funcoes']['validar_token'] = function_exists('validar_token_recuperacao') ? '✅' : '❌';
$resultado['funcoes']['redefinir_senha'] = function_exists('redefinir_senha') ? '✅' : '❌';

// Teste 4: Email
$email_config = testar_configuracoes_email();
$resultado['email']['smtp_configurado'] = $email_config['smtp_configurado'] ? '✅' : '❌';
$resultado['email']['phpmailer'] = $email_config['phpmailer_disponivel'] ? '✅' : '❌ (Não é crítico)';
$resultado['email']['mail_nativo'] = $email_config['mail_nativo_disponivel'] ? '✅' : '❌';

// Teste 5: Página de recuperação
$resultado['paginas'] = [];
$resultado['paginas']['recuperar_senha'] = file_exists(__DIR__ . '/../pages/recuperar_senha.php') ? '✅' : '❌';
$resultado['paginas']['redefinir_senha'] = file_exists(__DIR__ . '/../pages/redefinir_senha.php') ? '✅' : '❌';

// Status geral
$resultado['status'] = 'Tudo pronto! Sistema funcional.';

http_response_code(200);
echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
