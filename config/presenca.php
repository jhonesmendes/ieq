<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/permissoes.php';

// Funções para gerenciar presença

function registrar_presenca($membro_id, $celula_id, $data_presenca, $presente = true, $visitante = false) {
    $db = getDB();
    
    // Verificar se já existe registro
    $stmt = $db->prepare("
        SELECT id FROM presencas 
        WHERE membro_id = ? AND celula_id = ? AND data_presenca = ?
    ");
    $stmt->execute([$membro_id, $celula_id, $data_presenca]);
    
    if ($stmt->fetch()) {
        // Atualizar
        $stmt = $db->prepare("
            UPDATE presencas 
            SET presente = ?, visitante = ? 
            WHERE membro_id = ? AND celula_id = ? AND data_presenca = ?
        ");
        return $stmt->execute([$presente ? 1 : 0, $visitante ? 1 : 0, $membro_id, $celula_id, $data_presenca]);
    } else {
        // Inserir
        $stmt = $db->prepare("
            INSERT INTO presencas (membro_id, celula_id, data_presenca, presente, visitante)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$membro_id, $celula_id, $data_presenca, $presente ? 1 : 0, $visitante ? 1 : 0]);
    }
}

function obter_presencas_celula($celula_id, $data_inicio = null, $data_fim = null, $aplicar_filtro_perfil = true) {
    $db = getDB();
    
    // Verificar permissões se aplicar filtro
    if ($aplicar_filtro_perfil && isset($_SESSION['user_id'])) {
        if (is_lider_exclusivo()) {
            $celulas_do_lider = obter_celulas_do_lider();
            if (!in_array($celula_id, $celulas_do_lider)) {
                // Líder tentando ver presença de célula que não é dele
                return [];
            }
        }
    }
    
    $sql = "
        SELECT p.*, u.nome, u.email
        FROM presencas p
        JOIN membros m ON p.membro_id = m.id
        JOIN usuarios u ON m.usuario_id = u.id
        WHERE p.celula_id = ?
    ";
    
    $params = [$celula_id];
    
    if ($data_inicio && $data_fim) {
        $sql .= " AND p.data_presenca BETWEEN ? AND ?";
        $params[] = $data_inicio;
        $params[] = $data_fim;
    }
    
    $sql .= " ORDER BY p.data_presenca DESC, u.nome";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obter_presencas_membro($membro_id, $meses = 3) {
    $db = getDB();
    
    $data_inicio = date('Y-m-d', strtotime("-$meses months"));
    
    $stmt = $db->prepare("
        SELECT p.*, c.nome as celula_nome
        FROM presencas p
        JOIN celulas c ON p.celula_id = c.id
        WHERE p.membro_id = ? AND p.data_presenca >= ?
        ORDER BY p.data_presenca DESC
    ");
    $stmt->execute([$membro_id, $data_inicio]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function contar_presencas_membro($membro_id, $meses = 3) {
    $db = getDB();
    
    $data_inicio = date('Y-m-d', strtotime("-$meses months"));
    
    $stmt = $db->prepare("
        SELECT COUNT(*) as total FROM presencas 
        WHERE membro_id = ? AND presente = 1 AND data_presenca >= ?
    ");
    $stmt->execute([$membro_id, $data_inicio]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    return $resultado['total'];
}

function obter_estatisticas_celula($celula_id, $mes = null) {
    $db = getDB();
    
    if ($mes) {
        $data_inicio = $mes . '-01';
        $data_fim = date('Y-m-t', strtotime($data_inicio));
    } else {
        $data_inicio = date('Y-m-01');
        $data_fim = date('Y-m-d');
    }
    
    // Total de presentes
    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT membro_id) as total_presentes
        FROM presencas
        WHERE celula_id = ? AND presente = 1 AND data_presenca BETWEEN ? AND ?
    ");
    $stmt->execute([$celula_id, $data_inicio, $data_fim]);
    $presentes = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Total de visitantes
    $stmt = $db->prepare("
        SELECT COUNT(*) as total_visitantes
        FROM presencas
        WHERE celula_id = ? AND visitante = 1 AND data_presenca BETWEEN ? AND ?
    ");
    $stmt->execute([$celula_id, $data_inicio, $data_fim]);
    $visitantes = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Média por reunião
    $stmt = $db->prepare("
        SELECT COUNT(*) / COUNT(DISTINCT data_presenca) as media_por_reuniao
        FROM presencas
        WHERE celula_id = ? AND presente = 1 AND data_presenca BETWEEN ? AND ?
    ");
    $stmt->execute([$celula_id, $data_inicio, $data_fim]);
    $media = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return [
        'total_presentes' => $presentes['total_presentes'] ?? 0,
        'total_visitantes' => $visitantes['total_visitantes'] ?? 0,
        'media_por_reuniao' => round($media['media_por_reuniao'] ?? 0, 2),
        'periodo' => "$data_inicio a $data_fim"
    ];
}

// Obter relatório consolidado de presença
function obter_relatorio_presenca($data_inicio, $data_fim, $celula_id = null) {
    $db = getDB();
    
    // Estatísticas gerais
    $sql = "
        SELECT 
            COUNT(CASE WHEN presente = 1 THEN 1 END) as total_presencas,
            COUNT(CASE WHEN presente = 0 THEN 1 END) as total_ausencias,
            COUNT(CASE WHEN visitante = 1 THEN 1 END) as total_visitantes
        FROM presencas
        WHERE data_presenca BETWEEN ? AND ?
    ";
    
    $params = [$data_inicio, $data_fim];
    
    if ($celula_id) {
        $sql .= " AND celula_id = ?";
        $params[] = $celula_id;
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $totais = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $totalPresencas = $totais['total_presencas'] ?? 0;
    $totalAusencias = $totais['total_ausencias'] ?? 0;
    $totalVisitantes = $totais['total_visitantes'] ?? 0;
    
    $taxaPresenca = ($totalPresencas + $totalAusencias) > 0 
        ? round(($totalPresencas / ($totalPresencas + $totalAusencias)) * 100, 1)
        : 0;
    
    // Estatísticas por célula
    $sql = "
        SELECT 
            c.id,
            c.nome,
            u.nome as lider,
            COUNT(CASE WHEN p.presente = 1 THEN 1 END) as presencas,
            COUNT(CASE WHEN p.presente = 0 THEN 1 END) as ausencias,
            COUNT(CASE WHEN p.visitante = 1 THEN 1 END) as visitantes,
            COUNT(DISTINCT p.data_presenca) as reunioes,
            COUNT(DISTINCT p.membro_id) as total_membros
        FROM celulas c
        LEFT JOIN usuarios u ON c.lider_id = u.id
        LEFT JOIN presencas p ON c.id = p.celula_id 
            AND p.data_presenca BETWEEN ? AND ?
        WHERE 1=1
    ";
    
    $params = [$data_inicio, $data_fim];
    
    if ($celula_id) {
        $sql .= " AND c.id = ?";
        $params[] = $celula_id;
    }
    
    $sql .= " GROUP BY c.id, c.nome, u.nome ORDER BY presencas DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $celulas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Adicionar detalhes de membros para cada célula
    foreach ($celulas as &$celula) {
        $celula['membros'] = obter_membros_relatorio($celula['id'], $data_inicio, $data_fim);
        // Adicionar registros individuais de presença
        $celula['registros_presenca'] = obter_registros_presenca($celula['id'], $data_inicio, $data_fim);
    }
    
    return [
        'status' => 'sucesso',
        'dados' => [
            'totais' => [
                'taxa_presenca' => $taxaPresenca,
                'total_presencas' => $totalPresencas,
                'total_ausencias' => $totalAusencias,
                'total_visitantes' => $totalVisitantes
            ],
            'celulas' => $celulas
        ]
    ];
}

// Obter detalhes de membros para o relatório
function obter_membros_relatorio($celula_id, $data_inicio, $data_fim) {
    $db = getDB();
    
    $sql = "
        SELECT 
            u.nome,
            COUNT(CASE WHEN p.presente = 1 THEN 1 END) as presencas,
            COUNT(CASE WHEN p.presente = 0 THEN 1 END) as ausencias
        FROM presencas p
        JOIN membros m ON p.membro_id = m.id
        JOIN usuarios u ON m.usuario_id = u.id
        WHERE p.celula_id = ? AND p.data_presenca BETWEEN ? AND ?
        GROUP BY u.id, u.nome
        ORDER BY presencas DESC, u.nome
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$celula_id, $data_inicio, $data_fim]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Criar ou atualizar reunião de célula
 */
function criar_reuniao_celula($celula_id, $data_reuniao, $foto_url = null, $observacoes = null) {
    $db = getDB();
    
    // ✅ SEMPRE criar novo registro (permite múltiplas fotos da mesma reunião)
    $stmt = $db->prepare("
        INSERT INTO reunioes_celula (celula_id, data_reuniao, foto_url, observacoes)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$celula_id, $data_reuniao, $foto_url, $observacoes]);
    $reuniao_id = $db->lastInsertId();
    
    return $reuniao_id;
}

/**
 * Atualizar totais de presentes e visitantes na reunião
 * ✅ Atualiza TODOS os registros da mesma data+célula (múltiplas fotos)
 */
function atualizar_totais_reuniao($reuniao_id) {
    $db = getDB();
    
    // Buscar data e célula da reunião
    $stmt = $db->prepare("SELECT celula_id, data_reuniao FROM reunioes_celula WHERE id = ?");
    $stmt->execute([$reuniao_id]);
    $reuniao = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reuniao) {
        return false;
    }
    
    // Contar presentes (não visitantes)
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM presencas 
        WHERE celula_id = ? AND data_presenca = ? AND presente = 1 AND visitante = 0
    ");
    $stmt->execute([$reuniao['celula_id'], $reuniao['data_reuniao']]);
    $total_presentes = $stmt->fetchColumn();
    
    // Contar visitantes
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM presencas 
        WHERE celula_id = ? AND data_presenca = ? AND presente = 1 AND visitante = 1
    ");
    $stmt->execute([$reuniao['celula_id'], $reuniao['data_reuniao']]);
    $total_visitantes = $stmt->fetchColumn();
    
    // Atualizar TODOS os registros da mesma reunião (mesma data + célula)
    // Isso é necessário porque agora podemos ter múltiplas fotos da mesma reunião
    $stmt = $db->prepare("
        UPDATE reunioes_celula 
        SET total_presentes = ?, total_visitantes = ?
        WHERE celula_id = ? AND data_reuniao = ?
    ");
    return $stmt->execute([$total_presentes, $total_visitantes, $reuniao['celula_id'], $reuniao['data_reuniao']]);
}

/**
 * Listar reuniões de uma célula
 */
function listar_reunioes_celula($celula_id, $limite = 20) {
    $db = getDB();
    $limite = (int)$limite;
    
    $stmt = $db->prepare("
        SELECT * FROM reunioes_celula 
        WHERE celula_id = ? 
        ORDER BY data_reuniao DESC 
        LIMIT $limite
    ");
    $stmt->execute([$celula_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obter reunião específica
 */
function obter_reuniao($reuniao_id) {
    $db = getDB();
    
    $stmt = $db->prepare("
        SELECT r.*, c.nome as celula_nome
        FROM reunioes_celula r
        INNER JOIN celulas c ON r.celula_id = c.id
        WHERE r.id = ?
    ");
    $stmt->execute([$reuniao_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Listar todas as reuniões com fotos (para galeria completa)
 */
function listar_todas_reunioes($celula_id = '', $mes = '', $limite = 500) {
    $db = getDB();
    
    // Query simplificada e compatível com SQLite e MySQL
    $sql = "
        SELECT r.*, c.nome as celula_nome
        FROM reunioes_celula r
        LEFT JOIN celulas c ON r.celula_id = c.id
        WHERE r.foto_url IS NOT NULL AND r.foto_url != ''
    ";
    
    $params = [];
    
    if ($celula_id) {
        $sql .= " AND r.celula_id = ?";
        $params[] = $celula_id;
    }
    
    if ($mes) {
        // Compatível com MySQL e SQLite
        if (DB_DRIVER === 'mysql') {
            $sql .= " AND DATE_FORMAT(r.data_reuniao, '%Y-%m') = ?";
        } else {
            $sql .= " AND strftime('%Y-%m', r.data_reuniao) = ?";
        }
        $params[] = $mes;
    }
    
    $limite = (int)$limite;
    $sql .= " ORDER BY r.data_reuniao DESC, r.criado_em DESC LIMIT $limite";
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $reunioes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Adicionar contagem de visitantes (os presentes já estão na tabela reunioes_celula)
        foreach ($reunioes as &$reuniao) {
            // Se já tem total_presentes, não precisa calcular
            if (empty($reuniao['total_presentes'])) {
                $reuniao['total_presentes'] = 0;
            }
            
            // Contar visitantes para a mesma data e célula
            $visitStmt = $db->prepare("SELECT COUNT(*) as total FROM visitantes WHERE celula_id = ? AND data_visita = ?");
            $visitStmt->execute([$reuniao['celula_id'], $reuniao['data_reuniao']]);
            $visitResult = $visitStmt->fetch(PDO::FETCH_ASSOC);
            $reuniao['total_visitantes'] = $visitResult['total'] ?? 0;
        }
        
        error_log("Galeria: " . count($reunioes) . " reuniões com fotos encontradas");
        return $reunioes;
    } catch (PDOException $e) {
        error_log("Erro ao listar reuniões: " . $e->getMessage());
        error_log("SQL: " . $sql);
        error_log("Params: " . json_encode($params));
        return [];
    }
}

/**
 * Obter total de visitantes em uma reunião
 */
function obter_total_visitantes($reuniao_id) {
    $db = getDB();
    try {
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM visitantes WHERE reuniao_id = ?");
        $stmt->execute([$reuniao_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    } catch (PDOException $e) {
        error_log("Erro ao contar visitantes: " . $e->getMessage());
        return 0;
    }
}

// Obter registros individuais de presença
function obter_registros_presenca($celula_id, $data_inicio, $data_fim) {
    $db = getDB();
    
    $sql = "
        SELECT 
            u.nome as membro_nome,
            p.data_presenca,
            p.presente
        FROM presencas p
        JOIN membros m ON p.membro_id = m.id
        JOIN usuarios u ON m.usuario_id = u.id
        WHERE p.celula_id = ? AND p.data_presenca BETWEEN ? AND ?
        ORDER BY p.data_presenca DESC, u.nome ASC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$celula_id, $data_inicio, $data_fim]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
