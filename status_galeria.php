<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    die("❌ Você precisa estar logado para ver este diagnóstico\n");
}

$user_id = $_SESSION['user_id'];
$user_funcao = $_SESSION['user_funcao'] ?? 'desconhecido';
$db = getDB();

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║         DIAGNÓSTICO GALERIA - STATUS ATUAL                    ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// 1. Dados do usuário
echo "👤 VOCÊ:\n";
$stmt = $db->prepare("SELECT id, nome, email, funcao FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if ($usuario) {
    echo "   Nome: {$usuario['nome']}\n";
    echo "   Email: {$usuario['email']}\n";
    echo "   Função: {$usuario['funcao']}\n";
    echo "   ID: {$usuario['id']}\n";
} else {
    die("❌ Usuário não encontrado no banco!\n");
}

// 2. Suas células
echo "\n🏢 SUAS CÉLULAS:\n";
if ($user_funcao === 'lider') {
    $stmt = $db->prepare("SELECT id, nome FROM celulas WHERE lider_id = ? OR lider_id_2 = ? ORDER BY nome");
    $stmt->execute([$user_id, $user_id]);
    $celulas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($celulas) {
        foreach ($celulas as $c) {
            echo "   ✅ {$c['nome']} (#{$c['id']})\n";
        }
    } else {
        echo "   ❌ VOCÊ NÃO É LÍDER DE NENHUMA CÉLULA\n";
        echo "   ℹ️ Isso explica a mensagem 'Nenhuma célula encontrada para este líder'\n";
    }
} elseif ($user_funcao === 'membro') {
    $stmt = $db->prepare("SELECT DISTINCT c.id, c.nome FROM membros m JOIN celulas c ON m.celula_id = c.id WHERE m.usuario_id = ?");
    $stmt->execute([$user_id]);
    $celulas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($celulas) {
        foreach ($celulas as $c) {
            echo "   ✅ {$c['nome']} (#{$c['id']})\n";
        }
    } else {
        echo "   ❌ Você não é membro de nenhuma célula\n";
    }
} else {
    echo "   ℹ️ Como $user_funcao, você pode ver TODAS as células\n";
}

// 3. Fotos da célula 13
echo "\n📸 FOTOS NA CÉLULA 13:\n";
$stmt = $db->prepare("SELECT id, data_reuniao, foto_url FROM reunioes_celula WHERE celula_id = 13 AND foto_url IS NOT NULL ORDER BY data_reuniao DESC");
$stmt->execute();
$fotos = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($fotos) {
    foreach ($fotos as $f) {
        echo "   ✅ Reunião #{$f['id']} ({$f['data_reuniao']}): {$f['foto_url']}\n";
    }
} else {
    echo "   ❌ Nenhuma foto encontrada na célula 13\n";
}

// 4. Permissão de visualizar
echo "\n👁️ VOCÊ CAN VER FOTOS DA CÉLULA 13?\n";
if ($user_funcao === 'lider') {
    $stmt = $db->prepare("SELECT id FROM celulas WHERE (lider_id = ? OR lider_id_2 = ?) AND id = 13");
    $stmt->execute([$user_id, $user_id]);
    if ($stmt->fetch()) {
        echo "   ✅ SIM - Você é líder desta célula\n";
    } else {
        echo "   ❌ NÃO - Você não é líder desta célula\n";
    }
} else {
    echo "   ✅ SIM - Você é $user_funcao\n";
}

echo "\n";
