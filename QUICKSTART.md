# Guida Rapida per Provare il Portale

## Metodo 1: Server PHP Built-in (Più Veloce)

### 1. Crea il Database
```bash
# Avvia MySQL
mysql -u root -p

# Crea il database
CREATE DATABASE uda_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;

# Importa lo schema
mysql -u root -p uda_portal < database/schema.sql
```

### 2. Verifica la Configurazione
Apri `config/config.php` e verifica:
- `DB_USER` e `DB_PASS` siano corretti per il tuo MySQL
- `BASE_URL` può rimanere come `http://localhost/UDA` o cambiare in `http://localhost:8000`

### 3. Avvia il Server
```bash
cd /path/to/UDA
php -S localhost:8000 -t public
```

### 4. Apri il Browser
Vai su: **http://localhost:8000/login.php**

### 5. Accedi
- **Admin**: username `admin`, password `admin123`
- **Docente**: username `docente1`, password `docente123`
- **Studente**: username `studente1`, password `studente123`

---

## Metodo 2: XAMPP/WAMP (Windows)

### 1. Installa XAMPP
Scarica da: https://www.apachefriends.org/

### 2. Copia il Progetto
```
C:\xampp\htdocs\UDA
```

### 3. Crea il Database
- Apri http://localhost/phpmyadmin
- Crea database `uda_portal`
- Vai su "Import" e importa `database/schema.sql`

### 4. Configura
Modifica `config/config.php`:
```php
define('BASE_URL', 'http://localhost/UDA');
```

### 5. Accedi
http://localhost/UDA/public/login.php

---

## Metodo 3: MAMP (Mac)

### 1. Installa MAMP
Scarica da: https://www.mamp.info/

### 2. Copia il Progetto
```
/Applications/MAMP/htdocs/UDA
```

### 3. Crea il Database
- Apri http://localhost:8888/phpMyAdmin
- Crea database `uda_portal`
- Importa `database/schema.sql`

### 4. Configura
Modifica `config/config.php`:
```php
define('DB_HOST', 'localhost:8889'); // MAMP usa porta 8889
define('BASE_URL', 'http://localhost:8888/UDA');
```

### 5. Accedi
http://localhost:8888/UDA/public/login.php

---

## Test delle Funzionalità

### Come Admin:
1. Vai su "Griglie di Valutazione"
2. Clicca "+ Nuova Griglia"
3. Inserisci: Nome "Italiano", Materia "Italiano", Anno "2024/25"
4. Clicca "Gestisci Criteri"
5. Aggiungi criterio "Comprensione testo" con peso 2.0
6. Aggiungi livelli: "Eccellente" (10 punti), "Buono" (8 punti), etc.

### Come Docente:
1. Vai su "Le Mie Prove"
2. Clicca "+ Nuova Prova"
3. Seleziona la griglia creata
4. Inserisci nome prova e salva
5. Clicca "Valuta Studenti"
6. Seleziona uno studente (es. studente1)
7. Scegli il livello per ogni criterio
8. Salva

### Come Studente:
1. Vedi la dashboard con le valutazioni
2. Clicca "Visualizza Dettaglio" su una prova
3. Vedi il voto medio ponderato e i dettagli

---

## Troubleshooting

### Errore: "Connection refused"
- Verifica che MySQL sia attivo: `systemctl status mysql` o controlla XAMPP/MAMP

### Errore: "Access denied for user"
- Controlla username e password in `config/config.php`
- Default XAMPP: user `root`, password vuota `''`
- Default MAMP: user `root`, password `root`

### Errore: "Call to undefined function password_verify"
- PHP versione troppo vecchia
- Serve almeno PHP 7.4

### Pagina bianca
- Controlla i log PHP
- Abilita errori: aggiungi in `config/config.php`:
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### CSS non caricato
- Verifica che `BASE_URL` in `config/config.php` sia corretto
- Deve puntare alla root del progetto
