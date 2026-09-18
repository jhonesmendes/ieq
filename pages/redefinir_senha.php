<?php
/**
 * Página de Redefinição de Senha
 * Permite ao usuário redefinir sua senha usando um token válido
 */

// DEBUG
echo "<!-- redefinir_senha.php carregado -->";
error_log("[REDEFINIR_SENHA] Página carregada");

// Verificar se usuário já está autenticado
if (isset($_SESSION['user_id'])) {
    header('Location: index.php?page=dashboard');
    exit;
}

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$mensagem = '';
$tipo_mensagem = '';
$token_valido = false;
$usuario_info = null;
$senha_redefinida = false;

// Variáveis para mostrar força da senha
$mostrar_forca_senha = true;

// Processar GET - Validar token
if (!empty($token)) {
    error_log("[REDEFINIR_SENHA] Token fornecido: " . substr($token, 0, 10));
    $resultado_validacao = validar_token_recuperacao($token);
    
    if ($resultado_validacao['valido']) {
        $token_valido = true;
        $usuario_info = [
            'usuario_id' => $resultado_validacao['usuario_id'],
            'email' => $resultado_validacao['email']
        ];
    } else {
        $tipo_mensagem = 'erro';
        switch ($resultado_validacao['error_tipo']) {
            case 'token_nao_encontrado':
                $mensagem = '❌ Link inválido. O token não foi encontrado.';
                break;
            case 'token_expirado':
                $mensagem = '❌ Link expirado. O token foi válido por apenas 1 hora.';
                break;
            case 'token_ja_usado':
                $mensagem = '❌ Link já utilizado. Este token já foi usado uma vez. Solicite um novo link.';
                break;
            default:
                $mensagem = '❌ Erro ao validar o token. Por favor, tente novamente.';
        }
    }
} else {
    $tipo_mensagem = 'erro';
    $mensagem = '❌ Token não fornecido. Por favor, verifique o link enviado para seu email.';
}

