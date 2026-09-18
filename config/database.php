<?php
// ═══════════════════════════════════════════════════════════════════
// CONFIGURAÇÕES DE BANCO DE DADOS - IEQ
// ═══════════════════════════════════════════════════════════════════
// 
// Driver Active: define('DB_DRIVER', 'sqlite') PARA SQLite
//                define('DB_DRIVER', 'mysql')  PARA MySQL
//
// Para migrar de SQLite para MySQL, siga:
// 📖 Documents/MIGRAR_PASSO_A_PASSO.md
// ═══════════════════════════════════════════════════════════════════

// Carregar arquivo .env se não estiver em variáveis de ambiente
if (!getenv('DB_DRIVER')) {
    $env_file = __DIR__ . '/../.env';
    if (file_exists($env_file)) {
        $env_lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($env_lines as $line) {
            if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                if (!getenv($key)) {
                    putenv("$key=$value");
                }
            }
        }
    }
}

define('DB_DRIVER', getenv('DB_DRIVER') ?: 'mysql');   // ← ALTERADO PARA 'mysql'
define('DB_PATH', __DIR__ . '/../data/ieq.db');         // Caminho do SQLite (ignorado se usar MySQL)

// ═══════════════════════════════════════════════════════════════════
// CREDENCIAIS MYSQL - ATUALIZE ABAIXO
// ═══════════════════════════════════════════════════════════════════

// As credenciais reais ficam só no .env (nunca commitado no Git — ver
// .gitignore). Isso evita senha de banco de produção exposta no código-fonte.
// Configure o .env do servidor com DB_HOST/DB_PORT/DB_NAME/DB_USER/DB_PASS.
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'ieq');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');


// Criar pasta de dados se não existir (apenas para sqlite)
if (!is_dir(__DIR__ . '/../data')) {
    mkdir(__DIR__ . '/../data', 0755, true);
}

