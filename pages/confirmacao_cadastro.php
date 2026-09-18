<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro Pendente - IEQ</title>
    <link rel="stylesheet" href="../assets/css/login.css">
    <style>
        .confirmacao-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }

        .confirmacao-box {
            background: white;
            padding: 50px 40px;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 450px;
            text-align: center;
        }

        .icon-pending {
            font-size: 80px;
            margin-bottom: 20px;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .confirmacao-box h1 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .confirmacao-box p {
            color: #666;
            margin-bottom: 20px;
            font-size: 16px;
            line-height: 1.6;
        }

        .status-badge {
            display: inline-block;
            background: #fff3cd;
            color: #856404;
            padding: 12px 24px;
            border-radius: 20px;
            font-weight: bold;
            margin: 20px 0;
            border-left: 4px solid #ffc107;
        }

        .info-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin: 30px 0;
            text-align: left;
            border-left: 4px solid #667eea;
        }

        .info-box h3 {
            color: #667eea;
            margin-top: 0;
            font-size: 16px;
        }

        .info-box ul {
            margin: 10px 0;
            padding-left: 20px;
        }

        .info-box li {
            color: #555;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .btn-login {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 40px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.3s;
            margin-top: 20px;
        }

        .btn-login:hover {
            background: #5568d3;
        }

        .timer {
            color: #999;
            font-size: 12px;
            margin-top: 30px;
        }

        .timer strong {
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="confirmacao-container">
        <div class="confirmacao-box">
            <div class="icon-pending">⏳</div>
            
            <h1>Cadastro Pendente</h1>
            
            <div class="status-badge">
                Aguardando Aprovação do Administrador
            </div>

            <p>
                Seu cadastro foi criado com sucesso! 
                Agora você precisa aguardar a aprovação de um administrador.
            </p>

            <div class="info-box">
                <h3>📋 O que fazer agora?</h3>
                <ul>
                    <li>✅ Seu cadastro foi registrado no sistema</li>
                    <li>⏳ Um administrador analisará suas informações</li>
                    <li>📧 Você receberá um email quando for aprovado</li>
                    <li>🔐 Após aprovação, poderá fazer login no sistema</li>
                </ul>
            </div>

            <p style="color: #999; font-size: 14px;">
                O processo de aprovação geralmente leva até 24 horas.
            </p>

            <a href="/pages/login.php" class="btn-login">Voltar para Login</a>

            <div class="timer">
                Você será redirecionado em <strong id="countdown">10</strong> segundos...
            </div>
        </div>
    </div>

    <script>
        // Redirecionamento automático após 10 segundos
        let count = 10;
        const timer = setInterval(() => {
            count--;
            document.getElementById('countdown').textContent = count;
            if (count <= 0) {
                clearInterval(timer);
                window.location.href = '/index.php?page=login';
            }
        }, 1000);
    </script>
</body>
</html>
