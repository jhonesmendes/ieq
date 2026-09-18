<?php
/**
 * Google OAuth Callback Wrapper - Filtra parâmetros para evitar Mod_Security
 * 
 * Google envia MUITOS parâmetros extras: ?code=...&iss=...&authuser=...&session_state=...
 * Mod_Security bloqueia URLs com muitos parâmetros
 * 
 * Solução: Este arquivo filtra APENAS code e state, depois redireciona internamente
 * 
 * Fluxo:
 * Google → google_callback.php?code=...&iss=...&authuser=... (muitos params)
 *          ↓ (filtra)
 *        oauth_handler.php?code=...&state=...  (apenas essencial, via SESSION)
 */

// Inicia sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_log("=== GOOGLE_CALLBACK WRAPPER ===");
error_log("GET params recebidos: " . json_encode($_GET));

// Extrai APENAS os parâmetros que precisamos
$code = $_GET['code'] ?? null;
$state = $_GET['state'] ?? null;

if (!$code) {
    error_log("❌ Code não recebido!");
    $_SESSION['erro'] = 'Código de autorização não recebido do Google';
    header('Location: index.php?error=no_code', true, 302);
    exit();
}

// Armazenar code na SESSION (evita passar via GET)
$_SESSION['google_code'] = $code;

error_log("✅ Code extraído e armazenado na SESSION");
error_log("State recebido: " . ($state ? substr($state, 0, 20) . "..." : "NÃO DEFINIDO"));

// Se tem state, validar contra SESSION
if ($state && isset($_SESSION['oauth_state'])) {
    if ($state !== $_SESSION['oauth_state']) {
        error_log("❌ State mismatch!");
        unset($_SESSION['google_code']);
        $_SESSION['erro'] = 'State token inválido';
        header('Location: index.php?error=state_mismatch', true, 302);
        exit();
    }
    error_log("✅ State validado com sucesso");
}

// Redirecionar para oauth_handler COM minimal params
// URL muito curta = Mod_Security aceita!
header('Location: oauth_handler.php', true, 302);
exit();
