<?php
require_once __DIR__ . '/database.php';

// Criar uma nova igreja
function criar_igreja($dados) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            INSERT INTO igrejas (nome, endereco, bairro, cidade, telefone, email, pastor_presidente_id, pastor_auxiliar_id)
            VALUES (:nome, :endereco, :bairro, :cidade, :telefone, :email, :pastor_presidente_id, :pastor_auxiliar_id)
        ");
        
        $stmt->execute([
            ':nome' => $dados['nome'],
            ':endereco' => $dados['endereco'] ?? null,
            ':bairro' => $dados['bairro'] ?? null,
            ':cidade' => $dados['cidade'] ?? null,
            ':telefone' => $dados['telefone'] ?? null,
            ':email' => $dados['email'] ?? null,
            ':pastor_presidente_id' => $dados['pastor_presidente_id'] ?? null,
            ':pastor_auxiliar_id' => $dados['pastor_auxiliar_id'] ?? null
        ]);
        
        return ['status' => 'sucesso', 'id' => $db->lastInsertId()];
    } catch (PDOException $e) {
        return ['status' => 'erro', 'mensagem' => $e->getMessage()];
    }
}

// Obter uma igreja específica
function obter_igreja($id) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT i.*,
                   pp.nome as pastor_presidente_nome,
                   pa.nome as pastor_auxiliar_nome
            FROM igrejas i
            LEFT JOIN usuarios pp ON i.pastor_presidente_id = pp.id
            LEFT JOIN usuarios pa ON i.pastor_auxiliar_id = pa.id
            WHERE i.id = :id
        ");
        
        $stmt->execute([':id' => $id]);
        $igreja = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($igreja) {
            return ['status' => 'sucesso', 'dados' => $igreja];
        } else {
            return ['status' => 'erro', 'mensagem' => 'Igreja não encontrada'];
        }
    } catch (PDOException $e) {
        return ['status' => 'erro', 'mensagem' => $e->getMessage()];
    }
}

// Listar todas as igrejas
function listar_igrejas() {
    try {
        $db = getDB();
        
        $stmt = $db->query("
            SELECT i.*,
                   pp.nome as pastor_presidente_nome,
                   pa.nome as pastor_auxiliar_nome,
                   (SELECT COUNT(*) FROM celulas WHERE igreja_id = i.id) as total_celulas,
                   (SELECT COUNT(*) FROM eventos_igreja WHERE igreja_id = i.id) as total_eventos,
                   (SELECT COUNT(*) FROM visitantes_igreja WHERE igreja_id = i.id) as total_visitantes,
                   (SELECT COUNT(*) FROM conversoes_igreja WHERE igreja_id = i.id) as total_conversoes,
                   (SELECT COUNT(*) FROM reconciliacao_igreja WHERE igreja_id = i.id) as total_reconciliacao,
                   (SELECT COUNT(*) FROM batismos_igreja WHERE igreja_id = i.id) as total_batismos
            FROM igrejas i
            LEFT JOIN usuarios pp ON i.pastor_presidente_id = pp.id
            LEFT JOIN usuarios pa ON i.pastor_auxiliar_id = pa.id
            ORDER BY i.nome ASC
        ");
        
        $igrejas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return ['status' => 'sucesso', 'dados' => $igrejas];
    } catch (PDOException $e) {
        return ['status' => 'erro', 'mensagem' => $e->getMessage()];
    }
}

// Atualizar uma igreja
function atualizar_igreja($id, $dados) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            UPDATE igrejas 
            SET nome = :nome,
                endereco = :endereco,
                bairro = :bairro,
                cidade = :cidade,
                telefone = :telefone,
                email = :email,
                pastor_presidente_id = :pastor_presidente_id,
                pastor_auxiliar_id = :pastor_auxiliar_id
            WHERE id = :id
        ");
        
        $stmt->execute([
            ':id' => $id,
            ':nome' => $dados['nome'],
            ':endereco' => $dados['endereco'] ?? null,
            ':bairro' => $dados['bairro'] ?? null,
            ':cidade' => $dados['cidade'] ?? null,
            ':telefone' => $dados['telefone'] ?? null,
            ':email' => $dados['email'] ?? null,
            ':pastor_presidente_id' => $dados['pastor_presidente_id'] ?? null,
            ':pastor_auxiliar_id' => $dados['pastor_auxiliar_id'] ?? null
        ]);
        
        return ['status' => 'sucesso'];
    } catch (PDOException $e) {
        return ['status' => 'erro', 'mensagem' => $e->getMessage()];
    }
}

