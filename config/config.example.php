<?php
/**
 * File di configurazione di ESEMPIO del portale UDA
 * COPIARE questo file come config.php e modificare i valori
 */

// Configurazione Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'uda_portal');
define('DB_USER', 'root');              // MODIFICARE con il tuo utente MySQL
define('DB_PASS', '');                  // MODIFICARE con la tua password MySQL
define('DB_CHARSET', 'utf8mb4');

// Configurazione Sessione
define('SESSION_NAME', 'UDA_PORTAL_SESSION');
define('SESSION_LIFETIME', 3600); // 1 ora in secondi

// Configurazione Path
define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', 'http://localhost/UDA'); // MODIFICARE con il tuo URL

// Configurazione Applicazione
define('APP_NAME', 'Portale Valutazione UDA');
define('APP_VERSION', '1.0.0');

// Timezone
date_default_timezone_set('Europe/Rome'); // Modificare se necessario

// Avvio sessione
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Impostare a 1 se si usa HTTPS
    session_name(SESSION_NAME);
    session_start();
}
?>
