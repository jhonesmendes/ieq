<?php
/**
 * Sistema de Envio de Emails
 * Configurações e funções para enviar emails
 */

// Carregar variáveis de ambiente
if (file_exists(__DIR__ . '/../.env')) {
    $env_lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($env_lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (strpos($key, 'EMAIL_') === 0 || strpos($key, 'SMTP_') === 0) {
                putenv("$key=$value");
            }
        }
    }
}

// Configurações de Email
define('EMAIL_DE_ENDERECO', getenv('EMAIL_DE_ENDERECO') ?: 'noreply@ieq.local');
define('EMAIL_DE_NOME', getenv('EMAIL_DE_NOME') ?: 'IEQ - Sistema de Gestão');
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'localhost');
define('SMTP_PORT', getenv('SMTP_PORT') ?: '587');
define('SMTP_USER', getenv('SMTP_USER') ?: '');
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
define('SMTP_SEGURANCA', getenv('SMTP_SEGURANCA') ?: 'tls'); // tls ou ssl

/**
 * Enviar email de recuperação de senha
 * @param string $para Email do destinatário
 * @param string $nome Nome do usuário
 * @param string $token Token de recuperação
 * @param string $url_base URL base do sistema
 * @return array Resultado do envio
 */
function enviar_email_recuperacao_senha($para, $nome, $token, $url_base = null) {
    if (!$url_base) {
        // Calcular a URL base corretamente (raiz do projeto, não da API)
        $url_base = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        
        // Se for dentro de uma subpasta, incluir (ex: /ieq)
        $script = $_SERVER['SCRIPT_NAME']; // /ieq/api/index.php ou /ieq/index.php
        $dir = dirname(dirname($script)); // Volta dois níveis: /ieq
        if ($dir !== '/' && !empty($dir)) {
            $url_base .= $dir;
        }
    }
    
    // URL do link de recuperação
    $link_recuperacao = rtrim($url_base, '/') . '/?page=redefinir_senha&token=' . $token;
    
    // Assunto
    $assunto = 'Recuperação de Senha - IEQ';
    
    // Corpo do email em HTML
    $corpo_html = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f5f5f5; }
            .email-content { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            .header { text-align: center; margin-bottom: 30px; }
            .header h1 { color: #667eea; margin: 0; }
            .logo { font-size: 32px; margin: 10px 0; }
            .content { line-height: 1.6; }
            .content p { margin-bottom: 15px; }
            .botao { display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 6px; margin: 20px 0; font-weight: bold; }
            .aviso { background: #fff3cd; padding: 15px; border-radius: 6px; margin: 20px 0; border-left: 4px solid #ffc107; }
            .aviso-titulo { font-weight: bold; color: #856404; }
            .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px; }
            .expiracao { color: #d32f2f; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='email-content'>
                <div class='header'>
                    <div class='logo'>🔐</div>
                    <h1>Recuperação de Senha</h1>
                </div>
                
                <div class='content'>
                    <p>Olá <strong>$nome</strong>,</p>
                    
                    <p>Recebemos uma solicitação para recuperar a senha da sua conta no sistema IEQ.</p>
                    
                    <p style='text-align: center;'>
                        <a href='$link_recuperacao' class='botao'>🔓 Redefinir Minha Senha</a>
                    </p>
                    
                    <p>Ou copie e cole este link no seu navegador:</p>
                    <p style='word-break: break-all; background: #f5f5f5; padding: 10px; border-radius: 4px; font-size: 12px;'>
                        $link_recuperacao
                    </p>
                    
                    <div class='aviso'>
                        <div class='aviso-titulo'>⚠️ Atenção</div>
                        <ul style='margin: 10px 0; padding-left: 20px;'>
                            <li>Este link é <span class='expiracao'>válido por apenas 1 hora</span></li>
                            <li>Se você não solicitou esta recuperação, desconsidere este email</li>
                            <li>Nunca compartilhe este link com outras pessoas</li>
                            <li>Por segurança, este link só pode ser usado uma vez</li>
                        </ul>
                    </div>
                    
                    <p>Se você não conseguir clicar no botão acima, entre em contato com o administrador do sistema.</p>
                </div>
                
                <div class='footer'>
                    <p>IEQ - Sistema de Gestão de Células</p>
                    <p>Este é um email automático. Não responda neste endereço.</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Corpo em texto puro (fallback)
    $corpo_texto = "
Recuperação de Senha - IEQ

Olá $nome,

Recebemos uma solicitação para recuperar a senha da sua conta no sistema IEQ.

Clique no link abaixo para redefinir sua senha:
$link_recuperacao

IMPORTANTE:
- Este link é válido por apenas 1 hora
- Se você não solicitou esta recuperação, desconsidere este email
- Nunca compartilhe este link com outras pessoas
- Por segurança, este link só pode ser usado uma vez

---
IEQ - Sistema de Gestão de Células
Este é um email automático. Não responda neste endereço.
    ";
    
    // Enviar email
    return enviar_email($para, $assunto, $corpo_html, $corpo_texto);
}

/**
 * Enviar email genérico
 * @param string $para Email do destinatário
 * @param string $assunto Assunto do email
 * @param string $corpo_html Corpo em HTML
 * @param string $corpo_texto Corpo em texto puro (opcional)
 * @return array Resultado do envio
 */
function enviar_email($para, $assunto, $corpo_html, $corpo_texto = null) {
    try {
        // Usar PHPMailer se disponível
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            return enviar_email_phpmailer($para, $assunto, $corpo_html, $corpo_texto);
        } 
        // Usar SMTP customizado com Gmail
        else if (!empty(SMTP_HOST) && !empty(SMTP_USER) && !empty(SMTP_PASS)) {
            return enviar_email_smtp_custom($para, $assunto, $corpo_html, $corpo_texto);
        }
        // Fallback para mail() nativa
        else {
            return enviar_email_nativo($para, $assunto, $corpo_html, $corpo_texto);
        }
    } catch (Exception $e) {
        error_log("Erro ao enviar email para $para: " . $e->getMessage());
        return [
            'sucesso' => false,
            'mensagem' => 'Erro ao enviar email: ' . $e->getMessage()
        ];
    }
}

/**
 * Enviar email usando SMTP customizado (Gmail)
 */
function enviar_email_smtp_custom($para, $assunto, $corpo_html, $corpo_texto = null) {
    try {
        // Usar SSL direto para porta 465, TLS para porta 587
        $protocol = (SMTP_PORT == 465) ? 'ssl' : 'tcp';
        
        // Conectar ao servidor SMTP com SSL/TLS
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);
        
        $smtp = stream_socket_client(
            "$protocol://" . SMTP_HOST . ":" . SMTP_PORT,
            $errno, 
            $errstr, 
            10,
            STREAM_CLIENT_CONNECT,
            $context
        );
        
        if (!$smtp) {
            throw new Exception("Não foi possível conectar: $errstr ($errno)");
        }
        
        stream_set_timeout($smtp, 5);
        
        // Função auxiliar para ler resposta multi-linha
        $read_response = function($stream) {
            $response = '';
            while ($line = fgets($stream, 1024)) {
                $response .= $line;
                if (substr($line, 3, 1) === ' ') break;
            }
            return $response;
        };
        
        // Ler resposta inicial
        $response = $read_response($smtp);
        if (strpos($response, '220') === false) {
            throw new Exception("Resposta inesperada: $response");
        }
        
        // Enviar EHLO
        fputs($smtp, "EHLO localhost\r\n");
        $response = $read_response($smtp);
        
        // Se for porta 587, fazer STARTTLS
        if (SMTP_PORT == 587) {
            fputs($smtp, "STARTTLS\r\n");
            $response = $read_response($smtp);
            
            if (strpos($response, '220') === false) {
                throw new Exception("STARTTLS falhou: $response");
            }
            
            // Habilitar criptografia TLS
            if (!stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT)) {
                throw new Exception("Falha ao ativar criptografia TLS");
            }
            
            // Enviar EHLO novamente após TLS
            fputs($smtp, "EHLO localhost\r\n");
            $response = $read_response($smtp);
        }
        
        // Autenticar
        fputs($smtp, "AUTH LOGIN\r\n");
        $response = $read_response($smtp);
        
        if (strpos($response, '334') === false) {
            throw new Exception("AUTH LOGIN falhou: $response");
        }
        
        // Enviar usuário (base64)
        fputs($smtp, base64_encode(SMTP_USER) . "\r\n");
        $response = $read_response($smtp);
        
        // Enviar senha (base64)
        fputs($smtp, base64_encode(SMTP_PASS) . "\r\n");
        $response = $read_response($smtp);
        
        if (strpos($response, '235') === false) {
            throw new Exception("Autenticação falhou: $response");
        }
        
        // Enviar email
        fputs($smtp, "MAIL FROM:<" . EMAIL_DE_ENDERECO . ">\r\n");
        $response = $read_response($smtp);
        
        if (strpos($response, '250') === false) {
            throw new Exception("MAIL FROM falhou: $response");
        }
        
        fputs($smtp, "RCPT TO:<$para>\r\n");
        $response = $read_response($smtp);
        
        if (strpos($response, '250') === false) {
            throw new Exception("RCPT TO falhou: $response");
        }
        
        fputs($smtp, "DATA\r\n");
        $response = $read_response($smtp);
        
        if (strpos($response, '354') === false) {
            throw new Exception("DATA falhou: $response");
        }
        
        // Construir mensagem completa
        $headers = "From: " . EMAIL_DE_NOME . " <" . EMAIL_DE_ENDERECO . ">\r\n";
        $headers .= "To: $para\r\n";
        $headers .= "Subject: $assunto\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "X-Priority: 3\r\n";
        
        $mensagem = $headers . "\r\n" . $corpo_html;
        
        // Escapa pontos no começo de linhas
        $linhas = explode("\r\n", $mensagem);
        $mensagem_final = "";
        foreach ($linhas as $linha) {
            if (substr($linha, 0, 1) === '.') {
                $mensagem_final .= '.' . $linha . "\r\n";
            } else {
                $mensagem_final .= $linha . "\r\n";
            }
        }
        
        fputs($smtp, $mensagem_final . "\r\n.\r\n");
        $response = $read_response($smtp);
        
        if (strpos($response, '250') === false) {
            throw new Exception("Envio falhou: $response");
        }
        
        // Desconectar
        fputs($smtp, "QUIT\r\n");
        fclose($smtp);
        
        error_log("Email enviado com sucesso para $para via SMTP");
        return [
            'sucesso' => true,
            'mensagem' => 'Email enviado com sucesso'
        ];
        
    } catch (Exception $e) {
        error_log("Erro ao enviar email via SMTP para $para: " . $e->getMessage());
        return [
            'sucesso' => false,
            'mensagem' => 'Erro ao enviar email: ' . $e->getMessage()
        ];
    }
}