// Deletar uma igreja
function deletar_igreja($id) {
    try {
        $db = getDB();
        
        // Verificar se há células vinculadas
        $stmt = $db->prepare("SELECT COUNT(*) FROM celulas WHERE igreja_id = :id");
        $stmt->execute([':id' => $id]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            return ['status' => 'erro', 'mensagem' => 'Não é possível deletar uma igreja que possui células vinculadas'];
        }
        
        $stmt = $db->prepare("DELETE FROM igrejas WHERE id = :id");
        $stmt->execute([':id' => $id]);
        
        return ['status' => 'sucesso'];
    } catch (PDOException $e) {
        return ['status' => 'erro', 'mensagem' => $e->getMessage()];
    }
}

// Obter pastores disponíveis para seleção
function obter_pastores() {
    try {
        $db = getDB();
        
        $stmt = $db->query("
            SELECT id, nome, email
            FROM usuarios
            WHERE funcao IN ('lider', 'membro', 'gestor_igreja', 'pastor')
            ORDER BY nome ASC
        ");
        
        $pastores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return ['status' => 'sucesso', 'dados' => $pastores];
    } catch (PDOException $e) {
        return ['status' => 'erro', 'mensagem' => $e->getMessage()];
    }
}

// Descobre a igreja_id de um registro (evento/visitante/conversão/batismo/
// reconciliação) a partir do seu id — usado pra checar permissão antes de um
// gestor_igreja excluir algo (ver usuario_pode_gerenciar_igreja em
// config/permissoes.php).
function obter_igreja_id_do_registro($tabela, $id) {
    $tabelas_validas = ['eventos_igreja', 'visitantes_igreja', 'conversoes_igreja', 'batismos_igreja', 'reconciliacao_igreja'];
    if (!in_array($tabela, $tabelas_validas, true)) {
        return null;
    }
    $db = getDB();
    $stmt = $db->prepare("SELECT igreja_id FROM {$tabela} WHERE id = ?");
    $stmt->execute([$id]);
    $igreja_id = $stmt->fetchColumn();
    return $igreja_id !== false ? (int)$igreja_id : null;
}

// Obtém a igreja gerenciada por um usuário (pastor presidente ou auxiliar).
// Mesma lógica de obter_celulas_do_lider() em config/permissoes.php, mas pra
// nível de igreja: usada pra restringir o "perfil igreja" só aos dados da
// igreja dele.
function obter_igreja_do_gestor($user_id = null) {
    if ($user_id === null) {
        $user_id = $_SESSION['user_id'] ?? 0;
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM igrejas WHERE pastor_presidente_id = ? OR pastor_auxiliar_id = ? LIMIT 1");
    $stmt->execute([$user_id, $user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

// ============= EVENTOS DA IGREJA =============

function criar_evento_igreja($dados) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            INSERT INTO eventos_igreja (igreja_id, nome, descricao, data_evento, localizacao, responsavel_id, tipo, vagas)
            VALUES (:igreja_id, :nome, :descricao, :data_evento, :localizacao, :responsavel_id, :tipo, :vagas)
        ");
        
        $stmt->execute([
            ':igreja_id' => $dados['igreja_id'],
            ':nome' => $dados['nome'],
            ':descricao' => $dados['descricao'] ?? null,
            ':data_evento' => $dados['data_evento'],
            ':localizacao' => $dados['localizacao'] ?? null,
            ':responsavel_id' => $dados['responsavel_id'] ?? null,
            ':tipo' => $dados['tipo'] ?? null,
            ':vagas' => $dados['vagas'] ?? null
        ]);
        
        return ['status' => 'sucesso', 'id' => $db->lastInsertId()];
    } catch (PDOException $e) {
        return ['status' => 'erro', 'mensagem' => $e->getMessage()];
    }
}

function listar_eventos_igreja($igreja_id) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT ei.*,
                   u.nome as responsavel_nome
            FROM eventos_igreja ei
            LEFT JOIN usuarios u ON ei.responsavel_id = u.id
            WHERE ei.igreja_id = :igreja_id
            ORDER BY ei.data_evento DESC
        ");
        
        $stmt->execute([':igreja_id' => $igreja_id]);
        $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return ['status' => 'sucesso', 'dados' => $eventos];
    } catch (PDOException $e) {
        return ['status' => 'erro', 'mensagem' => $e->getMessage()];
    }
}

function deletar_evento_igreja($id) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("DELETE FROM eventos_igreja WHERE id = :id");
        $stmt->execute([':id' => $id]);
        
        return ['status' => 'sucesso'];
    } catch (PDOException $e) {
        return ['status' => 'erro', 'mensagem' => $e->getMessage()];
    }
}

