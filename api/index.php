<?php
// Ativar exibição de erros para desenvolvimento
error_reporting(E_ALL);
ini_set('display_errors', 0); // Não exibir erros no output
ini_set('log_errors', 1); // Logar erros

// ============ CARREGAR .ENV ============
$env_file = __DIR__ . '/../.env';
if (file_exists($env_file)) {
    $env_lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($env_lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // Sempre setar (sobrescrever se existir)
            putenv("$key=$value");
            $_ENV[$key] = $value; // Também salvar em $_ENV
        }
    }
}
// =====================================

// CORS Headers - Permitir requisições da mesma origem
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Responder a requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Handler de erros customizado
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error [$errno]: $errstr in $errfile on line $errline");
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

// Handler de exceções
set_exception_handler(function($exception) {
    error_log("Exception: " . $exception->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Erro no servidor: ' . $exception->getMessage(),
        'dados' => []
    ]);
    exit;
});

// Inicia sessão apenas se não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once '../config/seguranca.php';
require_once '../config/auth.php';
require_once '../config/membros.php';
require_once '../config/celulas.php';
require_once '../config/presenca.php';
require_once '../config/eventos.php';
require_once '../config/cursos.php';
require_once '../config/igrejas.php';
require_once '../config/visitantes.php';
require_once '../config/permissoes.php';
// require_once '../config/telegram.php'; // DESABILITADO TEMPORARIAMENTE
require_once '../config/upload.php';

// Inicializar banco de dados
initDatabase();

