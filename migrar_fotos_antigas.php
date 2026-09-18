<?php
/**
 * Script de Migração - Reorganizar fotos antigas por célula
 * 
 * Executa apenas UMA VEZ para mover fotos da antiga estrutura
 * para a nova estrutura (uma pasta por célula)
 */

require_once 'config/database.php';

$upload_dir = __DIR__ . '/uploads/presenca/';
$db = getDB();

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║     MIGRAÇÃO DE FOTOS - REORGANIZAR POR CÉLULA                ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Verificar se há fotos no diretório raiz
$files = scandir($upload_dir);
$fotos_antigas = [];

foreach ($files as $file) {
    if ($file !== '.' && $file !== '..' && is_file($upload_dir . $file)) {
        // Verificar se é foto (começa com celula_)
        if (strpos($file, 'celula_') === 0) {
            $fotos_antigas[] = $file;
        }
    }
}

if (empty($fotos_antigas)) {
    echo "✅ Nenhuma foto antiga encontrada para migrar\n";
    echo "   A nova estrutura de pastas por célula já está em uso\n";
    exit;
}

echo "🔍 Encontradas " . count($fotos_antigas) . " foto(s) para migrar\n";
echo "ℹ️ Formato antigo: uploads/presenca/celula_13_20260320_1773980279.png\n";
echo "ℹ️ Formato novo: uploads/presenca/celula_13/20260320_1773980279.png\n\n";

$migradas = 0;
$erros = 0;

foreach ($fotos_antigas as $arquivo) {
    // Extrair celula_id do nome
    // Formato: celula_13_20260320_1773980279.png
    if (preg_match('/^celula_(\d+)_(.+)$/', $arquivo, $matches)) {
        $celula_id = $matches[1];
        $resto_nome = $matches[2];  // 20260320_1773980279.png
        
        // Criar pasta da célula
        $pasta_celula = $upload_dir . 'celula_' . $celula_id . '/';
        if (!is_dir($pasta_celula)) {
            if (!mkdir($pasta_celula, 0755, true)) {
                echo "❌ Erro ao criar pasta: $pasta_celula\n";
                $erros++;
                continue;
            }
        }
        
        // Mover arquivo
        $caminho_antigo = $upload_dir . $arquivo;
        $caminho_novo = $pasta_celula . $resto_nome;
        
        if (rename($caminho_antigo, $caminho_novo)) {
            echo "✅ Migrada: $arquivo → celula_$celula_id/$resto_nome\n";
            $migradas++;
            
            // Atualizar banco de dados
            $caminho_relativo_novo = 'uploads/presenca/celula_' . $celula_id . '/' . $resto_nome;
            $caminho_relativo_antigo = 'uploads/presenca/' . $arquivo;
            
            try {
                $stmt = $db->prepare("UPDATE reunioes_celula SET foto_url = ? WHERE foto_url = ?");
                $stmt->execute([$caminho_relativo_novo, $caminho_relativo_antigo]);
                echo "   ✅ Banco de dados atualizado\n";
            } catch (Exception $e) {
                echo "   ⚠️ Erro ao atualizar banco: " . $e->getMessage() . "\n";
            }
        } else {
            echo "❌ Erro ao mover: $arquivo\n";
            $erros++;
        }
    } else {
        echo "⚠️ Nome de arquivo não reconhecido: $arquivo\n";
    }
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                    RESULTADO DA MIGRAÇÃO                      ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "✅ Migradas: $migradas\n";
echo "❌ Erros: $erros\n";
echo "\n";

if ($migradas > 0) {
    echo "Estrutura de pastas agora:\n";
    echo "  /uploads/presenca/\n";
    echo "    ├── celula_13/\n";
    echo "    │   ├── 20260320_1773980279.png\n";
    echo "    │   └── 20260320_1773980280.png\n";
    echo "    ├── celula_14/\n";
    echo "    │   └── 20260320_1773980281.png\n";
    echo "    └── ...\n";
}