// Inicializar banco de dados (cria tabelas se necessário)
function initDatabase() {
    try {
        if (DB_DRIVER === 'mysql') {
            $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', DB_HOST, DB_PORT);
            $db = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            // Criar base se não existir
            $db->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $db->exec("USE `" . DB_NAME . "`");
        } else {
            $db = new PDO('sqlite:' . DB_PATH);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        // Tabela de usuários
        if (DB_DRIVER === 'mysql') {
            $db->exec("CREATE TABLE IF NOT EXISTS usuarios (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome TEXT NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                senha TEXT,
                telefone VARCHAR(50),
                foto_url TEXT,
                google_id VARCHAR(255),
                funcao VARCHAR(50) DEFAULT 'membro',
                status_aprovacao VARCHAR(50) DEFAULT 'aprovado',
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            $db->exec("CREATE TABLE IF NOT EXISTS usuarios (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nome TEXT NOT NULL,
                email TEXT UNIQUE NOT NULL,
                senha TEXT,
                telefone TEXT,
                foto_url TEXT,
                google_id TEXT,
                funcao TEXT DEFAULT 'membro',
                status_aprovacao TEXT DEFAULT 'aprovado',
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
        }

        // Tabela de rate limiting (proteção contra força bruta)
        if (DB_DRIVER === 'mysql') {
            $db->exec("CREATE TABLE IF NOT EXISTS rate_limiting (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip VARCHAR(45) NOT NULL,
                endpoint VARCHAR(100) NOT NULL,
                tentativas INT DEFAULT 1,
                primeira_tentativa DATETIME DEFAULT CURRENT_TIMESTAMP,
                ultima_tentativa DATETIME DEFAULT CURRENT_TIMESTAMP,
                bloqueado TINYINT DEFAULT 0,
                data_desbloqueio DATETIME,
                motivo VARCHAR(255),
                UNIQUE KEY unique_rate_limit (ip, endpoint),
                INDEX idx_ip (ip),
                INDEX idx_desbloqueio (data_desbloqueio)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            $db->exec("CREATE TABLE IF NOT EXISTS rate_limiting (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                ip TEXT NOT NULL,
                endpoint TEXT NOT NULL,
                tentativas INTEGER DEFAULT 1,
                primeira_tentativa DATETIME DEFAULT CURRENT_TIMESTAMP,
                ultima_tentativa DATETIME DEFAULT CURRENT_TIMESTAMP,
                bloqueado INTEGER DEFAULT 0,
                data_desbloqueio DATETIME,
                motivo TEXT,
                UNIQUE(ip, endpoint)
            )");
        }

        // Tabela de igrejas
        if (DB_DRIVER === 'mysql') {
            $db->exec("CREATE TABLE IF NOT EXISTS igrejas (
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
        } else {
            $db->exec("CREATE TABLE IF NOT EXISTS igrejas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nome TEXT NOT NULL,
                endereco TEXT,
                bairro TEXT,
                cidade TEXT,
                telefone TEXT,
                email TEXT,
                pastor_presidente_id INTEGER,
                pastor_auxiliar_id INTEGER,
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (pastor_presidente_id) REFERENCES usuarios(id),
                FOREIGN KEY (pastor_auxiliar_id) REFERENCES usuarios(id)
            )");
        }

        // Tabela de células
        if (DB_DRIVER === 'mysql') {
            $db->exec("CREATE TABLE IF NOT EXISTS celulas (
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
        } else {
            $db->exec("CREATE TABLE IF NOT EXISTS celulas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nome TEXT NOT NULL,
                igreja_id INTEGER,
                localizacao TEXT,
                latitude REAL,
                longitude REAL,
                lider_id INTEGER,
                lider_id_2 INTEGER,
                lider_treinamento_id INTEGER,
                supervisor_id INTEGER,
                tipo TEXT DEFAULT 'celula',
                dia_semana TEXT,
                hora TEXT,
                endereco TEXT,
                bairro TEXT,
                cidade TEXT,
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (igreja_id) REFERENCES igrejas(id),
                FOREIGN KEY (lider_id) REFERENCES usuarios(id),
                FOREIGN KEY (lider_id_2) REFERENCES usuarios(id),
                FOREIGN KEY (lider_treinamento_id) REFERENCES usuarios(id),
                FOREIGN KEY (supervisor_id) REFERENCES usuarios(id)
            )");
        }

        // Tabela de membros
        if (DB_DRIVER === 'mysql') {
            $db->exec("CREATE TABLE IF NOT EXISTS membros (
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
        } else {
            $db->exec("CREATE TABLE IF NOT EXISTS membros (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                usuario_id INTEGER NOT NULL,
                celula_id INTEGER,
                data_conversao DATE,
                data_batismo DATE,
                status TEXT DEFAULT 'ativo',
                endereco TEXT,
                bairro TEXT,
                cidade TEXT,
                cep TEXT,
                data_nasc DATE,
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
                FOREIGN KEY (celula_id) REFERENCES celulas(id)
            )");
        }

        // Tabela de presença
        if (DB_DRIVER === 'mysql') {
            $db->exec("CREATE TABLE IF NOT EXISTS presencas (
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
        } else {
            $db->exec("CREATE TABLE IF NOT EXISTS presencas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                membro_id INTEGER NOT NULL,
                celula_id INTEGER NOT NULL,
                data_presenca DATE NOT NULL,
                presente BOOLEAN DEFAULT 1,
                visitante BOOLEAN DEFAULT 0,
                reuniao_id INTEGER,
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (membro_id) REFERENCES membros(id),
                FOREIGN KEY (celula_id) REFERENCES celulas(id)
            )");
        }

        // Tabela de reuniões de célula (para fotos e registros)
        if (DB_DRIVER === 'mysql') {
            $db->exec("CREATE TABLE IF NOT EXISTS reunioes_celula (
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
        } else {
            $db->exec("CREATE TABLE IF NOT EXISTS reunioes_celula (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                celula_id INTEGER NOT NULL,
                data_reuniao DATE NOT NULL,
                foto_url TEXT,
                observacoes TEXT,
                total_presentes INTEGER DEFAULT 0,
                total_visitantes INTEGER DEFAULT 0,
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (celula_id) REFERENCES celulas(id)
            )");
        }

        // Tabela de visitantes
        if (DB_DRIVER === 'mysql') {
            $db->exec("CREATE TABLE IF NOT EXISTS visitantes (
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
        } else {
            $db->exec("CREATE TABLE IF NOT EXISTS visitantes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nome TEXT NOT NULL,
                telefone TEXT,
                email TEXT,
                celula_id INTEGER,
                data_visita DATE NOT NULL,
                status TEXT DEFAULT 'primeira_visita',
                observacoes TEXT,
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (celula_id) REFERENCES celulas(id)
            )");
        }

        // Tabela de cursos
        if (DB_DRIVER === 'mysql') {
            $db->exec("CREATE TABLE IF NOT EXISTS cursos (
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
        } else {
            $db->exec("CREATE TABLE IF NOT EXISTS cursos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nome TEXT NOT NULL,
                descricao TEXT,
                professor_id INTEGER,
                data_inicio DATE,
                data_fim DATE,
                localizacao TEXT,
                vagas INTEGER,
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (professor_id) REFERENCES usuarios(id)
            )");
        }

        // Tabela de eventos
        if (DB_DRIVER === 'mysql') {
            $db->exec("CREATE TABLE IF NOT EXISTS eventos (
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
        } else {
            $db->exec("CREATE TABLE IF NOT EXISTS eventos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nome TEXT NOT NULL,
                descricao TEXT,
                data_evento DATETIME NOT NULL,
                localizacao TEXT,
                responsavel_id INTEGER,
                tipo TEXT,
                vagas INTEGER,
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (responsavel_id) REFERENCES usuarios(id)
            )");
        }

        // Tabela de cadastros pendentes (Google)
        if (DB_DRIVER === 'mysql') {
            $db->exec("CREATE TABLE IF NOT EXISTS cadastros_pendentes (
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
        } else {
            $db->exec("CREATE TABLE IF NOT EXISTS cadastros_pendentes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nome TEXT NOT NULL,
                email TEXT UNIQUE NOT NULL,
                google_id TEXT,
                foto_url TEXT,
                status TEXT DEFAULT 'pendente',
                motivo_rejeicao TEXT,
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
        }

        // Tabela de configurações
        if (DB_DRIVER === 'mysql') {
            $db->exec("CREATE TABLE IF NOT EXISTS configuracoes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                chave VARCHAR(255) UNIQUE NOT NULL,
                valor TEXT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            $db->exec("CREATE TABLE IF NOT EXISTS configuracoes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                chave TEXT UNIQUE NOT NULL,
                valor TEXT
            )");
        }

        // Novas tabelas para dados da Igreja
        if (DB_DRIVER === 'mysql') {
            // Eventos da Igreja
            $db->exec("CREATE TABLE IF NOT EXISTS eventos_igreja (
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

            // Visitantes da Igreja
            $db->exec("CREATE TABLE IF NOT EXISTS visitantes_igreja (
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

            // Conversões na Igreja
            $db->exec("CREATE TABLE IF NOT EXISTS conversoes_igreja (
                id INT AUTO_INCREMENT PRIMARY KEY,
                igreja_id INT NOT NULL,
                nome VARCHAR(255) NOT NULL,
                data_conversao DATE NOT NULL,
                obs TEXT,
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (igreja_id) REFERENCES igrejas(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            // Reconciliações na Igreja
            $db->exec("CREATE TABLE IF NOT EXISTS reconciliacao_igreja (
                id INT AUTO_INCREMENT PRIMARY KEY,
                igreja_id INT NOT NULL,
                nome VARCHAR(255) NOT NULL,
                data_reconciliacao DATE NOT NULL,
                obs TEXT,
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (igreja_id) REFERENCES igrejas(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            // Batismos
            $db->exec("CREATE TABLE IF NOT EXISTS batismos_igreja (
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
        } else {
            // SQLite
            $db->exec("CREATE TABLE IF NOT EXISTS eventos_igreja (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                igreja_id INTEGER NOT NULL,
                nome TEXT NOT NULL,
                descricao TEXT,
                data_evento DATETIME NOT NULL,
                localizacao TEXT,
                responsavel_id INTEGER,
                tipo TEXT,
                vagas INTEGER,
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (igreja_id) REFERENCES igrejas(id) ON DELETE CASCADE,
                FOREIGN KEY (responsavel_id) REFERENCES usuarios(id)
            )");

            $db->exec("CREATE TABLE IF NOT EXISTS visitantes_igreja (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                igreja_id INTEGER NOT NULL,
                nome TEXT NOT NULL,
                telefone TEXT,
                email TEXT,
                data_visita DATE NOT NULL,
                obs TEXT,
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (igreja_id) REFERENCES igrejas(id) ON DELETE CASCADE
            )");

            $db->exec("CREATE TABLE IF NOT EXISTS conversoes_igreja (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                igreja_id INTEGER NOT NULL,
                nome TEXT NOT NULL,
                data_conversao DATE NOT NULL,
                obs TEXT,
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (igreja_id) REFERENCES igrejas(id) ON DELETE CASCADE
            )");

            $db->exec("CREATE TABLE IF NOT EXISTS reconciliacao_igreja (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                igreja_id INTEGER NOT NULL,
                nome TEXT NOT NULL,
                data_reconciliacao DATE NOT NULL,
                obs TEXT,
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (igreja_id) REFERENCES igrejas(id) ON DELETE CASCADE
            )");

            $db->exec("CREATE TABLE IF NOT EXISTS batismos_igreja (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                igreja_id INTEGER NOT NULL,
                nome TEXT NOT NULL,
                data_batismo DATE NOT NULL,
                ministro TEXT,
                localizacao TEXT,
                obs TEXT,
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (igreja_id) REFERENCES igrejas(id) ON DELETE CASCADE
            )");
        }

        // Tabela de Recuperação de Senha
        if (DB_DRIVER === 'mysql') {
            $db->exec("CREATE TABLE IF NOT EXISTS recuperacao_senha (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NOT NULL,
                token VARCHAR(100) UNIQUE NOT NULL,
                email VARCHAR(255) NOT NULL,
                usado TINYINT DEFAULT 0,
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                expira_em DATETIME NOT NULL,
                usado_em DATETIME,
                ip_request VARCHAR(45),
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
                INDEX idx_token (token),
                INDEX idx_expira (expira_em),
                INDEX idx_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            $db->exec("CREATE TABLE IF NOT EXISTS recuperacao_senha (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                usuario_id INTEGER NOT NULL,
                token TEXT UNIQUE NOT NULL,
                email TEXT NOT NULL,
                usado INTEGER DEFAULT 0,
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                expira_em DATETIME NOT NULL,
                usado_em DATETIME,
                ip_request TEXT,
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
            )");
        }

        // Verificar se precisa inserir dados de exemplo (banco vazio)
        $stmt = $db->prepare("SELECT COUNT(*) FROM usuarios");
        $stmt->execute();
        $count = $stmt->fetchColumn();
        
        if ($count == 0) {
            inserirDadosExemplo($db);
        }
        
        return $db;
    } catch (PDOException $e) {
        die('Erro ao conectar ao banco de dados: ' . $e->getMessage());
    }
}

// Conectar ao banco de dados
function getDB() {
    try {
        // Para mysql, conectamos diretamente ao schema
        if (DB_DRIVER === 'mysql') {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
            $db = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            return $db;
        }

        // SQLite
        garantirBancoDados();
        $db = new PDO('sqlite:' . DB_PATH);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch (PDOException $e) {
        die('Erro ao conectar ao banco de dados: ' . $e->getMessage());
    }
}

// Garantir que o banco existe e está configurado (apenas sqlite)
function garantirBancoDados() {
    if (DB_DRIVER === 'sqlite' && !file_exists(DB_PATH)) {
        initDatabase();
    }
}

// Função para inserir dados de exemplo
function inserirDadosExemplo($db = null) {
    if (!$db) $db = getDB();
    
    try {
        // Inserir usuários de exemplo
        $usuarios = [
            ['Pastor João Silva', 'pastor@ieq.com', password_hash('123456', PASSWORD_BCRYPT), '(11) 99999-1111', 'lider'],
            ['Maria Santos', 'maria@ieq.com', password_hash('123456', PASSWORD_BCRYPT), '(11) 98888-2222', 'lider'],
            ['Carlos Oliveira', 'carlos@ieq.com', password_hash('123456', PASSWORD_BCRYPT), '(11) 97777-3333', 'lider'],
            ['Ana Paula Costa', 'ana@ieq.com', password_hash('123456', PASSWORD_BCRYPT), '(11) 96666-4444', 'lider'],
            ['Pedro Mendes', 'pedro@ieq.com', password_hash('123456', PASSWORD_BCRYPT), '(11) 95555-5555', 'membro'],
            ['Lucas Silva', 'lucas@ieq.com', password_hash('123456', PASSWORD_BCRYPT), '(11) 94444-6666', 'membro'],
            ['Sofia Santos', 'sofia@ieq.com', password_hash('123456', PASSWORD_BCRYPT), '(11) 93333-7777', 'membro'],
            ['Rafael Costa', 'rafael@ieq.com', password_hash('123456', PASSWORD_BCRYPT), '(11) 92222-8888', 'membro']
        ];
        
        $stmt = $db->prepare("INSERT INTO usuarios (nome, email, senha, telefone, funcao) VALUES (?, ?, ?, ?, ?)");
        foreach ($usuarios as $usuario) {
            $stmt->execute($usuario);
        }
        
        // Inserir células de exemplo
        $celulas = [
            ['Célula Vida Nova', 'Pinheiros', -23.5629, -46.7006, 3, 1, 'celula', 'Quarta-feira', '19:30', 'Rua dos Pinheiros, 123', 'Pinheiros', 'São Paulo'],
            ['Célula Jovens Vencedores', 'Moema', -23.6034, -46.6617, 4, 2, 'jovens', 'Sexta-feira', '20:00', 'Av. Ibirapuera, 456', 'Moema', 'São Paulo'],
            ['Célula Casais em Cristo', 'Vila Madalena', -23.5505, -46.6889, 2, 1, 'casais', 'Sábado', '18:00', 'Rua Harmonia, 789', 'Vila Madalena', 'São Paulo'],
            ['Célula Mulheres de Fé', 'Centro', -23.5431, -46.6291, null, 2, 'mulheres', 'Terça-feira', '14:00', 'Rua São Bento, 321', 'Centro', 'São Paulo']
        ];
        
        $stmt = $db->prepare("INSERT INTO celulas (nome, localizacao, latitude, longitude, lider_id, supervisor_id, tipo, dia_semana, hora, endereco, bairro, cidade) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($celulas as $celula) {
            $stmt->execute($celula);
        }
        
        // Inserir membros de exemplo
        $membros = [
            [1, 1, '2020-01-15', '2020-03-20', 'ativo', 'Rua A, 100', 'Pinheiros', 'São Paulo', '05422-000', '1980-05-10'],
            [2, 3, '2019-06-10', '2019-08-15', 'ativo', 'Rua B, 200', 'Vila Madalena', 'São Paulo', '05433-000', '1985-09-22'],
            [3, 1, '2021-02-20', '2021-04-25', 'ativo', 'Rua C, 300', 'Pinheiros', 'São Paulo', '05444-000', '1975-12-05'],
            [4, 2, '2020-11-08', '2021-01-10', 'ativo', 'Rua D, 400', 'Moema', 'São Paulo', '05455-000', '1990-03-18'],
            [5, 1, '2022-03-15', null, 'ativo', 'Rua E, 500', 'Pinheiros', 'São Paulo', '05466-000', '1995-07-30'],
            [6, 2, '2021-08-22', '2021-10-27', 'ativo', 'Rua F, 600', 'Moema', 'São Paulo', '05477-000', '1988-11-12'],
            [7, 4, '2020-05-14', null, 'ativo', 'Rua G, 700', 'Centro', 'São Paulo', '05488-000', '1993-01-25'],
            [8, 1, '2023-01-10', null, 'ativo', 'Rua H, 800', 'Pinheiros', 'São Paulo', '05499-000', '1987-06-08']
        ];
        
        $stmt = $db->prepare("INSERT INTO membros (usuario_id, celula_id, data_conversao, data_batismo, status, endereco, bairro, cidade, cep, data_nasc) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($membros as $membro) {
            $stmt->execute($membro);
        }
        
        // Inserir eventos de exemplo
        $eventos = [
            ['Culto de Celebração', 'Culto especial de celebração com louvor e palavra', '2024-01-19 10:00:00', 'Templo Central - IEQ', 1, 'culto', 500],
            ['Congresso de Líderes 2025', 'Encontro anual para capacitação de líderes', '2024-02-15 08:00:00', 'Centro de Convenções', 1, 'congresso', 200],
            ['Retiro de Jovens', 'Retiro espiritual para jovens da igreja', '2024-03-01 18:00:00', 'Chácara Vista Alegre', 4, 'retiro', 80]
        ];
        
        $stmt = $db->prepare("INSERT INTO eventos (nome, descricao, data_evento, localizacao, responsavel_id, tipo, vagas) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($eventos as $evento) {
            $stmt->execute($evento);
        }
        
        return true;
    } catch (PDOException $e) {
        error_log('Erro ao inserir dados de exemplo: ' . $e->getMessage());
        return false;
    }
}
?>
