<?php
/**
 * Gestione autenticazione e controllo accessi
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Verifica se l'utente è autenticato
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_ruolo']);
}

/**
 * Verifica se l'utente ha un ruolo specifico
 */
function hasRole($ruolo) {
    return isLoggedIn() && $_SESSION['user_ruolo'] === $ruolo;
}

/**
 * Richiede autenticazione, altrimenti reindirizza al login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/public/login.php');
        exit;
    }
}

/**
 * Richiede un ruolo specifico
 */
function requireRole($ruolo) {
    requireLogin();
    if (!hasRole($ruolo)) {
        header('Location: ' . BASE_URL . '/public/access-denied.php');
        exit;
    }
}

/**
 * Login utente
 */
function login($username, $password) {
    $db = getDB();

    try {
        $stmt = $db->prepare("SELECT id, username, password, nome, cognome, email, ruolo, attivo, primo_accesso FROM utenti WHERE username = ? AND attivo = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Login riuscito
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_username'] = $user['username'];
            $_SESSION['user_nome'] = $user['nome'];
            $_SESSION['user_cognome'] = $user['cognome'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_ruolo'] = $user['ruolo'];
            $_SESSION['primo_accesso'] = $user['primo_accesso'];
            $_SESSION['login_time'] = time();

            return true;
        }

        return false;
    } catch (PDOException $e) {
        error_log("Errore login: " . $e->getMessage());
        return false;
    }
}

/**
 * Logout utente
 */
function logout() {
    $_SESSION = array();

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy();
}

/**
 * Ottiene l'ID dell'utente corrente
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Ottiene il ruolo dell'utente corrente
 */
function getCurrentUserRole() {
    return $_SESSION['user_ruolo'] ?? null;
}

/**
 * Ottiene il nome completo dell'utente corrente
 */
function getCurrentUserFullName() {
    if (!isLoggedIn()) return '';
    return $_SESSION['user_nome'] . ' ' . $_SESSION['user_cognome'];
}

/**
 * Verifica la validità della sessione
 */
function checkSessionTimeout() {
    if (isLoggedIn()) {
        $last_activity = $_SESSION['login_time'] ?? 0;
        if (time() - $last_activity > SESSION_LIFETIME) {
            logout();
            return false;
        }
        $_SESSION['login_time'] = time();
    }
    return true;
}

/**
 * Verifica se è il primo accesso dell'utente
 */
function isPrimoAccesso() {
    return isset($_SESSION['primo_accesso']) && $_SESSION['primo_accesso'] == 1;
}

/**
 * Controlla se l'utente deve cambiare password e reindirizza se necessario
 * Da chiamare nelle pagine protette degli studenti
 */
function checkCambioPasswordObbligatorio() {
    if (isLoggedIn() && isPrimoAccesso() && getCurrentUserRole() === 'studente') {
        // Permetti accesso solo alla pagina di cambio password e logout
        $current_page = basename($_SERVER['PHP_SELF']);
        if ($current_page !== 'cambio-password.php') {
            header('Location: cambio-password.php');
            exit;
        }
    }
}
?>
