# Portale Valutazione UDA

Sistema completo per la gestione delle valutazioni delle Unità Di Apprendimento (UDA) con tre ruoli: **Amministratore**, **Docente** e **Studente**.

## Caratteristiche Principali

### Pannello Amministratore
- ✅ Creazione e gestione griglie di valutazione
- ✅ Definizione criteri con pesi personalizzati
- ✅ Configurazione livelli di valutazione con descrizioni e punteggi
- ✅ Gestione utenti (admin, docenti, studenti)
- ✅ Visualizzazione statistiche globali

### Pannello Docente
- ✅ Creazione prove/UDA basate su griglie predefinite
- ✅ Valutazione studenti per ogni criterio
- ✅ Selezione livello raggiunto con descrizioni dettagliate
- ✅ Aggiunta note per ogni criterio
- ✅ Visualizzazione elenco studenti

### Pannello Studente
- ✅ Visualizzazione valutazioni ricevute
- ✅ Dettaglio per ogni prova con criteri e livelli raggiunti
- ✅ Calcolo automatico voto medio ponderato
- ✅ Storico completo delle valutazioni
- ✅ Note del docente per ogni criterio

## Requisiti

- **PHP**: 7.4 o superiore
- **MySQL/MariaDB**: 5.7 o superiore
- **Web Server**: Apache o Nginx
- **Estensioni PHP**: PDO, PDO_MySQL

## Installazione

### 1. Clonare o Scaricare il Progetto

```bash
git clone <repository-url> uda-portal
cd uda-portal
```

### 2. Configurazione Database

Creare un database MySQL:

```bash
mysql -u root -p
```

```sql
CREATE DATABASE uda_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

Importare lo schema:

```bash
mysql -u root -p uda_portal < database/schema.sql
```

### 3. Configurazione PHP

Modificare il file `config/config.php` con le credenziali del database:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'uda_portal');
define('DB_USER', 'root');        // Cambia con il tuo utente
define('DB_PASS', '');            // Cambia con la tua password
```

Se necessario, modificare anche:
- `BASE_URL`: l'URL del portale (es. `http://localhost/uda-portal`)
- Timezone se diverso da `Europe/Rome`

### 4. Configurazione Web Server

#### Apache

Creare un VirtualHost o usare la DocumentRoot esistente:

