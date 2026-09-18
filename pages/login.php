<?php
// google_oauth é incluído pelo index.php
// Debug: verificar se a função existe
if (function_exists('google_oauth_configurado')) {
    $oauth_configurado = google_oauth_configurado();
} else {
    $oauth_configurado = false;
    error_log("ERRO: Função google_oauth_configurado não encontrada!");
}
?>
<link rel="stylesheet" href="assets/css/login.css?v=<?php echo time(); ?>">

<div class="login-container">
    <div class="login-box">
        <div class="logo">
            <span class="logo-badge">IEQ</span>
            <h1>Bem-vindo de volta</h1>
            <p class="subtitle">Gestão de células e membros</p>
        </div>

        <?php
        // Exibir mensagens de logout
        if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
            echo '<div class="alerta alerta-sucesso" role="alert" id="logout-message">';
            echo '✓ Desconectado com sucesso! Bem-vindo de volta.';
            echo '</div>';
        }
        
        // Exibir mensagens de sessão
        if (isset($_SESSION['sucesso'])) {
            echo '<div class="alerta alerta-sucesso">' . htmlspecialchars($_SESSION['sucesso']) . '</div>';
            unset($_SESSION['sucesso']);
        }
        if (isset($_SESSION['erro'])) {
            echo '<div class="alerta alerta-erro">' . htmlspecialchars($_SESSION['erro']) . '</div>';
            unset($_SESSION['erro']);
        }
        if (isset($_SESSION['aviso'])) {
            echo '<div class="alerta alerta-aviso">' . htmlspecialchars($_SESSION['aviso']) . '</div>';
            unset($_SESSION['aviso']);
        }
        ?>

        <div id="mensagem" class="mensagem" style="display:none;"></div>

        <form id="loginForm" class="form-group">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(obter_csrf_token()); ?>">

            <div class="form-item">
                <label>E-mail</label>
                <div class="input-com-icone">
                    <span class="campo-icone">✉️</span>
                    <input type="text" id="email" name="email" autocomplete="username" required placeholder="seu@email.com">
                </div>
            </div>

            <div class="form-item">
                <label>Senha</label>
                <div class="input-com-icone">
                    <span class="campo-icone">🔒</span>
                    <input type="password" id="senha" name="senha" autocomplete="current-password" required placeholder="Sua senha">
                    <button type="button" class="botao-ver-senha" id="btnVerSenha" aria-label="Mostrar senha">👁️</button>
                </div>
            </div>

            <div class="login-recovery">
                <a href="?page=recuperar_senha" class="link-recuperacao">Esqueceu a senha?</a>
            </div>

            <button type="submit" class="btn btn-primary">Entrar</button>
        </form>

        <?php if ($oauth_configurado): ?>
        <div class="login-divider"><span>ou continue com</span></div>

        <div class="external-login">
            <a href="/iniciar_login_google.php" class="btn-external btn-google" title="Entrar com Google">
                <span class="google-g">G</span>
                <span>Google</span>
            </a>
        </div>
        <?php endif; ?>

        <p class="login-link">Não tem conta? <a href="/pages/registro.php">Registre-se aqui</a></p>
    </div>
</div>

<script>
document.getElementById('btnVerSenha').addEventListener('click', function() {
    const campo = document.getElementById('senha');
    const mostrando = campo.type === 'text';
    campo.type = mostrando ? 'password' : 'text';
    this.textContent = mostrando ? '👁️' : '🙈';
});

document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const email = document.getElementById('email').value;
    const senha = document.getElementById('senha').value;
    const csrfToken = document.querySelector('input[name="csrf_token"]').value;
    const mensagemEl = document.getElementById('mensagem');

    // Validar campos
    if (!email || !senha) {
        mensagemEl.innerHTML = '❌ Por favor, preencha todos os campos';
        mensagemEl.style.display = 'block';
        mensagemEl.style.color = '#d32f2f';
        return;
    }

    // Mostrar loading
    mensagemEl.innerHTML = '⏳ Autenticando...';
    mensagemEl.style.display = 'block';
    mensagemEl.style.color = '#1976d2';

    console.log('Token CSRF sendo enviado:', csrfToken);
    console.log('Cookies disponíveis:', document.cookie);

    // Usar caminho absoluto para a API
    fetch('/api/index.php?acao=login', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': csrfToken
        },
        body: `email=${encodeURIComponent(email)}&senha=${encodeURIComponent(senha)}&csrf_token=${encodeURIComponent(csrfToken)}`,
        credentials: 'include'
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.mensagem || 'Erro na autenticação');
            }).catch(e => {
                throw new Error('Erro na autenticação: ' + response.status);
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('Login response:', data);
        
        if (data.status === 'sucesso') {
            // Atualizar token CSRF se foi regenerado
            if (data.dados && data.dados.csrf_token) {
                if (typeof atualizarCsrfToken === 'function') {
                    atualizarCsrfToken(data.dados.csrf_token);
                }
            }
            
            mensagemEl.innerHTML = '✅ ' + data.mensagem;
            mensagemEl.style.color = '#28a745';
            // Redirecionar após 1 segundo
            setTimeout(() => {
                window.location.href = '/index.php?page=dashboard';
            }, 1000);
        } else {
            mensagemEl.innerHTML = '❌ ' + (data.mensagem || 'Erro desconhecido');
            mensagemEl.style.display = 'block';
            mensagemEl.style.color = '#d32f2f';
        }
    })
    .catch(error => {
        console.error('Erro completo:', error);
        mensagemEl.innerHTML = '❌ ' + error.message;
        mensagemEl.style.display = 'block';
        mensagemEl.style.color = '#d32f2f';
    });
});
</script>
<!-- Script de logout seguro -->
<script src="/assets/js/logout.js"></script>