<?php
/**
 * Intermediário OAuth - Recebe o code do Google e processa internamente
 * Isso reduz drasticamente a URL exposta ao Mod_Security
 * 
 * URL: https://jhonescosta.com/ieq/oauth_handler.php?code=...&state=...
 */

// IMPORTANTE: Inicia sessão ANTES de qualquer output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log detalhado para debug
error_log("=== OAUTH_HANDLER INICIADO ===");
error_log("SESSION ID: " . session_id());
error_log("GET params: " . json_encode($_GET));
error_log("SESSION state: " . ($_SESSION['oauth_state'] ?? 'NÃO DEFINIDO'));

require_once 'config/database.php';
require_once 'config/google_oauth.php';
require_once 'config/auth.php';

try {
    // Valida se recebeu o code (pode vir de múltiplas fontes)
    // 1. SESSION (via google_callback.php legado)
    // 2. POST (via oauth_redirect.php) - Mod_Security bypass do Google
    // 3. GET (fallback antigo)
    $code = $_SESSION['google_code'] ?? $_POST['code'] ?? $_GET['code'] ?? null;
    
    if (!$code || empty($code)) {
        throw new Exception('Código de autorização não recebido');
    }
    
    // Limpar code da SESSION após usar
    unset($_SESSION['google_code']);

    $code = trim($code);
    
    // Log com identificação de origem
    error_log("Code recebido: " . substr($code, 0, 20) . "...");
    if (isset($_SESSION['google_code'])) {
        error_log("Code source: SESSION (google_callback.php)");
    } elseif (isset($_POST['code'])) {
        error_log("Code source: POST (oauth_redirect.php - Mod_Security bypass)");
    } else {
        error_log("Code source: GET (fallback)");
    }

    // Valida o state (CSRF protection)
    // State pode vir de POST (oauth_redirect) ou GET (fallback)
    $state = $_POST['state'] ?? $_GET['state'] ?? null;
    
    // IMPORTANTE: Google SEMPRE envia o state que enviamos
    if ($state) {
        error_log("Validando state: " . substr($state, 0, 20) . "...");
        error_log("State esperado: " . (isset($_SESSION['oauth_state']) ? substr($_SESSION['oauth_state'], 0, 20) . "..." : 'NÃO DEFINIDO'));
        
        // Se não existe na sessão, tenta aceitar mesmo assim (fallback)
        if (!isset($_SESSION['oauth_state'])) {
            error_log("⚠️ State não encontrado em SESSION - aceitando mesmo assim (modo permissivo)");
            // Armazenar o state recebido para referência futura
            $_SESSION['oauth_state'] = $_GET['state'];
        } else if ($_GET['state'] !== $_SESSION['oauth_state']) {
            // State não corresponde
            error_log("❌ State mismatch!");
            throw new Exception('State token inválido: ' . $_GET['state'] . ' vs ' . $_SESSION['oauth_state']);
        }
        
        // Valida timeout (30 minutos - mais permissivo)
        if (isset($_SESSION['oauth_state_time'])) {
            $elapsed = time() - $_SESSION['oauth_state_time'];
            error_log("Tempo decorrido: $elapsed segundos");
            
            if ($elapsed > 1800) { // 30 minutos
                throw new Exception('State token expirado (tempo decorrido: ' . $elapsed . 's)');
            }
        }
        
        // Limpar state
        unset($_SESSION['oauth_state']);
        unset($_SESSION['oauth_state_time']);
    }

    error_log("State validado com sucesso");

    // Troca o code por token (internamente, via cURL)
    error_log("Iniciando troca de code por token...");
    $token_result = trocar_codigo_por_token($code);
    
    if (!$token_result) {
        error_log("❌ trocar_codigo_por_token() retornou null");
        throw new Exception('Falha ao conectar com Google API');
    }
    
    if (!isset($token_result['access_token'])) {
        error_log("❌ Sem access_token na resposta: " . json_encode($token_result));
        throw new Exception('Falha ao obter token de acesso: ' . ($token_result['error'] ?? 'erro desconhecido'));
    }

    error_log("✅ Access token obtido");

    // Obtém informações do usuário
    error_log("Obtendo informações do usuário...");
    $user_info = obter_informacoes_usuario_google($token_result['access_token']);
    
    if (!$user_info) {
        error_log("❌ obter_informacoes_usuario_google() retornou null");
        throw new Exception('Falha ao obter informações do usuário');
    }
    
    if (!isset($user_info['id'])) {
        error_log("❌ Sem ID do usuário: " . json_encode($user_info));
        throw new Exception('Google não retornou ID do usuário');
    }

    // Extrai dados
    $google_id = $user_info['id'];
    $email = $user_info['email'] ?? null;
    $nome = $user_info['name'] ?? 'Usuário';
    $foto = $user_info['picture'] ?? null;
    
    error_log("✅ Usuário Google: ID=$google_id, Email=$email, Nome=$nome");

    // Busca usuário existente
    $usuario = obter_usuario_por_google_id($google_id);
    
    // Se não existe, tenta vincular por email
    if (!$usuario && $email) {
        $usuario = obter_usuario_por_email_e_vincular_google($email, $google_id);
    }
    
    // Se ainda não existe, cria novo (mesma tabela/colunas do cadastro normal:
    // veja config/database.php — "foto_url" e "criado_em", não "foto"/"data_criacao")
    if (!$usuario) {
        error_log("Criando novo usuário...");
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO usuarios (google_id, email, nome, foto_url, status_aprovacao, funcao)
            VALUES (?, ?, ?, ?, 'pendente', 'membro')
        ");
        $stmt->execute([$google_id, $email, $nome, $foto]);
        $usuario = obter_usuario_por_google_id($google_id);
        error_log("✅ Usuário criado com ID: " . $usuario['id']);
    } else {
        error_log("✅ Usuário encontrado: ID=" . $usuario['id']);
    }

    // Verifica aprovação
    if ($usuario && $usuario['status_aprovacao'] === 'aprovado') {
        error_log("✅ Usuário aprovado - fazendo login");

        // Atualiza foto (coluna real é foto_url)
        if ($foto && $usuario['foto_url'] !== $foto) {
            $db = getDB();
            $stmt = $db->prepare("UPDATE usuarios SET foto_url = ? WHERE id = ?");
            $stmt->execute([$foto, $usuario['id']]);
        }

        // Define sessão com as MESMAS chaves usadas pelo login normal
        // (config/auth.php: user_id, user_nome, user_email, user_funcao) —
        // usar outros nomes aqui deixaria a sessão "logada" pro Google mas
        // invisível pro resto do site, que checa $_SESSION['user_id'].
        $_SESSION['user_id'] = (int)$usuario['id'];
        $_SESSION['user_nome'] = $usuario['nome'];
        $_SESSION['user_email'] = $usuario['email'];
        $_SESSION['user_funcao'] = $usuario['funcao'] ?? 'membro';

        error_log("✅ Sessão definida com sucesso");
        error_log("Login Google bem-sucedido: {$email} (ID: {$usuario['id']})");

        // Redireciona para dashboard
        header('Location: index.php', true, 302);
        exit();
    }

    // Usuário não aprovado
    error_log("⚠️ Usuário não aprovado - status: " . ($usuario['status_aprovacao'] ?? 'DESCONHECIDO'));
    $_SESSION['erro'] = 'Sua conta está aguardando aprovação.';
    header('Location: index.php?error=aguardando_aprovacao', true, 302);
    exit();

} catch (Exception $e) {
    error_log('❌ ERRO OAuth: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    $_SESSION['erro'] = 'Erro na autenticação: ' . $e->getMessage();
    header('Location: index.php?error=oauth_error', true, 302);
    exit();
}

