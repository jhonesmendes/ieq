<?php
require_once __DIR__ . '/database.php';

// Funções para gerenciar cursos

function criar_curso($nome, $dados) {
    $db = getDB();
    
    $stmt = $db->prepare("
        INSERT INTO cursos (nome, descricao, professor_id, data_inicio, data_fim, localizacao, vagas)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    return $stmt->execute([
        $nome,
        $dados['descricao'] ?? '',
        $dados['professor_id'] ?? null,
        $dados['data_inicio'] ?? null,
        $dados['data_fim'] ?? null,
        $dados['localizacao'] ?? '',
        $dados['vagas'] ?? null
    ]);
}

function obter_curso($id) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT c.*, u.nome as professor_nome, u.email as professor_email
        FROM cursos c
        LEFT JOIN usuarios u ON c.professor_id = u.id
        WHERE c.id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function listar_cursos($ativo = null) {
    $db = getDB();
    $sql = "
        SELECT c.*, u.nome as professor_nome
        FROM cursos c
        LEFT JOIN usuarios u ON c.professor_id = u.id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($ativo === true) {
        // Cursos que ainda não terminaram
        $sql .= " AND (c.data_fim IS NULL OR c.data_fim >= ?)";
        $params[] = date('Y-m-d');
    }
    
    $sql .= " ORDER BY c.data_inicio DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function atualizar_curso($id, $dados) {
    $db = getDB();
    $campos = [];
    $valores = [];
    
    $campos_permitidos = ['nome', 'descricao', 'professor_id', 'data_inicio', 'data_fim', 'localizacao', 'vagas'];
    
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
    $sql = "UPDATE cursos SET " . implode(', ', $campos) . " WHERE id = ?";
    
    $stmt = $db->prepare($sql);
    return $stmt->execute($valores);
}

function deletar_curso($id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM cursos WHERE id = ?");
    return $stmt->execute([$id]);
}

function contar_cursos_ativos() {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT COUNT(*) as total FROM cursos 
        WHERE data_fim IS NULL OR data_fim >= ?
    ");
    $stmt->execute([date('Y-m-d')]);
    return $stmt->fetchColumn();
}
?>
