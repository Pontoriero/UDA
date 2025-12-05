<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Verifica che l'utente sia loggato
if (!isLoggedIn()) {
    redirect('../public/login.php');
}

$db = getDB();
$user_id = getCurrentUserId();

// Carica dati utente
$stmt = $db->prepare("SELECT * FROM utenti WHERE id = ?");
$stmt->execute([$user_id]);
$utente = $stmt->fetch();

// Se non è il primo accesso, redirect alla dashboard
if (!$utente || $utente['primo_accesso'] == 0) {
    redirect('index.php');
}

// Gestione form
if (isPost()) {
    $password_attuale = post('password_attuale');
    $nuova_password = post('nuova_password');
    $conferma_password = post('conferma_password');

    $errors = [];

    // Validazione
    if (empty($password_attuale)) {
        $errors[] = 'Password attuale obbligatoria';
    } elseif (!password_verify($password_attuale, $utente['password'])) {
        $errors[] = 'Password attuale non corretta';
    }

    if (empty($nuova_password)) {
        $errors[] = 'Nuova password obbligatoria';
    } elseif (strlen($nuova_password) < 6) {
        $errors[] = 'La password deve essere lunga almeno 6 caratteri';
    }

    if ($nuova_password !== $conferma_password) {
        $errors[] = 'Le password non coincidono';
    }

    if ($password_attuale === $nuova_password) {
        $errors[] = 'La nuova password deve essere diversa da quella attuale';
    }

    if (empty($errors)) {
        try {
            // Aggiorna password e imposta primo_accesso a 0
            $stmt = $db->prepare("UPDATE utenti SET password = ?, primo_accesso = 0 WHERE id = ?");
            $stmt->execute([hashPassword($nuova_password), $user_id]);

            setSuccessMessage('Password modificata con successo! Ora puoi accedere al portale.');
            redirect('index.php');
        } catch (PDOException $e) {
            setErrorMessage('Errore durante l\'aggiornamento della password');
        }
    } else {
        setErrorMessage(implode('<br>', $errors));
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambio Password Obbligatorio - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        .password-change-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }

        .password-change-box {
            background: white;
            border-radius: 10px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 500px;
            width: 100%;
        }

        .password-change-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .password-change-header .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .password-change-header h1 {
            font-size: 24px;
            color: #1f2937;
            margin-bottom: 10px;
        }

        .password-change-header p {
            color: #6b7280;
            font-size: 14px;
            line-height: 1.6;
        }

        .requirements {
            background: #f3f4f6;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }

        .requirements h4 {
            margin: 0 0 10px 0;
            color: #1f2937;
            font-size: 14px;
        }

        .requirements ul {
            margin: 0;
            padding-left: 20px;
            color: #6b7280;
            font-size: 13px;
        }

        .requirements li {
            margin: 5px 0;
        }

        .password-strength {
            margin-top: 5px;
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s;
        }

        .strength-weak { width: 33%; background: #ef4444; }
        .strength-medium { width: 66%; background: #f59e0b; }
        .strength-strong { width: 100%; background: #10b981; }
    </style>
</head>
<body>
    <div class="password-change-container">
        <div class="password-change-box">
            <div class="password-change-header">
                <div class="icon">🔒</div>
                <h1>Cambio Password Obbligatorio</h1>
                <p>
                    <strong>Benvenuto, <?php echo e($utente['nome'] . ' ' . $utente['cognome']); ?>!</strong><br>
                    Per motivi di sicurezza, devi cambiare la tua password al primo accesso.
                </p>
            </div>

            <?php if ($error = getErrorMessage()): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="password_attuale">Password Attuale *</label>
                    <input type="password" id="password_attuale" name="password_attuale" class="form-control" required autofocus>
                    <small class="text-muted">La password temporanea che ti è stata fornita</small>
                </div>

                <div class="form-group">
                    <label for="nuova_password">Nuova Password *</label>
                    <input type="password" id="nuova_password" name="nuova_password" class="form-control" required>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="strengthBar"></div>
                    </div>
                    <small class="text-muted" id="strengthText">Inserisci una password</small>
                </div>

                <div class="form-group">
                    <label for="conferma_password">Conferma Nuova Password *</label>
                    <input type="password" id="conferma_password" name="conferma_password" class="form-control" required>
                    <small class="text-muted" id="matchText"></small>
                </div>

                <div class="requirements">
                    <h4>📋 Requisiti Password:</h4>
                    <ul>
                        <li>Almeno 6 caratteri</li>
                        <li>Deve essere diversa dalla password attuale</li>
                        <li>Si consiglia di usare lettere maiuscole, minuscole e numeri</li>
                    </ul>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Cambia Password e Accedi</button>
            </form>

            <div style="text-align: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                <a href="../public/logout.php" class="text-muted" style="font-size: 14px;">Esci</a>
            </div>
        </div>
    </div>

    <script>
        const passwordInput = document.getElementById('nuova_password');
        const confermaInput = document.getElementById('conferma_password');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        const matchText = document.getElementById('matchText');

        // Verifica forza password
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;

            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;

            strengthBar.className = 'password-strength-bar';

            if (strength <= 2) {
                strengthBar.classList.add('strength-weak');
                strengthText.textContent = 'Password debole';
                strengthText.style.color = '#ef4444';
            } else if (strength <= 3) {
                strengthBar.classList.add('strength-medium');
                strengthText.textContent = 'Password media';
                strengthText.style.color = '#f59e0b';
            } else {
                strengthBar.classList.add('strength-strong');
                strengthText.textContent = 'Password forte';
                strengthText.style.color = '#10b981';
            }

            checkMatch();
        });

        // Verifica corrispondenza password
        confermaInput.addEventListener('input', checkMatch);

        function checkMatch() {
            if (confermaInput.value === '') {
                matchText.textContent = '';
                return;
            }

            if (passwordInput.value === confermaInput.value) {
                matchText.textContent = '✓ Le password coincidono';
                matchText.style.color = '#10b981';
            } else {
                matchText.textContent = '✗ Le password non coincidono';
                matchText.style.color = '#ef4444';
            }
        }
    </script>
</body>
</html>