/**
 * Enviar email usando função nativa mail()
 */
function enviar_email_nativo($para, $assunto, $corpo_html, $corpo_texto = null) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    $headers .= 'From: ' . EMAIL_DE_NOME . ' <' . EMAIL_DE_ENDERECO . '>' . "\r\n";
    $headers .= 'Reply-To: ' . EMAIL_DE_ENDERECO . "\r\n";
    $headers .= 'X-Mailer: PHP/' . phpversion() . "\r\n";
    
    // Usar corpo em texto se disponível, senão usar HTML
    $corpo = $corpo_texto ?? strip_tags($corpo_html);
    
    $enviado = mail($para, $assunto, $corpo_html, $headers);
    
    if ($enviado) {
        error_log("Email enviado com sucesso para $para");
        return [
            'sucesso' => true,
            'mensagem' => 'Email enviado com sucesso'
        ];
    } else {
        error_log("Falha ao enviar email para $para (usando mail())");
        return [
            'sucesso' => false,
            'mensagem' => 'Falha ao enviar email usando mail()'
        ];
    }
}

/**
 * Enviar email usando PHPMailer
 */
function enviar_email_phpmailer($para, $assunto, $corpo_html, $corpo_texto = null) {
    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        
        // Configurações do SMTP
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->Port = SMTP_PORT;
        $mail->SMTPSecure = SMTP_SEGURANCA;
        
        if (SMTP_USER && SMTP_PASS) {
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
        }
        
        // Remetente
        $mail->setFrom(EMAIL_DE_ENDERECO, EMAIL_DE_NOME);
        
        // Destinatário
        $mail->addAddress($para);
        
        // Assunto e corpo
        $mail->Subject = $assunto;
        $mail->isHTML(true);
        $mail->Body = $corpo_html;
        $mail->AltBody = $corpo_texto ?? strip_tags($corpo_html);
        
        // Enviar
        $enviado = $mail->send();
        
        if ($enviado) {
            error_log("Email enviado com sucesso para $para via PHPMailer");
            return [
                'sucesso' => true,
                'mensagem' => 'Email enviado com sucesso'
            ];
        }
        
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        error_log("Erro ao enviar email para $para: " . $mail->ErrorInfo);
        return [
            'sucesso' => false,
            'mensagem' => 'Erro ao enviar email: ' . $mail->ErrorInfo
        ];
    }
}

