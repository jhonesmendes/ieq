<?php
session_start();
require_once 'config/database.php';

echo "=== DEBUG GALERIA ===\n\n";

// Info da sessão
echo "📍 SESSION:\n";
echo "  user_id: " . ($_SESSION['user_id'] ?? 'NÃO DEFINIDO') . "\n";
echo "  user_funcao: " . ($_SESSION['user_funcao'] ?? 'NÃO DEFINIDO') . "\n";
echo "  user_name: " . ($_SESSION['user_nome'] ?? 'NÃO DEFINIDO') . "\n";
echo "\n";

$user_id = $_SESSION['user_id'] ?? null;
$user_funcao = $_SESSION['user_funcao'] ?? null;

if (!$user_id) {
    die("❌ Não autenticado!\n");
}

$db = getDB();

// 1. Verificar dados do usuário
echo "👤 DADOS DO USUÁRIO (ID: $user_id):\n";
$stmt = $db->prepare("SELECT id, nome, email, funcao FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);
if ($usuario) {
    echo "  Nome: {$usuario['nome']}\n";
    echo "  Email: {$usuario['email']}\n";
    echo "  Funcao no DB: {$usuario['funcao']}\n";
} else {
    echo "  ❌ Usuário não encontrado no banco!\n";
}
echo "\n";

// 2. Se é líder, verificar células
if ($user_funcao === 'lider') {
    echo "🎯 VERIFICAÇÃO DE LÍDER:\n";
    $stmt = $db->prepare("SELECT id, nome, lider_id, lider_id_2 FROM celulas WHERE lider_id = ? OR lider_id_2 = ?");
    $stmt->execute([$user_id, $user_id]);
    $celulas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($celulas) {
        echo "  ✅ É líder de " . count($celulas) . " célula(s):\n";
        foreach ($celulas as $c) {
            echo "    - Célula #{$c['id']}: {$c['nome']} (lider_id: {$c['lider_id']}, lider_id_2: {$c['lider_id_2']})\n";
        }
    } else {
        echo "  ❌ NÃO é líder de nenhuma célula!\n";
    }
} else {
    echo "ℹ️ Usuário não é líder (funcao: $user_funcao)\n";
}
echo "\n";

// 3. Verificar reuniões com fotos
echo "📸 REUNIÕES COM FOTOS:\n";
$stmt = $db->prepare("SELECT r.id, r.celula_id, r.data_reuniao, r.foto_url, c.nome as celula_nome 
                     FROM reunioes_celula r 
                     LEFT JOIN celulas c ON r.celula_id = c.id 
                     WHERE r.foto_url IS NOT NULL AND r.foto_url != ''
                     ORDER BY r.data_reuniao DESC LIMIT 10");
$stmt->execute();
$reunioes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($reunioes) {
    echo "  Encontradas " . count($reunioes) . " reunião(ões) com fotos:\n";
    foreach ($reunioes as $r) {
        echo "    - Reunião #{$r['id']}: Célula #{$r['celula_id']} ({$r['celula_nome']}) em {$r['data_reuniao']}\n";
        echo "      Foto: {$r['foto_url']}\n";
    }
} else {
    echo "  ❌ Nenhuma reunião com foto encontrada!\n";
}
echo "\n";

// 4. Verificar uploads recentes
echo "📁 UPLOADS RECENTES:\n";
$uploads_dir = __DIR__ . '/uploads/ieq';
if (is_dir($uploads_dir)) {
    $files = array_slice(array_diff(scandir($uploads_dir), ['.', '..']), -10);
    if ($files) {
        echo "  Últimos arquivos em $uploads_dir:\n";
        foreach (array_reverse($files) as $file) {
            $path = $uploads_dir . '/' . $file;
            $size = filesize($path) / 1024 / 1024;
            $time = date('Y-m-d H:i:s', filemtime($path));
            echo "    - $file ($size MB, $time)\n";
        }
    } else {
        echo "  Pasta vazia!\n";
    }
} else {
    echo "  ❌ Pasta de uploads não existe: $uploads_dir\n";
}
echo "\n";

// 5. Se célula 13 existe, mostrar info
echo "🔍 CÉLULA #13:\n";
$stmt = $db->prepare("SELECT id, nome, lider_id, lider_id_2 FROM celulas WHERE id = 13");
$stmt->execute();
$celula13 = $stmt->fetch(PDO::FETCH_ASSOC);
if ($celula13) {
    echo "  Nome: {$celula13['nome']}\n";
    echo "  Líder 1: {$celula13['lider_id']}\n";
    echo "  Líder 2: {$celula13['lider_id_2']}\n";
    
    // Verificar nome do líder1
    if ($celula13['lider_id']) {
        $stmt = $db->prepare("SELECT nome FROM usuarios WHERE id = ?");
        $stmt->execute([$celula13['lider_id']]);
        $lider = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($lider) {
            echo "  Líder 1 nome: {$lider['nome']}\n";
        }
    }
} else {
    echo "  ❌ Célula 13 não existe!\n";
}
echo "\n";

// 6. Verificar reuniões da célula 13
echo "📊 REUNIÕES DA CÉLULA 13:\n";
$stmt = $db->prepare("SELECT id, data_reuniao, foto_url, total_presentes, total_visitantes 
                     FROM reunioes_celula 
                     WHERE celula_id = 13 
                     ORDER BY data_reuniao DESC LIMIT 5");
$stmt->execute();
$reunioes13 = $stmt->fetchAll(PDO::FETCH_ASSOC);
if ($reunioes13) {
    echo "  Encontradas " . count($reunioes13) . " reunião(ões):\n";
    foreach ($reunioes13 as $r) {
        echo "    - Reunião #{$r['id']}: {$r['data_reuniao']} | Foto: " . ($r['foto_url'] ? "✅ {$r['foto_url']}" : "❌ sem foto") . "\n";
        echo "      Presentes: {$r['total_presentes']}, Visitantes: {$r['total_visitantes']}\n";
    }
} else {
    echo "  ❌ Nenhuma reunião encontrada para célula 13!\n";
}
