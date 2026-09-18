<?php
/**
 * Script de Migração do SQLite para MySQL
 * Realiza backup completo e migração segura de dados
 * 
 * Uso: php scripts/migrar_sqlite_para_mysql.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Carregar configurações
require_once __DIR__ . '/../config/database.php';

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  MIGRAÇÃO DE SQLITE PARA MYSQL - IEQ\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Passo 1: Fazer backup do SQLite
echo "[1/5] Fazendo backup do banco SQLite...\n";
$backup_dir = __DIR__ . '/../data/backups';
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

$backup_file = $backup_dir . '/ieq_backup_' . date('Y-m-d_H-i-s') . '.db';
if (!copy(DB_PATH, $backup_file)) {
    die("❌ Erro ao fazer backup do banco SQLite\n");
}
echo "✓ Backup criado: $backup_file\n\n";

// Passo 2: Conectar ao SQLite (origem)
echo "[2/5] Conectando ao banco SQLite...\n";
try {
    $sqlite_db = new PDO('sqlite:' . DB_PATH);
    $sqlite_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Conectado ao SQLite com sucesso\n\n";
} catch (PDOException $e) {
    die("❌ Erro ao conectar SQLite: " . $e->getMessage() . "\n");
}

// Passo 3: Conectar ao MySQL (destino)
echo "[3/5] Conectando ao banco MySQL...\n";
try {
    // Conectar sem o banco primeiro para criar o schema
    $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', DB_HOST, DB_PORT);
    $mysql_db = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    // Criar banco se não existir
    $mysql_db->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Banco de dados MySQL criado/verificado\n";
    
    // Conectar ao banco específico
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
    $mysql_db = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "✓ Conectado ao MySQL com sucesso\n\n";
} catch (PDOException $e) {
    die("❌ Erro ao conectar MySQL: " . $e->getMessage() . "\n");
}

// Passo 4: Criar tabelas no MySQL
echo "[4/5] Criando estrutura de tabelas no MySQL...\n";
try {
    // Desabilitar verificação de chaves estrangeiras temporariamente
    $mysql_db->exec("SET FOREIGN_KEY_CHECKS=0");
    
    // Criar tabelas (usando a função do database.php)
    $temp_driver = 'mysql';
    
    // Usuários
    $mysql_db->exec("CREATE TABLE IF NOT EXISTS usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome TEXT NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        senha TEXT,
        telefone VARCHAR(50),
        foto_url TEXT,
        google_id VARCHAR(255),
        funcao VARCHAR(50) DEFAULT 'membro',
        status_aprovacao VARCHAR(50) DEFAULT 'aprovado',
        permissoes JSON,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Igrejas
    $mysql_db->exec("CREATE TABLE IF NOT EXISTS igrejas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome TEXT NOT NULL,
        endereco TEXT,
        bairro VARCHAR(150),
        cidade VARCHAR(150),
        telefone VARCHAR(50),
        email VARCHAR(255),
        pastor_presidente_id INT,
        pastor_auxiliar_id INT,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (pastor_presidente_id) REFERENCES usuarios(id),
        FOREIGN KEY (pastor_auxiliar_id) REFERENCES usuarios(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Células
    $mysql_db->exec("CREATE TABLE IF NOT EXISTS celulas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome TEXT NOT NULL,
        igreja_id INT,
        localizacao TEXT,
        latitude DOUBLE,
        longitude DOUBLE,
        lider_id INT,
        lider_id_2 INT,
        lider_treinamento_id INT,
        supervisor_id INT,
        tipo VARCHAR(50) DEFAULT 'celula',
        dia_semana VARCHAR(50),
        hora VARCHAR(20),
        endereco TEXT,
        bairro VARCHAR(150),
        cidade VARCHAR(150),
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (igreja_id) REFERENCES igrejas(id),
        FOREIGN KEY (lider_id) REFERENCES usuarios(id),
        FOREIGN KEY (lider_id_2) REFERENCES usuarios(id),
        FOREIGN KEY (lider_treinamento_id) REFERENCES usuarios(id),
        FOREIGN KEY (supervisor_id) REFERENCES usuarios(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Membros
    $mysql_db->exec("CREATE TABLE IF NOT EXISTS membros (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        celula_id INT,
        data_conversao DATE,
        data_batismo DATE,
        status VARCHAR(30) DEFAULT 'ativo',
        endereco TEXT,
        bairro VARCHAR(150),
        cidade VARCHAR(150),
        cep VARCHAR(20),
        data_nasc DATE,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
        FOREIGN KEY (celula_id) REFERENCES celulas(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Presença
    $mysql_db->exec("CREATE TABLE IF NOT EXISTS presencas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        membro_id INT NOT NULL,
        celula_id INT NOT NULL,
        data_presenca DATE NOT NULL,
        presente TINYINT(1) DEFAULT 1,
        visitante TINYINT(1) DEFAULT 0,
        reuniao_id INT,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (membro_id) REFERENCES membros(id),
        FOREIGN KEY (celula_id) REFERENCES celulas(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Reuniões de célula
    $mysql_db->exec("CREATE TABLE IF NOT EXISTS reunioes_celula (
        id INT AUTO_INCREMENT PRIMARY KEY,
        celula_id INT NOT NULL,
        data_reuniao DATE NOT NULL,
        foto_url TEXT,
        observacoes TEXT,
        total_presentes INT DEFAULT 0,
        total_visitantes INT DEFAULT 0,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (celula_id) REFERENCES celulas(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Visitantes
    $mysql_db->exec("CREATE TABLE IF NOT EXISTS visitantes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(255) NOT NULL,
        telefone VARCHAR(20),
        email VARCHAR(255),
        celula_id INT,
        data_visita DATE NOT NULL,
        status VARCHAR(30) DEFAULT 'primeira_visita',
        observacoes TEXT,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (celula_id) REFERENCES celulas(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Cursos
    $mysql_db->exec("CREATE TABLE IF NOT EXISTS cursos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome TEXT NOT NULL,
        descricao TEXT,
        professor_id INT,
        data_inicio DATE,
        data_fim DATE,
        localizacao TEXT,
        vagas INT,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (professor_id) REFERENCES usuarios(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Eventos
    $mysql_db->exec("CREATE TABLE IF NOT EXISTS eventos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome TEXT NOT NULL,
        descricao TEXT,
        data_evento DATETIME NOT NULL,
        localizacao TEXT,
        responsavel_id INT,
        tipo VARCHAR(50),
        vagas INT,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (responsavel_id) REFERENCES usuarios(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Cadastros pendentes
    $mysql_db->exec("CREATE TABLE IF NOT EXISTS cadastros_pendentes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        google_id VARCHAR(255),
        foto_url TEXT,
        status VARCHAR(50) DEFAULT 'pendente',
        motivo_rejeicao TEXT,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Configurações
    $mysql_db->exec("CREATE TABLE IF NOT EXISTS configuracoes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        chave VARCHAR(255) UNIQUE NOT NULL,
        valor TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Rate limiting
    $mysql_db->exec("CREATE TABLE IF NOT EXISTS rate_limiting (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip VARCHAR(45) NOT NULL,
        endpoint VARCHAR(255),
        tentativas INT DEFAULT 1,
        primeira_tentativa DATETIME DEFAULT CURRENT_TIMESTAMP,
        ultima_tentativa DATETIME DEFAULT CURRENT_TIMESTAMP,
        bloqueado INT DEFAULT 0,
        data_desbloqueio DATETIME,
        motivo VARCHAR(255),
        KEY idx_ip (ip),
        KEY idx_endpoint (endpoint)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Tabelas da Igreja
    $mysql_db->exec("CREATE TABLE IF NOT EXISTS eventos_igreja (
        id INT AUTO_INCREMENT PRIMARY KEY,
        igreja_id INT NOT NULL,
        nome VARCHAR(255) NOT NULL,
        descricao TEXT,
        data_evento DATETIME NOT NULL,
        localizacao VARCHAR(255),
        responsavel_id INT,
        tipo VARCHAR(50),
        vagas INT,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (igreja_id) REFERENCES igrejas(id) ON DELETE CASCADE,
        FOREIGN KEY (responsavel_id) REFERENCES usuarios(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    $mysql_db->exec("CREATE TABLE IF NOT EXISTS visitantes_igreja (
        id INT AUTO_INCREMENT PRIMARY KEY,
        igreja_id INT NOT NULL,
        nome VARCHAR(255) NOT NULL,
        telefone VARCHAR(50),
        email VARCHAR(255),
        data_visita DATE NOT NULL,
        obs TEXT,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (igreja_id) REFERENCES igrejas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    $mysql_db->exec("CREATE TABLE IF NOT EXISTS conversoes_igreja (
        id INT AUTO_INCREMENT PRIMARY KEY,
        igreja_id INT NOT NULL,
        nome VARCHAR(255) NOT NULL,
        data_conversao DATE NOT NULL,
        obs TEXT,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (igreja_id) REFERENCES igrejas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    $mysql_db->exec("CREATE TABLE IF NOT EXISTS reconciliacao_igreja (
        id INT AUTO_INCREMENT PRIMARY KEY,
        igreja_id INT NOT NULL,
        nome VARCHAR(255) NOT NULL,
        data_reconciliacao DATE NOT NULL,
        obs TEXT,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (igreja_id) REFERENCES igrejas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    $mysql_db->exec("CREATE TABLE IF NOT EXISTS batismos_igreja (
        id INT AUTO_INCREMENT PRIMARY KEY,
        igreja_id INT NOT NULL,
        nome VARCHAR(255) NOT NULL,
        data_batismo DATE NOT NULL,
        ministro VARCHAR(255),
        localizacao VARCHAR(255),
        obs TEXT,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (igreja_id) REFERENCES igrejas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    echo "✓ Tabelas criadas com sucesso no MySQL\n\n";
} catch (PDOException $e) {
    die("❌ Erro ao criar tabelas: " . $e->getMessage() . "\n");
}

// Passo 5: Migrar dados
echo "[5/5] Migrando dados do SQLite para MySQL...\n";
try {
    $tabelas = [
        'usuarios',
        'igrejas',
        'celulas',
        'membros',
        'presencas',
        'reunioes_celula',
        'visitantes',
        'cursos',
        'eventos',
        'cadastros_pendentes',
        'configuracoes',
        'rate_limiting',
        'eventos_igreja',
        'visitantes_igreja',
        'conversoes_igreja',
        'reconciliacao_igreja',
        'batismos_igreja'
    ];
    
    foreach ($tabelas as $tabela) {
        // Contar registros no SQLite
        $stmt_count = $sqlite_db->prepare("SELECT COUNT(*) as cnt FROM $tabela");
        $stmt_count->execute();
        $count = $stmt_count->fetch(PDO::FETCH_ASSOC)['cnt'];
        
        if ($count == 0) {
            echo "  └─ $tabela: 0 registros\n";
            continue;
        }
        
        // Buscar dados do SQLite
        $stmt_select = $sqlite_db->prepare("SELECT * FROM $tabela");
        $stmt_select->execute();
        $dados = $stmt_select->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($dados)) {
            echo "  └─ $tabela: 0 registros\n";
            continue;
        }
        
        // Preparar insert para MySQL
        $colunas = array_keys($dados[0]);
        $placeholders = implode(',', array_fill(0, count($colunas), '?'));
        $sql_insert = "INSERT INTO $tabela (" . implode(',', $colunas) . ") VALUES ($placeholders)";
        $stmt_insert = $mysql_db->prepare($sql_insert);
        
        // Inserir dados
        foreach ($dados as $row) {
            $stmt_insert->execute(array_values($row));
        }
        
        echo "  └─ $tabela: $count registros migrados ✓\n";
    }
    
    // Reabilitar verificação de chaves estrangeiras
    $mysql_db->exec("SET FOREIGN_KEY_CHECKS=1");
    
    echo "\n✓ Migração concluída com sucesso!\n\n";
} catch (PDOException $e) {
    die("❌ Erro ao migrar dados: " . $e->getMessage() . "\n");
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "  PRÓXIMOS PASSOS:\n";
echo "═══════════════════════════════════════════════════════════════\n\n";
echo "1. Abra o arquivo: config/database.php\n";
echo "2. Altere a linha 5 de:\n";
echo "   define('DB_DRIVER', getenv('DB_DRIVER') ?: 'sqlite');\n";
echo "   Para:\n";
echo "   define('DB_DRIVER', 'mysql');\n\n";
echo "3. Teste a aplicação para confirmar que tudo está funcionando\n";
echo "4. Se tudo funcionar, você pode deletar o arquivo:\n";
echo "   data/ieq.db\n\n";
echo "5. Faça backup da senha do MySQL e guarde em local seguro\n";
echo "   Host: " . DB_HOST . "\n";
echo "   Usuário: " . DB_USER . "\n";
echo "   Banco: " . DB_NAME . "\n\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

?>
