<?php
/**
 * Intermediário de Login Google - Evita bloqueio do Mod_Security
 * 
 * Estratégia:
 * 1. Inicia sessão PHP (preserva cookie PHPSESSID)
 * 2. Gera state token e armazena em SESSION
 * 3. Redireciona via JavaScript (client-side, invisível para Mod_Security)
 * 
 * Isso garante que cuando Google retorna, a sessão está intacta
 */

// IMPORTANTE: Inicia sessão PHP ANTES de cualquer output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nota: Configurações de sessão já estão em php.ini/htaccess
// Não usamos ini_set() aqui porque sessão já foi iniciada

error_log("=== INICIAR_LOGIN_GOOGLE ===");
error_log("SESSION ID: " . session_id());

require_once 'config/google_oauth.php';

// Gera state token NOVO (sempre gera novo a cada clique)
$state = bin2hex(random_bytes(32));
$_SESSION['oauth_state'] = $state;
$_SESSION['oauth_state_time'] = time();

error_log("State gerado: " . substr($state, 0, 20) . "...");
error_log("Redirecionando para Google...");

// Montar URL comletamente (sem usar a função gerar_url_login_google que depende de session)
$params = [
    'client_id' => GOOGLE_CLIENT_ID,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope' => 'email profile',
    'state' => $state,
    'access_type' => 'online',
    'prompt' => 'consent'
];

$oauth_url = GOOGLE_AUTH_URL . '?' . http_build_query($params);

error_log("OAuth URL gerada: " . substr($oauth_url, 0, 100) . "...");
error_log("PHPSESSID cookie presente: " . (isset($_COOKIE['PHPSESSID']) ? "SIM" : "NÃO"));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autenticando com Google...</title>
    <style>
        body { 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            margin: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .container {
            text-align: center;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 { color: #333; margin: 0 0 10px; font-size: 24px; }
        p { color: #666; margin: 10px 0 0; font-size: 14px; }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .fallback {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .fallback a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            display: inline-block;
            padding: 10px 20px;
            border: 1px solid #667eea;
            border-radius: 5px;
            transition: all 0.3s;
        }
        .fallback a:hover {
            background: #667eea;
            color: white;
        }
        .debug {
            font-size: 11px;
            color: #999;
            margin-top: 20px;
            text-align: left;
            max-width: 400px;
            background: #f5f5f5;
            padding: 10px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Autenticando...</h1>
        <div class="spinner"></div>
        <p>Redirecionando para Google</p>
        
        <div class="fallback">
            <p>Se não for redirecionado em 3 segundos:</p>
            <a href="<?php echo htmlspecialchars($oauth_url, ENT_QUOTES, 'UTF-8'); ?>" id="manual-link">
                Clique aqui para continuar
            </a>
        </div>

        <div class="debug">
            <p>🔍 Debug Info:</p>
            <p>Session ID: <?php echo substr(session_id(), 0, 10); ?>...</p>
            <p>State: <?php echo substr($state, 0, 10); ?>...</p>
            <p>Timestamp: <?php echo time(); ?></p>
        </div>
    </div>

    <script>
        // URL para redirecionamento
        const oauthUrl = <?php echo json_encode($oauth_url); ?>;
        const sessionId = <?php echo json_encode(session_id()); ?>;

        console.log('Iniciando OAuth...');
        console.log('Session ID: ' + sessionId.substring(0, 10) + '...');
        console.log('OAuth URL: ' + oauthUrl.substring(0, 100) + '...');

        // Estratégia 1: Redirecionamento direto (mantém cookies)
        window.location.href = oauthUrl;

        // Fallback: Se não redirecionar em 3 segundos, tentar form submit
        setTimeout(function() {
            console.log('Timeout 3s - tentando alternativa...');
            
            if (window.location.href === oauthUrl) {
                console.log('Já foi redirecionado, abortando fallback');
                return;
            }

            // Criar form invisível
            const form = document.createElement('form');
            form.method = 'GET';
            form.action = 'https://accounts.google.com/o/oauth2/v2/auth';
            form.style.display = 'none';
            
            const params = {
                'client_id': <?php echo json_encode(GOOGLE_CLIENT_ID); ?>,
                'redirect_uri': <?php echo json_encode(GOOGLE_REDIRECT_URI); ?>,
                'response_type': 'code',
                'scope': 'email profile',
                'state': <?php echo json_encode($state); ?>,
                'access_type': 'online',
                'prompt': 'consent'
            };
            
            Object.keys(params).forEach(function(key) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = params[key];
                form.appendChild(input);
            });
            
            document.body.appendChild(form);
            console.log('Form submit tentando...');
            form.submit();
        }, 3000);
    </script>
</body>
</html>

