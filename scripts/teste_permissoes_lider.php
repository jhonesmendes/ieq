<?php
/**
 * Script de Teste de Permissões para Líder de Célula
 * 
 * Este script valida que o sistema de permissões está funcionando corretamente
 * para o perfil de Líder, garantindo que líderes vejam apenas seus dados.
 * 
 * COMO USAR:
 * 1. Acesse via browser: http://localhost/ieq/scripts/teste_permissoes_lider.php
 * 2. Ou via terminal: php teste_permissoes_lider.php
 */

// Configuração
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/permissoes.php';
require_once __DIR__ . '/../config/celulas.php';
require_once __DIR__ . '/../config/membros.php';

// Inicializar banco
initDatabase();

// Cores para terminal (se executado via CLI)
$is_cli = php_sapi_name() === 'cli';
$GREEN = $is_cli ? "\033[0;32m" : '<span style="color: green;">';
$RED = $is_cli ? "\033[0;31m" : '<span style="color: red;">';
$YELLOW = $is_cli ? "\033[1;33m" : '<span style="color: orange;">';
$RESET = $is_cli ? "\033[0m" : '</span>';
$BOLD = $is_cli ? "\033[1m" : '<strong>';

if (!$is_cli) {
    echo '<html><head><meta charset="UTF-8"><title>Teste de Permissões</title></head><body style="font-family: monospace; padding: 20px; background: #1e1e1e; color: #ccc;">';
}

echo "\n{$BOLD}╔══════════════════════════════════════════════════════════╗\n";
echo "║  TESTE DE PERMISSÕES - LÍDER DE CÉLULA                   ║\n";
echo "╚══════════════════════════════════════════════════════════╝{$RESET}\n\n";

$total_testes = 0;
$testes_passaram = 0;
$testes_falharam = 0;

/**
 * Função helper para executar teste
 */
function executar_teste($nome, $funcao_teste) {
    global $total_testes, $testes_passaram, $testes_falharam, $GREEN, $RED, $RESET;
    
    $total_testes++;
    echo "Teste #{$total_testes}: {$nome}... ";
    
    try {
        $resultado = $funcao_teste();
        if ($resultado === true) {
            echo "{$GREEN}✓ PASSOU{$RESET}\n";
            $testes_passaram++;
            return true;
        } else {
            echo "{$RED}✗ FALHOU{$RESET}\n";
            if (is_string($resultado)) {
                echo "   Motivo: {$resultado}\n";
            }
            $testes_falharam++;
            return false;
        }
    } catch (Exception $e) {
        echo "{$RED}✗ ERRO{$RESET}\n";
        echo "   Exceção: " . $e->getMessage() . "\n";
        $testes_falharam++;
        return false;
    }
}

// ============ PREPARAR DADOS DE TESTE ============
echo "{$YELLOW}[PREPARAÇÃO] Criando dados de teste...{$RESET}\n\n";

$db = getDB();

// Criar usuário líder de teste
$lider_email = 'lider_teste_' . time() . '@teste.com';
$lider_id = registrar_usuario('Líder Teste', $lider_email, 'senha123', '11999999999', 'lider');

if (!$lider_id) {
    die("{$RED}ERRO: Não foi possível criar usuário líder de teste{$RESET}\n");
}

// Criar célula do líder
$stmt = $db->prepare("INSERT INTO celulas (nome, lider_id, tipo, dia_semana, hora) VALUES (?, ?, 'celula', 'Quinta', '19:30')");
$stmt->execute(['Célula do Líder Teste', $lider_id]);
$celula_lider_id = $db->lastInsertId();

// Criar outra célula (de outro líder)
$outro_lider_id = registrar_usuario('Outro Líder', 'outro_lider_' . time() . '@teste.com', 'senha123', '', 'lider');
$stmt = $db->prepare("INSERT INTO celulas (nome, lider_id, tipo) VALUES (?, ?, 'celula')");
$stmt->execute(['Célula de Outro Líder', $outro_lider_id]);
$outra_celula_id = $db->lastInsertId();

// Adicionar membros à célula do líder
$membro1_id = registrar_usuario('Membro 1 Líder', 'membro1_lider_' . time() . '@teste.com', 'senha123', '', 'membro');
$stmt = $db->prepare("INSERT INTO membros (usuario_id, celula_id, status) VALUES (?, ?, 'ativo')");
$stmt->execute([$membro1_id, $celula_lider_id]);

// Adicionar membro à outra célula
$membro2_id = registrar_usuario('Membro Outra Célula', 'membro2_' . time() . '@teste.com', 'senha123', '', 'membro');
$stmt = $db->prepare("INSERT INTO membros (usuario_id, celula_id, status) VALUES (?, ?, 'ativo')");
$stmt->execute([$membro2_id, $outra_celula_id]);

echo "{$GREEN}✓ Dados de teste criados com sucesso{$RESET}\n";
echo "   Líder ID: {$lider_id}\n";
echo "   Célula do Líder ID: {$celula_lider_id}\n";
echo "   Outra Célula ID: {$outra_celula_id}\n\n";

// Simular sessão do líder
session_start();
$_SESSION['user_id'] = $lider_id;
$_SESSION['user_funcao'] = 'lider';
$_SESSION['user_nome'] = 'Líder Teste';
$_SESSION['user_email'] = $lider_email;

// ============ TESTES DE FUNÇÕES HELPER ============
echo "{$BOLD}[SEÇÃO 1] Testando Funções Helper de Permissões{$RESET}\n\n";

executar_teste("is_lider_exclusivo() retorna true para líder", function() {
    return is_lider_exclusivo() === true;
});

