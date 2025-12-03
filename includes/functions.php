<?php
/**
 * Funzioni helper generiche
 */

/**
 * Escape HTML per prevenire XSS
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect a una URL
 */
function redirect($url) {
    header("Location: " . $url);
    exit;
}

/**
 * Formatta una data
 */
function formatDate($date, $format = 'd/m/Y') {
    if (empty($date)) return '-';
    $dt = new DateTime($date);
    return $dt->format($format);
}

/**
 * Formatta data e ora
 */
function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    if (empty($datetime)) return '-';
    $dt = new DateTime($datetime);
    return $dt->format($format);
}

/**
 * Genera un messaggio di successo
 */
function setSuccessMessage($message) {
    $_SESSION['success_message'] = $message;
}

/**
 * Genera un messaggio di errore
 */
function setErrorMessage($message) {
    $_SESSION['error_message'] = $message;
}

/**
 * Mostra e rimuove il messaggio di successo
 */
function getSuccessMessage() {
    if (isset($_SESSION['success_message'])) {
        $message = $_SESSION['success_message'];
        unset($_SESSION['success_message']);
        return $message;
    }
    return null;
}

/**
 * Mostra e rimuove il messaggio di errore
 */
function getErrorMessage() {
    if (isset($_SESSION['error_message'])) {
        $message = $_SESSION['error_message'];
        unset($_SESSION['error_message']);
        return $message;
    }
    return null;
}

/**
 * Formatta un numero decimale
 */
function formatNumber($number, $decimals = 2) {
    return number_format($number, $decimals, ',', '.');
}

/**
 * Valida email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Genera una password hash sicura
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Sanitize input
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    return $data;
}

/**
 * Verifica se una richiesta è POST
 */
function isPost() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Ottiene un valore POST
 */
function post($key, $default = null) {
    return isset($_POST[$key]) ? sanitizeInput($_POST[$key]) : $default;
}

/**
 * Ottiene un valore GET
 */
function get($key, $default = null) {
    return isset($_GET[$key]) ? sanitizeInput($_GET[$key]) : $default;
}
?>
