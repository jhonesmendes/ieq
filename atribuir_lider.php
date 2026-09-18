<?php
session_start();
require_once 'config/database.php';

// Se usuário não está autenticado, redirecionar
if (!isset($_SESSION['user_id'])) {
    echo "❌ Você precisa estar logado para executar esta ação!\n";
    exit;
}

$user_id = $_SESSION['user_id'];
$user_nome = $_SESSION['user_nome'] ?? 'Usuário';
$user_funcao = $_SESSION['user_funcao'] ?? 'membro';

echo "=== ATRIBUIR LÍDER A CÉLULA ===\n\n";
echo "👤 Usuário logado: $user_id - $user_nome ($user_funcao)\n";
echo "📍 Célula alvo: #13 (Charis)\n\n";

$db = getDB();

// Verificar célula 13
$stmt = $db->prepare("SELECT id, nome, lider_id, lider_id_2 FROM celulas WHERE id = 13");
$stmt->execute();
$celula = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$celula) {
    echo "❌ Célula 13 não existe!\n";
    exit;
}

echo "Célula antes:\n";
echo "  Nome: {$celula['nome']}\n";
echo "  Líder 1 (lider_id): {$celula['lider_id']}\n";
echo "  Líder 2 (lider_id_2): {$celula['lider_id_2']}\n\n";

// Atribuir como líder principal (se vazio) ou líder secundário
if (empty($celula['lider_id'])) {
    echo "🔧 Atribuindo como LÍDER PRINCIPAL...\n";
    $campo = 'lider_id';
} elseif (empty($celula['lider_id_2'])) {
    echo "🔧 Atribuindo como LÍDER SECUNDÁRIO...\n";
    $campo = 'lider_id_2';
} else {
    echo "⚠️ Ambos os campos de líder estão preenchidos!\n";
    echo "Substituir?\n";
    echo "\n✏️ Qual campo deseja atualizar?\n";
    echo "  1 - Substituir lider_id (atual: {$celula['lider_id']})\n";
    echo "  2 - Substituir lider_id_2 (atual: {$celula['lider_id_2']})\n";
    echo "  -> Indique (via GET ?campo=1 ou ?campo=2)\n\n";
    
    $campo_manual = $_GET['campo'] ?? null;
    if ($campo_manual == '1') $campo = 'lider_id';
    elseif ($campo_manual == '2') $campo = 'lider_id_2';
    else {
        echo "Por favor, indique qual campo atualizar via GET: ?campo=1 ou ?campo=2\n";
        exit;
    }
}

// Atualizar
$stmt = $db->prepare("UPDATE celulas SET $campo = ? WHERE id = 13");
if ($stmt->execute([$user_id])) {
    echo "✅ Atribuição realizada com sucesso!\n\n";
    
    // Verificar resultado
    $stmt = $db->prepare("SELECT id, nome, lider_id, lider_id_2 FROM celulas WHERE id = 13");
    $stmt->execute();
    $celula_nova = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Célula depois:\n";
    echo "  Nome: {$celula_nova['nome']}\n";
    echo "  Líder 1 (lider_id): {$celula_nova['lider_id']}\n";
    echo "  Líder 2 (lider_id_2): {$celula_nova['lider_id_2']}\n\n";
    
    echo "✨ Agora você pode ver as fotos da célula 13 na galeria!\n";
} else {
    echo "❌ Erro ao atualizar célula!\n";
}
