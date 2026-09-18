<?php
require_once __DIR__ . '/database.php';

// Funções de autenticação

function autenticar_usuario($email, $senha) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usuario && password_verify($senha, $usuario['senha'])) {
        $_SESSION['user_id'] = $usuario['id'];
        $_SESSION['user_nome'] = $usuario['nome'];
        $_SESSION['user_email'] = $usuario['email'];
        $_SESSION['user_funcao'] = $usuario['funcao'];
        return true;
    }
    return false;
}

// Função melhorada que retorna detalhes sobre o erro de autenticação
function autenticar_usuario_detalhado($email, $senha) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$usuario) {
            return [
                'sucesso' => false,
                'motivo' => 'usuario_nao_encontrado',
                'mensagem' => 'Usuário não encontrado'
            ];
        }
        
        if (!password_verify($senha, $usuario['senha'])) {
            return [
                'sucesso' => false,
                'motivo' => 'senha_incorreta',
                'mensagem' => 'Senha incorreta'
            ];
        }
        
        // Sucesso!
        $_SESSION['user_id'] = $usuario['id'];
        $_SESSION['user_nome'] = $usuario['nome'];
        $_SESSION['user_email'] = $usuario['email'];
        $_SESSION['user_funcao'] = $usuario['funcao'];
        
        return [
            'sucesso' => true,
            'motivo' => 'login_ok',
            'mensagem' => 'Login realizado com sucesso'
        ];
        
    } catch (PDOException $e) {
        return [
            'sucesso' => false,
            'motivo' => 'erro_banco_dados',
            'mensagem' => 'Erro ao acessar banco de dados: ' . $e->getMessage()
        ];
    }
}

function registrar_usuario($nome, $email, $senha, $telefone = '', $funcao = 'membro') {
    $db = getDB();
    $senha_hash = password_hash($senha, PASSWORD_BCRYPT);
    
    try {
        $stmt = $db->prepare("
            INSERT INTO usuarios (nome, email, senha, telefone, funcao)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$nome, $email, $senha_hash, $telefone, $funcao]);
        return $db->lastInsertId();
    } catch (PDOException $e) {
        return false;
    }
}

