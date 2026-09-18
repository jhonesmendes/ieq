<?php
/**
 * API de Aprovação de Cadastros
 * Endpoints para listar, aprovar e rejeitar cadastros pendentes
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

// CORS Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// Responder a preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

$base_path = dirname(dirname(__FILE__));
require_once $base_path . '/config/database.php';
require_once $base_path . '/config/auth.php';
require_once $base_path . '/config/permissoes.php';
require_once $base_path . '/config/email.php';

try {
    initDatabase();
    
    // Verificar se está autenticado
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'Usuário não autenticado'
        ]);
        exit;
    }
    
    // Verificar se pode gerenciar aprovações
    if (!pode_gerenciar_aprovacoes()) {
        http_response_code(403);
        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'Acesso negado. Você não tem permissão para gerenciar aprovações de cadastros.'
        ]);
        exit;
    }
    
    // Obter ação
    $acao = $_GET['acao'] ?? $_POST['acao'] ?? '';
    
    if (empty($acao)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'Ação não especificada'
        ]);
        exit;
    }
    
    // ========== LISTAR CADASTROS PENDENTES ==========
    if ($acao === 'listar_cadastros_pendentes' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT 
                id,
                nome,
                email,
                google_id,
                foto_url,
                status,
                criado_em
            FROM cadastros_pendentes 
            WHERE status = 'pendente'
            ORDER BY criado_em DESC
        ");
        $stmt->execute();
        $cadastros = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode([
            'status' => 'sucesso',
            'mensagem' => 'Cadastros carregados',
            'dados' => $cadastros,
            'total' => count($cadastros)
        ]);
        exit;
    }
    
    // ========== APROVAR CADASTRO ==========
    if ($acao === 'aprovar_cadastro' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $cadastro_id = intval($_POST['cadastro_id'] ?? $_GET['cadastro_id'] ?? 0);
        $funcao = trim($_POST['funcao'] ?? $_GET['funcao'] ?? 'membro');
        
        if ($cadastro_id <= 0) {
            http_response_code(400);
            echo json_encode([
                'status' => 'erro',
                'mensagem' => 'ID do cadastro inválido'
            ]);
            exit;
        }
        
        // Validar função
        $funcoes_validas = ['membro', 'lider', 'supervisor', 'pastor'];
        if (!in_array($funcao, $funcoes_validas)) {
            $funcao = 'membro';
        }
        
        $db = getDB();
        
        try {
            $db->beginTransaction();
            
            // Obter dados do cadastro pendente
            $stmt = $db->prepare("SELECT * FROM cadastros_pendentes WHERE id = ?");
            $stmt->execute([$cadastro_id]);
            $cadastro = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$cadastro) {
                $db->rollBack();
                http_response_code(404);
                echo json_encode([
                    'status' => 'erro',
                    'mensagem' => 'Cadastro não encontrado'
                ]);
                exit;
            }
            
            // Verificar se email já existe como usuário aprovado
            $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ? AND status_aprovacao = 'aprovado'");
            $stmt->execute([$cadastro['email']]);
            if ($stmt->fetch()) {
                $db->rollBack();
                http_response_code(409);
                echo json_encode([
                    'status' => 'erro',
                    'mensagem' => 'Email já cadastrado como usuário ativo'
                ]);
                exit;
            }
            
            // Criar usuário aprovado
            $stmt = $db->prepare("
                INSERT INTO usuarios (
                    nome, 
                    email, 
                    google_id, 
                    foto_url, 
                    funcao, 
                    status_aprovacao,
                    permissoes,
                    senha
                ) VALUES (?, ?, ?, ?, ?, 'aprovado', ?, ?)
            ");
            
            // Definir permissões padrão baseado em função
            $permissoes = json_encode(['modulos_acesso']);
            
            // USAR SENHA CADASTRADA PELO USUÁRIO (já está hasheada em $cadastro['senha'])
            $senha_hash = $cadastro['senha'];
            
            if (!$stmt->execute([
                $cadastro['nome'],
                $cadastro['email'],
                $cadastro['google_id'] ?? null,
                $cadastro['foto_url'] ?? null,
                $funcao,
                $permissoes,
                $senha_hash
            ])) {
                throw new Exception("Erro ao criar usuário: " . implode(', ', $stmt->errorInfo()));
            }
            
            $novo_usuario_id = $db->lastInsertId();
            
            // Criar registro de membro associado (necessário para que apareça em "Membros")
            $stmt_membro = $db->prepare("
                INSERT INTO membros (usuario_id, status)
                VALUES (?, 'ativo')
            ");
            
            if (!$stmt_membro->execute([$novo_usuario_id])) {
                throw new Exception("Erro ao criar registro de membro: " . implode(', ', $stmt_membro->errorInfo()));
            }
            
            // Atualizar status do cadastro pendente
            $stmt = $db->prepare("UPDATE cadastros_pendentes SET status = 'aprovado' WHERE id = ?");
            $stmt->execute([$cadastro_id]);
            
            $db->commit();
            
            // Enviar email de aprovação
            $resultado_email = enviar_email_aprovacao_cadastro(
                $cadastro['email'],
                $cadastro['nome']
            );
            
            // Log do envio de email
            if ($resultado_email['sucesso'] ?? false) {
                error_log("Email de aprovação enviado para: {$cadastro['email']}");
            } else {
                error_log("AVISO: Falha ao enviar email de aprovação para {$cadastro['email']}: " . ($resultado_email['mensagem'] ?? 'Erro desconhecido'));
            }
            
            error_log("Cadastro aprovado: ID $cadastro_id -> Usuário ID $novo_usuario_id (Função: $funcao)");
            
            http_response_code(200);
            echo json_encode([
                'status' => 'sucesso',
                'mensagem' => "Cadastro de {$cadastro['nome']} aprovado com sucesso! Usuário pode fazer login com a senha cadastrada.",
                'dados' => [
                    'usuario_id' => $novo_usuario_id,
                    'nome' => $cadastro['nome'],
                    'email' => $cadastro['email'],
                    'funcao' => $funcao
                ]
            ]);
            exit;
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Erro ao aprovar cadastro: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'status' => 'erro',
                'mensagem' => 'Erro ao processar aprovação'
            ]);
            exit;
        }
    }
    
    // ========== REJEITAR CADASTRO ==========
    if ($acao === 'rejeitar_cadastro' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $cadastro_id = intval($_POST['cadastro_id'] ?? $_GET['cadastro_id'] ?? 0);
        $motivo = trim($_POST['motivo'] ?? $_GET['motivo'] ?? 'Sem motivo especificado');
        
        if ($cadastro_id <= 0) {
            http_response_code(400);
            echo json_encode([
                'status' => 'erro',
                'mensagem' => 'ID do cadastro inválido'
            ]);
            exit;
        }
        
        $db = getDB();
        
        try {
            // Obter dados do cadastro
            $stmt = $db->prepare("SELECT * FROM cadastros_pendentes WHERE id = ?");
            $stmt->execute([$cadastro_id]);
            $cadastro = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$cadastro) {
                http_response_code(404);
                echo json_encode([
                    'status' => 'erro',
                    'mensagem' => 'Cadastro não encontrado'
                ]);
                exit;
            }
            
            // Atualizar status para rejeitado
            $stmt = $db->prepare("
                UPDATE cadastros_pendentes 
                SET status = 'rejeitado', motivo_rejeicao = ?
                WHERE id = ?
            ");
            $stmt->execute([$motivo, $cadastro_id]);
            
            error_log("Cadastro rejeitado: ID $cadastro_id - Email: {$cadastro['email']} - Motivo: $motivo");
            
            http_response_code(200);
            echo json_encode([
                'status' => 'sucesso',
                'mensagem' => "Cadastro de {$cadastro['nome']} rejeitado com sucesso!",
                'dados' => [
                    'email' => $cadastro['email'],
                    'motivo' => $motivo
                ]
            ]);
            exit;
            
        } catch (Exception $e) {
            error_log("Erro ao rejeitar cadastro: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'status' => 'erro',
                'mensagem' => 'Erro ao processar rejeição'
            ]);
            exit;
        }
    }
    
    // Ação não reconhecida
    http_response_code(400);
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Ação desconhecida: ' . $acao
    ]);
    exit;
    
} catch (Exception $e) {
    error_log("Erro na API de aprovações: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Erro ao processar requisição'
    ]);
    exit;
}
?>
