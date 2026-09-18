<?php
require_once __DIR__ . '/database.php';

// Criar visitante
function criar_visitante($dados) {
    $db = getDB();
    
    $stmt = $db->prepare("
        INSERT INTO visitantes (nome, telefone, email, celula_id, data_visita, status, observacoes)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $ok = $stmt->execute([
        $dados['nome'],
        $dados['telefone'],
        $dados['email'] ?? null,
        $dados['celula_id'],
        $dados['data_visita'],
        $dados['status'] ?? 'primeira_visita',
        $dados['observacoes'] ?? null
    ]);

    return $ok ? $db->lastInsertId() : false;
}

// Listar visitantes
function listar_visitantes($celula_id = null) {
    $db = getDB();
    
    $sql = "
        SELECT v.*, c.nome as celula_nome
        FROM visitantes v
        LEFT JOIN celulas c ON v.celula_id = c.id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($celula_id) {
        $sql .= " AND v.celula_id = ?";
        $params[] = $celula_id;
    }
    
    $sql .= " ORDER BY v.data_visita DESC, v.nome";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obter visitante por ID
function obter_visitante($id) {
    $db = getDB();
    
    $stmt = $db->prepare("
        SELECT v.*, c.nome as celula_nome
        FROM visitantes v
        LEFT JOIN celulas c ON v.celula_id = c.id
        WHERE v.id = ?
    ");
    
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Atualizar visitante
function atualizar_visitante($id, $dados) {
    $db = getDB();
    
    $campos = [];
    $valores = [];
    
    $campos_permitidos = ['nome', 'telefone', 'email', 'celula_id', 'data_visita', 'status', 'observacoes'];
    
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
    $sql = "UPDATE visitantes SET " . implode(', ', $campos) . " WHERE id = ?";
    
    $stmt = $db->prepare($sql);
    return $stmt->execute($valores);
}

// Deletar visitante
function deletar_visitante($id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM visitantes WHERE id = ?");
    return $stmt->execute([$id]);
}

// Obter estatísticas de visitantes
function obter_estatisticas_visitantes() {
    $db = getDB();
    
    // Total de visitantes
    $stmt = $db->prepare("SELECT COUNT(*) FROM visitantes");
    $stmt->execute();
    $total_visitantes = $stmt->fetchColumn();
    
    // Visitantes que retornaram
    $stmt = $db->prepare("SELECT COUNT(*) FROM visitantes WHERE status IN ('retornou', 'convertido', 'membro')");
    $stmt->execute();
    $retornaram = $stmt->fetchColumn();
    
    // Visitantes convertidos
    $stmt = $db->prepare("SELECT COUNT(*) FROM visitantes WHERE status IN ('convertido', 'membro')");
    $stmt->execute();
    $convertidos = $stmt->fetchColumn();
    
    // Visitantes nos últimos 30 dias
    $data_limite = date('Y-m-d', strtotime('-30 days'));
    $stmt = $db->prepare("SELECT COUNT(*) FROM visitantes WHERE data_visita >= ?");
    $stmt->execute([$data_limite]);
    $ultimos_30_dias = $stmt->fetchColumn();
    
    return [
        'total_visitantes' => (int)$total_visitantes,
        'retornaram' => (int)$retornaram,
        'convertidos' => (int)$convertidos,
        'ultimos_30_dias' => (int)$ultimos_30_dias,
        'taxa_retorno' => $total_visitantes > 0 ? round(($retornaram / $total_visitantes) * 100, 1) : 0
    ];
}
?>
