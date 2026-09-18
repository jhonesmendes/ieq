<?php
/**
 * Página de Recuperação de Senha
 * Envia um link de recuperação para o email do usuário
 */

// Verificar se usuário já está autenticado
if (isset($_SESSION['user_id'])) {
    header('Location: index.php?page=dashboard');
    exit;
}

// Gerar CSRF token se não existir
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<style>
        .password-recovery-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .recovery-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 40px;
            max-width: 400px;
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

        .recovery-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .recovery-header .logo {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .recovery-header h1 {
            color: #333;
            margin: 0 0 10px;
            font-size: 24px;
        }

        .recovery-header p {
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
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
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

        .info-box {
            background: #f0f4ff;
            border-left: 4px solid #667eea;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #555;
            line-height: 1.6;
        }

        .info-box strong {
            color: #333;
        }

        .info-box ul {
            margin: 10px 0;
            padding-left: 20px;
        }

        .info-box li {
            margin-bottom: 5px;
        }

        .message {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            animation: slideDown 0.3s ease-out;
            display: none;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #fc5c65;
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
            .recovery-card {
                padding: 25px;
            }

            .recovery-header h1 {
                font-size: 20px;
            }

            .recovery-header .logo {
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

    <div class="password-recovery-container">
        <div class="recovery-card">
            <div class="recovery-header">
                <div class="logo">🔐</div>
                <h1>Recuperar Senha</h1>
                <p>Insira seu email para receber um link de recuperação</p>
            </div>

            <div id="mensagem" class="message" style="display:none;"></div>

            <div id="formulario-container">
                <div class="info-box">
                    <strong>ℹ️ Como funciona:</strong>
                    <ul>
                        <li>Digite o email da sua conta</li>
                        <li>Você receberá um link de recuperação</li>
                        <li>O link é válido por <strong>1 hora</strong></li>
                        <li>Clique no link para redefinir sua senha</li>
                    </ul>
                </div>

                <form method="POST" id="recoveryForm">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            placeholder="seu@email.com"
                            required
                            autofocus
                        >
                    </div>

                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <span id="btnText">Enviar Link de Recuperação</span>
                    </button>
                </form>
            </div>

            <div class="links">
                <a href="?page=login">← Voltar ao Login</a>
                <span style="color: #ddd;">|</span>
                <a href="?page=registro">Criar Conta</a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('recoveryForm')?.addEventListener('submit', function(e) {
            e.preventDefault(); // Previne envio tradicional
            
            const btn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const email = document.getElementById('email').value;
            const csrfToken = document.querySelector('input[name="csrf_token"]').value;
            const mensagemDiv = document.getElementById('mensagem');
            
            if (!email) {
                alert('Por favor, digite seu email');
                return;
            }
            
            // Desabilitar botão e mostrar status
            btn.disabled = true;
            btnText.innerHTML = '<span class="spinner"></span>Enviando...';
            
            console.log('Enviando requisição para API...');
            console.log('Email:', email);
            console.log('CSRF Token:', csrfToken.substring(0, 10) + '...');
            
            // Fazer requisição AJAX para a API
            fetch('/api/index.php?acao=solicitar_recuperacao_senha', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `email=${encodeURIComponent(email)}&csrf_token=${encodeURIComponent(csrfToken)}`
            })
            .then(response => {
                console.log('Status da resposta:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Dados recebidos:', data);
                
                if (data.status === 'sucesso') {
                    // Sucesso - esconder formulário e mostrar mensagem
                    document.getElementById('formulario-container').style.display = 'none';
                    
                    mensagemDiv.className = 'message success';
                    mensagemDiv.style.display = 'block';
                    mensagemDiv.innerHTML = `
                        <strong>✅ Email Enviado!</strong>
                        <p style="margin: 10px 0 0;">Se um email corresponder a uma conta registrada, você receberá instruções de recuperação em breve. Verifique sua caixa de entrada e pasta de spam.</p>
                    `;
                } else {
                    // Erro
                    mensagemDiv.className = 'message error';
                    mensagemDiv.style.display = 'block';
                    mensagemDiv.innerHTML = '<strong>❌ Erro:</strong> ' + (data.mensagem || 'Erro desconhecido');
                    
                    btn.disabled = false;
                    btnText.innerHTML = 'Enviar Link de Recuperação';
                }
            })
            .catch(error => {
                console.error('Erro na requisição:', error);
                
                mensagemDiv.className = 'message error';
                mensagemDiv.style.display = 'block';
                mensagemDiv.innerHTML = '<strong>❌ Erro:</strong> ' + error.message;
                
                btn.disabled = false;
                btnText.innerHTML = 'Enviar Link de Recuperação';
            });
        });

        // Auto-focus no campo de email
        document.addEventListener('DOMContentLoaded', function() {
            const emailInput = document.getElementById('email');
            if (emailInput && !emailInput.value) {
                emailInput.focus();
            }
        });
    </script>