/**
 * Enviar email de aprovação de cadastro (sem senha temporária)
 * Usa a senha que o usuário cadastrou
 * @param string $para Email do destinatário
 * @param string $nome Nome do usuário
 * @param string $url_base URL base do sistema
 * @return array Resultado do envio
 */
function enviar_email_aprovacao_cadastro($para, $nome, $url_base = null) {
    if (!$url_base) {
        // Calcular a URL base corretamente (raiz do projeto, não da API)
        $url_base = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        
        // Se for dentro de uma subpasta, incluir (ex: /ieq)
        $script = $_SERVER['SCRIPT_NAME']; // /ieq/api/index.php ou /ieq/index.php
        $dir = dirname(dirname($script)); // Volta dois níveis: /ieq
        if ($dir !== '/' && !empty($dir)) {
            $url_base .= $dir;
        }
    }
    
    // URL de login
    $link_login = rtrim($url_base, '/') . '/?page=login';
    
    // Assunto
    $assunto = 'Sua Conta foi Aprovada! - IEQ';
    
    // Corpo do email em HTML
    $corpo_html = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f5f5f5; }
            .email-content { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            .header { text-align: center; margin-bottom: 30px; }
            .header h1 { color: #28a745; margin: 0; }
            .logo { font-size: 32px; margin: 10px 0; }
            .content { line-height: 1.6; }
            .content p { margin-bottom: 15px; }
            .botao { display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; text-decoration: none; border-radius: 6px; margin: 20px 0; font-weight: bold; }
            .aviso { background: #e7f3ff; padding: 15px; border-radius: 6px; margin: 20px 0; border-left: 4px solid #2196F3; }
            .aviso-titulo { font-weight: bold; color: #0c5aa0; }
            .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='email-content'>
                <div class='header'>
                    <div class='logo'>✅</div>
                    <h1>Parabéns!</h1>
                    <p style='color: #666; margin: 10px 0;'>Sua conta foi aprovada com sucesso</p>
                </div>
                
                <div class='content'>
                    <p>Olá <strong>$nome</strong>,</p>
                    
                    <p>Sua conta no sistema <strong>IEQ</strong> foi aprovada pelos administradores! Você já pode acessar o sistema com a senha que cadastrou durante o registro.</p>
                    
                    <p style='text-align: center;'>
                        <a href='$link_login' class='botao'>🔐 Acessar o Sistema</a>
                    </p>
                    
                    <div class='aviso'>
                        <div class='aviso-titulo'>📋 Dados de Acesso</div>
                        <ul style='margin: 10px 0; padding-left: 20px;'>
                            <li><strong>Email:</strong> $para</li>
                            <li><strong>Senha:</strong> A senha que você cadastrou durante o registro</li>
                        </ul>
                    </div>
                    
                    <p><strong>Próximos passos:</strong></p>
                    <ol style='margin: 10px 0; padding-left: 20px;'>
                        <li>Clique no botão de acesso acima ou vá em $link_login</li>
                        <li>Use seu email e a senha cadastrada para fazer login</li>
                        <li>Complemente seu perfil com informações pessoais</li>
                        <li>Bem-vindo ao sistema!</li>
                    </ol>
                </div>
                
                <div class='footer'>
                    <p>IEQ - Sistema de Gestão de Células</p>
                    <p>Este é um email automático. Não responda neste endereço.</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Corpo em texto puro (fallback)
    $corpo_texto = "
Sua Conta foi Aprovada! - IEQ

Olá $nome,

Sua conta no sistema IEQ foi aprovada pelos administradores! Você já pode acessar com a senha que cadastrou.

DADOS DE ACESSO:
Email: $para
Senha: A senha que você cadastrou durante o registro

ACESSAR O SISTEMA:
$link_login

Use seu email e a senha cadastrada para fazer login.

---
IEQ - Sistema de Gestão de Células
Este é um email automático. Não responda neste endereço.
    ";
    
    // Enviar email
    return enviar_email($para, $assunto, $corpo_html, $corpo_texto);
}

/**
 * Testar configurações de email
 */
function testar_configuracoes_email() {
    $resultado = [
        'smtp_configurado' => SMTP_USER && SMTP_PASS,
        'email_padrao' => EMAIL_DE_ENDERECO,
        'email_nome' => EMAIL_DE_NOME,
        'smtp_host' => SMTP_HOST,
        'smtp_port' => SMTP_PORT,
        'phpmailer_disponivel' => class_exists('PHPMailer\PHPMailer\PHPMailer'),
        'mail_nativo_disponivel' => function_exists('mail')
    ];
    
    return $resultado;
}