// ============= VISITANTES DA IGREJA =============

function criar_visitante_igreja($dados) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            INSERT INTO visitantes_igreja (igreja_id, nome, telefone, email, data_visita, obs)
            VALUES (:igreja_id, :nome, :telefone, :email, :data_visita, :obs)
        ");
        
        $stmt->execute([
            ':igreja_id' => $dados['igreja_id'],
            ':nome' => $dados['nome'],
            ':telefone' => $dados['telefone'] ?? null,
            ':email' => $dados['email'] ?? null,
            ':data_visita' => $dados['data_visita'],
            ':obs' => $dados['obs'] ?? null
        ]);
        
        return ['status' => 'sucesso', 'id' => $db->lastInsertId()];
    } catch (PDOException $e) {
        return ['status' => 'erro', 'mensagem' => $e->getMessage()];
    }
}

function listar_visitantes_igreja($igreja_id) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT * FROM visitantes_igreja
            WHERE igreja_id = :igreja_id
            ORDER BY data_visita DESC, nome ASC
        ");
        
        $stmt->execute([':igreja_id' => $igreja_id]);
        $visitantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return ['status' => 'sucesso', 'dados' => $visitantes];
    } catch (PDOException $e) {
        return ['status' => 'erro', 'mensagem' => $e->getMessage()];
    }
}

function deletar_visitante_igreja($id) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("DELETE FROM visitantes_igreja WHERE id = :id");
        $stmt->execute([':id' => $id]);
        
        return ['status' => 'sucesso'];
    } catch (PDOException $e) {
        return ['status' => 'erro', 'mensagem' => $e->getMessage()];
    }
}

// ============= CONVERSÕES NA IGREJA =============

function criar_conversao_igreja($dados) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            INSERT INTO conversoes_igreja (igreja_id, nome, data_conversao, obs)
            VALUES (:igreja_id, :nome, :data_conversao, :obs)
        ");
        
        $stmt->execute([
            ':igreja_id' => $dados['igreja_id'],
            ':nome' => $dados['nome'],
            ':data_conversao' => $dados['data_conversao'],
            ':obs' => $dados['obs'] ?? null
        ]);
        
        return ['status' => 'sucesso', 'id' => $db->lastInsertId()];
    } catch (PDOException $e) {
        return ['status' => 'erro', 'mensagem' => $e->getMessage()];
    }
}

function listar_conversoes_igreja($igreja_id) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT * FROM conversoes_igreja
            WHERE igreja_id = :igreja_id
            ORDER BY data_conversao DESC, nome ASC
        ");
        
        $stmt->execute([':igreja_id' => $igreja_id]);
        $conversoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return ['status' => 'sucesso', 'dados' => $conversoes];
    } catch (PDOException $e) {
        return ['status' => 'erro', 'mensagem' => $e->getMessage()];
    }
}

