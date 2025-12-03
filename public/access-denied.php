<?php
require_once __DIR__ . '/../config/config.php';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accesso Negato - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="error-box">
            <h1>Accesso Negato</h1>
            <p>Non hai i permessi necessari per accedere a questa pagina.</p>
            <a href="index.php" class="btn btn-primary">Torna alla Home</a>
        </div>
    </div>
</body>
</html>
