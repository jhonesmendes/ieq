<?php
// Arquivo de logout com redirecionamento seguro
session_start();

// Validar se o usuário está realmente autenticado
if (!isset($_SESSION['usuario_id'])) {
    // Se não está autenticado, redireciona direto para login
    header('Location: ../index.php?page=login', true, 302);
    exit;
}

// Incluir arquivo de autenticação
require_once __DIR__ . '/../config/auth.php';

// Destruir a sessão
if (function_exists('fazer_logout')) {
    fazer_logout();
} else {
    // Fallback se a função não existir
    session_destroy();
}

// Limpar cookies de segurança
if (isset($_COOKIE['usuario_token'])) {
    setcookie('usuario_token', '', time() - 3600, '/', '', false, true);
}

// Redirecionar para página de login com mensagem
header('Location: ../index.php?page=login&logout=success', true, 302);
exit;