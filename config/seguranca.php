<?php
/**
 * Sistema de Segurança - CSRF e Rate Limiting
 * 
 * Implementa proteção contra:
 * - CSRF (Cross-Site Request Forgery)
 * - Brute Force attacks
 * - DoS attacks
 */

require_once __DIR__ . '/database.php';

// ==================== CSRF PROTECTION ====================

/**
 * Gerar um token CSRF único
 */
function gerar_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Obter o token CSRF da sessão
 */
function obter_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        gerar_csrf_token();
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validar token CSRF de requisição
 */
function validar_csrf_token($token_recebido) {
    // Se não tiver token na sessão, gerar um
    if (empty($_SESSION['csrf_token'])) {
        return false;
    }
    
    // Usar hash_equals para evitar timing attacks
    return hash_equals($_SESSION['csrf_token'], $token_recebido);
}

/**
 * Validar CSRF para POST/PUT/DELETE
 * Chamada automática em operações sensíveis
 */
function verificar_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' || 
        $_SERVER['REQUEST_METHOD'] === 'PUT' || 
        $_SERVER['REQUEST_METHOD'] === 'DELETE') {
        
        // Obter token de: POST data, JSON body, ou header
        $token = $_POST['csrf_token'] ?? 
                 $_POST['_token'] ?? 
                 (json_decode(file_get_contents('php://input'), true)['csrf_token'] ?? null) ??
                 $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        
        if (!$token || !validar_csrf_token($token)) {
            http_response_code(403);
            die(json_encode(['status' => 'erro', 'mensagem' => 'Token CSRF inválido']));
        }
    }
}

/**
 * Regenerar token CSRF após login (segurança)
 * Apenas gera um novo token, não tenta regenerar session
 */
function regenerar_csrf_token() {
    gerar_csrf_token();
}

// ==================== RATE LIMITING ====================

/**
 * Criar tabela de rate limiting se não existir
 */
