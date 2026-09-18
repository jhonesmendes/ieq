<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/permissoes.php';

// Funções para gerenciar eventos

function criar_evento($nome, $dados) {
    $db = getDB();
    
    $stmt = $db->prepare("
        INSERT INTO eventos (nome, descricao, data_evento, localizacao, responsavel_id, tipo, vagas)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    return $stmt->execute([
        $nome,
        $dados['descricao'] ?? '',
        $dados['data_evento'],
        $dados['localizacao'] ?? '',
        $dados['responsavel_id'] ?? null,
        $dados['tipo'] ?? 'evento',
        $dados['vagas'] ?? null
    ]);
}

function obter_evento($id) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT e.*, u.nome as responsavel_nome, u.email as responsavel_email
        FROM eventos e
        LEFT JOIN usuarios u ON e.responsavel_id = u.id
        WHERE e.id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function listar_eventos($tipo = null, $data_inicio = null, $data_fim = null) {
    $db = getDB();
    $sql = "
        SELECT e.*, u.nome as responsavel_nome
        FROM eventos e
        LEFT JOIN usuarios u ON e.responsavel_id = u.id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($tipo) {
        $sql .= " AND e.tipo = ?";
        $params[] = $tipo;
    }
    
    if ($data_inicio && $data_fim) {
        $sql .= " AND e.data_evento BETWEEN ? AND ?";
        $params[] = $data_inicio;
        $params[] = $data_fim;
    }
    
    $sql .= " ORDER BY e.data_evento";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function listar_proximos_eventos($limite = 5) {
    $db = getDB();
    $limite = (int)$limite;
    $stmt = $db->prepare("
        SELECT e.*, u.nome as responsavel_nome
        FROM eventos e
        LEFT JOIN usuarios u ON e.responsavel_id = u.id
        WHERE e.data_evento >= ?
        ORDER BY e.data_evento
        LIMIT $limite
    ");
    $stmt->execute([date('Y-m-d H:i:s')]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function atualizar_evento($id, $dados) {
    $db = getDB();
    $campos = [];
    $valores = [];
    
    $campos_permitidos = ['nome', 'descricao', 'data_evento', 'localizacao', 'responsavel_id', 'tipo', 'vagas'];
    
    foreach ($dados as $campo => $valor) {
        if (in_array($campo, $campos_permitidos)) {
            $campos[] = "$campo = ?";
            $valores[] = $valor;
        }
    }
    
    if (empty($campos)) {
        return false;
    }
    
    $valores[] = $id;
    $sql = "UPDATE eventos SET " . implode(', ', $campos) . " WHERE id = ?";
    
    $stmt = $db->prepare($sql);
    return $stmt->execute($valores);
}

function deletar_evento($id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM eventos WHERE id = ?");
    return $stmt->execute([$id]);
}

function contar_eventos_mes($mes = null) {
    $db = getDB();
    
    if (!$mes) {
        $mes = date('Y-m');
    }
    
    $data_inicio = $mes . '-01';
    $data_fim = date('Y-m-t', strtotime($data_inicio));
    
    $stmt = $db->prepare("
        SELECT COUNT(*) as total FROM eventos 
        WHERE data_evento BETWEEN ? AND ?
    ");
    $stmt->execute([$data_inicio . ' 00:00:00', $data_fim . ' 23:59:59']);
    return $stmt->fetchColumn();
}
?>
