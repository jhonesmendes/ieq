<?php
/**
 * migrate_sqlite_to_mysql.php
 *
 * Migra dados do banco SQLite (`data/ieq.db`) para MariaDB/MySQL.
 * Uso:
 *  php migrate_sqlite_to_mysql.php --sqlite=data/ieq.db --host=127.0.0.1 --port=3306 --db=ieq --user=root --pass=senha
 *
 * Ou defina variáveis de ambiente: DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, SQLITE_PATH
 */

function usage() {
    echo "Uso:\n";
    echo "  php migrate_sqlite_to_mysql.php [--sqlite=path] [--host=HOST] [--port=PORT] [--db=DBNAME] [--user=USER] [--pass=PASS]\n";
    exit(1);
}

// Ler argumentos simples
$options = [];
foreach ($argv as $i => $arg) {
    if ($i === 0) continue;
    if (strpos($arg, '--') === 0) {
        $parts = explode('=', substr($arg, 2), 2);
        $options[$parts[0]] = $parts[1] ?? '';
    }
}

$sqlitePath = $options['sqlite'] ?? getenv('SQLITE_PATH') ?: __DIR__ . '/../data/ieq.db';
$mysqlHost = $options['host'] ?? getenv('DB_HOST') ?: '127.0.0.1';
$mysqlPort = $options['port'] ?? getenv('DB_PORT') ?: '3306';
$mysqlDB   = $options['db'] ?? getenv('DB_NAME') ?: 'ieq';
$mysqlUser = $options['user'] ?? getenv('DB_USER') ?: 'root';
$mysqlPass = $options['pass'] ?? getenv('DB_PASS') ?: '';

if (!file_exists($sqlitePath)) {
    echo "Arquivo SQLite não encontrado: {$sqlitePath}\n";
    exit(1);
}

echo "SQLite: {$sqlitePath}\n";
echo "MySQL: {$mysqlUser}@{$mysqlHost}:{$mysqlPort}/{$mysqlDB}\n";

try {
    $sqlite = new PDO('sqlite:' . $sqlitePath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $sqlite->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Erro ao conectar ao SQLite: " . $e->getMessage() . "\n";
    exit(1);
}

try {
    // conectar sem especificar DB para poder criar a database
    $mysqlDsnNoDb = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $mysqlHost, $mysqlPort);
    $mysql = new PDO($mysqlDsnNoDb, $mysqlUser, $mysqlPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    // criar database se não existir
    $mysql->exec("CREATE DATABASE IF NOT EXISTS `" . str_replace('`','', $mysqlDB) . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    // conectar ao schema
    $mysql = null;
    $mysqlDsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $mysqlHost, $mysqlPort, $mysqlDB);
    $mysql = new PDO($mysqlDsn, $mysqlUser, $mysqlPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $mysql->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Erro ao conectar ao MySQL/MariaDB: " . $e->getMessage() . "\n";
    exit(1);
}

function createTablesMysql(PDO $db) {
    // Reutilizamos o esquema compatível com MySQL (InnoDB utf8mb4)
    $stmts = [
        // usuarios
        "CREATE TABLE IF NOT EXISTS usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome TEXT NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            senha TEXT NOT NULL,
            telefone VARCHAR(50),
            foto_url TEXT,
            funcao VARCHAR(50) DEFAULT 'membro',
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        // celulas
        "CREATE TABLE IF NOT EXISTS celulas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome TEXT NOT NULL,
            localizacao TEXT,
            latitude DOUBLE,
            longitude DOUBLE,
            lider_id INT,
            supervisor_id INT,
            tipo VARCHAR(50) DEFAULT 'celula',
            dia_semana VARCHAR(50),
            hora VARCHAR(20),
            endereco TEXT,
            bairro VARCHAR(150),
            cidade VARCHAR(150),
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (lider_id) REFERENCES usuarios(id),
            FOREIGN KEY (supervisor_id) REFERENCES usuarios(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        // membros
        "CREATE TABLE IF NOT EXISTS membros (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        // presencas
        "CREATE TABLE IF NOT EXISTS presencas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            membro_id INT NOT NULL,
            celula_id INT NOT NULL,
            data_presenca DATE NOT NULL,
            presente TINYINT(1) DEFAULT 1,
            visitante TINYINT(1) DEFAULT 0,
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (membro_id) REFERENCES membros(id),
            FOREIGN KEY (celula_id) REFERENCES celulas(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        // cursos
        "CREATE TABLE IF NOT EXISTS cursos (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        // eventos
        "CREATE TABLE IF NOT EXISTS eventos (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        // configuracoes
        "CREATE TABLE IF NOT EXISTS configuracoes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            chave VARCHAR(255) UNIQUE NOT NULL,
            valor TEXT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];

    foreach ($stmts as $sql) {
        $db->exec($sql);
    }
}

function copyTable(PDO $from, PDO $to, $table) {
    echo "Migrando tabela: {$table}...\n";
    $rows = $from->query("SELECT * FROM {$table}")->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) {
        echo "  0 linhas.\n";
        return 0;
    }

    // desabilitar checagens FK temporariamente
    $to->exec('SET FOREIGN_KEY_CHECKS=0');

    // Determinar colunas com a primeira linha
    $cols = array_keys($rows[0]);
    $colList = implode(', ', array_map(function($c){ return "`$c`"; }, $cols));
    $placeholders = implode(', ', array_map(function($c){ return '?'; }, $cols));
    $insertSql = "INSERT INTO {$table} ({$colList}) VALUES ({$placeholders})";
    $stmt = $to->prepare($insertSql);

    $count = 0;
    foreach ($rows as $r) {
        // Normalizar booleanos (sqlite stores 0/1)
        $values = array_values($r);
        try {
            $stmt->execute($values);
            $count++;
        } catch (PDOException $e) {
            echo "  Erro inserindo linha (ignorada): " . $e->getMessage() . "\n";
        }
    }

    // ajustar AUTO_INCREMENT
    $maxId = 0;
    foreach ($rows as $r) {
        if (isset($r['id']) && $r['id'] > $maxId) $maxId = $r['id'];
    }
    if ($maxId > 0) {
        try {
            $to->exec("ALTER TABLE {$table} AUTO_INCREMENT = " . ($maxId + 1));
        } catch (Exception $e) {
            // pode falhar em algumas engines ou versões
        }
    }

    $to->exec('SET FOREIGN_KEY_CHECKS=1');
    echo "  {$count} linhas migradas.\n";
    return $count;
}

try {
    createTablesMysql($mysql);

    // Ordem: usuarios -> celulas -> membros -> eventos -> cursos -> configuracoes -> presencas
    $tables = ['usuarios','celulas','membros','eventos','cursos','configuracoes','presencas'];

    $total = 0;
    foreach ($tables as $t) {
        // Se tabela não existir no sqlite, pular
        $exists = $sqlite->query("SELECT name FROM sqlite_master WHERE type='table' AND name='{$t}'")->fetch();
        if (!$exists) {
            echo "Tabela {$t} não encontrada no SQLite — pulando.\n";
            continue;
        }
        $total += copyTable($sqlite, $mysql, $t);
    }

    echo "Migração concluída. Total de linhas migradas: {$total}\n";
} catch (Exception $e) {
    echo "Erro durante migração: " . $e->getMessage() . "\n";
    exit(1);
}

exit(0);
