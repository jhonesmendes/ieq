<?php
// Funções para dashboard hierárquico

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/permissoes.php';

/**
 * Retorna a célula do usuário (se for membro ou líder)
 */
function get_celula_usuario($user_id) {
    $db = getDB();
    
    // Verificar se é líder (principal, secundário ou em treinamento) de alguma célula
    $stmt = $db->prepare("SELECT * FROM celulas WHERE lider_id = ? OR lider_id_2 = ? OR lider_treinamento_id = ? LIMIT 1");
    $stmt->execute([$user_id, $user_id, $user_id]);
    $celula = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($celula) {
        return $celula;
    }
    
    // Verificar se é membro de alguma célula
    $stmt = $db->prepare("
        SELECT c.* 
        FROM celulas c
        INNER JOIN membros m ON m.celula_id = c.id
        WHERE m.usuario_id = ?
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Retorna todas as células que o usuário pode visualizar conforme hierarquia
 */
function get_celulas_visiveis($user_id, $funcao) {
    $db = getDB();
    
    switch ($funcao) {
        case 'admin':
        case 'pastor':
            // Pastor e admin veem todas as células
            $stmt = $db->query("SELECT * FROM celulas ORDER BY nome");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        case 'supervisor':
            // Supervisor vê células que supervisiona
            $stmt = $db->prepare("SELECT * FROM celulas WHERE supervisor_id = ? ORDER BY nome");
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        case 'lider':
        case 'lider_treinamento':
            // Líder e Líder em Treinamento veem suas células
            $stmt = $db->prepare("SELECT * FROM celulas WHERE lider_id = ? OR lider_id_2 = ? OR lider_treinamento_id = ? ORDER BY nome");
            $stmt->execute([$user_id, $user_id, $user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        case 'membro':
            // Membro vê apenas sua célula
            $celula = get_celula_usuario($user_id);
            return $celula ? [$celula] : [];
            
        default:
            return [];
    }
}

/**
 * Retorna total de membros que o usuário pode visualizar
 */
function get_total_membros_visiveis($user_id, $funcao) {
    $db = getDB();
    
    switch ($funcao) {
        case 'admin':
        case 'pastor':
            // Ver todos os membros
            $stmt = $db->query("SELECT COUNT(*) FROM membros WHERE status = 'ativo'");
            return $stmt->fetchColumn();
            
        case 'supervisor':
            // Ver membros das células que supervisiona
            $stmt = $db->prepare("
                SELECT COUNT(DISTINCT m.id) 
                FROM membros m
                INNER JOIN celulas c ON m.celula_id = c.id
                WHERE c.supervisor_id = ? AND m.status = 'ativo'
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetchColumn();
            
        case 'lider':
        case 'lider_treinamento':
            // Ver membros das suas células
            $stmt = $db->prepare("
                SELECT COUNT(*) 
                FROM membros m
                INNER JOIN celulas c ON m.celula_id = c.id
                WHERE (c.lider_id = ? OR c.lider_id_2 = ? OR c.lider_treinamento_id = ?) AND m.status = 'ativo'
            ");
            $stmt->execute([$user_id, $user_id, $user_id]);
            return $stmt->fetchColumn();
            
        case 'membro':
            // Membros não veem estatísticas de outros membros
            return 0;
            
        default:
            return 0;
    }
}

/**
 * Retorna total de células visíveis
 */
function get_total_celulas_visiveis($user_id, $funcao) {
    return count(get_celulas_visiveis($user_id, $funcao));
}

/**
 * Retorna total de novos convertidos visíveis
 */
function get_total_convertidos_visiveis($user_id, $funcao) {
    $db = getDB();
    $data_limite = date('Y-m-d', strtotime('-30 days')); // Últimos 30 dias
    
    switch ($funcao) {
        case 'admin':
        case 'pastor':
            // Ver todos os convertidos
            $stmt = $db->prepare("
                SELECT COUNT(*) 
                FROM membros 
                WHERE data_conversao >= ? AND status = 'ativo'
            ");
            $stmt->execute([$data_limite]);
            return $stmt->fetchColumn();
            
        case 'supervisor':
            // Ver convertidos das células que supervisiona
            $stmt = $db->prepare("
                SELECT COUNT(DISTINCT m.id) 
                FROM membros m
                INNER JOIN celulas c ON m.celula_id = c.id
                WHERE c.supervisor_id = ? 
                AND m.data_conversao >= ? 
                AND m.status = 'ativo'
            ");
            $stmt->execute([$user_id, $data_limite]);
            return $stmt->fetchColumn();
            
        case 'lider':
        case 'lider_treinamento':
            // Ver convertidos das suas células
            $stmt = $db->prepare("
                SELECT COUNT(*) 
                FROM membros m
                INNER JOIN celulas c ON m.celula_id = c.id
                WHERE (c.lider_id = ? OR c.lider_id_2 = ? OR c.lider_treinamento_id = ?)
                AND m.data_conversao >= ?
                AND m.status = 'ativo'
            ");
            $stmt->execute([$user_id, $user_id, $user_id, $data_limite]);
            return $stmt->fetchColumn();
            
        case 'membro':
            return 0;
            
        default:
            return 0;
    }
}

/**
 * Retorna eventos próximos visíveis para o usuário
 * (Quando implementar tabela de eventos)
 */
function get_eventos_proximos_visiveis($user_id, $funcao, $limite = 5) {
    // TODO: Implementar quando criar tabela de eventos
    // Por enquanto retorna array vazio
    return [];
}

/**
 * Retorna informações da célula formatadas para exibição
 */
function get_info_celula_card($celula) {
    if (!$celula) {
        return null;
    }
    
    $db = getDB();
    
    // Buscar nome do líder
    $lider_nome = 'Não definido';
    if ($celula['lider_id']) {
        $stmt = $db->prepare("SELECT nome FROM usuarios WHERE id = ?");
        $stmt->execute([$celula['lider_id']]);
        $lider_nome = $stmt->fetchColumn() ?: 'Não definido';
    }
    
    // Buscar total de membros
    $stmt = $db->prepare("SELECT COUNT(*) FROM membros WHERE celula_id = ? AND status = 'ativo'");
    $stmt->execute([$celula['id']]);
    $total_membros = $stmt->fetchColumn();
    
    // Calcular próxima reunião
    $proxima_reuniao = calcular_proxima_reuniao($celula['dia_semana'], $celula['hora']);
    
    return [
        'nome' => $celula['nome'],
        'lider' => $lider_nome,
        'dia_semana' => $celula['dia_semana'],
        'hora' => $celula['hora'],
        'endereco' => $celula['endereco'],
        'bairro' => $celula['bairro'],
        'cidade' => $celula['cidade'],
        'total_membros' => $total_membros,
        'proxima_reuniao' => $proxima_reuniao
    ];
}

/**
 * Calcula a data/hora da próxima reunião com base no dia da semana
 */
function calcular_proxima_reuniao($dia_semana, $hora) {
    if (!$dia_semana || !$hora) {
        return null;
    }
    
    $dias = [
        'domingo' => 'Sunday',
        'segunda-feira' => 'Monday',
        'segunda' => 'Monday',
        'terça-feira' => 'Tuesday',
        'terça' => 'Tuesday',
        'quarta-feira' => 'Wednesday',
        'quarta' => 'Wednesday',
        'quinta-feira' => 'Thursday',
        'quinta' => 'Thursday',
        'sexta-feira' => 'Friday',
        'sexta' => 'Friday',
        'sábado' => 'Saturday',
        'sabado' => 'Saturday'
    ];
    
    $dia_ingles = $dias[strtolower($dia_semana)] ?? null;
    
    if (!$dia_ingles) {
        return null;
    }
    
    $hoje = new DateTime();
    $proxima = new DateTime("next $dia_ingles $hora");
    
    // Se a próxima data for daqui a mais de 7 dias, é porque já passou hoje
    // Então pegamos a data de hoje se for o mesmo dia
    $intervalo = $hoje->diff($proxima);
    if ($intervalo->days > 7) {
        $proxima = new DateTime("$dia_ingles this week $hora");
        if ($proxima < $hoje) {
            $proxima = new DateTime("next $dia_ingles $hora");
        }
    }
    
    return $proxima;
}

/**
 * Retorna média de presença da célula (quando implementar controle de presença)
 */
function get_media_presenca_celula($celula_id, $ultimos_encontros = 4) {
    // TODO: Implementar quando tiver controle de presença completo
    return null;
}

/**
 * Estatísticas detalhadas da célula do líder
 */
function get_estatisticas_celula_lider($user_id) {
    try {
        $db = getDB();
        
        // Buscar célula do líder (principal, secundário ou em treinamento)
        $stmt = $db->prepare("SELECT * FROM celulas WHERE lider_id = ? OR lider_id_2 = ? OR lider_treinamento_id = ? LIMIT 1");
        $stmt->execute([$user_id, $user_id, $user_id]);
        $celula = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$celula) {
            return null;
        }
        
        $celula_id = $celula['id'];
        
        // Total de membros ativos
        $stmt = $db->prepare("SELECT COUNT(*) FROM membros WHERE celula_id = ? AND status = 'ativo'");
        $stmt->execute([$celula_id]);
        $total_membros = (int)$stmt->fetchColumn() ?? 0;
        
        // Novos membros (últimos 30 dias)
        $data_30_dias = date('Y-m-d', strtotime('-30 days'));
        if (DB_DRIVER === 'mysql') {
            $stmt = $db->prepare("SELECT COUNT(*) FROM membros WHERE celula_id = ? AND criado_em >= ?");
        } else {
            // SQLite - usar date() para comparação
            $stmt = $db->prepare("SELECT COUNT(*) FROM membros WHERE celula_id = ? AND date(criado_em) >= ?");
        }
        $stmt->execute([$celula_id, $data_30_dias]);
        $novos_membros = (int)$stmt->fetchColumn() ?? 0;
        
        // Novos convertidos (últimos 30 dias)
        if (DB_DRIVER === 'mysql') {
            $stmt = $db->prepare("SELECT COUNT(*) FROM membros WHERE celula_id = ? AND data_conversao >= ? AND status = 'ativo'");
        } else {
            // SQLite
            $stmt = $db->prepare("SELECT COUNT(*) FROM membros WHERE celula_id = ? AND date(data_conversao) >= ? AND status = 'ativo'");
        }
        $stmt->execute([$celula_id, $data_30_dias]);
        $novos_convertidos = (int)$stmt->fetchColumn() ?? 0;
        
        // Membros batizados
        $stmt = $db->prepare("SELECT COUNT(*) FROM membros WHERE celula_id = ? AND data_batismo IS NOT NULL AND data_batismo != '' AND status = 'ativo'");
        $stmt->execute([$celula_id]);
        $batizados = (int)$stmt->fetchColumn() ?? 0;
        
        // Presenças no último mês
        if (DB_DRIVER === 'mysql') {
            $stmt = $db->prepare("
                SELECT COUNT(DISTINCT data_presenca) as total_reunioes,
                       AVG(total_presentes) as media_presentes
                FROM (
                    SELECT data_presenca, COUNT(*) as total_presentes
                    FROM presencas
                    WHERE celula_id = ? AND data_presenca >= ? AND presente = 1
                    GROUP BY data_presenca
                ) as reunioes
            ");
        } else {
            // SQLite - sintaxe diferente para subconsulttas
            $stmt = $db->prepare("
                SELECT COUNT(DISTINCT data_presenca) as total_reunioes,
                       AVG(total_presentes) as media_presentes
                FROM (
                    SELECT data_presenca, COUNT(*) as total_presentes
                    FROM presencas
                    WHERE celula_id = ? AND date(data_presenca) >= ? AND presente = 1
                    GROUP BY data_presenca
                )
            ");
        }
        $stmt->execute([$celula_id, $data_30_dias]);
        $presenca_stats = $stmt->fetch(PDO::FETCH_ASSOC) ?? [];
        
        // Aniversariantes do mês
        $mes_atual = date('m');
        if (DB_DRIVER === 'mysql') {
            $stmt = $db->prepare("
                SELECT COUNT(*) 
                FROM membros 
                WHERE celula_id = ? 
                AND status = 'ativo'
                AND data_nasc IS NOT NULL
                AND data_nasc != ''
                AND MONTH(data_nasc) = ?
            ");
        } else {
            // SQLite
            $stmt = $db->prepare("
                SELECT COUNT(*) 
                FROM membros 
                WHERE celula_id = ? 
                AND status = 'ativo'
                AND data_nasc IS NOT NULL
                AND data_nasc != ''
                AND strftime('%m', data_nasc) = ?
            ");
        }
        $stmt->execute([$celula_id, str_pad($mes_atual, 2, '0', STR_PAD_LEFT)]);
        $aniversariantes = (int)$stmt->fetchColumn() ?? 0;
        
        return [
            'celula' => $celula,
            'total_membros' => $total_membros,
            'novos_membros' => $novos_membros,
            'novos_convertidos' => $novos_convertidos,
            'batizados' => $batizados,
            'total_reunioes_mes' => (int)($presenca_stats['total_reunioes'] ?? 0),
            'media_presentes' => round($presenca_stats['media_presentes'] ?? 0, 1),
            'aniversariantes_mes' => $aniversariantes,
            'crescimento_percentual' => $total_membros > 0 ? round(($novos_membros / $total_membros) * 100, 1) : 0
        ];
    } catch (Exception $e) {
        error_log("Erro em get_estatisticas_celula_lider: " . $e->getMessage());
        // Retornar array vazio ao invés de null para evitar erros em display
        return [
            'celula' => null,
            'total_membros' => 0,
            'novos_membros' => 0,
            'novos_convertidos' => 0,
            'batizados' => 0,
            'total_reunioes_mes' => 0,
            'media_presentes' => 0,
            'aniversariantes_mes' => 0,
            'crescimento_percentual' => 0,
            'erro' => true
        ];
    }
}

/**
 * Lista os últimos membros adicionados à célula do líder
 */
function get_ultimos_membros_celula($user_id, $limite = 5) {
    try {
        $db = getDB();
        $limite = (int)$limite;
        
        if (DB_DRIVER === 'mysql') {
            $sql = "
                SELECT m.*, u.nome, u.email, u.telefone, u.foto_url
                FROM membros m
                LEFT JOIN usuarios u ON m.usuario_id = u.id
                LEFT JOIN celulas c ON m.celula_id = c.id
                WHERE (c.lider_id = ? OR c.lider_id_2 = ? OR c.lider_treinamento_id = ?)
                ORDER BY m.criado_em DESC
                LIMIT $limite
            ";
        } else {
            // SQLite - usar DATE() para comparação
            $sql = "
                SELECT m.*, u.nome, u.email, u.telefone, u.foto_url
                FROM membros m
                LEFT JOIN usuarios u ON m.usuario_id = u.id
                LEFT JOIN celulas c ON m.celula_id = c.id
                WHERE (c.lider_id = ? OR c.lider_id_2 = ? OR c.lider_treinamento_id = ?)
                ORDER BY date(m.criado_em) DESC
                LIMIT $limite
            ";
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$user_id, $user_id, $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
    } catch (Exception $e) {
        error_log("Erro em get_ultimos_membros_celula: " . $e->getMessage());
        return [];
    }
}

/**
 * Lista aniversariantes do mês da célula do líder
 */
function get_aniversariantes_mes_celula($user_id) {
    try {
        $db = getDB();
        $mes_atual = date('m');
        $mes_padrao = str_pad($mes_atual, 2, '0', STR_PAD_LEFT);
        
        if (DB_DRIVER === 'mysql') {
            $stmt = $db->prepare("
                SELECT m.*, u.nome, u.telefone, u.foto_url,
                       DAY(m.data_nasc) as dia_aniversario,
                       MONTH(m.data_nasc) as mes_aniversario
                FROM membros m
                LEFT JOIN usuarios u ON m.usuario_id = u.id
                LEFT JOIN celulas c ON m.celula_id = c.id
                WHERE (c.lider_id = ? OR c.lider_id_2 = ? OR c.lider_treinamento_id = ?)
                AND m.status = 'ativo'
                AND m.data_nasc IS NOT NULL
                AND m.data_nasc != ''
                AND MONTH(m.data_nasc) = ?
                ORDER BY DAY(m.data_nasc)
            ");
        } else {
            // SQLite
            $stmt = $db->prepare("
                SELECT m.*, u.nome, u.telefone, u.foto_url,
                       CAST(strftime('%d', m.data_nasc) AS INTEGER) as dia_aniversario,
                       CAST(strftime('%m', m.data_nasc) AS INTEGER) as mes_aniversario
                FROM membros m
                LEFT JOIN usuarios u ON m.usuario_id = u.id
                LEFT JOIN celulas c ON m.celula_id = c.id
                WHERE (c.lider_id = ? OR c.lider_id_2 = ? OR c.lider_treinamento_id = ?)
                AND m.status = 'ativo'
                AND m.data_nasc IS NOT NULL
                AND m.data_nasc != ''
                AND strftime('%m', m.data_nasc) = ?
                ORDER BY strftime('%d', m.data_nasc)
            ");
        }
        $stmt->execute([$user_id, $user_id, $user_id, $mes_padrao]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
    } catch (Exception $e) {
        error_log("Erro em get_aniversariantes_mes_celula: " . $e->getMessage());
        return [];
    }
}
