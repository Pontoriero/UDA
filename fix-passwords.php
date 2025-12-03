<?php
/**
 * Script per generare password corrette per il portale UDA
 */

// Password da hashare
$passwords = [
    'admin123',
    'docente123',
    'studente123'
];

echo "Password Hasher per Portale UDA\n";
echo "================================\n\n";

foreach ($passwords as $password) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    echo "Password: $password\n";
    echo "Hash: $hash\n\n";
}

echo "\n=== SQL per aggiornare gli utenti ===\n\n";

$admin_hash = password_hash('admin123', PASSWORD_DEFAULT);
$docente_hash = password_hash('docente123', PASSWORD_DEFAULT);
$studente_hash = password_hash('studente123', PASSWORD_DEFAULT);

echo "UPDATE utenti SET password = '$admin_hash' WHERE username = 'admin';\n";
echo "UPDATE utenti SET password = '$docente_hash' WHERE username = 'docente1';\n";
echo "UPDATE utenti SET password = '$studente_hash' WHERE username = 'studente1';\n";
?>