executar_teste("obter_celulas_do_lider() retorna apenas células do líder", function() use ($celula_lider_id) {
    $celulas = obter_celulas_do_lider();
    return is_array($celulas) && count($celulas) === 1 && in_array($celula_lider_id, $celulas);
});

executar_teste("obter_celula_principal_lider() retorna célula correta", function() use ($celula_lider_id) {
    $celula_id = obter_celula_principal_lider();
    return $celula_id == $celula_lider_id;
});

// ============ TESTES DE FILTRO DE CÉLULAS ============
echo "\n{$BOLD}[SEÇÃO 2] Testando Filtros de Células{$RESET}\n\n";

executar_teste("listar_celulas() retorna apenas célula do líder", function() use ($celula_lider_id) {
    $celulas = listar_celulas();
    return count($celulas) === 1 && $celulas[0]['id'] == $celula_lider_id;
});

executar_teste("pode_editar_celula() permite editar própria célula", function() use ($celula_lider_id) {
    return pode_editar_celula($celula_lider_id) === true;
});

executar_teste("pode_editar_celula() NÃO permite editar outra célula", function() use ($outra_celula_id) {
    return pode_editar_celula($outra_celula_id) === false;
});

// ============ TESTES DE FILTRO DE MEMBROS ============
echo "\n{$BOLD}[SEÇÃO 3] Testando Filtros de Membros{$RESET}\n\n";

executar_teste("listar_membros() retorna apenas membros da célula do líder", function() use ($membro1_id) {
    $membros = listar_membros();
    if (count($membros) !== 1) {
        return "Esperado 1 membro, recebido " . count($membros);
    }
    return $membros[0]['usuario_id'] == $membro1_id;
});

executar_teste("listar_membros() NÃO retorna membros de outras células", function() use ($membro2_id) {
    $membros = listar_membros();
    foreach ($membros as $membro) {
        if ($membro['usuario_id'] == $membro2_id) {
            return "Membro de outra célula foi retornado";
        }
    }
    return true;
});

executar_teste("buscar_membros() filtra apenas membros da célula do líder", function() use ($membro1_id) {
    $membros = buscar_membros('Membro');
    if (empty($membros)) {
        return "Nenhum membro encontrado";
    }
    foreach ($membros as $membro) {
        if ($membro['usuario_id'] != $membro1_id) {
            return "Retornou membro que não é da célula do líder";
        }
    }
    return true;
});

// ============ TESTES DE PERMISSÕES ============
echo "\n{$BOLD}[SEÇÃO 4] Testando Restrições de Permissões{$RESET}\n\n";

executar_teste("is_supervisor() retorna false para líder", function() {
    return is_supervisor() === false;
});

executar_teste("is_admin() retorna false para líder", function() {
    return is_admin() === false;
});

executar_teste("is_pastor() retorna false para líder", function() {
    return is_pastor() === false;
});

// ============ TESTES DE DASHBOARD ============
echo "\n{$BOLD}[SEÇÃO 5] Testando Dashboard do Líder{$RESET}\n\n";

executar_teste("get_estatisticas_celula_lider() retorna estatísticas", function() use ($lider_id) {
    $stats = get_estatisticas_celula_lider($lider_id);
    return is_array($stats) && isset($stats['total_membros']) && $stats['total_membros'] >= 1;
});

executar_teste("get_ultimos_membros_celula() retorna membros da célula", function() use ($lider_id) {
    $membros = get_ultimos_membros_celula($lider_id, 5);
    return is_array($membros) && count($membros) >= 1;
});

// ============ LIMPEZA ============
echo "\n{$YELLOW}[LIMPEZA] Removendo dados de teste...{$RESET}\n";

try {
    $db->exec("DELETE FROM presencas WHERE celula_id IN ({$celula_lider_id}, {$outra_celula_id})");
    $db->exec("DELETE FROM membros WHERE celula_id IN ({$celula_lider_id}, {$outra_celula_id})");
    $db->exec("DELETE FROM celulas WHERE id IN ({$celula_lider_id}, {$outra_celula_id})");
    $db->exec("DELETE FROM usuarios WHERE id IN ({$lider_id}, {$outro_lider_id}, {$membro1_id}, {$membro2_id})");
    echo "{$GREEN}✓ Dados de teste removidos{$RESET}\n";
} catch (Exception $e) {
    echo "{$RED}⚠ Erro ao limpar dados: " . $e->getMessage() . "{$RESET}\n";
}

// ============ RESUMO ============
echo "\n{$BOLD}╔══════════════════════════════════════════════════════════╗\n";
echo "║  RESUMO DOS TESTES                                       ║\n";
echo "╚══════════════════════════════════════════════════════════╝{$RESET}\n\n";

$taxa_sucesso = $total_testes > 0 ? round(($testes_passaram / $total_testes) * 100, 1) : 0;
$cor_taxa = $taxa_sucesso == 100 ? $GREEN : ($taxa_sucesso >= 80 ? $YELLOW : $RED);

echo "Total de Testes: {$total_testes}\n";
echo "Passaram: {$GREEN}{$testes_passaram}{$RESET}\n";
echo "Falharam: {$RED}{$testes_falharam}{$RESET}\n";
echo "Taxa de Sucesso: {$cor_taxa}{$taxa_sucesso}%{$RESET}\n\n";

if ($testes_falharam === 0) {
    echo "{$GREEN}{$BOLD}🎉 TODOS OS TESTES PASSARAM! Sistema de permissões funcionando corretamente.{$RESET}\n\n";
} else {
    echo "{$RED}{$BOLD}⚠ ALGUNS TESTES FALHARAM! Verifique o sistema de permissões.{$RESET}\n\n";
}

// Restaurar sessão
session_destroy();

if (!$is_cli) {
    echo '</body></html>';
}
