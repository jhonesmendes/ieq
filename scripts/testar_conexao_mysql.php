<?php
/**
 * Script de Teste de Conexão MySQL
 * Valida se as credenciais estão corretas
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "  TESTE DE CONEXÃO MYSQL - IEQ\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Credenciais a testar
$hosts = [
    'Servidor Remoto Atual' => [
        'host' => '69.6.213.188',
        'port' => '3306',
        'user' => 'jhones09',
        'pass' => 'j',
        'db' => 'jhones09_ieq'
    ],
    'MySQL Local - Padrão XAMPP' => [
        'host' => 'localhost',
        'port' => '3306',
        'user' => 'root',
        'pass' => '',
        'db' => 'ieq'
    ],
    'MySQL Local - Alternative' => [
        'host' => '127.0.0.1',
        'port' => '3306',
        'user' => 'root',
        'pass' => '',
        'db' => 'ieq'
    ]
];

$resultados = [];

foreach ($hosts as $nome => $config) {
    echo "Testando: $nome\n";
    echo "  Host: {$config['host']}\n";
    echo "  Porta: {$config['port']}\n";
    echo "  Usuário: {$config['user']}\n";
    
    try {
        // Tentar conectar
        $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', 
                       $config['host'], 
                       $config['port']);
        
        $timeout_inicio = time();
        $conexao = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);
        $tempo_conexao = time() - $timeout_inicio;
        
        echo "  ✓ Conexão bem-sucedida! (Tempo: {$tempo_conexao}s)\n\n";
        
        $resultados[$nome] = [
            'status' => 'sucesso',
            'mensagem' => 'Conexão bem-sucedida',
            'config' => $config
        ];
        
    } catch (PDOException $e) {
        echo "  ✗ Erro: " . $e->getMessage() . "\n\n";
        
        $resultados[$nome] = [
            'status' => 'erro',
            'mensagem' => $e->getMessage(),
            'config' => $config
        ];
    }
}

// Resumo
echo "═══════════════════════════════════════════════════════════════\n";
echo "  RESULTADO DOS TESTES\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$conectados = array_filter($resultados, function($r) { return $r['status'] === 'sucesso'; });

if (empty($conectados)) {
    echo "❌ NENHUMA CONEXÃO FOI BEM-SUCEDIDA\n\n";
    echo "Opções:\n";
    echo "1. Se você tem acesso ao MySQL remoto (69.6.213.188):\n";
    echo "   - Verifique as credenciais com seu provedor\n";
    echo "   - Atualize em config/database.php\n\n";
    echo "2. Se você quer usar MySQL local (RECOMENDADO):\n";
    echo "   - Abra XAMPP Control Panel\n";
    echo "   - Clique 'Start' em MySQL\n";
    echo "   - Aguarde ficar verde (Running)\n";
    echo "   - Execute este script novamente\n\n";
} else {
    echo "✓ CONEXÕES DISPONÍVEIS:\n\n";
    foreach ($conectados as $nome => $resultado) {
        echo "  • $nome\n";
        echo "    Host: {$resultado['config']['host']}\n";
        echo "    Usuário: {$resultado['config']['user']}\n\n";
    }
    
    echo "PRÓXIMOS PASSOS:\n\n";
    echo "1. Escolha qual configuração usar\n";
    echo "2. Abra o arquivo: config/database.php\n";
    echo "3. Atualize as linhas 7-12 com a configuração escolhida:\n\n";
    
    foreach ($conectados as $nome => $resultado) {
        $cfg = $resultado['config'];
        echo "   // Para usar: $nome\n";
        echo "   define('DB_HOST', '{$cfg['host']}');\n";
        echo "   define('DB_PORT', '{$cfg['port']}');\n";
        echo "   define('DB_USER', '{$cfg['user']}');\n";
        echo "   define('DB_PASS', '{$cfg['pass']}');\n";
        echo "   define('DB_NAME', '{$cfg['db']}');\n\n";
    }
    
    echo "4. Execute o script de migração:\n";
    echo "   C:\\xampp02\\php\\php.exe scripts/migrar_sqlite_para_mysql.php\n\n";
}

echo "═══════════════════════════════════════════════════════════════\n\n";

?>
