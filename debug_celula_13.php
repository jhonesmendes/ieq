<?php
session_start();
require_once 'config/database.php';

$db = getDB();

echo "=== ANÁLISE DETALHADA DE ATRIBUIÇÕES ===\n\n";

// Listar todos os líderes
echo "👥 USUÁRIOS COM FUNCAO 'LIDER':\n";
$stmt = $db->prepare("SELECT id, nome, email, funcao FROM usuarios WHERE funcao = 'lider' ORDER BY id");
$stmt->execute();
$lideres = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($lideres as $user) {
    echo "\n  👤 ID {$user['id']}: {$user['nome']} ({$user['email']})\n";
    
    // Verificar se é líder de alguma célula
    $stmt2 = $db->prepare("SELECT id, nome FROM celulas WHERE lider_id = ? OR lider_id_2 = ?");
    $stmt2->execute([$user['id'], $user['id']]);
    $celulas = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    if ($celulas) {
        echo "    ✅ Líder de: " . implode(', ', array_map(fn($c) => "{$c['nome']} (#{$c['id']})", $celulas)) . "\n";
    } else {
        echo "    ❌ NÃO é líder de nenhuma célula\n";
    }
}

echo "\n\n📋 CÉLULA #13 (CHARIS):\n";
$stmt = $db->prepare("SELECT id, nome, lider_id, lider_id_2 FROM celulas WHERE id = 13");
$stmt->execute();
$celula13 = $stmt->fetch(PDO::FETCH_ASSOC);

if ($celula13) {
    echo "  Nome: {$celula13['nome']}\n";
    echo "  Lider_id: {$celula13['lider_id']}\n";
    echo "  Lider_id_2: {$celula13['lider_id_2']}\n";
    
    if ($celula13['lider_id']) {
        $stmt = $db->prepare("SELECT nome FROM usuarios WHERE id = ?");
        $stmt->execute([$celula13['lider_id']]);
        $lider = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "  Líder 1 nome: {$lider['nome']}\n";
    }
}

echo "\n\n📸 REUNIÕES COM FOTOS DA CÉLULA 13:\n";
$stmt = $db->prepare("SELECT id, data_reuniao, foto_url, total_presentes, total_visitantes FROM reunioes_celula WHERE celula_id = 13 AND foto_url IS NOT NULL ORDER BY data_reuniao DESC");
$stmt->execute();
$reunioes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($reunioes) {
    echo "  Encontradas " . count($reunioes) . " reunião(ões) com fotos:\n";
    foreach ($reunioes as $r) {
        echo "    - #{$r['id']}: {$r['data_reuniao']} | Foto: {$r['foto_url']}\n";
    }
} else {
    echo "  ❌ Nenhuma reunião com foto\n";
}

echo "\n\n💾 TODAS AS REUNIÕES (COM OU SEM FOTO) DA CÉLULA 13:\n";
$stmt = $db->prepare("SELECT id, data_reuniao, foto_url FROM reunioes_celula WHERE celula_id = 13 ORDER BY data_reuniao DESC LIMIT 5");
$stmt->execute();
$todas = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($todas) {
    echo "  Encontradas " . count($todas) . " reunião(ões):\n";
    foreach ($todas as $r) {
        $tem_foto = $r['foto_url'] ? "✅ {$r['foto_url']}" : "❌";
        echo "    - #{$r['id']}: {$r['data_reuniao']} | Foto: $tem_foto\n";
    }
} else {
    echo "  ❌ Nenhuma reunião encontrada\n";
}

echo "\n\n🔍 SIMULANDO GALERIA COMPLETA:\n";

// Simular como admin
echo "  Como ADMIN:\n";
$resultado = listar_todas_reunioes('', '');
echo "    Retornar " . count($resultado) . " reuniões com fotos\n";

// Simular como líder (cada um)
foreach ($lideres as $user) {
    echo "  Como LIDER '{$user['nome']}':\n";
    
    // Verificar se encontra célula
    $stmt = $db->prepare("SELECT id FROM celulas WHERE lider_id = ? LIMIT 1");
    $stmt->execute([$user['id']]);
    $celula_do_lider = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($celula_do_lider) {
        $resultado = listar_todas_reunioes($celula_do_lider['id'], '');
        echo "    ✅ Célula encontrada: #{$celula_do_lider['id']}\n";
        echo "    Retornar " . count($resultado) . " reuniões com fotos\n";
    } else {
        echo "    ❌ Nenhuma célula encontrada para este líder\n";
    }
}

function listar_todas_reunioes($celula_id = '', $mes = '', $limite = 500) {
    global $db;
    
    // Construir query
    $where = "r.foto_url IS NOT NULL AND r.foto_url != ''";
    $params = [];
    
    if (!empty($celula_id)) {
        $where .= " AND r.celula_id = ?";
        $params[] = $celula_id;
    }
    
    if (!empty($mes)) {
        $where .= " AND strftime('%Y-%m', r.data_reuniao) = ?";
        $params[] = $mes;
    }
    
    $sql = "SELECT r.* FROM reunioes_celula r 
            WHERE $where 
            ORDER BY r.data_reuniao DESC 
            LIMIT $limite";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
