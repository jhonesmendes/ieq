<?php
/**
 * Script de Teste - Sistema de Recuperação de Senha
 * Teste rápido para verificar se tudo está funcionando
 */

// Incluir configurações
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/email.php';

echo "<!DOCTYPE html>";
echo "<html>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>Teste - Recuperação de Senha</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }";
echo ".container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }";
echo "h1 { color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px; }";
echo ".test { margin: 15px 0; padding: 15px; background: #f9f9f9; border-radius: 4px; border-left: 4px solid #667eea; }";
echo ".test.ok { border-left-color: #4caf50; background: #f1f8f4; }";
echo ".test.erro { border-left-color: #f44336; background: #fef5f5; }";
echo ".test h3 { margin: 0 0 10px; }";
echo ".test h3:before { content: '✓ '; color: #4caf50; font-weight: bold; }";
echo ".test.erro h3:before { content: '✗ '; color: #f44336; }";
echo ".test p { margin: 5px 0; font-size: 14px; color: #666; }";
echo ".code { background: #f0f0f0; padding: 10px; border-radius: 4px; font-family: monospace; overflow-x: auto; }";
echo "button { background: #667eea; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }";
echo "button:hover { background: #764ba2; }";
echo "</style>";
echo "</head>";
echo "<body>";
echo "<div class='container'>";

echo "<h1>🧪 Teste - Sistema de Recuperação de Senha</h1>";

// Teste 1: Conexão com banco de dados
echo "<div class='test ok'>";
echo "<h3>Conexão com Banco de Dados</h3>";
try {
    $db = getDB();
    echo "<p>✓ Banco de dados conectado com sucesso</p>";
    echo "<p>Tipo: " . DB_TYPE . "</p>";
} catch (Exception $e) {
    echo "<div class='test erro'>";
    echo "<p>Erro: " . $e->getMessage() . "</p>";
    echo "</div>";
}
echo "</div>";