// Obter ação da API
$acao = $_GET['acao'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Funções de resposta
function responder($status, $mensagem, $dados = []) {
    echo json_encode([
        'status' => $status,
        'mensagem' => $mensagem,
        'dados' => $dados
    ]);
    exit;
}

// Obter dados JSON do POST
function obter_json_post() {
    $input = file_get_contents('php://input');
    $dados = json_decode($input, true);
    return is_array($dados) ? $dados : $_POST;
}

// Autenticação
// Exceções: login, registrar, e processar_telegram_presenca (quando com token)
$requer_autenticacao = true;

// Se for processar_telegram_presenca, valida token ao invés de sessão
if ($acao === 'processar_telegram_presenca') {
    $token_fornecido = $_GET['token'] ?? $_POST['token'] ?? null;
    $webhook_token = getenv('TELEGRAM_WEBHOOK_TOKEN');
    
    // Se tem token correto, não requer autenticação de sessão
    if (!empty($webhook_token) && $token_fornecido === $webhook_token) {
        $requer_autenticacao = false;
    }
}

// Ações que não requerem autenticação
$acoes_publicas = [
    'login',
    'registrar',
    'solicitar_recuperacao_senha',
    'validar_token_recuperacao',
    'redefinir_senha'
];

if ($requer_autenticacao && !isset($_SESSION['user_id']) && !in_array($acao, $acoes_publicas)) {
    responder('erro', 'Usuário não autenticado');
}

// Validar CSRF para operações sensíveis (POST, PUT, DELETE)
// Exceções: login, registrar, listar (GET safe) e ações de recuperação de senha
if (($_SERVER['REQUEST_METHOD'] === 'POST' || 
     $_SERVER['REQUEST_METHOD'] === 'PUT' || 
     $_SERVER['REQUEST_METHOD'] === 'DELETE') &&
    $acao !== 'login' && 
    $acao !== 'registrar' &&
    $acao !== 'solicitar_recuperacao_senha' &&
    $acao !== 'validar_token_recuperacao' &&
    $acao !== 'redefinir_senha') {
    
    // Obter token de múltiplas fontes
    $csrf_token = $_POST['csrf_token'] ?? 
                  $_GET['csrf_token'] ??
                  (json_decode(file_get_contents('php://input'), true)['csrf_token'] ?? null) ??
                  $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    
    if (!$csrf_token || !validar_csrf_token($csrf_token)) {
        registrar_log_seguranca('CSRF_FALHOU', [
            'acao' => $acao,
            'method' => $_SERVER['REQUEST_METHOD'],
            'ip' => obter_ip_cliente()
        ]);
        responder('erro', 'Token CSRF inválido ou expirado');
    }
}

// Rotas da API
try {
    switch ($acao) {
        // =============== AUTENTICAÇÃO ===============
        case 'login':
        if ($method === 'POST') {
            // Obtém token de múltiplas fontes (POST, Header, ou GET)
            $csrf_token = $_POST['csrf_token'] ?? 
                         $_SERVER['HTTP_X_CSRF_TOKEN'] ?? 
                         $_GET['csrf_token'] ?? null;
            
            // Verificar Rate Limiting para login
            verificar_rate_limit('login');
            
            // Validar CSRF token
            if (!$csrf_token || !validar_csrf_token($csrf_token)) {
                registrar_tentativa_acesso('login', false);
                responder('erro', 'Token de segurança inválido');
            }
            
            $email = $_POST['email'] ?? '';
            $senha = $_POST['senha'] ?? '';
            
            // Usar função detalhada para obter informações sobre o erro
            $resultado_auth = autenticar_usuario_detalhado($email, $senha);
            
            if ($resultado_auth['sucesso']) {
                // Sucesso no login - resetar contador de tentativas
                registrar_tentativa_acesso('login', true);
                
                // Gerar novo token ANTES de enviar resposta
                gerar_csrf_token();
                $novo_token = obter_csrf_token();
                
                // Log de sucesso
                registrar_log_seguranca('LOGIN_SUCESSO', [
                    'email' => $email, 
                    'user_id' => $_SESSION['user_id'],
                    'user_nome' => $_SESSION['user_nome']
                ]);
                
                responder('sucesso', 'Login realizado com sucesso', [
                    'user_id' => $_SESSION['user_id'],
                    'user_nome' => $_SESSION['user_nome'],
                    'csrf_token' => $novo_token
                ]);
            } else {
                // Falha no login - incrementar contador
                registrar_tentativa_acesso('login', false);
                
                // Log detalhado da falha com motivo específico
                registrar_log_seguranca('FALHA_LOGIN', [
                    'email' => $email,
                    'motivo' => $resultado_auth['motivo'],
                    'mensagem' => $resultado_auth['mensagem']
                ]);
                
                responder('erro', 'Email ou senha inválidos');
            }
        }
        break;

    case 'registrar':
        if ($method === 'POST') {
            // Validar CSRF token
            $csrf_token = $_POST['csrf_token'] ?? null;
            if (!$csrf_token || !validar_csrf_token($csrf_token)) {
                responder('erro', 'Token de segurança inválido');
            }
            
            // Rate limiting para registro (20 registros por minuto)
            verificar_rate_limit('registrar');
            
            $nome = $_POST['nome'] ?? '';
            $email = $_POST['email'] ?? '';
            $senha = $_POST['senha'] ?? '';
            $telefone = $_POST['telefone'] ?? '';
            $funcao = $_POST['funcao'] ?? 'membro';
            
            if (empty($nome) || empty($senha)) {
                responder('erro', 'Nome e senha são obrigatórios');
            }
            
            // Email é opcional agora - se vazio, criar um automático
            if (empty($email)) {
                // Gerar um email temporário que será atualizado depois
                $email = 'membro_' . time() . '_' . rand(1000, 9999) . '@ieq.local';
            }
            
            $user_id = registrar_usuario($nome, $email, $senha, $telefone, $funcao);
            
            if ($user_id) {
                registrar_tentativa_acesso('registrar', true);
                responder('sucesso', 'Usuário registrado com sucesso', ['user_id' => $user_id]);
            } else {
                responder('erro', 'Email já existe ou erro ao registrar');
            }
        }
        break;

    // =============== RECUPERAÇÃO DE SENHA ===============
    case 'solicitar_recuperacao_senha':
        if ($method === 'POST') {
            // Rate limiting para recuperação
            verificar_rate_limit('recuperacao_senha');
            
            $email = $_POST['email'] ?? '';
            
            if (empty($email)) {
                responder('erro', 'Email é obrigatório');
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                responder('erro', 'Email inválido');
            }
            
            try {
                // Verificar se email existe (sem revelar se existe ou não)
                $db = getDB();
                $stmt = $db->prepare("SELECT id, nome FROM usuarios WHERE email = ?");
                $stmt->execute([$email]);
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Resposta genérica por segurança (mesmo que email não exista)
                $resposta = [
                    'mensagem' => 'Se um email corresponder a uma conta registrada, você receberá instruções em breve.'
                ];
                
                if ($usuario) {
                    // Gerar token
                    $resultado_token = gerar_token_recuperacao($email);
                    
                    if ($resultado_token && isset($resultado_token['token'])) {
                        // Carregar config de email
                        require_once __DIR__ . '/../config/email.php';
                        
                        // Construir URL base (raiz do projeto, não da API)
                        $protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
                        $host = $_SERVER['HTTP_HOST'];
                        $script = $_SERVER['SCRIPT_NAME']; // /ieq/api/index.php
                        $dir = dirname(dirname($script)); // /ieq (volta dois níveis)
                        $url_base = $protocolo . '://' . $host . ($dir !== '/' ? $dir : '');
                        
                        // Enviar email
                        $token = $resultado_token['token'];
                        $nome = $usuario['nome'];
                        $resultado_email = enviar_email_recuperacao_senha($email, $nome, $token, $url_base);
                        
                        if ($resultado_email['sucesso']) {
                            registrar_log_seguranca('RECUPERACAO_SENHA_SOLICITADA', ['email' => $email]);
                            responder('sucesso', $resposta['mensagem'], []);
                        } else {
                            error_log("[ERRO_EMAIL_RECUPERACAO] " . $resultado_email['mensagem']);
                            registrar_log_seguranca('ERRO_EMAIL_RECUPERACAO', ['email' => $email, 'erro' => $resultado_email['mensagem']]);
                            responder('sucesso', $resposta['mensagem'], []);
                        }
                    } else {
                        registrar_log_seguranca('ERRO_GERAR_TOKEN_RECUPERACAO', ['email' => $email]);
                        responder('sucesso', $resposta['mensagem'], []);
                    }
                } else {
                    // Email não encontrado - mas respondemos com a mesma mensagem por segurança
                    responder('sucesso', $resposta['mensagem'], []);
                }
                
            } catch (Exception $e) {
                error_log("[ERRO_RECUPERACAO] " . $e->getMessage());
                responder('erro', 'Erro ao processar sua solicitação');
            }
        }
        break;

    case 'validar_token_recuperacao':
        if ($method === 'GET') {
            $token = $_GET['token'] ?? '';
            
            if (empty($token)) {
                responder('erro', 'Token não fornecido');
            }
            
            $resultado = validar_token_recuperacao($token);
            
            if ($resultado['valido']) {
                responder('sucesso', 'Token válido', [
                    'usuario_id' => $resultado['usuario_id'],
                    'email' => $resultado['email'],
                    'expira_em' => $resultado['expira_em'] ?? null
                ]);
            } else {
                $mensagem = 'Token inválido';
                switch ($resultado['error_tipo']) {
                    case 'token_nao_encontrado':
                        $mensagem = 'Token não encontrado';
                        break;
                    case 'token_expirado':
                        $mensagem = 'Token expirado (válido por 1 hora)';
                        break;
                    case 'token_ja_usado':
                        $mensagem = 'Token já foi utilizado';
                        break;
                }
                responder('erro', $mensagem);
            }
        }
        break;

    case 'redefinir_senha':
        if ($method === 'POST') {
            // Rate limiting para redefinir senha
            verificar_rate_limit('redefinir_senha');
            
            $token = $_POST['token'] ?? '';
            $nova_senha = $_POST['nova_senha'] ?? '';
            
            if (empty($token)) {
                responder('erro', 'Token não fornecido');
            }
            
            if (empty($nova_senha)) {
                responder('erro', 'Nova senha é obrigatória');
            }
            
            if (strlen($nova_senha) < 6) {
                responder('erro', 'Senha deve ter no mínimo 6 caracteres');
            }
            
            try {
                // Validar token primeiro
                $resultado_validacao = validar_token_recuperacao($token);
                
                if (!$resultado_validacao['valido']) {
                    $mensagem = 'Token inválido';
                    switch ($resultado_validacao['error_tipo']) {
                        case 'token_nao_encontrado':
                            $mensagem = 'Token não encontrado';
                            break;
                        case 'token_expirado':
                            $mensagem = 'Token expirado';
                            break;
                        case 'token_ja_usado':
                            $mensagem = 'Token já foi utilizado';
                            break;
                    }
                    responder('erro', $mensagem);
                }
                
                // Redefinir senha
                $resultado = redefinir_senha($token, $nova_senha);
                
                if ($resultado['sucesso']) {
                    registrar_log_seguranca('SENHA_REDEFINIDA', [
                        'usuario_id' => $resultado_validacao['usuario_id'],
                        'email' => $resultado_validacao['email']
                    ]);
                    responder('sucesso', 'Senha redefinida com sucesso. Você será redirecionado para o login.');
                } else {
                    responder('erro', $resultado['erro']);
                }
                
            } catch (Exception $e) {
                error_log("[ERRO_REDEFINIR_SENHA] " . $e->getMessage());
                responder('erro', 'Erro ao redefinir senha: ' . $e->getMessage());
            }
        }
        break;

    // =============== MEMBROS ===============
    case 'listar_membros':
        $celula_id = $_GET['celula_id'] ?? null;
        $membros = listar_membros($celula_id);
        responder('sucesso', 'Membros listados', $membros);
        break;

    case 'obter_membro':
        $id = $_GET['id'] ?? '';
        $membro = obter_membro($id);
        if ($membro) {
            responder('sucesso', 'Membro obtido', $membro);
        } else {
            responder('erro', 'Membro não encontrado');
        }
        break;

    case 'criar_membro':
        if ($method === 'POST') {
            $usuario_id = $_POST['usuario_id'] ?? '';
            $celula_id = $_POST['celula_id'] ?? '';
            
            // Verificar se líder está tentando adicionar membro em célula que não é dele
            if (is_lider_exclusivo() && !empty($celula_id)) {
                $celulas_do_lider = obter_celulas_do_lider();
                if (!in_array($celula_id, $celulas_do_lider)) {
                    responder('erro', 'Você só pode adicionar membros à sua própria célula');
                }
            }
            
            $dados = [
                'data_conversao' => $_POST['data_conversao'] ?? null,
                'data_batismo' => $_POST['data_batismo'] ?? null,
                'endereco' => $_POST['endereco'] ?? '',
                'bairro' => $_POST['bairro'] ?? '',
                'cidade' => $_POST['cidade'] ?? '',
                'cep' => $_POST['cep'] ?? '',
                'data_nasc' => $_POST['data_nasc'] ?? null
            ];
            
            if (criar_membro($usuario_id, $celula_id, $dados)) {
                responder('sucesso', 'Membro criado com sucesso');
            } else {
                responder('erro', 'Erro ao criar membro');
            }
        }
        break;

    case 'atualizar_membro':
        if ($method === 'POST') {
            $id = $_POST['id'] ?? '';
            
            // Verificar se líder está tentando editar membro que não é de sua célula
            if (is_lider_exclusivo()) {
                $membro = obter_membro($id);
                if ($membro) {
                    $celulas_do_lider = obter_celulas_do_lider();
                    if (!in_array($membro['celula_id'], $celulas_do_lider)) {
                        responder('erro', 'Você só pode editar membros da sua célula');
                    }
                }
                
                // Se está mudando de célula, verificar se a nova célula pertence ao líder
                if (isset($_POST['celula_id'])) {
                    $nova_celula_id = $_POST['celula_id'];
                    if (!in_array($nova_celula_id, $celulas_do_lider)) {
                        responder('erro', 'Você não pode transferir membros para outras células');
                    }
                }
            }
            
            $dados = [];
            foreach (['nome', 'email', 'telefone', 'celula_id', 'data_conversao', 'data_batismo', 'status', 'endereco', 'bairro', 'cidade', 'cep', 'data_nasc', 'funcao'] as $campo) {
                if (isset($_POST[$campo])) {
                    $dados[$campo] = $_POST[$campo];
                }
            }
            
            if (atualizar_membro($id, $dados)) {
                responder('sucesso', 'Membro atualizado com sucesso');
            } else {
                responder('erro', 'Erro ao atualizar membro');
            }
        }
        break;

    // =============== CÉLULAS ===============
    case 'listar_celulas':
        $supervisor_id = $_GET['supervisor_id'] ?? null;
        $only_my_cells = $_GET['only_my_cells'] ?? false;
        $user_id = $_GET['user_id'] ?? null;
        
        // Se é líder e pediu apenas suas células
        if ($only_my_cells && $user_id && function_exists('is_lider_exclusivo') && is_lider_exclusivo()) {
            // obter_celulas_do_lider() retorna só IDs; carregar os dados completos de cada célula
            // (mesmo formato de listar_celulas: id, nome, lider_nome...) para o front-end não quebrar
            $ids_celulas_lider = obter_celulas_do_lider();
            $celulas = array_values(array_filter(array_map('obter_celula', $ids_celulas_lider)));
        } else {
            // Listar todas as células (para admin/pastor/supervisor)
            $celulas = listar_celulas($supervisor_id);
        }
        
        responder('sucesso', 'Células listadas', $celulas);
        break;

    case 'obter_celula':
        $id = $_GET['id'] ?? '';
        $celula = obter_celula($id);
        if ($celula) {
            responder('sucesso', 'Célula obtida', $celula);
        } else {
            responder('erro', 'Célula não encontrada');
        }
        break;

    case 'criar_celula':
        if ($method === 'POST') {
            // Verificar se tem permissão para criar células
            if (!is_supervisor()) {
                responder('erro', 'Você não tem permissão para criar células');
            }
            
            $nome = $_POST['nome'] ?? '';
            
            $dados = [];
            foreach (['igreja_id', 'localizacao', 'latitude', 'longitude', 'lider_id', 'supervisor_id', 'tipo', 'dia_semana', 'hora', 'endereco', 'bairro', 'cidade'] as $campo) {
                if (isset($_POST[$campo])) {
                    $dados[$campo] = $_POST[$campo];
                }
            }
            
            if (criar_celula($nome, $dados)) {
                responder('sucesso', 'Célula criada com sucesso');
            } else {
                responder('erro', 'Erro ao criar célula');
            }
        }
        break;

    case 'atualizar_celula':
        if ($method === 'POST') {
            $id = $_POST['id'] ?? '';
            
            // Verificar se tem permissão para editar esta célula
            if (!pode_editar_celula($id)) {
                responder('erro', 'Você não tem permissão para editar esta célula');
            }
            
            $dados = [];
            foreach (['nome', 'igreja_id', 'localizacao', 'latitude', 'longitude', 'lider_id', 'lider_id_2', 'lider_treinamento_id', 'supervisor_id', 'tipo', 'dia_semana', 'hora', 'endereco', 'bairro', 'cidade'] as $campo) {
                if (isset($_POST[$campo])) {
                    $dados[$campo] = $_POST[$campo];
                }
            }
            
            if (atualizar_celula($id, $dados)) {
                responder('sucesso', 'Célula atualizada com sucesso');
            } else {
                responder('erro', 'Erro ao atualizar célula');
            }
        }
        break;

    case 'deletar_celula':
        if ($method === 'POST') {
            $id = $_POST['id'] ?? '';
            
            // Verificar se tem permissão para deletar células
            if (!is_supervisor()) {
                responder('erro', 'Você não tem permissão para deletar células');
            }
            
            // Verificar se pode editar esta célula específica
            if (!pode_editar_celula($id)) {
                responder('erro', 'Você não tem permissão para deletar esta célula');
            }
            if (deletar_celula($id)) {
                responder('sucesso', 'Célula deletada com sucesso');
            } else {
                responder('erro', 'Erro ao deletar célula');
            }
        }
        break;

    case 'listar_lideres':
        $lideres = obter_lideres();
        responder('sucesso', 'Líderes listados', $lideres);
        break;

    case 'listar_lideres_treinamento':
        $lideres_treinamento = obter_lideres_treinamento();
        responder('sucesso', 'Líderes em treinamento listados', $lideres_treinamento);
        break;

    // =============== PRESENÇA ===============
    case 'listar_presencas_celula':
        $celula_id = $_GET['celula_id'] ?? '';
        $data_inicio = $_GET['data_inicio'] ?? null;
        $data_fim = $_GET['data_fim'] ?? null;
        
        $presencas = obter_presencas_celula($celula_id, $data_inicio, $data_fim);
        responder('sucesso', 'Presenças listadas', $presencas);
        break;

    case 'registrar_presenca':
        if ($method === 'POST') {
            $membro_id = $_POST['membro_id'] ?? '';
            $celula_id = $_POST['celula_id'] ?? '';
            $data_presenca = $_POST['data_presenca'] ?? date('Y-m-d');
            $presente = $_POST['presente'] ?? 1;
            
            if (registrar_presenca($membro_id, $celula_id, $data_presenca, $presente)) {
                responder('sucesso', 'Presença registrada com sucesso');
            } else {
                responder('erro', 'Erro ao registrar presença');
            }
        }
        break;

    case 'criar_reuniao_com_foto':
        if ($method === 'POST') {
            // Verificar CSRF token
            verificar_csrf();
            
            $celula_id = $_POST['celula_id'] ?? '';
            $data_reuniao = $_POST['data_reuniao'] ?? date('Y-m-d');
            $observacoes = $_POST['observacoes'] ?? '';
            $user_id = $_SESSION['user_id'] ?? '';
            $user_funcao = $_SESSION['user_funcao'] ?? '';
            
            if (empty($celula_id)) {
                responder('erro', 'Célula não informada');
            }
            
            // 🔐 VALIDAÇÃO DE PERMISSÃO
            $db = getDB();

            if ($user_funcao === 'lider' || $user_funcao === 'lider_treinamento') {
                // Líder e Líder em Treinamento só podem fazer upload em suas próprias células
                $stmt = $db->prepare("SELECT id FROM celulas WHERE (lider_id = ? OR lider_id_2 = ? OR lider_treinamento_id = ?) AND id = ?");
                $stmt->execute([$user_id, $user_id, $user_id, $celula_id]);
                $tem_permissao = $stmt->fetch();
                $stmt->closeCursor();
                if (!$tem_permissao) {
                    responder('erro', 'Você não tem permissão para fazer upload nesta célula');
                }
            } elseif ($user_funcao === 'membro') {
                // Membro só pode fazer upload em sua célula
                $stmt = $db->prepare("SELECT DISTINCT celula_id FROM membros WHERE usuario_id = ? AND celula_id = ?");
                $stmt->execute([$user_id, $celula_id]);
                $tem_permissao = $stmt->fetch();
                $stmt->closeCursor();
                if (!$tem_permissao) {
                    responder('erro', 'Você só pode fazer upload em sua célula');
                }
            }
            // Admin, Supervisor, Pastor podem fazer upload em qualquer célula

            // Libera a conexão de leitura antes de escrever (criar_reuniao_celula abre a sua própria);
            // no SQLite um cursor aberto sem closeCursor() prende um lock e trava a escrita seguinte.
            $db = null;

            $foto_url = null;
            
            // Upload de foto se houver
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $resultado = upload_foto_reuniao($_FILES['foto'], $celula_id, $data_reuniao);
                if ($resultado['sucesso']) {
                    $foto_url = $resultado['caminho'];
                } else {
                    responder('erro', $resultado['mensagem']);
                }
            }
            
            // Criar reunião
            $reuniao_id = criar_reuniao_celula($celula_id, $data_reuniao, $foto_url, $observacoes);
            
            if ($reuniao_id) {
                // ✨ Buscar automaticamente totais de presentes e visitantes dos registros de presença
                atualizar_totais_reuniao($reuniao_id);
                
                responder('sucesso', 'Reunião criada com sucesso', ['reuniao_id' => $reuniao_id, 'foto_url' => $foto_url]);
            } else {
                responder('erro', 'Erro ao criar reunião');
            }
        }
        break;

    case 'listar_reunioes':
        if ($method === 'GET') {
            $celula_id = $_GET['celula_id'] ?? '';
            $reunioes = listar_reunioes_celula($celula_id);
            responder('sucesso', 'Reuniões carregadas', $reunioes);
        }
        break;

    case 'galeria_completa':
        if ($method === 'GET') {
            $celula_id = $_GET['celula_id'] ?? '';
            $mes = $_GET['mes'] ?? '';
            $user_funcao = $_SESSION['user_funcao'] ?? '';
            $user_id = $_SESSION['user_id'] ?? '';
            
            error_log("Galeria: Usuário=$user_id, Funcao=$user_funcao, Celula=$celula_id, Mes=$mes");
            
            $db = getDB();
            $reunioes = [];
            
            // Aplicar filtros de permissão
            if ($user_funcao === 'lider' || $user_funcao === 'lider_treinamento') {
                // Líder e Líder em Treinamento veem TODAS as suas células
                $stmt = $db->prepare("SELECT id FROM celulas WHERE lider_id = ? OR lider_id_2 = ? OR lider_treinamento_id = ?");
                $stmt->execute([$user_id, $user_id, $user_id]);
                $celulas_lider = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($celulas_lider)) {
                    error_log("Líder/Líder em Treinamento: Nenhuma célula encontrada para user_id=$user_id");
                    responder('erro', 'Você não é líder de nenhuma célula');
                    break;
                }
                
                error_log("Líder: Encontradas " . count($celulas_lider) . " célula(s)");
                
                // Buscar fotos de todas as células
                foreach ($celulas_lider as $celula) {
                    $fotos = listar_todas_reunioes($celula['id'], $mes);
                    $reunioes = array_merge($reunioes, $fotos);
                }
                
                // Ordenar por data decrescente
                usort($reunioes, function($a, $b) {
                    return strtotime($b['data_reuniao']) - strtotime($a['data_reuniao']);
                });
                
            } elseif ($user_funcao === 'membro') {
                // Membro vê apenas sua célula
                $stmt = $db->prepare("SELECT DISTINCT m.celula_id FROM membros m WHERE m.usuario_id = ?");
                $stmt->execute([$user_id]);
                $membro = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($membro && $membro['celula_id']) {
                    $celula_id = $membro['celula_id'];
                    $reunioes = listar_todas_reunioes($celula_id, $mes);
                    error_log("Membro: Célula encontrada: $celula_id, Fotos: " . count($reunioes));
                } else {
                    error_log("Membro: Nenhuma célula encontrada para user_id=$user_id");
                    responder('erro', 'Você não está afiliado a nenhuma célula');
                    break;
                }
                
            } else {
                // Pastor, Admin e Supervisor veem TODAS as fotos (sem restrição)
                if ($celula_id) {
                    $reunioes = listar_todas_reunioes($celula_id, $mes);
                    error_log("Admin/Supervisor/Pastor: Célula específica $celula_id, Fotos: " . count($reunioes));
                } else {
                    // Ver todas as células
                    $stmt = $db->prepare("SELECT id FROM celulas ORDER BY id");
                    $stmt->execute();
                    $todas_celulas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($todas_celulas as $celula) {
                        $fotos = listar_todas_reunioes($celula['id'], $mes);
                        $reunioes = array_merge($reunioes, $fotos);
                    }
                    
                    // Ordenar por data decrescente
                    usort($reunioes, function($a, $b) {
                        return strtotime($b['data_reuniao']) - strtotime($a['data_reuniao']);
                    });
                    
                    error_log("Admin/Supervisor/Pastor: Todas as células, Total de fotos: " . count($reunioes));
                }
            }
            
            responder('sucesso', 'Galeria carregada', $reunioes);
        }
        break;

    case 'obter_reuniao':
        if ($method === 'GET') {
            $reuniao_id = $_GET['reuniao_id'] ?? $_GET['id'] ?? '';
            $reuniao = obter_reuniao($reuniao_id);
            if ($reuniao) {
                responder('sucesso', 'Reunião obtida', $reuniao);
            } else {
                responder('erro', 'Reunião não encontrada');
            }
        }
        break;

    case 'listar_fotos_reuniao':
        if ($method === 'GET') {
            $celula_id = $_GET['celula_id'] ?? '';
            $data = $_GET['data'] ?? '';
            
            if (!$celula_id || !$data) {
                responder('erro', 'Célula e data são obrigatórias');
            }
            
            $db = getDB();
            try {
                $stmt = $db->prepare("
                    SELECT * FROM reunioes_celula 
                    WHERE celula_id = ? AND data_reuniao = ? AND foto_url IS NOT NULL AND foto_url != ''
                    ORDER BY criado_em ASC
                ");
                $stmt->execute([$celula_id, $data]);
                $fotos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                responder('sucesso', 'Fotos da reunião carregadas', $fotos);
            } catch (PDOException $e) {
                error_log("Erro ao listar fotos: " . $e->getMessage());
                responder('erro', 'Erro ao buscar fotos');
            }
        }
        break;

    case 'estatisticas_celula':
        $celula_id = $_GET['celula_id'] ?? '';
        $mes = $_GET['mes'] ?? null;
        
        $stats = obter_estatisticas_celula($celula_id, $mes);
        responder('sucesso', 'Estatísticas obtidas', $stats);
        break;

    case 'relatorio_presenca':
        $data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
        $data_fim = $_GET['data_fim'] ?? date('Y-m-d');
        $celula_id = $_GET['celula_id'] ?? null;
        $only_my_cells = $_GET['only_my_cells'] ?? false;
        $user_id = $_GET['user_id'] ?? null;
        
        // Se é líder e pediu apenas suas células
        if ($only_my_cells && $user_id && function_exists('is_lider_exclusivo') && is_lider_exclusivo()) {
            // Obter células do líder
            $celulas_lider = obter_celulas_do_lider();
            if (!empty($celulas_lider)) {
                // Usar a primeira célula do líder (array simples de IDs)
                $celula_id = $celulas_lider[0] ?? null;
            }
        }
        
        $relatorio = obter_relatorio_presenca($data_inicio, $data_fim, $celula_id);
        echo json_encode($relatorio);
        exit;
        break;

    // =============== EVENTOS ===============
    case 'listar_eventos':
        $tipo = $_GET['tipo'] ?? null;
        $data_inicio = $_GET['data_inicio'] ?? null;
        $data_fim = $_GET['data_fim'] ?? null;
        
        $eventos = listar_eventos($tipo, $data_inicio, $data_fim);
        responder('sucesso', 'Eventos listados', $eventos);
        break;

    case 'listar_proximos_eventos':
        $limite = $_GET['limite'] ?? 5;
        $eventos = listar_proximos_eventos($limite);
        responder('sucesso', 'Próximos eventos listados', $eventos);
        break;

    case 'obter_evento':
        $id = $_GET['id'] ?? '';
        $evento = obter_evento($id);
        if ($evento) {
            responder('sucesso', 'Evento obtido', $evento);
        } else {
            responder('erro', 'Evento não encontrado');
        }
        break;

    case 'criar_evento':
        if ($method === 'POST') {
            $nome = $_POST['nome'] ?? '';
            $dados = [];
            foreach (['descricao', 'data_evento', 'localizacao', 'responsavel_id', 'tipo', 'vagas'] as $campo) {
                if (isset($_POST[$campo])) {
                    $dados[$campo] = $_POST[$campo];
                }
            }
            
            if (criar_evento($nome, $dados)) {
                responder('sucesso', 'Evento criado com sucesso');
            } else {
                responder('erro', 'Erro ao criar evento');
            }
        }
        break;

    case 'atualizar_evento':
        if ($method === 'POST') {
            $id = $_POST['id'] ?? '';
            $dados = [];
            foreach (['nome', 'descricao', 'data_evento', 'localizacao', 'responsavel_id', 'tipo', 'vagas'] as $campo) {
                if (isset($_POST[$campo])) {
                    $dados[$campo] = $_POST[$campo];
                }
            }
            
            if (atualizar_evento($id, $dados)) {
                responder('sucesso', 'Evento atualizado com sucesso');
            } else {
                responder('erro', 'Erro ao atualizar evento');
            }
        }
        break;

    case 'deletar_evento':
        if ($method === 'POST') {
            $id = $_POST['id'] ?? '';
            if (deletar_evento($id)) {
                responder('sucesso', 'Evento deletado com sucesso');
            } else {
                responder('erro', 'Erro ao deletar evento');
            }
        }
        break;

    // =============== CURSOS ===============
    case 'listar_cursos':
        $ativo = isset($_GET['ativo']) ? (bool)$_GET['ativo'] : null;
        $cursos = listar_cursos($ativo);
        responder('sucesso', 'Cursos listados', $cursos);
        break;

    case 'obter_curso':
        $id = $_GET['id'] ?? '';
        $curso = obter_curso($id);
        if ($curso) {
            responder('sucesso', 'Curso obtido', $curso);
        } else {
            responder('erro', 'Curso não encontrado');
        }
        break;

    case 'criar_curso':
        if ($method === 'POST') {
            $nome = $_POST['nome'] ?? '';
            $dados = [];
            foreach (['descricao', 'professor_id', 'data_inicio', 'data_fim', 'localizacao', 'vagas'] as $campo) {
                if (isset($_POST[$campo])) {
                    $dados[$campo] = $_POST[$campo];
                }
            }
            
            if (criar_curso($nome, $dados)) {
                responder('sucesso', 'Curso criado com sucesso');
            } else {
                responder('erro', 'Erro ao criar curso');
            }
        }
        break;

    case 'atualizar_curso':
        if ($method === 'POST') {
            $id = $_POST['id'] ?? '';
            $dados = [];
            foreach (['nome', 'descricao', 'professor_id', 'data_inicio', 'data_fim', 'localizacao', 'vagas'] as $campo) {
                if (isset($_POST[$campo])) {
                    $dados[$campo] = $_POST[$campo];
                }
            }
            
            if (atualizar_curso($id, $dados)) {
                responder('sucesso', 'Curso atualizado com sucesso');
            } else {
                responder('erro', 'Erro ao atualizar curso');
            }
        }
        break;

    case 'deletar_curso':
        if ($method === 'POST') {
            $id = $_POST['id'] ?? '';
            if (deletar_curso($id)) {
                responder('sucesso', 'Curso deletado com sucesso');
            } else {
                responder('erro', 'Erro ao deletar curso');
            }
        }
        break;

    // =============== IGREJAS ===============
    case 'listar_igrejas':
        $resultado = listar_igrejas();
        responder($resultado['status'], $resultado['mensagem'] ?? 'Igrejas listadas', $resultado['dados'] ?? []);
        break;

    case 'obter_igreja':
        $id = $_GET['id'] ?? '';
        $resultado = obter_igreja($id);
        responder($resultado['status'], $resultado['mensagem'] ?? 'Igreja obtida', $resultado['dados'] ?? []);
        break;

    case 'criar_igreja':
        if ($method === 'POST') {
            $dados = [];
            foreach (['nome', 'endereco', 'bairro', 'cidade', 'telefone', 'email', 'pastor_presidente_id', 'pastor_auxiliar_id'] as $campo) {
                if (isset($_POST[$campo]) && $_POST[$campo] !== '') {
                    $dados[$campo] = $_POST[$campo];
                }
            }
            
            $resultado = criar_igreja($dados);
            responder($resultado['status'], $resultado['mensagem'] ?? ($resultado['status'] === 'sucesso' ? 'Igreja criada com sucesso' : 'Erro ao criar igreja'), $resultado);
        }
        break;

    case 'atualizar_igreja':
        if ($method === 'POST') {
            $id = $_POST['id'] ?? '';
            $dados = [];
            foreach (['nome', 'endereco', 'bairro', 'cidade', 'telefone', 'email', 'pastor_presidente_id', 'pastor_auxiliar_id'] as $campo) {
                if (isset($_POST[$campo]) && $_POST[$campo] !== '') {
                    $dados[$campo] = $_POST[$campo];
                }
            }
            
            $resultado = atualizar_igreja($id, $dados);
            responder($resultado['status'], $resultado['mensagem'] ?? ($resultado['status'] === 'sucesso' ? 'Igreja atualizada com sucesso' : 'Erro ao atualizar igreja'));
        }
        break;

    case 'deletar_igreja':
        if ($method === 'POST') {
            $id = $_POST['id'] ?? '';
            $resultado = deletar_igreja($id);
            responder($resultado['status'], $resultado['mensagem'] ?? ($resultado['status'] === 'sucesso' ? 'Igreja deletada com sucesso' : 'Erro ao deletar igreja'));
        }
        break;

    case 'listar_pastores':
        $resultado = obter_pastores();
        responder($resultado['status'], $resultado['mensagem'] ?? 'Pastores listados', $resultado['dados'] ?? []);
        break;

    // =============== EVENTOS DA IGREJA ===============
    case 'criar_evento_igreja':
        if ($method === 'POST') {
            $dados = obter_json_post();
            if (!usuario_pode_gerenciar_igreja($dados['igreja_id'] ?? null)) {
                responder('erro', 'Você não tem permissão para gerenciar esta igreja');
            }
            $resultado = criar_evento_igreja($dados);
            responder($resultado['status'], $resultado['mensagem'] ?? 'Evento criado', $resultado);
        }
        break;

    case 'listar_eventos_igreja':
        $id = $_GET['id'] ?? '';
        if (!usuario_pode_gerenciar_igreja($id)) {
            responder('erro', 'Você não tem permissão para ver esta igreja');
        }
        $resultado = listar_eventos_igreja($id);
        responder($resultado['status'], $resultado['mensagem'] ?? 'Eventos listados', $resultado['dados'] ?? []);
        break;

    case 'deletar_evento_igreja':
        if ($method === 'POST') {
            $dados = obter_json_post();
            if (!usuario_pode_gerenciar_igreja(obter_igreja_id_do_registro('eventos_igreja', $dados['id'] ?? null))) {
                responder('erro', 'Você não tem permissão para excluir este registro');
            }
            $resultado = deletar_evento_igreja($dados['id']);
            responder($resultado['status'], $resultado['mensagem'] ?? 'Evento deletado');
        }
        break;

    // =============== VISITANTES DA IGREJA ===============
    case 'criar_visitante_igreja':
        if ($method === 'POST') {
            $dados = obter_json_post();
            if (!usuario_pode_gerenciar_igreja($dados['igreja_id'] ?? null)) {
                responder('erro', 'Você não tem permissão para gerenciar esta igreja');
            }
            $resultado = criar_visitante_igreja($dados);
            responder($resultado['status'], $resultado['mensagem'] ?? 'Visitante cadastrado', $resultado);
        }
        break;

    case 'listar_visitantes_igreja':
        $id = $_GET['id'] ?? '';
        if (!usuario_pode_gerenciar_igreja($id)) {
            responder('erro', 'Você não tem permissão para ver esta igreja');
        }
        $resultado = listar_visitantes_igreja($id);
        responder($resultado['status'], $resultado['mensagem'] ?? 'Visitantes listados', $resultado['dados'] ?? []);
        break;

    case 'deletar_visitante_igreja':
        if ($method === 'POST') {
            $dados = obter_json_post();
            if (!usuario_pode_gerenciar_igreja(obter_igreja_id_do_registro('visitantes_igreja', $dados['id'] ?? null))) {
                responder('erro', 'Você não tem permissão para excluir este registro');
            }
            $resultado = deletar_visitante_igreja($dados['id']);
            responder($resultado['status'], $resultado['mensagem'] ?? 'Visitante deletado');
        }
        break;

    // =============== CONVERSÕES NA IGREJA ===============
    case 'criar_conversao_igreja':
        if ($method === 'POST') {
            $dados = obter_json_post();
            if (!usuario_pode_gerenciar_igreja($dados['igreja_id'] ?? null)) {
                responder('erro', 'Você não tem permissão para gerenciar esta igreja');
            }
            $resultado = criar_conversao_igreja($dados);
            responder($resultado['status'], $resultado['mensagem'] ?? 'Conversão registrada', $resultado);
        }
        break;

    case 'listar_conversoes_igreja':
        $id = $_GET['id'] ?? '';
        if (!usuario_pode_gerenciar_igreja($id)) {
            responder('erro', 'Você não tem permissão para ver esta igreja');
        }
        $resultado = listar_conversoes_igreja($id);
        responder($resultado['status'], $resultado['mensagem'] ?? 'Conversões listadas', $resultado['dados'] ?? []);
        break;

    case 'deletar_conversao_igreja':
        if ($method === 'POST') {
            $dados = obter_json_post();
            if (!usuario_pode_gerenciar_igreja(obter_igreja_id_do_registro('conversoes_igreja', $dados['id'] ?? null))) {
                responder('erro', 'Você não tem permissão para excluir este registro');
            }
            $resultado = deletar_conversao_igreja($dados['id']);
            responder($resultado['status'], $resultado['mensagem'] ?? 'Conversão deletada');
        }
        break;

    // =============== RECONCILIAÇÕES NA IGREJA ===============
    case 'criar_reconciliacao_igreja':
        if ($method === 'POST') {
            $dados = obter_json_post();
            if (!usuario_pode_gerenciar_igreja($dados['igreja_id'] ?? null)) {
                responder('erro', 'Você não tem permissão para gerenciar esta igreja');
            }
            $resultado = criar_reconciliacao_igreja($dados);
            responder($resultado['status'], $resultado['mensagem'] ?? 'Reconciliação registrada', $resultado);
        }
        break;

    case 'listar_reconciliacao_igreja':
        $id = $_GET['id'] ?? '';
        if (!usuario_pode_gerenciar_igreja($id)) {
            responder('erro', 'Você não tem permissão para ver esta igreja');
        }
        $resultado = listar_reconciliacao_igreja($id);
        responder($resultado['status'], $resultado['mensagem'] ?? 'Reconciliações listadas', $resultado['dados'] ?? []);
        break;

    case 'deletar_reconciliacao_igreja':
        if ($method === 'POST') {
            $dados = obter_json_post();
            if (!usuario_pode_gerenciar_igreja(obter_igreja_id_do_registro('reconciliacao_igreja', $dados['id'] ?? null))) {
                responder('erro', 'Você não tem permissão para excluir este registro');
            }
            $resultado = deletar_reconciliacao_igreja($dados['id']);
            responder($resultado['status'], $resultado['mensagem'] ?? 'Reconciliação deletada');
        }
        break;

    // =============== BATISMOS NA IGREJA ===============
    case 'criar_batismo_igreja':
        if ($method === 'POST') {
            $dados = obter_json_post();
            if (!usuario_pode_gerenciar_igreja($dados['igreja_id'] ?? null)) {
                responder('erro', 'Você não tem permissão para gerenciar esta igreja');
            }
            $resultado = criar_batismo_igreja($dados);
            responder($resultado['status'], $resultado['mensagem'] ?? 'Batismo registrado', $resultado);
        }
        break;

    case 'listar_batismos_igreja':
        $id = $_GET['id'] ?? '';
        if (!usuario_pode_gerenciar_igreja($id)) {
            responder('erro', 'Você não tem permissão para ver esta igreja');
        }
        $resultado = listar_batismos_igreja($id);
        responder($resultado['status'], $resultado['mensagem'] ?? 'Batismos listados', $resultado['dados'] ?? []);
        break;

    case 'deletar_batismo_igreja':
        if ($method === 'POST') {
            $dados = obter_json_post();
            if (!usuario_pode_gerenciar_igreja(obter_igreja_id_do_registro('batismos_igreja', $dados['id'] ?? null))) {
                responder('erro', 'Você não tem permissão para excluir este registro');
            }
            $resultado = deletar_batismo_igreja($dados['id']);
            responder($resultado['status'], $resultado['mensagem'] ?? 'Batismo deletado');
        }
        break;

    // =============== RELATÓRIOS ===============
    case 'relatorio_igreja':
        $id = $_GET['id'] ?? '';
        $filtro = $_GET['filtro'] ?? 'todos';
        if (!usuario_pode_gerenciar_igreja($id)) {
            responder('erro', 'Você não tem permissão para ver esta igreja');
        }
        $resultado = relatorio_igreja($id, $filtro);
        responder($resultado['status'], $resultado['mensagem'] ?? 'Relatório gerado', $resultado['dados'] ?? []);
        break;

    // =============== DASHBOARD ===============
    case 'estatisticas_dashboard':
        $stats_membros = obter_estatisticas_membros();
        $stats_celulas = obter_estatisticas_celulas();
        
        $stats = array_merge($stats_membros, $stats_celulas);
        responder('sucesso', 'Estatísticas do dashboard', $stats);
        break;

    case 'listar_usuarios':
        $usuarios = listar_todos_usuarios();
        responder('sucesso', 'Usuários listados', $usuarios);
        break;

    case 'criar_usuario':
        if ($method === 'POST') {
            // Verificar permissão de admin
            if (!is_admin()) {
                responder('erro', 'Sem permissão para criar usuários');
            }
            
            // Log completo do POST
            error_log("=== CRIAR USUÁRIO - POST COMPLETO ===");
            error_log("_POST: " . print_r($_POST, true));
            error_log("php://input: " . file_get_contents('php://input'));
            
            $dados = [
                'nome' => $_POST['nome'] ?? '',
                'email' => $_POST['email'] ?? '',
                'senha' => $_POST['senha'] ?? '',
                'telefone' => $_POST['telefone'] ?? '',
                'funcao' => $_POST['funcao'] ?? 'membro'
            ];
            
            // Adicionar permissões - verificar se vem como array direto ou precisa decodificar
            if (isset($_POST['permissoes']) && is_array($_POST['permissoes']) && !empty($_POST['permissoes'])) {
                $dados['permissoes'] = $_POST['permissoes'];
                error_log("Permissões recebidas como array: " . print_r($dados['permissoes'], true));
            } elseif (isset($_POST['permissoes']) && is_string($_POST['permissoes'])) {
                $dados['permissoes'] = json_decode($_POST['permissoes'], true);
                error_log("Permissões recebidas como string JSON");
            } else {
                error_log("AVISO: Nenhuma permissão recebida! _POST[permissoes] = " . var_export($_POST['permissoes'] ?? 'UNDEFINED', true));
            }
            
            error_log("Dados finais para criar usuário: " . print_r($dados, true));
            
            if (criar_usuario_admin($dados)) {
                responder('sucesso', 'Usuário criado com sucesso');
            } else {
                responder('erro', 'Erro ao criar usuário. Email pode já estar em uso.');
            }
        }
        break;

    case 'atualizar_usuario':
        if ($method === 'POST') {
            // Verificar permissão de admin
            if (!is_admin()) {
                responder('erro', 'Sem permissão para atualizar usuários');
            }
            
            $id = $_POST['id'] ?? '';
            $dados = [];
            
            foreach (['nome', 'email', 'telefone', 'funcao', 'senha'] as $campo) {
                if (isset($_POST[$campo]) && $_POST[$campo] !== '') {
                    $dados[$campo] = $_POST[$campo];
                }
            }
            
            // Adicionar permissões se fornecidas
            if (isset($_POST['permissoes'])) {
                $dados['permissoes'] = is_array($_POST['permissoes']) ? $_POST['permissoes'] : json_decode($_POST['permissoes'], true);
            }
            
            if (atualizar_usuario_admin($id, $dados)) {
                responder('sucesso', 'Usuário atualizado com sucesso');
            } else {
                responder('erro', 'Erro ao atualizar usuário');
            }
        }
        break;

    case 'deletar_usuario':
        if ($method === 'POST') {
            // Verificar permissão de admin
            if (!is_admin()) {
                responder('erro', 'Sem permissão para deletar usuários');
            }
            
            $id = $_POST['id'] ?? '';
            $resultado = deletar_usuario($id);
            
            // A função agora retorna um array
            if (is_array($resultado)) {
                if ($resultado['sucesso']) {
                    responder('sucesso', $resultado['mensagem']);
                } else {
                    responder('erro', $resultado['mensagem']);
                }
            } else {
                // Compatibilidade com retorno booleano antigo
                if ($resultado) {
                    responder('sucesso', 'Usuário excluído com sucesso');
                } else {
                    responder('erro', 'Erro ao excluir usuário');
                }
            }
        }
        break;

    case 'alterar_senha':
        if ($method === 'POST') {
            // Verificar se usuário está logado
            if (!usuario_logado()) {
                responder('erro', 'Você precisa estar logado para alterar a senha');
            }
            
            $usuario_id = obter_id_usuario_logado();
            $senha_atual = $_POST['senha_atual'] ?? '';
            $senha_nova = $_POST['senha_nova'] ?? '';
            $confirmar_senha = $_POST['confirmar_senha'] ?? '';
            
            // Validações básicas
            if (empty($senha_atual) || empty($senha_nova) || empty($confirmar_senha)) {
                responder('erro', 'Todos os campos de senha são obrigatórios');
            }
            
            if ($senha_nova !== $confirmar_senha) {
                responder('erro', 'A nova senha e confirmação não coincidem');
            }
            
            if ($senha_atual === $senha_nova) {
                responder('erro', 'A nova senha deve ser diferente da senha atual');
            }
            
            // Chamar função de alterar senha
            $resultado = alterar_senha($usuario_id, $senha_atual, $senha_nova);
            
            if ($resultado['sucesso']) {
                responder('sucesso', $resultado['mensagem']);
            } else {
                responder('erro', $resultado['mensagem']);
            }
        }
        break;

    case 'deletar_membro':
        if ($method === 'POST') {
            $id = $_POST['id'] ?? '';
            if (deletar_membro($id)) {
                responder('sucesso', 'Membro deletado com sucesso');
            } else {
                responder('erro', 'Erro ao deletar membro');
            }
        }
        break;

    // =============== VISITANTES ===============
    case 'listar_visitantes':
        $celula_id = $_GET['celula_id'] ?? null;
        $visitantes = listar_visitantes($celula_id);
        responder('sucesso', 'Visitantes listados', $visitantes);
        break;

    case 'obter_visitante':
        $id = $_GET['id'] ?? '';
        $visitante = obter_visitante($id);
        if ($visitante) {
            responder('sucesso', 'Visitante encontrado', $visitante);
        } else {
            responder('erro', 'Visitante não encontrado');
        }
        break;

    case 'criar_visitante':
        if ($method === 'POST') {
            $dados = [
                'nome' => $_POST['nome'] ?? '',
                'telefone' => $_POST['telefone'] ?? '',
                'email' => $_POST['email'] ?? '',
                'celula_id' => $_POST['celula_id'] ?? '',
                'data_visita' => $_POST['data_visita'] ?? '',
                'status' => $_POST['status'] ?? 'primeira_visita',
                'observacoes' => $_POST['observacoes'] ?? ''
            ];

            $visitante_id = criar_visitante($dados);
            if ($visitante_id) {
                responder('sucesso', 'Visitante cadastrado com sucesso', ['visitante_id' => $visitante_id]);
            } else {
                responder('erro', 'Erro ao cadastrar visitante');
            }
        }
        break;

    case 'atualizar_visitante':
        if ($method === 'POST') {
            $id = $_POST['id'] ?? '';
            
            $dados = [];
            foreach (['nome', 'telefone', 'email', 'celula_id', 'data_visita', 'status', 'observacoes'] as $campo) {
                if (isset($_POST[$campo])) {
                    $dados[$campo] = $_POST[$campo];
                }
            }

            if (atualizar_visitante($id, $dados)) {
                responder('sucesso', 'Visitante atualizado com sucesso');
            } else {
                responder('erro', 'Erro ao atualizar visitante');
            }
        }
        break;

    case 'deletar_visitante':
        if ($method === 'POST') {
            $id = $_POST['id'] ?? '';
            if (deletar_visitante($id)) {
                responder('sucesso', 'Visitante excluído com sucesso');
            } else {
                responder('erro', 'Erro ao excluir visitante');
            }
        }
        break;

    case 'estatisticas_visitantes':
        $stats = obter_estatisticas_visitantes();
        responder('sucesso', 'Estatísticas de visitantes', $stats);
        break;

    case 'deletar_presenca':
        if ($method === 'POST') {
            $dados = json_decode(file_get_contents('php://input'), true);
            $id = $dados['id'] ?? '';
            
            if (!$id) {
                responder('erro', 'ID da reunião não informado');
                break;
            }
            
            try {
                $db = getDB();
                
                // Buscar a reunião para obter o caminho da foto
                $stmt = $db->prepare("SELECT celula_id, foto_url FROM reunioes_celula WHERE id = ?");
                $stmt->execute([$id]);
                $reuniao = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$reuniao) {
                    responder('erro', 'Reunião não encontrada');
                    break;
                }
                
                // Validar se o usuário atual pode gerenciar esta célula
                if (!pode_editar_celula($reuniao['celula_id'])) {
                    responder('erro', 'Sem permissão para deletar');
                    break;
                }
                
                // Deletar arquivo físico se existir
                if ($reuniao['foto_url'] && strpos($reuniao['foto_url'], 'http') === false) {
                    $caminho_arquivo = __DIR__ . '/../' . $reuniao['foto_url'];
                    if (file_exists($caminho_arquivo)) {
                        unlink($caminho_arquivo);
                    }
                }
                
                // Deletar registro do banco
                $stmt = $db->prepare("DELETE FROM reunioes_celula WHERE id = ?");
                $stmt->execute([$id]);
                
                responder('sucesso', 'Foto deletada com sucesso');
            } catch (Exception $e) {
                responder('erro', 'Erro ao deletar foto: ' . $e->getMessage());
            }
        }
        break;

    // =============== GOOGLE OAUTH / CADASTROS PENDENTES ===============
    case 'listar_cadastros_pendentes':
        if (!is_admin()) {
            responder('erro', 'Sem permissão para acessar cadastros pendentes');
        }
        
        $cadastros = listar_cadastros_pendentes();
        responder('sucesso', 'Cadastros pendentes listados', $cadastros);
        break;

    case 'aprovar_cadastro':
        if ($method === 'POST') {
            if (!is_admin()) {
                responder('erro', 'Sem permissão para aprovar cadastros');
            }
            
            $cadastro_id = $_POST['cadastro_id'] ?? '';
            $funcao = $_POST['funcao'] ?? 'membro';
            
            if (empty($cadastro_id)) {
                responder('erro', 'ID do cadastro não fornecido');
            }
            
            $resultado = aprovar_cadastro_pendente($cadastro_id, $funcao);
            
            if ($resultado['sucesso']) {
                responder('sucesso', $resultado['mensagem']);
            } else {
                responder('erro', $resultado['mensagem']);
            }
        }
        break;

    case 'rejeitar_cadastro':
        if ($method === 'POST') {
            if (!is_admin()) {
                responder('erro', 'Sem permissão para rejeitar cadastros');
            }
            
            $cadastro_id = $_POST['cadastro_id'] ?? '';
            $motivo = $_POST['motivo'] ?? '';
            
            if (empty($cadastro_id)) {
                responder('erro', 'ID do cadastro não fornecido');
            }
            
            $resultado = rejeitar_cadastro_pendente($cadastro_id, $motivo);
            
            if ($resultado['sucesso']) {
                responder('sucesso', $resultado['mensagem']);
            } else {
                responder('erro', $resultado['mensagem']);
            }
        }
        break;

    case 'exportar_presenca_excel':
        if ($method === 'POST') {
            $dados = json_decode(file_get_contents('php://input'), true);
            $celula_id = $dados['celula_id'] ?? '';
            $data = $dados['data'] ?? date('Y-m-d');

            if (!$celula_id) {
                responder('erro', 'Célula não informada');
                break;
            }

            try {
                $db = getDB();
                
                // Buscar dados de presença
                $sql = "
                    SELECT 
                        m.nome as membro_nome,
                        m.email as membro_email,
                        c.nome as celula_nome,
                        p.data_presenca,
                        p.presente,
                        p.data_registro
                    FROM presencas p
                    INNER JOIN membros m ON p.membro_id = m.id
                    INNER JOIN celulas c ON p.celula_id = c.id
                    WHERE p.celula_id = ? AND p.data_presenca = ?
                    ORDER BY m.nome ASC
                ";
                
                $stmt = $db->prepare($sql);
                $stmt->execute([$celula_id, $data]);
                $presencas = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Criar CSV em memória
                $csv = fopen('php://memory', 'r+');
                
                // Cabeçalho
                fputcsv($csv, ['Membro', 'Email', 'Célula', 'Data', 'Status', 'Registrado em'], ';');
                
                // Dados
                foreach ($presencas as $presenca) {
                    fputcsv($csv, [
                        $presenca['membro_nome'],
                        $presenca['membro_email'],
                        $presenca['celula_nome'],
                        date('d/m/Y', strtotime($presenca['data_presenca'])),
                        $presenca['presente'] ? 'Presente' : 'Ausente',
                        $presenca['data_registro']
                    ], ';');
                }
                
                rewind($csv);
                $conteudo = stream_get_contents($csv);
                fclose($csv);

                // Enviar como download
                header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
                header('Content-Disposition: attachment; filename="presenca_' . date('Ymd') . '.csv"');
                header('Content-Length: ' . strlen($conteudo));
                header('Cache-Control: no-cache, no-store, must-revalidate');
                header('Pragma: no-cache');
                header('Expires: 0');
                
                echo "\xEF\xBB\xBF" . $conteudo; // BOM para UTF-8
                exit;
                
            } catch (Exception $e) {
                responder('erro', 'Erro ao exportar: ' . $e->getMessage());
            }
        }
        break;

    // =============== TELEGRAM ===============
    case 'configurar_telegram':
        if ($method === 'POST') {
            // Apenas admin pode configurar
            if (!is_admin()) {
                responder('erro', 'Sem permissão para configurar Telegram');
            }
            
            $bot_token = $_POST['bot_token'] ?? '';
            $chat_id = $_POST['chat_id'] ?? '';
            
            if (empty($bot_token) || empty($chat_id)) {
                responder('erro', 'Bot Token e Chat ID são obrigatórios');
            }
            
            $resultado = $telegram->salvarConfiguracao($bot_token, $chat_id);
            responder($resultado['status'], $resultado['mensagem']);
        }
        break;

    case 'obter_config_telegram':
        if (!is_admin()) {
            responder('erro', 'Sem permissão');
        }
        
        $config = $telegram->obterConfiguracao();
        responder('sucesso', 'Configuração do Telegram', $config);
        break;

    case 'enviar_notif_error':
        if ($method === 'POST') {
            if (!is_admin()) {
                responder('erro', 'Sem permissão');
            }
            
            $titulo = $_POST['titulo'] ?? 'Erro Desconhecido';
            $mensagem = $_POST['mensagem'] ?? '';
            $arquivo = $_POST['arquivo'] ?? '';
            $linha = $_POST['linha'] ?? '';
            
            $telegram->notificarErro($titulo, $mensagem, $arquivo, $linha);
            responder('sucesso', 'Erro notificado');
        }
        break;

    case 'notificar_administrativos':
        if ($method === 'POST') {
            if (!is_admin()) {
                responder('erro', 'Sem permissão');
            }
            
            // Verificar cadastros pendentes
            $telegram->notificarCadastrosPendentes();
            
            // Enviar relatório diário
            $telegram->enviarRelatorioDiario();
            
            responder('sucesso', 'Notificações enviadas');
        }
        break;

    case 'relatorio_diario_telegram':
        if (!is_admin()) {
            responder('erro', 'Sem permissão');
        }
        
        $resultado_envio = $telegram->enviarRelatorioDiario();
        
        if ($resultado_envio) {
            responder('sucesso', '✅ Relatório enviado com sucesso para o Telegram!');
        } else {
            responder('erro', '❌ Erro ao enviar relatório. Verifique os logs do servidor.');
        }
        break;

    // case 'processar_telegram_presenca':
    //     // Endpoint para processar mensagens do Telegram (via Poll)
    //     // Chamado periodicamente via Cron Job ou manualmente
    //     // DESABILITADO TEMPORARIAMENTE
    //     
    //     // Validar token (permite sem estar logado)
    //     $token_fornecido = $_GET['token'] ?? $_POST['token'] ?? null;
    //     $webhook_token = getenv('TELEGRAM_WEBHOOK_TOKEN');
    //     
    //     // Aceita se: está logado como admin OU token correto
    //     $autorizado = is_admin() || (!empty($webhook_token) && $token_fornecido === $webhook_token);
    //     
    //     if (!$autorizado) {
    //         responder('erro', 'Usuário não autenticado');
    //     }
    //     
    //     require_once __DIR__ . '/../config/telegram_presenca.php';
    //     $resultado = $telegram_presenca->processarAtualizacoes();
    //     
    //     if ($resultado) {
    //         responder('sucesso', '✅ Atualizações do Telegram processadas');
    //     } else {
    //         responder('erro', '❌ Erro ao processar Telegram');
    //     }
    //     break;

    default:
        responder('erro', 'Ação não encontrada');
    }
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    responder('erro', 'Erro ao processar requisição: ' . $e->getMessage());
}
?>
