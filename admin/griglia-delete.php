<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

$db = getDB();
$griglia_id = get('id', 0);

if (!$griglia_id) {
    setErrorMessage('ID griglia non valido');
    redirect('griglie.php');
}

try {
    $stmt = $db->prepare("DELETE FROM griglie WHERE id = ?");
    $stmt->execute([$griglia_id]);
    setSuccessMessage('Griglia eliminata con successo');
} catch (Exception $e) {
    setErrorMessage('Errore durante l\'eliminazione: ' . $e->getMessage());
}

redirect('griglie.php');
?>