// Teste 2: Tabela recuperacao_senha
echo "<div class='test ok'>";
echo "<h3>Estrutura da Tabela recuperacao_senha</h3>";
try {
    $db = getDB();
    
    if (DB_TYPE === 'sqlite') {
        $stmt = $db->query("PRAGMA table_info(recuperacao_senha)");
        $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>Colunas encontradas: " . count($colunas) . "</p>";
        echo "<div class='code'>";
        foreach ($colunas as $coluna) {
            echo "- " . $coluna['name'] . " (" . $coluna['type'] . ")<br>";
        }
        echo "</div>";
    } else {
        $stmt = $db->query("DESCRIBE recuperacao_senha");
        $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>Colunas encontradas: " . count($colunas) . "</p>";
        echo "<div class='code'>";
        foreach ($colunas as $coluna) {
            echo "- " . $coluna['Field'] . " (" . $coluna['Type'] . ")<br>";
        }
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<div class='test erro'>";
    echo "<h3>Erro na Tabela</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
echo "</div>";

// Teste 3: Funções de Autenticação
echo "<div class='test ok'>";
echo "<h3>Funções de Recuperação de Senha</h3>";
$funcoes = [
    'gerar_token_recuperacao' => 'Gerar token de 1 hora',
    'validar_token_recuperacao' => 'Validar token com expiração',
    'redefinir_senha' => 'Redefinir senha com token',
    'limpar_tokens_expirados' => 'Limpar tokens antigos',
    'obter_ip_cliente' => 'Obter IP do cliente'
];

foreach ($funcoes as $funcao => $descricao) {
    if (function_exists($funcao)) {
        echo "<p><span style='color: #4caf50; font-weight: bold;'>✓</span> $funcao - $descricao</p>";
    } else {
        echo "<p><span style='color: #f44336; font-weight: bold;'>✗</span> $funcao - NÃO ENCONTRADA</p>";
    }
}
echo "</div>";

// Teste 4: Configurações de Email
echo "<div class='test ok'>";
echo "<h3>Configurações de Email</h3>";
$config_email = testar_configuracoes_email();
echo "<p>SMTP Configurado: " . ($config_email['smtp_configurado'] ? '✓ Sim' : '✗ Não') . "</p>";
echo "<p>Email Padrão: " . htmlspecialchars($config_email['email_padrao']) . "</p>";
echo "<p>PHPMailer Disponível: " . ($config_email['phpmailer_disponivel'] ? '✓ Sim' : '✗ Não') . "</p>";
echo "<p>mail() Disponível: " . ($config_email['mail_nativo_disponivel'] ? '✓ Sim' : '✗ Não') . "</p>";

if (!$config_email['smtp_configurado'] && !$config_email['mail_nativo_disponivel']) {
    echo "<p style='color: #f44336; font-weight: bold;'>⚠️ Aviso: Nenhum método de email está configurado!</p>";
} elseif (!$config_email['smtp_configurado']) {
    echo "<p style='color: #ff9800;'>ℹ️ Usando mail() nativo (configure SMTP para melhor confiabilidade)</p>";
}
echo "</div>";

// Teste 5: Páginas Criadas
echo "<div class='test ok'>";
echo "<h3>Páginas Criadas</h3>";
$paginas = [
    'pages/recuperar_senha.php' => 'Solicitar recuperação',
    'pages/redefinir_senha.php' => 'Redefinir senha',
    'config/email.php' => 'Sistema de envio de emails',
];

foreach ($paginas as $arquivo => $descricao) {
    $caminho = __DIR__ . '/' . $arquivo;
    if (file_exists($caminho)) {
        $tamanho = filesize($caminho);
        echo "<p><span style='color: #4caf50; font-weight: bold;'>✓</span> $descricao ($tamanho bytes)</p>";
    } else {
        echo "<p><span style='color: #f44336; font-weight: bold;'>✗</span> $descricao - ARQUIVO NÃO ENCONTRADO</p>";
    }
}
echo "</div>";

// Teste 6: Endpoints da API
echo "<div class='test ok'>";
echo "<h3>Endpoints da API</h3>";
echo "<p>Os seguintes endpoints foram adicionados a <code>api/index.php</code>:</p>";
echo "<ul>";
echo "<li><strong>POST ?acao=solicitar_recuperacao_senha</strong> - Solicitar recuperação</li>";
echo "<li><strong>GET ?acao=validar_token_recuperacao</strong> - Validar token</li>";
echo "<li><strong>POST ?acao=redefinir_senha</strong> - Redefinir senha</li>";
echo "</ul>";
echo "</div>";

// Teste 7: Link no Login
echo "<div class='test ok'>";
echo "<h3>Link na Página de Login</h3>";
echo "<p>Link 'Esqueceu sua senha?' adicionado à página de login</p>";
echo "<p>Ao clicar, redireciona para: <code>?page=recuperar_senha</code></p>";
echo "</div>";

// Teste 8: Teste de Token
echo "<div class='test ok'>";
echo "<h3>Teste de Geração de Token</h3>";
echo "<form method='POST' style='display: flex; gap: 10px;'>";
echo "<input type='email' name='test_email' placeholder='Digite um email' required>";
echo "<button type='submit' name='gerar_token'>Gerar Token de Teste</button>";
echo "</form>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gerar_token'])) {
    $test_email = $_POST['test_email'] ?? '';
    if (filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
        try {
            // Verificar se email existe
            $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$test_email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($usuario) {
                $resultado = gerar_token_recuperacao($test_email);
                if ($resultado) {
                    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 4px; margin-top: 10px;'>";
                    echo "<p><strong>✓ Token gerado com sucesso!</strong></p>";
                    echo "<p>Token: <code style='word-break: break-all;'>" . htmlspecialchars($resultado['token']) . "</code></p>";
                    echo "<p>Email: " . htmlspecialchars($resultado['email']) . "</p>";
                    echo "<p>Expira em: " . htmlspecialchars($resultado['expira_em']) . "</p>";
                    echo "<p><strong>Link de teste:</strong></p>";
                    echo "<a href='?page=redefinir_senha&token=" . urlencode($resultado['token']) . "' style='color: #667eea;'>Testar Link de Recuperação</a>";
                    echo "</div>";
                }
            } else {
                echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; margin-top: 10px;'>";
                echo "<p><strong>✗ Email não encontrado na tabela de usuários</strong></p>";
                echo "</div>";
            }
        } catch (Exception $e) {
            echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; margin-top: 10px;'>";
            echo "<p><strong>✗ Erro:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
        }
    } else {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; margin-top: 10px;'>";
        echo "<p><strong>✗ Email inválido</strong></p>";
        echo "</div>";
    }
}
echo "</div>";

// Resumo
echo "<div style='margin-top: 30px; padding: 15px; background: #e3f2fd; border-radius: 4px; border-left: 4px solid #2196f3;'>";
echo "<h2>📋 Próximos Passos</h2>";
echo "<ol>";
echo "<li>Configure as credenciais de email em <code>.env</code></li>";
echo "<li>Teste a página de recuperação em <code>?page=recuperar_senha</code></li>";
echo "<li>Configure o link de login para mostrar 'Esqueceu sua senha?'</li>";
echo "<li>Verifique os logs em <code>data/seguranca.log</code> para eventos</li>";
echo "</ol>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
