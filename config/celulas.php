<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/permissoes.php';

// Funções para gerenciar células

function criar_celula($nome, $dados) {
    $db = getDB();
    
    $stmt = $db->prepare("
        INSERT INTO celulas (nome, igreja_id, localizacao, latitude, longitude, lider_id, lider_id_2, lider_treinamento_id, supervisor_id, tipo, dia_semana, hora, endereco, bairro, cidade)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    return $stmt->execute([
        $nome,
        $dados['igreja_id'] ?? null,
        $dados['localizacao'] ?? '',
        $dados['latitude'] ?? null,
        $dados['longitude'] ?? null,
        $dados['lider_id'] ?? null,
        $dados['lider_id_2'] ?? null,
        $dados['lider_treinamento_id'] ?? null,
        $dados['supervisor_id'] ?? null,
        $dados['tipo'] ?? 'celula',
        $dados['dia_semana'] ?? '',
        $dados['hora'] ?? '',
        $dados['endereco'] ?? '',
        $dados['bairro'] ?? '',
        $dados['cidade'] ?? ''
    ]);
}

function obter_celula($id) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT c.*, 
               i.nome as igreja_nome,
               ul.nome as lider_nome, ul.email as lider_email,
               ul2.nome as lider_2_nome, ul2.email as lider_2_email,
               ult.nome as lider_treinamento_nome, ult.email as lider_treinamento_email,
               us.nome as supervisor_nome, us.email as supervisor_email
        FROM celulas c
        LEFT JOIN igrejas i ON c.igreja_id = i.id
        LEFT JOIN usuarios ul ON c.lider_id = ul.id
        LEFT JOIN usuarios ul2 ON c.lider_id_2 = ul2.id
        LEFT JOIN usuarios ult ON c.lider_treinamento_id = ult.id
        LEFT JOIN usuarios us ON c.supervisor_id = us.id
        WHERE c.id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function listar_celulas($supervisor_id = null, $aplicar_filtro_perfil = true) {
    $db = getDB();
    $sql = "
        SELECT c.*, 
               COUNT(m.id) as total_membros,
               i.nome as igreja_nome,
               ul.nome as lider_nome,
               ul2.nome as lider_2_nome,
               ult.nome as lider_treinamento_nome,
               us.nome as supervisor_nome
        FROM celulas c
        LEFT JOIN igrejas i ON c.igreja_id = i.id
        LEFT JOIN membros m ON c.id = m.celula_id AND m.status = 'ativo'
        LEFT JOIN usuarios ul ON c.lider_id = ul.id
        LEFT JOIN usuarios ul2 ON c.lider_id_2 = ul2.id
        LEFT JOIN usuarios ult ON c.lider_treinamento_id = ult.id
        LEFT JOIN usuarios us ON c.supervisor_id = us.id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Aplicar filtro baseado no perfil do usuário
    if ($aplicar_filtro_perfil && isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        
        // Se for líder ou líder em treinamento (não admin, pastor ou supervisor), ver apenas suas células
        if (is_lider_exclusivo()) {
            $sql .= " AND (c.lider_id = ? OR c.lider_id_2 = ? OR c.lider_treinamento_id = ?)";
            $params[] = $user_id;
            $params[] = $user_id;
            $params[] = $user_id;
        }
        // Se for supervisor (mas não admin ou pastor), ver apenas células que supervisiona
        elseif ($_SESSION['user_funcao'] === 'supervisor') {
            $sql .= " AND c.supervisor_id = ?";
            $params[] = $user_id;
        }
        // Admin e pastor veem todas - não adiciona filtro
    }
    
    // Filtro adicional por supervisor_id (parâmetro da função)
    if ($supervisor_id) {
        $sql .= " AND c.supervisor_id = ?";
        $params[] = $supervisor_id;
    }
    
    $sql .= " GROUP BY c.id ORDER BY c.nome";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function atualizar_celula($id, $dados) {
    $db = getDB();
    $campos = [];
    $valores = [];
    
    $campos_permitidos = ['nome', 'igreja_id', 'localizacao', 'latitude', 'longitude', 'lider_id', 'lider_id_2', 'lider_treinamento_id', 'supervisor_id', 'tipo', 'dia_semana', 'hora', 'endereco', 'bairro', 'cidade'];
    
    // Campos que podem ser nulos (ForeignKeys opcionais)
    $campos_opcionais = ['igreja_id', 'lider_id', 'lider_id_2', 'lider_treinamento_id', 'supervisor_id'];
    
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
    
    if (empty($campos)) {
        return false;
    }
    
    $valores[] = $id;
    $sql = "UPDATE celulas SET " . implode(', ', $campos) . " WHERE id = ?";
    
    $stmt = $db->prepare($sql);
    return $stmt->execute($valores);
}

function deletar_celula($id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM celulas WHERE id = ?");
    return $stmt->execute([$id]);
}

function obter_lideres() {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, nome, email, telefone FROM usuarios WHERE funcao = 'lider' ORDER BY nome");
        $stmt->execute();
        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Log para debug
        error_log("obter_lideres retornou: " . count($resultado) . " líderes");
        
        return $resultado;
    } catch (Exception $e) {
        error_log("Erro em obter_lideres: " . $e->getMessage());
        return [];
    }
}

function obter_lideres_treinamento() {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, nome, email, telefone, funcao FROM usuarios WHERE funcao IN ('lider', 'lider_treinamento') ORDER BY nome");
        $stmt->execute();
        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Log para debug
        error_log("obter_lideres_treinamento retornou: " . count($resultado) . " líderes");
        
        return $resultado;
    } catch (Exception $e) {
        error_log("Erro em obter_lideres_treinamento: " . $e->getMessage());
        return [];
    }
}

function obter_estatisticas_celulas() {
    $db = getDB();
    
    // Total de células
    $stmt = $db->prepare("SELECT COUNT(*) FROM celulas");
    $stmt->execute();
    $total_celulas = $stmt->fetchColumn();
    
    // Total de líderes (usuários únicos que são líderes de células)
    $stmt = $db->prepare("SELECT COUNT(DISTINCT lider_id) FROM celulas WHERE lider_id IS NOT NULL");
    $stmt->execute();
    $total_lideres = $stmt->fetchColumn();
    
    // Média de membros por célula
    $stmt = $db->prepare("
        SELECT COALESCE(AVG(total), 0) as media FROM (
            SELECT COUNT(*) as total 
            FROM membros 
            WHERE celula_id IS NOT NULL
            GROUP BY celula_id
        )
    ");
    $stmt->execute();
    $media_membros = round($stmt->fetchColumn(), 1);
    
    return [
        'total_celulas' => (int)$total_celulas,
        'total_lideres' => (int)$total_lideres,
        'media_membros' => $media_membros
    ];
}
?>
