<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Se già loggato, reindirizza alla dashboard appropriata
if (isLoggedIn()) {
    $ruolo = getCurrentUserRole();
    switch ($ruolo) {
        case 'admin':
            redirect(BASE_URL . '/admin/index.php');
            break;
        case 'docente':
            redirect(BASE_URL . '/docente/index.php');
            break;
        case 'studente':
            redirect(BASE_URL . '/studente/index.php');
            break;
    }
}

$error = '';

if (isPost()) {
    $username = post('username');
    $password = post('password');

    if (empty($username) || empty($password)) {
        $error = 'Inserisci username e password';
    } else {
        if (login($username, $password)) {
            $ruolo = getCurrentUserRole();
            switch ($ruolo) {
                case 'admin':
                    redirect(BASE_URL . '/admin/index.php');
                    break;
                case 'docente':
                    redirect(BASE_URL . '/docente/index.php');
                    break;
                case 'studente':
                    redirect(BASE_URL . '/studente/index.php');
                    break;
            }
        } else {
            $error = 'Username o password non validi';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1><?php echo APP_NAME; ?></h1>
                <p>Accedi al tuo account</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo e($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="login-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Accedi</button>
            </form>

            <div class="login-footer">
                <p><small>Credenziali di default:</small></p>
                <ul>
                    <li><strong>Admin:</strong> admin / admin123</li>
                    <li><strong>Docente:</strong> docente1 / docente123</li>
                    <li><strong>Studente:</strong> studente1 / studente123</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