function criar_tabela_rate_limiting() {
    $db = getDB();
    
    try {
        if (DB_DRIVER === 'mysql') {
            $db->exec("
                CREATE TABLE IF NOT EXISTS rate_limiting (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    ip VARCHAR(45) NOT NULL,
                    endpoint VARCHAR(255),
                    tentativas INT DEFAULT 1,
                    primeira_tentativa DATETIME DEFAULT CURRENT_TIMESTAMP,
                    ultima_tentativa DATETIME DEFAULT CURRENT_TIMESTAMP,
                    bloqueado TINYINT DEFAULT 0,
                    data_desbloqueio DATETIME,
                    motivo VARCHAR(255),
                    INDEX idx_rate_limit_ip (ip),
                    INDEX idx_rate_limit_endpoint (endpoint),
                    INDEX idx_rate_limit_bloqueado (bloqueado)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } else {
            $db->exec("
                CREATE TABLE IF NOT EXISTS rate_limiting (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    ip VARCHAR(45) NOT NULL,
                    endpoint VARCHAR(255),
                    tentativas INTEGER DEFAULT 1,
                    primeira_tentativa DATETIME DEFAULT CURRENT_TIMESTAMP,
                    ultima_tentativa DATETIME DEFAULT CURRENT_TIMESTAMP,
                    bloqueado INTEGER DEFAULT 0,
                    data_desbloqueio DATETIME,
                    motivo VARCHAR(255)
                )
            ");
            
            // Criar índices para SQLite
            $db->exec("CREATE INDEX IF NOT EXISTS idx_rate_limit_ip ON rate_limiting(ip)");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_rate_limit_endpoint ON rate_limiting(endpoint)");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_rate_limit_bloqueado ON rate_limiting(bloqueado)");
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Erro ao criar tabela rate_limiting: " . $e->getMessage());
        return false;
    }
}

/**
 * Obter IP do cliente (considera proxies)
 */
function obter_ip_cliente() {
    // Verificar IP através de headers (para proxies/load balancers)
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Pegar o primeiro IP se houver múltiplos
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    // Validar IP
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        $ip = '0.0.0.0';
    }
    
    return $ip;
}

/**
 * Verificar se IP está bloqueado
 */
function ip_esta_bloqueado($ip) {
    $db = getDB();
    
    try {
        if (DB_DRIVER === 'mysql') {
            $stmt = $db->prepare("
                SELECT * FROM rate_limiting 
                WHERE ip = ? AND bloqueado = 1 AND data_desbloqueio > NOW()
                LIMIT 1
            ");
        } else {
            // SQLite
            $stmt = $db->prepare("
                SELECT * FROM rate_limiting 
                WHERE ip = ? AND bloqueado = 1 AND data_desbloqueio > CURRENT_TIMESTAMP
                LIMIT 1
            ");
        }
        $stmt->execute([$ip]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $resultado !== false;
    } catch (Exception $e) {
        error_log("Erro ao verificar bloqueio de IP: " . $e->getMessage());
        return false;
    }
}

/**
 * Registrar tentativa de acesso (login, API, etc)
 */
function registrar_tentativa_acesso($endpoint = 'login', $sucesso = false) {
    $ip = obter_ip_cliente();
    $db = getDB();
    
    try {
        // Verificar registro existente nos últimos 1 minuto
        $tempo_limite = date('Y-m-d H:i:s', time() - 60); // 1 minuto atrás
        
        if (DB_DRIVER === 'mysql') {
            $stmt = $db->prepare("
                SELECT * FROM rate_limiting 
                WHERE ip = ? AND endpoint = ?
                AND ultima_tentativa > ?
                LIMIT 1
            ");
        } else {
            // SQLite
            $stmt = $db->prepare("
                SELECT * FROM rate_limiting 
                WHERE ip = ? AND endpoint = ?
                AND ultima_tentativa > ?
                LIMIT 1
            ");
        }
        $stmt->execute([$ip, $endpoint, $tempo_limite]);
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($registro) {
            // Atualizar registro existente
            if (!$sucesso) {
                $novas_tentativas = $registro['tentativas'] + 1;
                
                // Verificar se atingiu limite
                $limite = $endpoint === 'login' ? 10 : 100; // 10 para login, 100 para outros
                
                if ($novas_tentativas >= $limite) {
                    // Bloquear IP por 15 minutos
                    $data_desbloqueio = date('Y-m-d H:i:s', time() + (15 * 60));
                    $data_agora = date('Y-m-d H:i:s');
                    
                    $stmt = $db->prepare("
                        UPDATE rate_limiting 
                        SET tentativas = ?, 
                            bloqueado = 1, 
                            data_desbloqueio = ?,
                            motivo = ?,
                            ultima_tentativa = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $novas_tentativas,
                        $data_desbloqueio,
                        "Limite de tentativas para $endpoint atingido",
                        $data_agora,
                        $registro['id']
                    ]);
                    
                    // Registrar em log
                    registrar_log_seguranca("IP bloqueado", [
                        'ip' => $ip,
                        'endpoint' => $endpoint,
                        'tentativas' => $novas_tentativas,
                        'motivo' => "Limite de tentativas atingido"
                    ]);
                } else {
                    // Só incrementar tentativas
                    $data_agora = date('Y-m-d H:i:s');
                    $stmt = $db->prepare("
                        UPDATE rate_limiting 
                        SET tentativas = ?, 
                            ultima_tentativa = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$novas_tentativas, $data_agora, $registro['id']]);
                }
            } else {
                // Sucesso - resetar tentativas
                $data_agora = date('Y-m-d H:i:s');
                $stmt = $db->prepare("
                    UPDATE rate_limiting 
                    SET tentativas = 0, 
                        bloqueado = 0,
                        ultima_tentativa = ?
                    WHERE id = ?
                ");
                $stmt->execute([$data_agora, $registro['id']]);
            }
        } else {
            // Criar novo registro
            $stmt = $db->prepare("
                INSERT INTO rate_limiting (ip, endpoint, tentativas)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$ip, $endpoint, $sucesso ? 0 : 1]);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Erro ao registrar tentativa de acesso: " . $e->getMessage());
        return false;
    }
}

/**
 * Verificar Rate Limit antes de operação
 */
function verificar_rate_limit($endpoint = 'login') {
    $ip = obter_ip_cliente();
    
    // Verificar se IP está bloqueado
    if (ip_esta_bloqueado($ip)) {
        http_response_code(429); // Too Many Requests
        die(json_encode([
            'status' => 'erro',
            'mensagem' => 'Seu IP foi bloqueado temporariamente. Tente novamente em 15 minutos.',
            'codigo' => 'IP_BLOQUEADO'
        ]));
    }
    
    $db = getDB();
    
    try {
        // Verificar tentativas no último minuto
        $tempo_limite = date('Y-m-d H:i:s', time() - 60); // 1 minuto atrás
        
        if (DB_DRIVER === 'mysql') {
            $stmt = $db->prepare("
                SELECT COUNT(*) as total_tentativas 
                FROM rate_limiting 
                WHERE ip = ? AND endpoint = ?
                AND primeira_tentativa > ?
            ");
        } else {
            // SQLite
            $stmt = $db->prepare("
                SELECT COUNT(*) as total_tentativas 
                FROM rate_limiting 
                WHERE ip = ? AND endpoint = ?
                AND primeira_tentativa > ?
            ");
        }
        $stmt->execute([$ip, $endpoint, $tempo_limite]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $limite = $endpoint === 'login' ? 10 : 100;
        
        if ($resultado['total_tentativas'] >= $limite) {
            http_response_code(429);
            die(json_encode([
                'status' => 'erro',
                'mensagem' => "Limite de $limite tentativas por minuto atingido. Tente novamente em alguns minutos.",
                'codigo' => 'RATE_LIMIT_ATINGIDO'
            ]));
        }
    } catch (Exception $e) {
        error_log("Erro ao verificar rate limit: " . $e->getMessage());
    }
}

/**
 * Limpar registros antigos de rate limiting (manutenção)
 */
function limpar_rate_limiting_antigo() {
    $db = getDB();
    
    try {
        // Deletar registros com mais de 24 horas
        $tempo_limite = date('Y-m-d H:i:s', time() - (24 * 3600)); // 24 horas atrás
        
        $stmt = $db->prepare("
            DELETE FROM rate_limiting 
            WHERE ultima_tentativa < ?
        ");
        $stmt->execute([$tempo_limite]);
        
        return true;
    } catch (Exception $e) {
        error_log("Erro ao limpar rate limiting: " . $e->getMessage());
        return false;
    }
}

/**
 * Registrar evento de segurança em log
 */
function registrar_log_seguranca($tipo, $dados = []) {
    // Log de segurança é auxiliar (auditoria): uma pasta/arquivo sem permissão de escrita
    // no servidor não pode derrubar a requisição real do usuário (login, presença, etc.).
    // O handler global de erros da API (api/index.php) converte até warnings de PHP em
    // exceção, então qualquer falha aqui — mesmo um simples "Permission denied" ao abrir
    // o arquivo — vira uma resposta de erro 500 para quem só estava tentando logar. Por
    // isso a escrita fica isolada num try/catch: se falhar, perdemos o registro de
    // auditoria dessa vez, mas o usuário não vê uma tela de erro por causa disso.
    try {
        $arquivo_log = __DIR__ . '/../data/seguranca.log';
        $timestamp = date('Y-m-d H:i:s');
        $ip = obter_ip_cliente();
        $usuario_id = $_SESSION['user_id'] ?? 'anonimo';

        $mensagem = "[$timestamp] [IP: $ip] [USER: $usuario_id] [$tipo] " . json_encode($dados) . "\n";

        error_log($mensagem, 3, $arquivo_log);
    } catch (\Throwable $e) {
        // Best-effort: tenta avisar no log padrão do PHP, mas não deixa isso quebrar também
        try {
            error_log('registrar_log_seguranca falhou: ' . $e->getMessage());
        } catch (\Throwable $ignorado) {
        }
    }
}

/**
 * Limpar linha CSRF inválida
 */
function tentar_regenerar_session() {
    // Se houver erro CSRF, limpar e regenerar
    session_destroy();
    session_start();
    gerar_csrf_token();
}

// ==================== INICIALIZAÇÃO ====================

// Criar tabela de rate limiting no primeiro acesso
if (!isset($_SESSION['rate_limiting_init'])) {
    criar_tabela_rate_limiting();
    $_SESSION['rate_limiting_init'] = true;
}

// Gerar token CSRF se não existir
if (empty($_SESSION['csrf_token'])) {
    gerar_csrf_token();
}