function deletar_conversao_igreja($id) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("DELETE FROM conversoes_igreja WHERE id = :id");
        $stmt->execute([':id' => $id]);
        
        return ['status' => 'sucesso'];
    } catch (PDOException $e) {
        return ['status' => 'erro', 'mensagem' => $e->getMessage()];
    }
}

// ============= RECONCILIAÇÕES NA IGREJA =============

function criar_reconciliacao_igreja($dados) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            INSERT INTO reconciliacao_igreja (igreja_id, nome, data_reconciliacao, obs)
            VALUES (:igreja_id, :nome, :data_reconciliacao, :obs)
        ");
        
        $stmt->execute([
            ':igreja_id' => $dados['igreja_id'],
            ':nome' => $dados['nome'],
            ':data_reconciliacao' => $dados['data_reconciliacao'],
            ':obs' => $dados['obs'] ?? null
        ]);
        
        return ['status' => 'sucesso', 'id' => $db->lastInsertId()];
    } catch (PDOException $e) {
        return ['status' => 'erro', 'mensagem' => $e->getMessage()];
    }
}

function listar_reconciliacao_igreja($igreja_id) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT * FROM reconciliacao_igreja
            WHERE igreja_id = :igreja_id
            ORDER BY data_reconciliacao DESC, nome ASC
        ");
        
        $stmt->execute([':igreja_id' => $igreja_id]);
        $reconciliacao = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return ['status' => 'sucesso', 'dados' => $reconciliacao];
    } catch (PDOException $e) {
        return ['status' => 'erro', 'mensagem' => $e->getMessage()];
    }
}

function deletar_reconciliacao_igreja($id) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("DELETE FROM reconciliacao_igreja WHERE id = :id");
        $stmt->execute([':id' => $id]);
        
        return ['status' => 'sucesso'];
    } catch (PDOException $e) {
        return ['status' => 'erro', 'mensagem' => $e->getMessage()];
    }
}

// ============= BATISMOS NA IGREJA =============

function criar_batismo_igreja($dados) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            INSERT INTO batismos_igreja (igreja_id, nome, data_batismo, ministro, localizacao, obs)
            VALUES (:igreja_id, :nome, :data_batismo, :ministro, :localizacao, :obs)
        ");
        
        $stmt->execute([
            ':igreja_id' => $dados['igreja_id'],
            ':nome' => $dados['nome'],
            ':data_batismo' => $dados['data_batismo'],
            ':ministro' => $dados['ministro'] ?? null,
            ':localizacao' => $dados['localizacao'] ?? null,
            ':obs' => $dados['obs'] ?? null
        ]);
        
        return ['status' => 'sucesso', 'id' => $db->lastInsertId()];
    } catch (PDOException $e) {
        return ['status' => 'erro', 'mensagem' => $e->getMessage()];
    }
}

function listar_batismos_igreja($igreja_id) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT * FROM batismos_igreja
            WHERE igreja_id = :igreja_id
            ORDER BY data_batismo DESC, nome ASC
        ");
        
        $stmt->execute([':igreja_id' => $igreja_id]);
        $batismos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return ['status' => 'sucesso', 'dados' => $batismos];
    } catch (PDOException $e) {
        return ['status' => 'erro', 'mensagem' => $e->getMessage()];
    }
}

function deletar_batismo_igreja($id) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("DELETE FROM batismos_igreja WHERE id = :id");
        $stmt->execute([':id' => $id]);
        
        return ['status' => 'sucesso'];
    } catch (PDOException $e) {
        return ['status' => 'erro', 'mensagem' => $e->getMessage()];
    }
}

// ============= RELATÓRIOS =============

