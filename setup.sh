#!/bin/bash

echo "======================================"
echo "   Setup Portale Valutazione UDA"
echo "======================================"
echo ""

# Colori
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Verifica se MySQL è installato
if ! command -v mysql &> /dev/null; then
    echo -e "${RED}Errore: MySQL non trovato. Installalo prima di continuare.${NC}"
    exit 1
fi

# Verifica se PHP è installato
if ! command -v php &> /dev/null; then
    echo -e "${RED}Errore: PHP non trovato. Installalo prima di continuare.${NC}"
    exit 1
fi

# Verifica versione PHP
PHP_VERSION=$(php -r 'echo PHP_VERSION;')
echo -e "${GREEN}✓${NC} PHP versione: $PHP_VERSION"

# Input credenziali MySQL
echo ""
echo "Inserisci le credenziali MySQL:"
read -p "Host (default: localhost): " DB_HOST
DB_HOST=${DB_HOST:-localhost}

read -p "Username (default: root): " DB_USER
DB_USER=${DB_USER:-root}

read -sp "Password: " DB_PASS
echo ""

read -p "Nome database (default: uda_portal): " DB_NAME
DB_NAME=${DB_NAME:-uda_portal}

# Test connessione
echo ""
echo "Test connessione MySQL..."
mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -e "SELECT 1;" &> /dev/null
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓${NC} Connessione MySQL riuscita"
else
    echo -e "${RED}✗${NC} Connessione MySQL fallita. Verifica le credenziali."
    exit 1
fi

# Crea database
echo ""
echo "Creazione database $DB_NAME..."
mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" &> /dev/null
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓${NC} Database creato"
else
    echo -e "${RED}✗${NC} Errore nella creazione del database"
    exit 1
fi

# Importa schema
echo ""
echo "Importazione schema database..."
mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < database/schema.sql
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓${NC} Schema importato"
else
    echo -e "${RED}✗${NC} Errore nell'importazione dello schema"
    exit 1
fi

# Aggiorna config.php
echo ""
echo "Configurazione file config.php..."
if [ ! -f "config/config.php" ]; then
    cp config/config.example.php config/config.php
fi

# Aggiorna le credenziali nel file config.php
sed -i "s/define('DB_HOST', 'localhost');/define('DB_HOST', '$DB_HOST');/" config/config.php
sed -i "s/define('DB_USER', 'root');/define('DB_USER', '$DB_USER');/" config/config.php
sed -i "s/define('DB_PASS', '');/define('DB_PASS', '$DB_PASS');/" config/config.php
sed -i "s/define('DB_NAME', 'uda_portal');/define('DB_NAME', '$DB_NAME');/" config/config.php

echo -e "${GREEN}✓${NC} Configurazione completata"

# Chiedi se avviare il server
echo ""
echo -e "${GREEN}======================================"
echo "   Setup Completato!"
echo "======================================${NC}"
echo ""
echo "Credenziali di default:"
echo "  Admin:    username: admin     password: admin123"
echo "  Docente:  username: docente1  password: docente123"
echo "  Studente: username: studente1 password: studente123"
echo ""
read -p "Vuoi avviare il server PHP built-in ora? (s/n): " START_SERVER

if [ "$START_SERVER" = "s" ] || [ "$START_SERVER" = "S" ]; then
    echo ""
    echo -e "${YELLOW}Avvio server su http://localhost:8000${NC}"
    echo -e "${YELLOW}Premi CTRL+C per fermare il server${NC}"
    echo ""
    php -S localhost:8000 -t public
else
    echo ""
    echo "Per avviare manualmente il server, esegui:"
    echo "  php -S localhost:8000 -t public"
    echo ""
    echo "Poi apri il browser su:"
    echo "  http://localhost:8000/login.php"
fi
