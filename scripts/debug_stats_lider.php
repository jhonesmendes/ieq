<?php
/**
 * Script de Debug - Estatísticas do Líder
 * Acesse: http://localhost/ieq/scripts/debug_stats_lider.php
 */

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/dashboard.php';
require_once __DIR__ . '/../config/auth.php';

initDatabase();

// Simular login de líder
$db = getDB();

echo "<html><head><meta charset='UTF-8'><title>Debug Stats Líder</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#f5f5f5;}";
echo ".success{color:green;}.error{color:red;}.info{color:blue;}";
echo "table{border-collapse:collapse;margin:20px 0;}";
echo "th,td{border:1px solid #ddd;padding:8px;text-align:left;}";
echo "th{background:#667eea;color:white;}</style></head><body>";

echo "<h1>🔍 Debug - Estatísticas do Líder</h1>";

// Buscar um líder para teste
$stmt = $db->query("SELECT * FROM usuarios WHERE funcao = 'lider' LIMIT 1");
$lider = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lider) {
    echo "<p class='error'>❌ Nenhum líder encontrado no banco. Crie um líder primeiro.</p>";
    echo "</body></html>";
    exit;
}

echo "<h2 class='success'>✅ Líder encontrado: {$lider['nome']} (ID: {$lider['id']})</h2>";

// Simular sessão
$_SESSION['user_id'] = $lider['id'];
$_SESSION['user_funcao'] = 'lider';

// Buscar célula do líder
$stmt = $db->prepare("SELECT * FROM celulas WHERE lider_id = ?");
$stmt->execute([$lider['id']]);
$celula = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$celula) {
    echo "<p class='error'>❌ Este líder não tem célula associada</p>";
    echo "</body></html>";
    exit;
}

echo "<h3 class='info'>📍 Célula: {$celula['nome']} (ID: {$celula['id']})</h3>";

// Testar estatísticas
echo "<h2>📊 Testando get_estatisticas_celula_lider()</h2>";
$stats = get_estatisticas_celula_lider($lider['id']);

if ($stats) {
    echo "<table>";
    echo "<tr><th>Métrica</th><th>Valor</th></tr>";
    echo "<tr><td>Total Membros</td><td class='success'>{$stats['total_membros']}</td></tr>";
    echo "<tr><td>Novos Membros (30 dias)</td><td class='success'>{$stats['novos_membros']}</td></tr>";
    echo "<tr><td>Convertidos (30 dias)</td><td class='success'>{$stats['novos_convertidos']}</td></tr>";
    echo "<tr><td>Batizados</td><td class='success'>{$stats['batizados']}</td></tr>";
    echo "<tr><td>Reuniões no Mês</td><td class='success'>{$stats['total_reunioes_mes']}</td></tr>";
    echo "<tr><td>Média Presentes</td><td class='success'>{$stats['media_presentes']}</td></tr>";
    echo "<tr><td>Aniversariantes Mês</td><td class='success'>{$stats['aniversariantes_mes']}</td></tr>";
    echo "<tr><td>Crescimento %</td><td class='success'>{$stats['crescimento_percentual']}%</td></tr>";
    echo "</table>";
} else {
    echo "<p class='error'>❌ Função retornou NULL</p>";
}

// Testar aniversariantes
echo "<h2>🎂 Testando get_aniversariantes_mes_celula()</h2>";
$aniversariantes = get_aniversariantes_mes_celula($lider['id']);

if (!empty($aniversariantes)) {
    echo "<p class='success'>✅ Encontrados " . count($aniversariantes) . " aniversariante(s)</p>";
    echo "<table>";
    echo "<tr><th>Nome</th><th>Dia</th><th>Mês</th><th>Data Nasc</th></tr>";
    foreach ($aniversariantes as $aniv) {
        echo "<tr>";
        echo "<td>{$aniv['nome']}</td>";
        echo "<td>{$aniv['dia_aniversario']}</td>";
        echo "<td>{$aniv['mes_aniversario']}</td>";
        echo "<td>{$aniv['data_nasc']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='info'>ℹ️ Nenhum aniversariante este mês</p>";
}

// Listar todos os membros da célula
echo "<h2>👥 Membros da Célula</h2>";
$stmt = $db->prepare("
    SELECT m.*, u.nome, u.telefone
    FROM membros m
    JOIN usuarios u ON m.usuario_id = u.id
    WHERE m.celula_id = ?
");
$stmt->execute([$celula['id']]);
$membros = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($membros)) {
    echo "<table>";
    echo "<tr><th>Nome</th><th>Data Nasc</th><th>Data Conversão</th><th>Data Batismo</th><th>Status</th><th>Criado Em</th></tr>";
    foreach ($membros as $membro) {
        echo "<tr>";
        echo "<td>{$membro['nome']}</td>";
        echo "<td>" . ($membro['data_nasc'] ?: '<span class="error">NULL</span>') . "</td>";
        echo "<td>" . ($membro['data_conversao'] ?: '<span class="error">NULL</span>') . "</td>";
        echo "<td>" . ($membro['data_batismo'] ?: '<span class="error">NULL</span>') . "</td>";
        echo "<td>{$membro['status']}</td>";
        echo "<td>{$membro['criado_em']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='info'>ℹ️ Nenhum membro cadastrado nesta célula</p>";
}

// Query manual de aniversariantes
echo "<h2>🔍 Query Manual - Aniversariantes</h2>";
$mes_atual = date('m');
echo "<p>Mês atual: <strong>$mes_atual</strong> (" . date('F') . ")</p>";

$stmt = $db->prepare("
    SELECT m.*, u.nome,
           strftime('%m', m.data_nasc) as mes_data,
           strftime('%d', m.data_nasc) as dia_data
    FROM membros m
    JOIN usuarios u ON m.usuario_id = u.id
    WHERE m.celula_id = ?
    AND m.data_nasc IS NOT NULL
    AND m.data_nasc != ''
");
$stmt->execute([$celula['id']]);
$todos_com_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($todos_com_data)) {
    echo "<p>Membros com data de nascimento: " . count($todos_com_data) . "</p>";
    echo "<table>";
    echo "<tr><th>Nome</th><th>Data Nasc</th><th>Mês Extraído</th><th>Dia Extraído</th><th>Match?</th></tr>";
    foreach ($todos_com_data as $m) {
        $match = ($m['mes_data'] === $mes_atual) ? '<span class="success">✅ SIM</span>' : '<span class="error">❌ NÃO</span>';
        echo "<tr>";
        echo "<td>{$m['nome']}</td>";
        echo "<td>{$m['data_nasc']}</td>";
        echo "<td>{$m['mes_data']}</td>";
        echo "<td>{$m['dia_data']}</td>";
        echo "<td>{$match}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<hr><p><strong>✅ Debug concluído!</strong></p>";
echo "<p><a href='../index.php?page=dashboard'>← Voltar ao Dashboard</a></p>";
echo "</body></html>";
