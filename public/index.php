<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Reindirizza al login se non autenticato
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Reindirizza alla dashboard in base al ruolo
$ruolo = getCurrentUserRole();
switch ($ruolo) {
    case 'admin':
        header('Location: ../admin/index.php');
        break;
    case 'docente':
        header('Location: ../docente/index.php');
        break;
    case 'studente':
        header('Location: ../studente/index.php');
        break;
    default:
        header('Location: login.php');
}
exit;
?>
