<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - IEQ Gestão de Células</title>
    <link rel="stylesheet" href="../assets/css/login.css">
    <style>
        .registro-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }

        .registro-box {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }

        .registro-box h1 {
            text-align: center;
            color: #667eea;
            margin-bottom: 5px;
            font-size: 28px;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .form-item {
            margin-bottom: 20px;
        }

        .form-item label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .form-item input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }

        .form-item input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
            margin-top: 10px;
        }

        .btn:hover {
            background: #5568d3;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 14px;
        }

        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .mensagem {
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 14px;
            display: none;
        }

        .mensagem-sucesso {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .mensagem-erro {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
    </style>
</head>
<body>
    <div class="registro-container">
        <div class="registro-box">
            <h1>📝 Registre-se</h1>
            <p class="subtitle">Crie sua conta para gerenciar sua célula</p>

            <div id="mensagem" class="mensagem"></div>

            <form id="registroForm" class="form-group">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(isset($csrf_token) ? $csrf_token : ''); ?>">
                
                <div class="form-item">
                    <label for="nome">Nome Completo</label>
                    <input type="text" id="nome" name="nome" required placeholder="Seu nome completo">
                </div>

                <div class="form-item">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required placeholder="seu@email.com">
                </div>

                <div class="form-item">
                    <label for="telefone">Telefone (opcional)</label>
                    <input type="tel" id="telefone" name="telefone" placeholder="(XX) XXXXX-XXXX">
                </div>

                <div class="form-item">
                    <label for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" required placeholder="Mínimo 6 caracteres">
                </div>

                <div class="form-item">
                    <label for="senha_confirma">Confirmar Senha</label>
                    <input type="password" id="senha_confirma" name="senha_confirma" required placeholder="Confirme sua senha">
                </div>

                <button type="submit" class="btn">Registrar</button>
            </form>

            <p class="login-link">Já tem conta? <a href="/pages/login.php">Faça login aqui</a></p>
        </div>
    </div>

    <script>
        document.getElementById('registroForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const nome = document.getElementById('nome').value.trim();
            const email = document.getElementById('email').value.trim();
            const telefone = document.getElementById('telefone').value.trim();
            const senha = document.getElementById('senha').value;
            const senhaConfirma = document.getElementById('senha_confirma').value;
            const mensagemEl = document.getElementById('mensagem');

            // Validações básicas
            if (!nome) {
                exibirMensagem('❌ Por favor, digite seu nome completo', 'erro');
                return;
            }

            if (!email) {
                exibirMensagem('❌ Por favor, digite seu email', 'erro');
                return;
            }

            if (!email.includes('@')) {
                exibirMensagem('❌ Email inválido', 'erro');
                return;
            }

            if (senha.length < 6) {
                exibirMensagem('❌ A senha deve ter no mínimo 6 caracteres', 'erro');
                return;
            }

            if (senha !== senhaConfirma) {
                exibirMensagem('❌ As senhas não conferem', 'erro');
                return;
            }

            // Mostrar loading
            exibirMensagem('⏳ Criando sua conta...', 'info');

            fetch('/api/register.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `nome=${encodeURIComponent(nome)}&email=${encodeURIComponent(email)}&telefone=${encodeURIComponent(telefone)}&senha=${encodeURIComponent(senha)}`,
                credentials: 'include'
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.mensagem || 'Erro no registro');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'sucesso') {
                    exibirMensagem('✅ ' + data.mensagem, 'sucesso');
                    document.getElementById('registroForm').reset();
                    setTimeout(() => {
                        window.location.href = '/pages/confirmacao_cadastro.php';
                    }, 1500);
                } else {
                    exibirMensagem('❌ ' + (data.mensagem || 'Erro desconhecido'), 'erro');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                exibirMensagem('❌ ' + error.message, 'erro');
            });

            function exibirMensagem(mensagem, tipo) {
                mensagemEl.innerHTML = mensagem;
                mensagemEl.className = 'mensagem';
                if (tipo === 'sucesso') {
                    mensagemEl.classList.add('mensagem-sucesso');
                } else if (tipo === 'erro') {
                    mensagemEl.classList.add('mensagem-erro');
                }
                mensagemEl.style.display = 'block';
            }
        });
    </script>
</body>
</html>
