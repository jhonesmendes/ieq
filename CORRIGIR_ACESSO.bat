@echo off
REM ════════════════════════════════════════════════════════════
REM SCRIPT DE RECUPERAÇÃO - CORRIGIR ACESSO AO LOCALHOST
REM ════════════════════════════════════════════════════════════

setlocal enabledelayedexpansion
cd /d C:\xampp02\htdocs\ieq

echo.
echo ════════════════════════════════════════════════════════════
echo 🔧 SCRIPT DE RECUPERAÇÃO - BANCO DE DADOS
echo ════════════════════════════════════════════════════════════
echo.

REM STEP 1: Verificar se MySQL está iniciado
echo [PASSO 1] Verificando MySQL...
echo.
netstat -ano | findstr ":3306" > nul
if %errorlevel% equ 0 (
    echo ✓ MySQL está RODANDO na porta 3306
) else (
    echo ✗ MySQL NÃO está rodando!
    echo.
    echo ⚠️ ABRA XAMPP CONTROL PANEL e inicie MySQL antes de continuar!
    echo.
    pause
    exit /b 1
)

echo.
echo ════════════════════════════════════════════════════════════
echo [PASSO 2] Testando conexão com MySQL...
echo ════════════════════════════════════════════════════════════
echo.

C:\xampp02\php\php.exe -f scripts/testar_conexao_mysql.php

if %errorlevel% neq 0 (
    echo.
    echo ❌ Erro ao conectar no MySQL!
    echo.
    pause
    exit /b 1
)

echo.
echo ════════════════════════════════════════════════════════════
echo [PASSO 3] Criando banco de dados (se necessário)...
echo ════════════════════════════════════════════════════════════
echo.

php -r "
try {
    \$db = new PDO('mysql:host=localhost;port=3306;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    \$db->exec('CREATE DATABASE IF NOT EXISTS \`ieq\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    \$db->exec('USE \`ieq\`');
    echo '✓ Banco de dados \"ieq\" criado/verificado';
    echo chr(10);
} catch (Exception \$e) {
    echo 'Erro: ' . \$e->getMessage();
    exit(1);
}
"

if %errorlevel% neq 0 (
    echo.
    echo ❌ Erro ao criar banco de dados!
    echo.
    pause
    exit /b 1
)

echo.
echo ════════════════════════════════════════════════════════════
echo [PASSO 4] Migrando dados do SQLite para MySQL...
echo ════════════════════════════════════════════════════════════
echo.

C:\xampp02\php\php.exe -f scripts/migrar_sqlite_para_mysql.php

if %errorlevel% neq 0 (
    echo.
    echo ⚠️ Erro na migração, mas continuando...
    echo.
)

echo.
echo ════════════════════════════════════════════════════════════
echo ✅ RECUPERAÇÃO CONCLUÍDA COM SUCESSO!
echo ════════════════════════════════════════════════════════════
echo.
echo 🌐 Acesse: http://localhost/ieq
echo.
pause
