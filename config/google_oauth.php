<?php
// Configuração do Google OAuth 2.0

// IMPORTANTE: para o botão "Google" funcionar, siga
// documentos/GOOGLE_OAUTH_SETUP.md — crie a credencial no Google Cloud
// Console e coloque GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET no .env (nunca
// direto neste arquivo, que vai pro Git). Nas "URIs de redirecionamento
// autorizados" do Google, cadastre EXATAMENTE estas duas:
//    - https://ieqroo.com.br/auth.html
//    - https://www.ieqroo.com.br/auth.html
// (o site responde nos dois domínios, e o Google só aceita a URI idêntica
// à que foi usada na requisição — por isso as duas precisam estar lá)

// FLAG PARA HABILITAR/DESABILITAR GOOGLE OAUTH
// Defina como FALSE para ocultar o acesso via Google até o .env estar
// configurado com as credenciais acima.
define('GOOGLE_OAUTH_HABILITADO', true);

// Credenciais do Google OAuth — ficam só no .env (nunca commitadas no Git).
// Enquanto o .env não tiver os valores reais, google_oauth_configurado()
// abaixo mantém o botão escondido.
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: '');
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: '');

// URL de retorno (callback) - Usa auth.html (nome genérico para evitar bloqueios)
// Mod_Security é menos rigoroso com nomes genéricos como "auth.html"
// Em vez de "oauth_redirect.php" que pode estar em blocklists
// URLs hardcoded por ambiente para evitar mismatch
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$is_localhost = (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false);

if ($is_localhost) {
    // Ambiente de desenvolvimento
    $redirect_uri = 'http://localhost/auth.html';
} else {
    // Produção: usa o host real da requisição (com ou sem "www"), em vez de
    // fixar um domínio. O site responde tanto em ieqroo.com.br quanto em
    // www.ieqroo.com.br — o Google exige a URI EXATA que o visitante usou,
    // então as DUAS precisam estar cadastradas como "URIs de redirecionamento
    // autorizados" no Google Cloud Console (ver instruções no topo do arquivo).
    $redirect_uri = 'https://' . $host . '/auth.html';
}

// Permitir override via variável de ambiente (útil para CI/CD)
$redirect_uri = getenv('GOOGLE_REDIRECT_URI') ?: $redirect_uri;
define('GOOGLE_REDIRECT_URI', $redirect_uri);

// URLs da API do Google
define('GOOGLE_AUTH_URL', 'https://accounts.google.com/o/oauth2/v2/auth');
define('GOOGLE_TOKEN_URL', 'https://www.googleapis.com/oauth2/v4/token');
define('GOOGLE_USERINFO_URL', 'https://www.googleapis.com/oauth2/v1/userinfo');

/**
 * Gera a URL de login do Google com state token minimizado
 * @return string URL para redirecionar o usuário
 */
function gerar_url_login_google() {
    // Inicia sessão se não estiver iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Gera um state token aleatório
    $state = bin2hex(random_bytes(32));
    
    // Armazena na sessão (não no URL - reduz tamanho)
    $_SESSION['oauth_state'] = $state;
    $_SESSION['oauth_state_time'] = time();
    
    // Parâmetros minimizados
    $params = [
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope' => 'email profile',
        'state' => $state,
        'access_type' => 'online'
    ];
    
    return GOOGLE_AUTH_URL . '?' . http_build_query($params);
}

/**
 * Troca o código de autorização por um token de acesso
 * @param string $code Código de autorização do Google
 * @return array Dados do token ou erro
 */
function trocar_codigo_por_token($code) {
    $params = [
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'code' => $code,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code'
    ];
    
    $ch = curl_init(GOOGLE_TOKEN_URL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    
    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Log para debug
    error_log("trocar_codigo_por_token - HTTP Code: {$http_code}, Response: " . substr($response, 0, 200));
    
    if ($curl_error) {
        error_log("trocar_codigo_por_token - CURL Error: {$curl_error}");
        return ['erro' => 'Erro de conexão: ' . $curl_error];
    }
    
    if ($http_code === 200) {
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("trocar_codigo_por_token - JSON Error: " . json_last_error_msg());
            return ['erro' => 'Erro ao decodificar resposta JSON'];
        }
        return $data;
    }
    
    error_log("trocar_codigo_por_token - Resposta: " . $response);
    return ['erro' => 'Falha ao obter token', 'http_code' => $http_code, 'response' => $response];
}

/**
 * Obtém as informações do usuário do Google
 * @param string $access_token Token de acesso
 * @return array Dados do usuário ou erro
 */
function obter_informacoes_usuario_google($access_token) {
    $headers = ['Authorization: Bearer ' . $access_token];
    
    $ch = curl_init(GOOGLE_USERINFO_URL);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    
    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Log para debug
    error_log("obter_informacoes_usuario_google - HTTP Code: {$http_code}");
    
    if ($curl_error) {
        error_log("obter_informacoes_usuario_google - CURL Error: {$curl_error}");
        return ['erro' => 'Erro de conexão: ' . $curl_error];
    }
    
    if ($http_code === 200) {
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("obter_informacoes_usuario_google - JSON Error: " . json_last_error_msg());
            return ['erro' => 'Erro ao decodificar resposta JSON'];
        }
        return $data;
    }
    
    error_log("obter_informacoes_usuario_google - Resposta: " . $response);
    return ['erro' => 'Falha ao obter informações do usuário', 'http_code' => $http_code];
}

/**
 * Verifica se o Google OAuth está habilitado E configurado corretamente
 * @return bool
 */
function google_oauth_configurado() {
    // Se a flag não está habilitada, retorna false (oculta Google)
    if (!GOOGLE_OAUTH_HABILITADO) {
        return false;
    }
    
    // Só mostra o botão quando as duas credenciais reais estiverem no .env
    return !empty(GOOGLE_CLIENT_ID) && !empty(GOOGLE_CLIENT_SECRET);
}

