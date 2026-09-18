<?php
/**
 * OAuth Redirect via POST - Contorna Mod_Security
 * 
 * Mod_Security bloqueia URLs com muitos parâmetros GET
 * Solução: Receber GET do Google, converter para POST interno
 * 
 * Fluxo:
 * Google → oauth_redirect.php?code=...&state=...&iss=... (muitos params, Mod_Security bloqueia)
 *          ↓ (usa JavaScript para POST)
 *        oauth_handler.php (recebe via POST, sem URL params)
 *          ↓
 *        Token exchange bem-sucedido!
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Processando autenticação...</title>
    <meta charset="UTF-8">
    <style>
        body { display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background: #f0f0f0; font-family: Arial, sans-serif; }
        .container { text-align: center; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #333; margin-top: 0; }
        .spinner { display: inline-block; width: 20px; height: 20px; border: 3px solid #f3f3f3; border-top: 3px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="container">
        <h2>Autenticando...</h2>
        <div class="spinner"></div>
        <p>Um momento...</p>
    </div>

    <form id="oauthForm" method="POST" action="oauth_handler.php" style="display: none;">
        <!-- Os inputs serão preenchidos via JavaScript -->
    </form>

    <script>
        // Captura parâmetros da URL (vindo do Google)
        const params = new URLSearchParams(window.location.search);
        
        // Log para debug
        console.log('OAuth params recebidos:', Object.fromEntries(params));

        // Cria formulário POST para contornar limites de URL
        const form = document.getElementById('oauthForm');
        
        // Adiciona cada parâmetro como campo POST (não na URL!)
        for (const [key, value] of params) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = value;
            form.appendChild(input);
        }
        
        // Log antes de submeter
        console.log('Submetendo formulário POST para oauth_handler.php');
        
        // Submete o formulário (vai para oauth_handler.php via POST)
        form.submit();
    </script>
</body>
</html>