function obter_usuario($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function atualizar_usuario($id, $dados) {
    $db = getDB();
    $campos = [];
    $valores = [];
    
    foreach ($dados as $campo => $valor) {
        if (in_array($campo, ['nome', 'email', 'telefone', 'foto_url', 'funcao'])) {
            $campos[] = "$campo = ?";
            $valores[] = $valor;
        }
    }
    
    if (empty($campos)) {
        return false;
    }
    
    $valores[] = $id;
    $sql = "UPDATE usuarios SET " . implode(', ', $campos) . ", atualizado_em = CURRENT_TIMESTAMP WHERE id = ?";
    
    $stmt = $db->prepare($sql);
    return $stmt->execute($valores);
}

function usuario_logado() {
    return isset($_SESSION['user_id']);
}

function obter_id_usuario_logado() {
    return $_SESSION['user_id'] ?? null;
}

function fazer_logout() {
    session_destroy();
    return true;
}

function listar_todos_usuarios() {
    $db = getDB();
    $stmt = $db->prepare("SELECT id, nome, email, telefone, funcao, permissoes FROM usuarios ORDER BY funcao DESC, nome");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function criar_usuario_admin($dados) {
    $db = getDB();
    $senha_hash = password_hash($dados['senha'], PASSWORD_BCRYPT);
    
    // Log para debug
    error_log("criar_usuario_admin - Dados recebidos: " . print_r($dados, true));
    
    // Preparar permissões
    $permissoes = isset($dados['permissoes']) && !empty($dados['permissoes']) ? json_encode($dados['permissoes']) : null;
    
    error_log("criar_usuario_admin - Permissões após json_encode: " . $permissoes);
    
    // Admin sempre tem acesso total
    if (isset($dados['funcao']) && $dados['funcao'] === 'admin') {
        $permissoes = json_encode(['*']);
    }
    
    try {
        $stmt = $db->prepare("
            INSERT INTO usuarios (nome, email, senha, telefone, funcao, permissoes)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $dados['nome'],
            $dados['email'],
            $senha_hash,
            $dados['telefone'] ?? '',
            $dados['funcao'] ?? 'membro',
            $permissoes
        ]);
        return $db->lastInsertId();
    } catch (PDOException $e) {
        error_log("Erro ao criar usuário: " . $e->getMessage());
        return false;
    }
}

function atualizar_usuario_admin($id, $dados) {
    $db = getDB();
    $campos = [];
    $valores = [];
    
    $campos_permitidos = ['nome', 'email', 'telefone', 'funcao'];
    
    foreach ($dados as $campo => $valor) {
        if (in_array($campo, $campos_permitidos)) {
            $campos[] = "$campo = ?";
            $valores[] = $valor;
        }
    }
    
    // Atualizar senha se fornecida
    if (isset($dados['senha']) && !empty($dados['senha'])) {
        $campos[] = "senha = ?";
        $valores[] = password_hash($dados['senha'], PASSWORD_BCRYPT);
    }
    
    // Atualizar permissões se fornecidas
    if (isset($dados['permissoes'])) {
        $permissoes = $dados['permissoes'];
        
        // Admin sempre tem acesso total
        if (isset($dados['funcao']) && $dados['funcao'] === 'admin') {
            $permissoes = ['*'];
        }
        
        $campos[] = "permissoes = ?";
        $valores[] = json_encode($permissoes);
    }
    
    if (empty($campos)) {
        return false;
    }
    
    $valores[] = $id;
    $sql = "UPDATE usuarios SET " . implode(', ', $campos) . " WHERE id = ?";
    
    $stmt = $db->prepare($sql);
    return $stmt->execute($valores);
}

function deletar_usuario($id) {
    $db = getDB();
    
    // Não permitir deletar o próprio usuário
    if ($id == $_SESSION['user_id']) {
        return ['sucesso' => false, 'mensagem' => 'Não é possível deletar sua própria conta'];
    }
    
    try {
        // Se usando MySQL, primeiro remover referências de chave estrangeira
        if (DB_DRIVER === 'mysql') {
            $db->beginTransaction();
            
            // Remover o usuário de célula_lider_id
            $stmt = $db->prepare("UPDATE celulas SET lider_id = NULL WHERE lider_id = ?");
            $stmt->execute([$id]);
            
            // Remover o usuário de célula_lider_id_2
            $stmt = $db->prepare("UPDATE celulas SET lider_id_2 = NULL WHERE lider_id_2 = ?");
            $stmt->execute([$id]);
            
            // Remover o usuário de lider_treinamento
            $stmt = $db->prepare("UPDATE celulas SET lider_treinamento_id = NULL WHERE lider_treinamento_id = ?");
            $stmt->execute([$id]);
            
            // Remover membros associados (opcional: pode arquivar ao invés de deletar)
            $stmt = $db->prepare("DELETE FROM membros WHERE usuario_id = ?");
            $stmt->execute([$id]);
            
            // Agora deletar o usuário
            $stmt = $db->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);
            
            $db->commit();
            return ['sucesso' => true, 'mensagem' => 'Usuário deletado com sucesso'];
        } else {
            // SQLite
            $stmt = $db->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);
            return ['sucesso' => true, 'mensagem' => 'Usuário deletado com sucesso'];
        }
    } catch (PDOException $e) {
        error_log("Erro ao deletar usuário: " . $e->getMessage());
        if (DB_DRIVER === 'mysql' && function_exists('$db->beginTransaction')) {
            try { $db->rollBack(); } catch (Exception $ex) {}
        }
        
        // Retornar mensagem de erro mais descritiva
        if (strpos($e->getMessage(), 'FOREIGN KEY') !== false) {
            return ['sucesso' => false, 'mensagem' => 'Usuário possui vínculo com células ou membros. Remova essas associações primeiro.'];
        }
        
        return ['sucesso' => false, 'mensagem' => 'Erro ao deletar usuário: ' . $e->getMessage()];
    }
}

