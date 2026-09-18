<?php
/**
 * Página de Redefinição de Senha - VERSÃO SIMPLIFICADA
 */

// Debug
echo "<!-- DEBUG: Página redefinir_senha_simples.php carregada -->";

// Verificar token
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

if (!empty($token)) {
    // Validar token
    $resultado = validar_token_recuperacao($token);
    
    if ($resultado['valido']) {
        $token_valido = true;
        $email = $resultado['email'];
        $usuario_id = $resultado['usuario_id'];
        $mensagem = '';
    } else {
        $token_valido = false;
        $mensagem = 'Token inválido ou expirado!';
        $email = '';
    }
} else {
    $token_valido = false;
    $token = '';
    $mensagem = 'Token não fornecido!';
    $email = '';
}
?>

<style>
    .password-reset-simple {
        max-width: 500px;
        margin: 50px auto;
        padding: 40px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .password-reset-simple h1 {
        color: #333;
        margin-top: 0;
    }
    
    .password-reset-simple .form-group {
        margin-bottom: 20px;
    }
    
    .password-reset-simple label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
    }
    
    .password-reset-simple input {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
        box-sizing: border-box;
    }
    
    .password-reset-simple input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .password-reset-simple button {
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
    }
    
    .password-reset-simple button:hover {
        background: #5568d3;
    }
    
    .password-reset-simple button:disabled {
        background: #ccc;
        cursor: not-allowed;
    }
    
    .message {
        padding: 15px;
        border-radius: 4px;
        margin-bottom: 20px;
        font-weight: 600;
    }
    
    .message.success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .message.error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .info-email {
        background: #e3f2fd;
        padding: 12px;
        border-radius: 4px;
        margin-bottom: 20px;
        font-size: 14px;
        color: #1565c0;
    }
</style>

<div class="password-reset-simple">
    <h1>🔑 Redefinir Senha</h1>
    
    <?php if (!empty($mensagem)): ?>
        <div class="message error">
            <?php echo htmlspecialchars($mensagem); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($token_valido): ?>
        <div class="info-email">
            📧 <strong><?php echo htmlspecialchars($email); ?></strong>
        </div>
        
        <form method="POST" id="resetForm">
            <div class="form-group">
                <label for="nova_senha">Nova Senha</label>
                <input 
                    type="password"
                    id="nova_senha"
                    name="nova_senha"
                    placeholder="Mínimo 6 caracteres"
                    required
                    autofocus
                >
            </div>
            
            <div class="form-group">
                <label for="confirmar_senha">Confirmar Senha</label>
                <input
                    type="password"
                    id="confirmar_senha"
                    name="confirmar_senha"
                    placeholder="Repita a nova senha"
                    required
                >
            </div>
            
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            
            <button type="submit">Redefinir Senha</button>
        </form>
        
        <p style="text-align: center; margin-top: 20px; color: #666; font-size: 14px;">
            <a href="?page=login" style="color: #667eea;">← Voltar ao Login</a>
        </p>
        
    <?php else: ?>
        <div class="message error">
            ❌ Este link é inválido ou expirou. <a href="?page=recuperar_senha" style="color: inherit; text-decoration: underline;">Solicitar novo link</a>
        </div>
    <?php endif; ?>
</div>

<script>
    document.getElementById('resetForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const novaSenha = document.getElementById('nova_senha').value;
        const confirmar = document.getElementById('confirmar_senha').value;
        const token = document.querySelector('input[name="token"]').value;
        const csrf = document.querySelector('input[name="csrf_token"]').value;
        
        if (novaSenha !== confirmar) {
            alert('As senhas não coincidem!');
            return;
        }
        
        if (novaSenha.length < 6) {
            alert('A senha deve ter no mínimo 6 caracteres!');
            return;
        }
        
        // Enviar para API
        fetch('/api/index.php?acao=redefinir_senha', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `token=${encodeURIComponent(token)}&nova_senha=${encodeURIComponent(novaSenha)}&csrf_token=${encodeURIComponent(csrf)}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'sucesso') {
                alert('✓ Senha redefinida com sucesso! Faça login com sua nova senha.');
                window.location.href = '?page=login';
            } else {
                alert('✗ Erro: ' + (data.mensagem || 'Erro desconhecido'));
            }
        })
        .catch(e => {
            alert('✗ Erro na requisição: ' + e.message);
        });
    });
</script>