```apache
<VirtualHost *:80>
    ServerName uda-portal.local
    DocumentRoot /path/to/uda-portal/public

    <Directory /path/to/uda-portal/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### Nginx

```nginx
server {
    listen 80;
    server_name uda-portal.local;
    root /path/to/uda-portal/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 5. Permessi File

Assicurarsi che il web server possa leggere i file:

```bash
chmod -R 755 /path/to/uda-portal
chown -R www-data:www-data /path/to/uda-portal  # per Apache/Nginx su Ubuntu
```

## Primo Accesso

Accedere al portale tramite browser: `http://localhost/uda-portal/public/login.php`

### Credenziali di Default

**Amministratore:**
- Username: `admin`
- Password: `admin123`

**Docente:**
- Username: `docente1`
- Password: `docente123`

**Studente:**
- Username: `studente1`
- Password: `studente123`

⚠️ **IMPORTANTE**: Cambiare le password di default dopo il primo accesso!

## Struttura del Progetto

```
uda-portal/
├── admin/              # Pannello amministratore
│   ├── index.php       # Dashboard admin
│   ├── griglie.php     # Gestione griglie
│   ├── griglia-edit.php # Crea/modifica griglia
│   ├── griglia-criteri.php # Gestione criteri e livelli
│   ├── utenti.php      # Gestione utenti
│   └── sidebar.php     # Menu laterale admin
├── docente/            # Pannello docente
│   ├── index.php       # Dashboard docente
│   ├── prove.php       # Gestione prove
│   ├── valuta.php      # Valutazione studenti
│   ├── griglie.php     # Visualizza griglie disponibili
│   ├── studenti.php    # Elenco studenti
│   └── sidebar.php     # Menu laterale docente
├── studente/           # Pannello studente
│   ├── index.php       # Dashboard studente
│   ├── dettaglio.php   # Dettaglio valutazione
│   ├── storico.php     # Storico completo
│   └── sidebar.php     # Menu laterale studente
├── public/             # File pubblici
│   ├── css/
│   │   └── style.css   # Stili del portale
│   ├── js/             # JavaScript (se necessario)
│   ├── login.php       # Pagina di login
│   ├── logout.php      # Logout
│   └── index.php       # Reindirizzamento
├── config/             # Configurazione
│   ├── config.php      # Configurazione generale
│   └── database.php    # Gestione connessione DB
├── includes/           # File di supporto
│   ├── auth.php        # Gestione autenticazione
│   └── functions.php   # Funzioni helper
└── database/           # Database
    └── schema.sql      # Schema del database
```

## Utilizzo

### Creazione di una Griglia (Admin)

1. Accedere come amministratore
2. Andare su "Griglie di Valutazione"
3. Cliccare "Nuova Griglia"
4. Inserire nome, materia, anno scolastico
5. Cliccare "Gestisci Criteri"
6. Aggiungere criteri con peso (es. "Conoscenze" peso 2.0)
7. Per ogni criterio, aggiungere livelli (es. "Eccellente" - 10 punti)

### Valutazione Studente (Docente)

1. Accedere come docente
2. Andare su "Le Mie Prove"
3. Cliccare "Nuova Prova"
4. Selezionare una griglia esistente
5. Cliccare "Valuta Studenti"
6. Selezionare uno studente
7. Per ogni criterio, scegliere il livello raggiunto
8. Aggiungere note se necessario
9. Salvare la valutazione

### Visualizzazione Valutazioni (Studente)

1. Accedere come studente
2. Dashboard mostra le valutazioni recenti
3. Cliccare "Visualizza Dettaglio" per vedere:
   - Voto medio ponderato
   - Livello raggiunto per ogni criterio
   - Descrizione dei livelli
   - Note del docente

## Database

### Tabelle Principali

- **utenti**: Gestione utenti con ruoli (admin, docente, studente)
- **griglie**: Griglie di valutazione create dall'admin
- **criteri**: Criteri di ogni griglia con peso
- **livelli**: Livelli di valutazione per ogni criterio
- **prove**: Prove/UDA create dai docenti
- **valutazioni**: Valutazioni degli studenti

### Calcolo Voto Medio Ponderato

Il voto medio viene calcolato con la formula:

```
Voto = (Σ(Punteggio_Criterio × Peso_Criterio)) / (Σ Peso_Criteri)
```

Esempio:
- Criterio A: 8 punti, peso 2 → 8 × 2 = 16
- Criterio B: 7 punti, peso 1 → 7 × 1 = 7
- Voto Medio = (16 + 7) / (2 + 1) = 23 / 3 = 7.67

## Sicurezza

- ✅ Password hashate con bcrypt
- ✅ Protezione XSS con escape HTML
- ✅ Prepared statements per prevenire SQL injection
- ✅ Controllo accessi basato su ruoli
- ✅ Timeout sessione configurabile
- ✅ Cookie HTTP-only

## Personalizzazione

### Modificare i Colori

Modificare le variabili CSS in `public/css/style.css`:

```css
:root {
    --primary-color: #2563eb;    /* Colore principale */
    --success-color: #10b981;    /* Colore successo */
    --danger-color: #ef4444;     /* Colore errore */
    /* ... */
}
```

### Aggiungere Nuovi Utenti

Utilizzare il pannello Admin → Gestione Utenti oppure direttamente via SQL:

```sql
INSERT INTO utenti (username, password, nome, cognome, email, ruolo)
VALUES ('nuovo_utente', '$2y$10$...', 'Nome', 'Cognome', 'email@example.com', 'studente');
```

⚠️ La password deve essere hashata con `password_hash()` in PHP.

## Troubleshooting

### Errore di connessione al database
- Verificare le credenziali in `config/config.php`
- Assicurarsi che il database esista
- Controllare che MySQL sia in esecuzione

### Pagina bianca / Errore 500
- Abilitare la visualizzazione errori PHP:
  ```php
  ini_set('display_errors', 1);
  error_reporting(E_ALL);
  ```
- Controllare i log del web server

### Sessione scade troppo presto
- Modificare `SESSION_LIFETIME` in `config/config.php`

### Problemi di permessi
- Verificare che il web server possa leggere tutti i file
- Controllare ownership e permessi delle cartelle

## Supporto e Contributi

Per segnalare bug o richiedere funzionalità, aprire una issue nel repository.

## Licenza

Questo progetto è open source e disponibile per uso educativo.

## Crediti

Sviluppato per la gestione delle valutazioni UDA nelle scuole italiane.

---

**Versione:** 1.0.0
**Data:** Dicembre 2024
