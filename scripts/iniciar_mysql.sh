#!/bin/bash
# Script para iniciar MySQL do XAMPP em Linux/Mac
# Para Windows, use XAMPP Control Panel GUI

# Detectar SO
if [[ "$OSTYPE" == "linux-gnu"* ]]; then
    # Linux
    /xampp/bin/mysql.server start
elif [[ "$OSTYPE" == "darwin"* ]]; then
    # macOS
    /Applications/XAMPP/xamppfiles/bin/mysql.server start
elif [[ "$OSTYPE" == "msys" ]]; then
    # Windows (Git Bash)
    C:/xampp02/mysql/bin/mysqld.exe --console
else
    echo "SO não reconhecido: $OSTYPE"
    echo "Inicie manualmente via XAMPP Control Panel"
fi
