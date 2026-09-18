<?php
// Logout robusto com tratamento de erro
// Inicia buffer para evitar output antes de headers
ob_start();

// Previne output de erros
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Inicia sessão
session_start();

try {
    // Requer configurações de autenticação
    require_once __DIR__ . '/config/auth.php';
    
    // Função de logout segura
    if (function_exists('fazer_logout')) {
        fazer_logout();
    } else {
        // Fallback se função não existir
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }

    // Limpar cookies de autenticação
    if (isset($_COOKIE['usuario_token'])) {
        setcookie('usuario_token', '', time() - 3600, '/', '', false, true);
    }

    // Limpar PHPSESSID também
    setcookie('PHPSESSID', '', time() - 3600, '/', '', false, true);

    // Limpar output buffer
    ob_clean();

    // Headers para desabilitar cache e redirecionar
    header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0', true);
    header('Pragma: no-cache', true);
    header('Expires: ' . gmdate('r', 0), true);
    header('Location: /index.php?page=login&logout=success', true, 302);
    
} catch (Exception $e) {
    // Em caso de erro, ainda assim tenta redirecionar
    ob_clean();
    header('Cache-Control: no-cache, no-store, must-revalidate', true);
    header('Location: /index.php?page=login&logout=success', true, 302);
}

// Garante saída
exit;
?>