// Processar POST - Redefinir senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valido) {
    $nova_senha = isset($_POST['nova_senha']) ? $_POST['nova_senha'] : '';
    $confirmar_senha = isset($_POST['confirmar_senha']) ? $_POST['confirmar_senha'] : '';
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    
    // Validar CSRF token
    if (!isset($_SESSION['csrf_token']) || $csrf_token !== $_SESSION['csrf_token']) {
        $tipo_mensagem = 'erro';
        $mensagem = '❌ Token de segurança inválido. Por favor, tente novamente.';
    } elseif (empty($nova_senha)) {
        $tipo_mensagem = 'erro';
        $mensagem = '❌ Por favor, digite uma nova senha.';
    } elseif (empty($confirmar_senha)) {
        $tipo_mensagem = 'erro';
        $mensagem = '❌ Por favor, confirme a nova senha.';
    } elseif ($nova_senha !== $confirmar_senha) {
        $tipo_mensagem = 'erro';
        $mensagem = '❌ As senhas não coincidem. Por favor, tente novamente.';
    } elseif (strlen($nova_senha) < 6) {
        $tipo_mensagem = 'erro';
        $mensagem = '❌ A senha deve ter pelo menos 6 caracteres.';
    } else {
        try {
            // Redefinir senha
            $resultado = redefinir_senha($token, $nova_senha);
            
            if ($resultado['sucesso']) {
                $tipo_mensagem = 'sucesso';
                $mensagem = '✅ Senha redefinida com sucesso! Você será redirecionado para o login em 5 segundos...';
                $senha_redefinida = true;
                $token_valido = false;
                
                // Redirecionar após 5 segundos
                header('Refresh: 5; url=?page=login');
            } else {
                $tipo_mensagem = 'erro';
                $mensagem = '❌ Erro ao redefinir a senha: ' . $resultado['mensagem'];
            }
        } catch (Exception $e) {
            $tipo_mensagem = 'erro';
            $mensagem = '❌ Erro ao processar sua solicitação: ' . $e->getMessage();
            error_log("[ERRO_REDEFINIR_SENHA] " . $e->getMessage());
        }
    }
    
    // Regenerar CSRF token
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Gerar CSRF token se não existir
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<style>
        .password-reset-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .reset-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 40px;
            max-width: 450px;
            width: 100%;
            animation: slideUp 0.4s ease-out;
        }

        @keyframes slideUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .reset-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .reset-header .logo {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .reset-header h1 {
            color: #333;
            margin: 0 0 10px;
            font-size: 24px;
        }

        .reset-header p {
            color: #666;
            font-size: 14px;
            margin: 0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group input:disabled {
            background-color: #f5f5f5;
            color: #999;
        }

        .password-strength {
            margin-top: 8px;
        }

        .strength-bar {
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            overflow: hidden;
        }

        .strength-indicator {
            height: 100%;
            width: 0%;
            transition: width 0.3s, background-color 0.3s;
            border-radius: 2px;
        }

        .strength-indicator.fraca {
            width: 33%;
            background-color: #f44336;
        }

        .strength-indicator.media {
            width: 66%;
            background-color: #ff9800;
        }

        .strength-indicator.forte {
            width: 100%;
            background-color: #4caf50;
        }

        .strength-text {
            font-size: 12px;
            margin-top: 4px;
            color: #666;
        }

        .strength-text.fraca { color: #f44336; }
        .strength-text.media { color: #ff9800; }
        .strength-text.forte { color: #4caf50; }

        .requirements {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            font-size: 13px;
        }

        .requirements h4 {
            margin: 0 0 10px;
            color: #333;
            font-size: 13px;
        }

        .requirements ul {
            margin: 0;
            padding-left: 20px;
            list-style: none;
        }

        .requirements li {
            margin-bottom: 5px;
            color: #666;
            position: relative;
            padding-left: 20px;
        }

        .requirements li:before {
            content: "○";
            position: absolute;
            left: 0;
            color: #999;
        }

        .requirements li.valid {
            color: #4caf50;
        }

        .requirements li.valid:before {
            content: "✓";
            color: #4caf50;
            font-weight: bold;
        }

        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .message {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-10px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #f5c6cb;
        }

        .links {
            text-align: center;
            margin-top: 20px;
        }

        .links a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }

        .links a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .info-email {
            background: #e3f2fd;
            padding: 10px;
            border-radius: 4px;
            font-size: 13px;
            color: #1565c0;
            margin-bottom: 20px;
            border-left: 4px solid #1976d2;
        }

        .toggle-password {
            position: relative;
        }

        .toggle-password-btn {
            position: absolute;
            right: 12px;
            top: 38px;
            background: none;
            border: none;
            cursor: pointer;
            color: #667eea;
            font-size: 18px;
            padding: 0;
            z-index: 10;
        }

        .toggle-password-btn:hover {
            color: #764ba2;
        }

        @media (max-width: 480px) {
            .reset-card {
                padding: 25px;
            }

            .reset-header h1 {
                font-size: 20px;
            }

            .reset-header .logo {
                font-size: 40px;
            }
        }

        .spinner {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 8px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>

    <div class="password-reset-container">
        <div class="reset-card">
            <div class="reset-header">
                <div class="logo">🔑</div>
                <h1>Redefinir Senha</h1>
                <p>Crie uma nova senha para sua conta</p>
            </div>

            <?php if (!empty($mensagem)): ?>
                <div class="message <?php echo $tipo_mensagem; ?>">
                    <?php echo htmlspecialchars($mensagem); ?>
                </div>
            <?php endif; ?>

            <?php if ($token_valido && !$senha_redefinida): ?>
                <div class="info-email">
                    📧 Email: <strong><?php echo htmlspecialchars($usuario_info['email']); ?></strong>
                </div>

                <form method="POST" id="resetForm">
                    <div class="form-group toggle-password">
                        <label for="nova_senha">Nova Senha</label>
                        <input 
                            type="password" 
                            id="nova_senha" 
                            name="nova_senha" 
                            placeholder="Digite uma nova senha"
                            required
                            autofocus
                        >
                        <button type="button" class="toggle-password-btn" id="toggleSenha1" title="Mostrar senha">👁️</button>

                        <div class="password-strength" id="senhaForca">
                            <div class="strength-bar">
                                <div class="strength-indicator" id="forceBar"></div>
                            </div>
                            <div class="strength-text" id="forceText">Força: Nenhuma</div>
                        </div>
                    </div>

                    <div class="form-group toggle-password">
                        <label for="confirmar_senha">Confirmar Senha</label>
                        <input 
                            type="password" 
                            id="confirmar_senha" 
                            name="confirmar_senha" 
                            placeholder="Confirme sua nova senha"
                            required
                        >
                        <button type="button" class="toggle-password-btn" id="toggleSenha2" title="Mostrar senha">👁️</button>
                        <div id="confirmButton"></div>
                    </div>

                    <div class="requirements">
                        <h4>✓ Requisitos de Senha:</h4>
                        <ul>
                            <li id="req-length">Mínimo 6 caracteres</li>
                            <li id="req-uppercase">Pelo menos 1 letra maiúscula (A-Z)</li>
                            <li id="req-lowercase">Pelo menos 1 letra minúscula (a-z)</li>
                            <li id="req-number">Pelo menos 1 número (0-9)</li>
                        </ul>
                    </div>

                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <span id="btnText">Redefinir Senha</span>
                    </button>
                </form>
            <?php elseif (!$token_valido && !$senha_redefinida): ?>
                <div class="info-email" style="background: #f3e5f5; color: #6a1b9a; border-left-color: #7b1fa2;">
                    Por favor, verifique o link enviado para seu email e tente novamente.
                </div>
            <?php endif; ?>

            <div class="links">
                <a href="?page=login">← Voltar ao Login</a>
            </div>
        </div>
    </div>

    <script>
        // Validação de força de senha em tempo real
        const senhaInput = document.getElementById('nova_senha');
        const confirmarInput = document.getElementById('confirmar_senha');
        const forceBar = document.getElementById('forceBar');
        const forceText = document.getElementById('forceText');
        const reqLength = document.getElementById('req-length');
        const reqUppercase = document.getElementById('req-uppercase');
        const reqLowercase = document.getElementById('req-lowercase');
        const reqNumber = document.getElementById('req-number');

        function calcularForcaSenha(senha) {
            let forca = 0;
            
            // Verifica comprimento
            if (senha.length >= 6) forca += 1;
            if (senha.length >= 12) forca += 1;
            if (senha.length >= 16) forca += 1;
            
            // Verifica caracteres
            if (/[A-Z]/.test(senha)) forca += 1;
            if (/[a-z]/.test(senha)) forca += 1;
            if (/[0-9]/.test(senha)) forca += 1;
            if (/[^A-Za-z0-9]/.test(senha)) forca += 1;
            
            return Math.min(forca, 3);
        }

        function atualizarValidacao(senha) {
            // Comprimento
            if (senha.length >= 6) {
                reqLength.classList.add('valid');
            } else {
                reqLength.classList.remove('valid');
            }
            
            // Maiúscula
            if (/[A-Z]/.test(senha)) {
                reqUppercase.classList.add('valid');
            } else {
                reqUppercase.classList.remove('valid');
            }
            
            // Minúscula
            if (/[a-z]/.test(senha)) {
                reqLowercase.classList.add('valid');
            } else {
                reqLowercase.classList.remove('valid');
            }
            
            // Número
            if (/[0-9]/.test(senha)) {
                reqNumber.classList.add('valid');
            } else {
                reqNumber.classList.remove('valid');
            }

            // Verifica requisitos (força básica)
            const temTodos = /[A-Z]/.test(senha) && /[a-z]/.test(senha) && /[0-9]/.test(senha) && senha.length >= 6;
            
            // Atualiza força visual
            const forca = calcularForcaSenha(senha);
            forceBar.className = 'strength-indicator';
            
            if (forca === 0) {
                forceBar.classList.add('fraca');
                forceText.textContent = 'Força: Fraca';
                forceText.className = 'strength-text fraca';
            } else if (forca === 1 || forca === 2) {
                forceBar.classList.add('media');
                forceText.textContent = 'Força: Média';
                forceText.className = 'strength-text media';
            } else {
                forceBar.classList.add('forte');
                forceText.textContent = 'Força: Forte ✓';
                forceText.className = 'strength-text forte';
            }
        }

        if (senhaInput) {
            senhaInput.addEventListener('input', function() {
                atualizarValidacao(this.value);
            });
        }

        // Toggle visibilidade de senha
        document.getElementById('toggleSenha1')?.addEventListener('click', function() {
            const input = document.getElementById('nova_senha');
            const tipo = input.type === 'password' ? 'text' : 'password';
            input.type = tipo;
            this.textContent = tipo === 'password' ? '👁️' : '🙈';
        });

        document.getElementById('toggleSenha2')?.addEventListener('click', function() {
            const input = document.getElementById('confirmar_senha');
            const tipo = input.type === 'password' ? 'text' : 'password';
            input.type = tipo;
            this.textContent = tipo === 'password' ? '👁️' : '🙈';
        });

        // Validação ao submeter formulário
        document.getElementById('resetForm')?.addEventListener('submit', function(e) {
            const novaSenha = document.getElementById('nova_senha').value;
            const confirmar = document.getElementById('confirmar_senha').value;

            if (novaSenha !== confirmar) {
                e.preventDefault();
                alert('❌ As senhas não coincidem!');
                return;
            }

            if (novaSenha.length < 6) {
                e.preventDefault();
                alert('❌ A senha deve ter no mínimo 6 caracteres!');
                return;
            }

            // Desabilitar botão durante submissão
            const btn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            btn.disabled = true;
            btnText.innerHTML = '<span class="spinner"></span>Redefinindo...';
        });

        // Auto-focus
        document.addEventListener('DOMContentLoaded', function() {
            senhaInput?.focus();
        });
    </script>

