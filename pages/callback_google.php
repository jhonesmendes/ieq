<?php
// Inicia a sessão
session_start();

// Inclui os arquivos necessários
require_once '../config/database.php';
require_once '../config/google_oauth.php';
require_once '../config/auth.php';

try {
    // Valida o state token
    if (!isset($_GET['state']) || !isset($_SESSION['oauth_state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
        throw new Exception('State token inválido - possível ataque CSRF');
    }
    
    // Valida tempo do state (máximo 10 minutos)
    if (time() - $_SESSION['oauth_state_time'] > 600) {
        throw new Exception('State token expirado');
    }
    
    // Limpa tokens usados
    unset($_SESSION['oauth_state']);
    unset($_SESSION['oauth_state_time']);

    // Verifica se o código foi retornado pelo Google
    if (!isset($_GET['code'])) {
        throw new Exception('Código de autorização não recebido do Google');
    }

    // Obtém o código
    $code = $_GET['code'];
    
    // Troca o código por um token de acesso
    $token_result = trocar_codigo_por_token($code);
    
    if (!$token_result) {
        throw new Exception('Erro ao trocar código por token');
    }

    // Obtém as informações do usuário
    $user_info = obter_informacoes_usuario_google($token_result['access_token']);
    
    if (!$user_info) {
        throw new Exception('Erro ao obter informações do usuário');
    }

    // Extrai dados do usuário
    $google_id = $user_info['id'];
    $email = $user_info['email'];
    $nome = $user_info['name'] ?? 'Usuário';
    $foto = $user_info['picture'] ?? null;

    // Tenta encontrar usuário existente por google_id
    $usuario = obter_usuario_por_google_id($google_id);
    
    // Se não encontrou, tenta vincular por email
    if (!$usuario) {
        $usuario = obter_usuario_por_email_e_vincular_google($email, $google_id);
    }
    
    // Se ainda não encontrou, cria novo usuário
    if (!$usuario) {
        $db = getDB();
        $timestamp_sql = (DB_DRIVER === 'mysql') ? 'NOW()' : "datetime('now')";
        $stmt = $db->prepare("INSERT INTO usuarios (google_id, email, nome, foto, status_aprovacao, data_criacao) VALUES (?, ?, ?, ?, 'pendente', " . $timestamp_sql . ")");
        $stmt->execute([$google_id, $email, $nome, $foto]);
        
        // Recarrega o usuário
        $usuario = obter_usuario_por_google_id($google_id);
    }

    // Verifica se o usuário foi aprovado
    if ($usuario && $usuario['status_aprovacao'] === 'aprovado') {
        // Atualiza foto se necessário
        if ($foto && $usuario['foto'] !== $foto) {
            $db = getDB();
            $stmt = $db->prepare("UPDATE usuarios SET foto = ? WHERE id = ?");
            $stmt->execute([$foto, $usuario['id']]);
        }

        // Define a sessão
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['nome'] = $usuario['nome'];
        $_SESSION['email'] = $usuario['email'];
        $_SESSION['google_id'] = $usuario['google_id'];
        $_SESSION['tipo'] = $usuario['tipo'];
        $_SESSION['autenticado'] = true;
        $_SESSION['data_login'] = time();

        // Redireciona para o dashboard
        header('Location: ../index.php');
        exit();
    } else if ($usuario) {
        // Usuário existe mas não foi aprovado
        $_SESSION['erro'] = 'Sua conta está aguardando aprovação. Por favor, contate um administrador.';
        header('Location: ../index.php?error=aguardando_aprovacao');
        exit();
    } else {
        // Não conseguiu criar ou encontrar usuário
        $_SESSION['erro'] = 'Erro ao processar login. Por favor, tente novamente.';
        header('Location: ../index.php?error=erro_processamento');
        exit();
    }

} catch (Exception $e) {
    // Log de erro
    error_log('Erro no callback Google: ' . $e->getMessage());
    
    // Redireciona com mensagem de erro
    $_SESSION['erro'] = 'Erro na autenticação: ' . $e->getMessage();
    header('Location: ../index.php?error=autenticacao');
    exit();
}
?>