function alterar_senha($usuario_id, $senha_atual, $senha_nova) {
    $db = getDB();
    
    // Obter usuário
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        return ['sucesso' => false, 'mensagem' => 'Usuário não encontrado'];
    }
    
    // Verificar se a senha atual está correta
    if (!password_verify($senha_atual, $usuario['senha'])) {
        return ['sucesso' => false, 'mensagem' => 'Senha atual incorreta'];
    }
    
    // Validar comprimento da nova senha
    if (strlen($senha_nova) < 6) {
        return ['sucesso' => false, 'mensagem' => 'A nova senha deve ter no mínimo 6 caracteres'];
    }
    
    // Atualizar senha
    $senha_hash = password_hash($senha_nova, PASSWORD_BCRYPT);
    $stmt = $db->prepare("UPDATE usuarios SET senha = ?, atualizado_em = CURRENT_TIMESTAMP WHERE id = ?");
    
    if ($stmt->execute([$senha_hash, $usuario_id])) {
        return ['sucesso' => true, 'mensagem' => 'Senha alterada com sucesso'];
    } else {
        return ['sucesso' => false, 'mensagem' => 'Erro ao alterar a senha'];
    }
}

// =============== FUNÇÕES DO GOOGLE OAUTH ===============

function registrar_cadastro_pendente($nome, $email, $google_id, $foto_url = null) {
    $db = getDB();
    
    try {
        error_log("Registrando cadastro pendente para: $email (google_id: " . substr($google_id, 0, 20) . "...)");
        
        // Verificar se já existe um usuário aprovado com este email
        $stmt = $db->prepare("SELECT id, status_aprovacao, google_id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario_existente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario_existente) {
            error_log("Usuário já existe: " . $usuario_existente['email'] . " - Status: " . $usuario_existente['status_aprovacao']);
            
            // Se usuário já existe e está aprovado
            if ($usuario_existente['status_aprovacao'] === 'aprovado') {
                // Se não tem google_id, atualizar
                if (!$usuario_existente['google_id'] && $google_id) {
                    error_log("Atualizando google_id para usuário existente");
                    $stmt = $db->prepare("UPDATE usuarios SET google_id = ? WHERE id = ?");
                    $stmt->execute([$google_id, $usuario_existente['id']]);
                }
                return ['sucesso' => true, 'tipo' => 'login_existente', 'usuario_id' => $usuario_existente['id']];
            }
            
            // Se está pendente, retornar que está aguardando aprovação
            return ['sucesso' => false, 'mensagem' => 'Seu cadastro está aguardando aprovação do administrador', 'tipo' => 'pendente'];
        }
        
        // Verificar se já existe em cadastros_pendentes
        $stmt = $db->prepare("SELECT id, status FROM cadastros_pendentes WHERE email = ?");
        $stmt->execute([$email]);
        $pendente_existente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($pendente_existente) {
            error_log("Cadastro pendente já existe: " . $email);
            
            // Se é um novo registro com google_id, atualizar
            if ($google_id && $pendente_existente['status'] === 'pendente') {
                $stmt = $db->prepare("UPDATE cadastros_pendentes SET google_id = ?, foto_url = ? WHERE email = ?");
                $stmt->execute([$google_id, $foto_url, $email]);
            }
            
            return ['sucesso' => false, 'mensagem' => 'Seu cadastro já está em análise', 'tipo' => 'ja_existe'];
        }
        
        // Inserir novo cadastro pendente
        $stmt = $db->prepare("
            INSERT INTO cadastros_pendentes (nome, email, google_id, foto_url, status)
            VALUES (?, ?, ?, ?, 'pendente')
        ");
        
        $result = $stmt->execute([$nome, $email, $google_id, $foto_url]);
        
        if ($result) {
            error_log("Cadastro pendente criado com sucesso para: $email");
            return [
                'sucesso' => true,
                'tipo' => 'novo_cadastro',
                'mensagem' => 'Cadastro realizado com sucesso! Aguarde a aprovação do administrador.'
            ];
        } else {
            error_log("Erro ao inserir cadastro pendente");
            return ['sucesso' => false, 'mensagem' => 'Erro ao registrar cadastro'];
        }
    } catch (PDOException $e) {
        error_log("Erro ao registrar cadastro pendente: " . $e->getMessage());
        return ['sucesso' => false, 'mensagem' => 'Erro ao registrar cadastro'];
    }
}

function listar_cadastros_pendentes() {
    $db = getDB();
    
    try {
        $stmt = $db->prepare("
            SELECT * FROM cadastros_pendentes 
            WHERE status = 'pendente'
            ORDER BY criado_em DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao listar cadastros pendentes: " . $e->getMessage());
        return [];
    }
}

function aprovar_cadastro_pendente($cadastro_id, $funcao = 'membro') {
    $db = getDB();
    
    try {
        // Obter dados do cadastro pendente
        $stmt = $db->prepare("SELECT * FROM cadastros_pendentes WHERE id = ?");
        $stmt->execute([$cadastro_id]);
        $cadastro = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cadastro) {
            return ['sucesso' => false, 'mensagem' => 'Cadastro não encontrado'];
        }
        
        error_log("Aprovando cadastro: " . $cadastro['email'] . " (google_id: " . ($cadastro['google_id'] ? substr($cadastro['google_id'], 0, 20) . "..." : "NULL") . ")");
        
        // Criar usuário aprovado com todos os dados necessários
        $stmt = $db->prepare("
            INSERT INTO usuarios (nome, email, google_id, foto_url, funcao, status_aprovacao)
            VALUES (?, ?, ?, ?, ?, 'aprovado')
        ");
        
        if ($stmt->execute([$cadastro['nome'], $cadastro['email'], $cadastro['google_id'], $cadastro['foto_url'], $funcao])) {
            $usuario_id = $db->lastInsertId();
            error_log("Usuário criado com sucesso: ID $usuario_id");
            
            // Atualizar status do cadastro pendente
            $stmt = $db->prepare("UPDATE cadastros_pendentes SET status = 'aprovado' WHERE id = ?");
            $stmt->execute([$cadastro_id]);
            
            return ['sucesso' => true, 'mensagem' => 'Cadastro aprovado com sucesso', 'usuario_id' => $usuario_id];
        } else {
            error_log("Erro ao executar INSERT de usuário");
            return ['sucesso' => false, 'mensagem' => 'Erro ao criar usuário'];
        }
    } catch (PDOException $e) {
        error_log("Erro ao aprovar cadastro: " . $e->getMessage());
        return ['sucesso' => false, 'mensagem' => 'Erro ao aprovar cadastro: ' . $e->getMessage()];
    }
}

function rejeitar_cadastro_pendente($cadastro_id, $motivo = '') {
    $db = getDB();
    
    try {
        $stmt = $db->prepare("
            UPDATE cadastros_pendentes 
            SET status = 'rejeitado', motivo_rejeicao = ?
            WHERE id = ?
        ");
        
        if ($stmt->execute([$motivo, $cadastro_id])) {
            return ['sucesso' => true, 'mensagem' => 'Cadastro rejeitado'];
        } else {
            return ['sucesso' => false, 'mensagem' => 'Erro ao rejeitar cadastro'];
        }
    } catch (PDOException $e) {
        error_log("Erro ao rejeitar cadastro: " . $e->getMessage());
        return ['sucesso' => false, 'mensagem' => 'Erro ao rejeitar cadastro'];
    }
}

function obter_usuario_por_google_id($google_id) {
    $db = getDB();
    
    try {
        if (!$google_id) {
            return null;
        }
        
        // Buscar usuário aprovado por google_id (usuários novos)
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE google_id = ? AND status_aprovacao = 'aprovado' LIMIT 1");
        $stmt->execute([$google_id]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario) {
            error_log("✅ Usuário Google encontrado por google_id: " . $usuario['email']);
            return $usuario;
        }
        
        error_log("⚠️ Nenhum usuário encontrado por google_id, tentando outras estratégias...");
        return null;
    } catch (PDOException $e) {
        error_log("Erro ao obter usuário por Google ID: " . $e->getMessage());
        return null;
    }
}

/**
 * Buscar usuário por email e atualizar google_id se necessário
 * @param string $email Email do usuário
 * @param string $google_id Google ID a ser vinculado
 * @return array Dados do usuário ou null
 */
function obter_usuario_por_email_e_vincular_google($email, $google_id) {
    $db = getDB();
    
    try {
        if (!$email || !$google_id) {
            return null;
        }
        
        // Buscar usuário em qualquer estado de aprovação
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$usuario) {
            error_log("ℹ️ Usuário não encontrado no banco: $email");
            return null;
        }
        
        // Verificar status de aprovação
        if ($usuario['status_aprovacao'] !== 'aprovado') {
            error_log("⚠️ Usuário $email encontrado mas não está aprovado: " . $usuario['status_aprovacao']);
            return null; // Retornar null para que seja tratado como cadastro pendente
        }
        
        // Se usuário existe, aprovado, mas não tem google_id, vincular agora
        if (empty($usuario['google_id'])) {
            error_log("📎 Vinculando google_id ao usuário existente: $email");
            
            $stmt = $db->prepare("UPDATE usuarios SET google_id = ? WHERE id = ?");
            $stmt->execute([$google_id, $usuario['id']]);
            
            // Recarregar dados do usuário
            $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ? LIMIT 1");
            $stmt->execute([$usuario['id']]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            error_log("✅ google_id vinculado com sucesso ao usuário: $email");
        } else {
            // Usuário já tem google_id, verificar se é o mesmo
            if ($usuario['google_id'] !== $google_id) {
                error_log("⚠️ Usuário $email tem google_id diferente. Atualizando...");
                $stmt = $db->prepare("UPDATE usuarios SET google_id = ? WHERE id = ?");
                $stmt->execute([$google_id, $usuario['id']]);
                
                $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ? LIMIT 1");
                $stmt->execute([$usuario['id']]);
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        }
        
        return $usuario;
    } catch (PDOException $e) {
        error_log("Erro ao buscar/vincular usuário por email: " . $e->getMessage());
        return null;
    }
}

// ═════════════════════════════════════════════════════════════════════
// FUNÇÕES DE RECUPERAÇÃO DE SENHA
// ═════════════════════════════════════════════════════════════════════

/**
 * Gerar token de recuperação de senha
 * @param string $email Email do usuário
 * @return array Resultado com token ou erro
 */
function gerar_token_recuperacao($email) {
    try {
        $db = getDB();
        
        // Verificar se usuário existe
        $stmt = $db->prepare("SELECT id, email FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$usuario) {
            return [
                'sucesso' => false,
                'mensagem' => 'Email não encontrado no sistema'
            ];
        }
        
        // Gerar token único e seguro
        $token = bin2hex(random_bytes(32));
        $expira_em = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token válido por 1 hora
        $ip = obter_ip_cliente();
        
        // Limpar tokens antigos do usuário (opcionalse)
        $stmt = $db->prepare("DELETE FROM recuperacao_senha WHERE usuario_id = ? AND usado = 0");
        $stmt->execute([$usuario['id']]);
        
        // Inserir novo token
        $stmt = $db->prepare("
            INSERT INTO recuperacao_senha (usuario_id, token, email, expira_em, ip_request)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$usuario['id'], $token, $email, $expira_em, $ip]);
        
        return [
            'sucesso' => true,
            'token' => $token,
            'email' => $email,
            'usuario_id' => $usuario['id'],
            'expira_em' => $expira_em,
            'mensagem' => 'Token gerado com sucesso'
        ];
        
    } catch (PDOException $e) {
        return [
            'sucesso' => false,
            'mensagem' => 'Erro ao gerar token: ' . $e->getMessage()
        ];
    }
}

/**
 * Validar token de recuperação de senha
 * @param string $token Token a validar
 * @return array Resultado da validação
 */
function validar_token_recuperacao($token) {
    try {
        $db = getDB();
        
        // Buscar token no banco
        $stmt = $db->prepare("
            SELECT r.*, u.email as usuario_email, u.nome
            FROM recuperacao_senha r
            JOIN usuarios u ON r.usuario_id = u.id
            WHERE r.token = ?
        ");
        $stmt->execute([$token]);
        $recuperacao = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$recuperacao) {
            return [
                'valido' => false,
                'mensagem' => 'Token inválido',
                'erro_tipo' => 'token_nao_encontrado'
            ];
        }
        
        // Verificar se já foi usado
        if ($recuperacao['usado'] == 1) {
            return [
                'valido' => false,
                'mensagem' => 'Este link já foi utilizado',
                'erro_tipo' => 'token_ja_usado'
            ];
        }
        
        // Verificar se expirou
        $data_expiracao = new DateTime($recuperacao['expira_em']);
        $agora = new DateTime();
        
        if ($agora > $data_expiracao) {
            return [
                'valido' => false,
                'mensagem' => 'Este link expirou. Solicite um novo link de recuperação',
                'erro_tipo' => 'token_expirado'
            ];
        }
        
        // Token válido!
        return [
            'valido' => true,
            'usuario_id' => $recuperacao['usuario_id'],
            'email' => $recuperacao['usuario_email'],
            'nome' => $recuperacao['nome'],
            'mensagem' => 'Token válido'
        ];
        
    } catch (PDOException $e) {
        return [
            'valido' => false,
            'mensagem' => 'Erro ao validar token: ' . $e->getMessage(),
            'erro_tipo' => 'erro_banco'
        ];
    }
}

/**
 * Redefinir senha do usuário
 * @param string $token Token de recuperação
 * @param string $nova_senha Nova senha
 * @return array Resultado da operação
 */
function redefinir_senha($token, $nova_senha) {
    try {
        $db = getDB();
        
        // Validar token primeiro
        $validacao = validar_token_recuperacao($token);
        
        if (!$validacao['valido']) {
            return [
                'sucesso' => false,
                'mensagem' => $validacao['mensagem']
            ];
        }
        
        // Validar nova senha
        if (strlen($nova_senha) < 6) {
            return [
                'sucesso' => false,
                'mensagem' => 'A nova senha deve ter no mínimo 6 caracteres'
            ];
        }
        
        $usuario_id = $validacao['usuario_id'];
        $senha_hash = password_hash($nova_senha, PASSWORD_BCRYPT);
        
        // Atualizar senha do usuário
        $stmt = $db->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
        $stmt->execute([$senha_hash, $usuario_id]);
        
        // Marcar token como usado
        $stmt = $db->prepare("
            UPDATE recuperacao_senha
            SET usado = 1, usado_em = CURRENT_TIMESTAMP
            WHERE token = ?
        ");
        $stmt->execute([$token]);
        
        // Log de sucesso
        registrar_log_seguranca('SENHA_REDEFINIDA', [
            'usuario_id' => $usuario_id,
            'email' => $validacao['email']
        ]);
        
        return [
            'sucesso' => true,
            'mensagem' => 'Senha redefinida com sucesso',
            'usuario_id' => $usuario_id,
            'email' => $validacao['email']
        ];
        
    } catch (PDOException $e) {
        return [
            'sucesso' => false,
            'mensagem' => 'Erro ao redefinir senha: ' . $e->getMessage()
        ];
    }
}

/**
 * Limpar tokens expirados
 */
function limpar_tokens_expirados() {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            DELETE FROM recuperacao_senha
            WHERE usado = 0 AND expira_em < CURRENT_TIMESTAMP
        ");
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Erro ao limpar tokens expirados: " . $e->getMessage());
        return false;
    }
}