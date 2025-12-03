# Guida Rapida all'Installazione

## Prerequisiti
- PHP 7.4+
- MySQL/MariaDB 5.7+
- Apache o Nginx
- Estensioni PHP: PDO, PDO_MySQL

## Installazione in 5 Passi

### 1. Database
```bash
# Creare il database
mysql -u root -p -e "CREATE DATABASE uda_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Importare lo schema
mysql -u root -p uda_portal < database/schema.sql
```

### 2. Configurazione
```bash
# Copiare il file di esempio
cp config/config.example.php config/config.php

# Modificare config/config.php con i tuoi dati
nano config/config.php
```

Cambiare:
- `DB_USER`: il tuo utente MySQL
- `DB_PASS`: la tua password MySQL
- `BASE_URL`: l'URL del tuo portale

### 3. Permessi
```bash
# Dare i permessi corretti
chmod -R 755 /path/to/uda-portal
chown -R www-data:www-data /path/to/uda-portal
```

### 4. Web Server

**Apache:** Puntare DocumentRoot a `/path/to/uda-portal/public`

**Nginx:** Configurare root su `/path/to/uda-portal/public`

### 5. Primo Accesso
Visitare: `http://your-domain/public/login.php`

**Credenziali Admin:**
- Username: `admin`
- Password: `admin123`

⚠️ **CAMBIARE la password dopo il primo accesso!**

## Verifica Installazione

✅ La pagina di login appare correttamente
✅ Puoi accedere con le credenziali di default
✅ La dashboard mostra le statistiche
✅ Nessun errore PHP nei log

## Problemi Comuni

**Errore connessione database:**
- Verifica credenziali in `config/config.php`
- Controlla che MySQL sia attivo: `systemctl status mysql`

**Pagina bianca:**
- Controlla i log: `/var/log/apache2/error.log` o `/var/log/nginx/error.log`
- Abilita errori PHP: `ini_set('display_errors', 1);` in `config/config.php`

**Permessi negati:**
- Verifica ownership: `ls -la /path/to/uda-portal`
- Controlla che il web server possa leggere i file

## Supporto

Per maggiori dettagli, consulta il file `README.md`.
