# Configurazione Accesso Remoto - UDA Portal

## Problema Comune

Se riesci a vedere la pagina di login ma dopo il login vieni bloccato, il problema è il `BASE_URL` in `config/config.php`.

## Soluzione

### 1. Modifica BASE_URL

Apri `config/config.php` e modifica la riga 25:

```php
// PRIMA (sbagliato per accesso remoto):
define('BASE_URL', 'http://localhost/UDA');

// DOPO (con IP del server):
define('BASE_URL', 'http://192.168.1.100/UDA');

// Oppure con dominio:
define('BASE_URL', 'http://mioserver.com/UDA');

// Con HTTPS (consigliato):
define('BASE_URL', 'https://mioserver.com/UDA');
```

**Trova il tuo IP Ubuntu:**
```bash
# IP locale (rete LAN)
hostname -I

# IP pubblico (se esposto su internet)
curl ifconfig.me
```

### 2. Configura Firewall Ubuntu (UFW)

#### Porte da Aprire

**Solo queste porte devono essere aperte:**

```bash
# Verifica stato firewall
sudo ufw status

# Abilita firewall se disabilitato
sudo ufw enable

# Porta 80 - HTTP (obbligatoria)
sudo ufw allow 80/tcp

# Porta 443 - HTTPS (se usi SSL)
sudo ufw allow 443/tcp

# Porta 22 - SSH (per amministrazione remota)
sudo ufw allow 22/tcp

# Ricarica firewall
sudo ufw reload

# Verifica porte aperte
sudo ufw status verbose
```

#### Porte da NON Aprire

**NON aprire queste porte dall'esterno:**
- **Porta 3306** (MySQL) - deve rimanere solo locale per sicurezza
- Altre porte non necessarie

### 3. Configura Apache/Nginx

#### Apache

Assicurati che Apache ascolti su tutte le interfacce:

```bash
# Controlla configurazione
sudo nano /etc/apache2/ports.conf
```

Deve contenere:
```apache
Listen 80
```

NON deve essere:
```apache
Listen 127.0.0.1:80  # <-- sbagliato, solo locale
```

Riavvia Apache:
```bash
sudo systemctl restart apache2
```

#### VirtualHost (opzionale ma consigliato)

Crea un VirtualHost specifico:

```bash
sudo nano /etc/apache2/sites-available/uda-portal.conf
```

Contenuto:
```apache
<VirtualHost *:80>
    ServerName 192.168.1.100
    # O ServerName miodominio.com

    DocumentRoot /home/user/UDA/public

    <Directory /home/user/UDA>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/uda-error.log
    CustomLog ${APACHE_LOG_DIR}/uda-access.log combined
</VirtualHost>
```

Abilita e riavvia:
```bash
sudo a2ensite uda-portal.conf
sudo systemctl reload apache2
```

### 4. Verifica Accesso

#### Da locale (sul server Ubuntu):
```bash
curl http://localhost/UDA/public/
```

#### Da remoto (da altro PC nella rete):
```bash
# Sostituisci con il tuo IP
curl http://192.168.1.100/UDA/public/
```

### 5. Risoluzione Problemi

#### Il login non funziona ancora

**Problema: Sessioni PHP**

Verifica permessi cartella sessioni:
```bash
sudo chmod 1733 /var/lib/php/sessions
sudo chown root:root /var/lib/php/sessions
```

**Problema: Cookie non impostati**

Se usi un dominio diverso, modifica `config.php`:
```php
// Per HTTP
ini_set('session.cookie_secure', 0);

// Per HTTPS
ini_set('session.cookie_secure', 1);
```

#### Errore "Connection refused"

**Controlla se Apache è in ascolto:**
```bash
sudo netstat -tlnp | grep :80
# Oppure
sudo ss -tlnp | grep :80
```

Dovresti vedere:
```
tcp  0  0  0.0.0.0:80  0.0.0.0:*  LISTEN  1234/apache2
```

#### Errore 403 Forbidden

**Controlla permessi file:**
```bash
# Nella cartella UDA
sudo chown -R www-data:www-data /home/user/UDA
sudo chmod -R 755 /home/user/UDA
sudo chmod -R 775 /home/user/UDA/database
```

### 6. Setup HTTPS (Consigliato)

Per connessioni sicure con SSL/TLS:

#### Con Let's Encrypt (gratuito):

```bash
# Installa Certbot
sudo apt update
sudo apt install certbot python3-certbot-apache

# Ottieni certificato
sudo certbot --apache -d miodominio.com

# Rinnovo automatico (già configurato)
sudo certbot renew --dry-run
```

Poi modifica `config.php`:
```php
define('BASE_URL', 'https://miodominio.com/UDA');
ini_set('session.cookie_secure', 1);
```

### 7. Accesso da Internet (non solo LAN)

Se vuoi accedere da internet e non solo dalla rete locale:

#### Router Port Forwarding

Accedi al router e configura port forwarding:
- **Porta esterna**: 80 → **Porta interna**: 80 → **IP**: 192.168.1.100
- **Porta esterna**: 443 → **Porta interna**: 443 → **IP**: 192.168.1.100

#### DNS Dinamico (se IP pubblico cambia)

Usa servizi come:
- No-IP
- DuckDNS
- DynDNS

### 8. Checklist Finale

- [ ] Modificato `BASE_URL` in `config/config.php`
- [ ] Aperta porta 80 su UFW
- [ ] Aperta porta 443 su UFW (se HTTPS)
- [ ] Apache in ascolto su 0.0.0.0:80
- [ ] Permessi file corretti (www-data)
- [ ] Testato login da browser remoto
- [ ] (Opzionale) Configurato HTTPS con Let's Encrypt
- [ ] (Opzionale) Port forwarding su router

### 9. Test Completo

```bash
# 1. Dal server Ubuntu
curl -I http://localhost/UDA/public/

# 2. Da PC remoto (stessa rete)
curl -I http://192.168.1.100/UDA/public/

# 3. Testa login
# Vai su http://192.168.1.100/UDA/public/
# Username: admin
# Password: admin123
```

## Riepilogo Porte

| Porta | Protocollo | Scopo | Aprire su UFW? |
|-------|------------|-------|----------------|
| 80    | HTTP       | Web server | ✅ SÌ |
| 443   | HTTPS      | Web server sicuro | ✅ SÌ (se usi SSL) |
| 22    | SSH        | Amministrazione remota | ✅ SÌ |
| 3306  | MySQL      | Database | ❌ NO (solo locale) |

## Sicurezza

**Best Practices:**
1. Usa sempre HTTPS in produzione
2. NON esporre MySQL (3306) all'esterno
3. Cambia password di default (admin123, docente123, studente123)
4. Abilita fail2ban per SSH
5. Mantieni Ubuntu e Apache aggiornati

```bash
# Aggiorna sistema
sudo apt update && sudo apt upgrade -y

# Installa fail2ban (opzionale)
sudo apt install fail2ban
```
