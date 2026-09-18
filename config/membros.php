<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/permissoes.php';

// Funções para gerenciar membros

function criar_membro($usuario_id, $celula_id, $dados) {
    $db = getDB();
    
    $stmt = $db->prepare("
        INSERT INTO membros (usuario_id, celula_id, data_conversao, data_batismo, status, endereco, bairro, cidade, cep, data_nasc)
        VALUES (?, ?, ?, ?, 'ativo', ?, ?, ?, ?, ?)
    ");
    
    return $stmt->execute([
        $usuario_id,
        $celula_id,
        $dados['data_conversao'] ?? null,
        $dados['data_batismo'] ?? null,
        $dados['endereco'] ?? '',
        $dados['bairro'] ?? '',
        $dados['cidade'] ?? '',
        $dados['cep'] ?? '',
        $dados['data_nasc'] ?? null
    ]);
}

function obter_membro($id) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT m.*, u.nome, u.email, u.telefone, u.funcao, c.nome as celula_nome
        FROM membros m
        JOIN usuarios u ON m.usuario_id = u.id
        LEFT JOIN celulas c ON m.celula_id = c.id
        WHERE m.id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function listar_membros($celula_id = null, $status = null, $aplicar_filtro_perfil = true) {
    $db = getDB();
    $sql = "
        SELECT m.*, u.nome, u.email, u.telefone, u.funcao, c.nome as celula_nome
        FROM membros m
        JOIN usuarios u ON m.usuario_id = u.id
        LEFT JOIN celulas c ON m.celula_id = c.id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Aplicar filtro baseado no perfil do usuário
    if ($aplicar_filtro_perfil && isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        
        // Se for líder exclusivo, ver apenas membros de suas células
        if (is_lider_exclusivo()) {
            $celulas_do_lider = obter_celulas_do_lider($user_id);
            if (!empty($celulas_do_lider)) {
                $placeholders = implode(',', array_fill(0, count($celulas_do_lider), '?'));
                $sql .= " AND m.celula_id IN ($placeholders)";
                $params = array_merge($params, $celulas_do_lider);
            } else {
                // Líder sem células não vê nenhum membro
                $sql .= " AND 0 = 1";
            }
        }
        // Se for supervisor (mas não admin ou pastor), ver apenas membros de células que supervisiona
        elseif ($_SESSION['user_funcao'] === 'supervisor') {
            $sql .= " AND c.supervisor_id = ?";
            $params[] = $user_id;
        }
        // Admin e pastor veem todos - não adiciona filtro
    }
    
    // Filtro adicional por celula_id (parâmetro da função)
    if ($celula_id) {
        $sql .= " AND m.celula_id = ?";
        $params[] = $celula_id;
    }
    
    if ($status) {
        $sql .= " AND m.status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY u.nome";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function atualizar_membro($id, $dados) {
    $db = getDB();
    
    // Atualizar tabela membros
    $campos = [];
    $valores = [];
    
    $campos_permitidos = ['celula_id', 'data_conversao', 'data_batismo', 'status', 'endereco', 'bairro', 'cidade', 'cep', 'data_nasc'];
    
    // Campos que podem ser nulos (ForeignKeys opcionais ou datas opcionais)
    $campos_opcionais = ['celula_id', 'data_conversao', 'data_batismo', 'data_nasc'];
    
    foreach ($dados as $campo => $valor) {
        if (in_array($campo, $campos_permitidos)) {
            // Se é um campo opcional e o valor é vazio, converter para NULL
            if (in_array($campo, $campos_opcionais) && ($valor === '' || $valor === null)) {
                $valor = null;
            }
            
            $campos[] = "$campo = ?";
            $valores[] = $valor;
        }
    }
    
    if (!empty($campos)) {
        $valores[] = $id;
        $sql = "UPDATE membros SET " . implode(', ', $campos) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($valores);
    }
    
    // Buscar usuario_id do membro
    $stmt = $db->prepare("SELECT usuario_id FROM membros WHERE id = ?");
    $stmt->execute([$id]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($resultado && $resultado['usuario_id']) {
        $usuario_id = $resultado['usuario_id'];
        
        // Atualizar dados do usuário (nome, email, telefone, funcao)
        $campos_usuario = [];
        $valores_usuario = [];
        
        if (isset($dados['nome']) && !empty($dados['nome'])) {
            $campos_usuario[] = "nome = ?";
            $valores_usuario[] = $dados['nome'];
        }
        
        if (isset($dados['email']) && !empty($dados['email'])) {
            $campos_usuario[] = "email = ?";
            $valores_usuario[] = $dados['email'];
        }
        
        if (isset($dados['telefone']) && !empty($dados['telefone'])) {
            $campos_usuario[] = "telefone = ?";
            $valores_usuario[] = $dados['telefone'];
        }
        
        if (isset($dados['funcao']) && !empty($dados['funcao'])) {
            $campos_usuario[] = "funcao = ?";
            $valores_usuario[] = $dados['funcao'];
        }
        
        // Executar update de usuário se houver campos para atualizar
        if (!empty($campos_usuario)) {
            $valores_usuario[] = $usuario_id;
            $sql_usuario = "UPDATE usuarios SET " . implode(', ', $campos_usuario) . " WHERE id = ?";
            $stmt = $db->prepare($sql_usuario);
            $stmt->execute($valores_usuario);
        }
    }
    
    return true;
}

function deletar_membro($id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM membros WHERE id = ?");
    return $stmt->execute([$id]);
}

function contar_membros_celula($celula_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM membros WHERE celula_id = ? AND status = 'ativo'");
    $stmt->execute([$celula_id]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    return $resultado['total'];
}

function buscar_membros($termo = '', $aplicar_filtro_perfil = true) {
    $db = getDB();
    $sql = "
        SELECT m.*, u.nome, u.email, u.telefone, c.nome as celula_nome
        FROM membros m
        JOIN usuarios u ON m.usuario_id = u.id
        LEFT JOIN celulas c ON m.celula_id = c.id
        WHERE (u.nome LIKE ? OR u.email LIKE ?)
    ";
    
    $params = [];
    $termo_busca = "%{$termo}%";
    $params[] = $termo_busca;
    $params[] = $termo_busca;
    
    // Aplicar filtro baseado no perfil do usuário
    if ($aplicar_filtro_perfil && isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        
        // Se for líder exclusivo, buscar apenas em suas células
        if (is_lider_exclusivo()) {
            $celulas_do_lider = obter_celulas_do_lider($user_id);
            if (!empty($celulas_do_lider)) {
                $placeholders = implode(',', array_fill(0, count($celulas_do_lider), '?'));
                $sql .= " AND m.celula_id IN ($placeholders)";
                $params = array_merge($params, $celulas_do_lider);
            } else {
                // Líder sem células não vê nenhum membro
                $sql .= " AND 0 = 1";
            }
        }
        // Se for supervisor, buscar apenas em células que supervisiona
        elseif ($_SESSION['user_funcao'] === 'supervisor') {
            $sql .= " AND c.supervisor_id = ?";
            $params[] = $user_id;
        }
    }
    
    $sql .= " ORDER BY u.nome";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obter_estatisticas_membros() {
    $db = getDB();
    
    // Total de membros (contar registros na tabela membros)
    $stmt = $db->prepare("SELECT COUNT(*) FROM membros");
    $stmt->execute();
    $total_membros = $stmt->fetchColumn();
    
    // Membros convertidos (com data de conversão preenchida)
    $stmt = $db->prepare("SELECT COUNT(*) FROM membros WHERE data_conversao IS NOT NULL AND data_conversao != ''");
    $stmt->execute();
    $novos_convertidos = $stmt->fetchColumn();
    
    // Membros batizados (com data de batismo preenchida)
    $stmt = $db->prepare("SELECT COUNT(*) FROM membros WHERE data_batismo IS NOT NULL AND data_batismo != ''");
    $stmt->execute();
    $novos_batizados = $stmt->fetchColumn();
    
    return [
        'total_membros' => (int)$total_membros,
        'novos_convertidos' => (int)$novos_convertidos,
        'novos_batizados' => (int)$novos_batizados
    ];
}
?>