function relatorio_igreja($igreja_id, $filtro_data = 'todos') {
    try {
        $db = getDB();
        
        // Obter informações da Igreja
        $stmt = $db->prepare("SELECT * FROM igrejas WHERE id = :id");
        $stmt->execute([':id' => $igreja_id]);
        $igreja = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$igreja) {
            return ['status' => 'erro', 'mensagem' => 'Igreja não encontrada'];
        }
        
        // Determinar filtro de data
        $data_inicio = null;
        $params = [':id' => $igreja_id];
        
        switch ($filtro_data) {
            case 'mes_atual':
                $data_inicio = date('Y-m-01');
                $params[':data_inicio'] = $data_inicio;
                break;
            case 'ultimos_30_dias':
                $data_inicio = date('Y-m-d', strtotime('-30 days'));
                $params[':data_inicio'] = $data_inicio;
                break;
            case 'ultimos_90_dias':
                $data_inicio = date('Y-m-d', strtotime('-90 days'));
                $params[':data_inicio'] = $data_inicio;
                break;
            default:
                $data_inicio = null;
        }
        
        // Visitantes (apenas com data válida)
        if ($data_inicio) {
            $stmt = $db->prepare("
                SELECT COUNT(*) as total 
                FROM visitantes_igreja 
                WHERE igreja_id = :id 
                  AND data_visita IS NOT NULL 
                  AND data_visita >= :data_inicio
            ");
            $stmt->execute($params);
        } else {
            $stmt = $db->prepare("
                SELECT COUNT(*) as total 
                FROM visitantes_igreja 
                WHERE igreja_id = :id 
                  AND data_visita IS NOT NULL
            ");
            $stmt->execute([':id' => $igreja_id]);
        }
        $total_visitantes = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        // Conversões (apenas com data válida)
        if ($data_inicio) {
            $stmt = $db->prepare("
                SELECT COUNT(*) as total 
                FROM conversoes_igreja 
                WHERE igreja_id = :id 
                  AND data_conversao IS NOT NULL 
                  AND data_conversao >= :data_inicio
            ");
            $stmt->execute($params);
        } else {
            $stmt = $db->prepare("
                SELECT COUNT(*) as total 
                FROM conversoes_igreja 
                WHERE igreja_id = :id 
                  AND data_conversao IS NOT NULL
            ");
            $stmt->execute([':id' => $igreja_id]);
        }
        $total_conversoes = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        // Reconciliações (apenas com data válida)
        if ($data_inicio) {
            $stmt = $db->prepare("
                SELECT COUNT(*) as total 
                FROM reconciliacao_igreja 
                WHERE igreja_id = :id 
                  AND data_reconciliacao IS NOT NULL 
                  AND data_reconciliacao >= :data_inicio
            ");
            $stmt->execute($params);
        } else {
            $stmt = $db->prepare("
                SELECT COUNT(*) as total 
                FROM reconciliacao_igreja 
                WHERE igreja_id = :id 
                  AND data_reconciliacao IS NOT NULL
            ");
            $stmt->execute([':id' => $igreja_id]);
        }
        $total_reconciliacao = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        // Batismos (apenas com data válida)
        if ($data_inicio) {
            $stmt = $db->prepare("
                SELECT COUNT(*) as total 
                FROM batismos_igreja 
                WHERE igreja_id = :id 
                  AND data_batismo IS NOT NULL 
                  AND data_batismo >= :data_inicio
            ");
            $stmt->execute($params);
        } else {
            $stmt = $db->prepare("
                SELECT COUNT(*) as total 
                FROM batismos_igreja 
                WHERE igreja_id = :id 
                  AND data_batismo IS NOT NULL
            ");
            $stmt->execute([':id' => $igreja_id]);
        }
        $total_batismos = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        return [
            'status' => 'sucesso',
            'dados' => [
                'igreja' => $igreja,
                'total_visitantes' => (int)$total_visitantes,
                'total_conversoes' => (int)$total_conversoes,
                'total_reconciliacao' => (int)$total_reconciliacao,
                'total_batismos' => (int)$total_batismos,
                'filtro_data' => $filtro_data,
                'data_inicio' => $data_inicio,
                'data_geracao' => date('Y-m-d H:i:s')
            ]
        ];
    } catch (PDOException $e) {
        return ['status' => 'erro', 'mensagem' => $e->getMessage()];
    }
}
