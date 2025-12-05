<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

$db = getDB();

// Gestione form
if (isPost()) {
    $azione = post('azione');

    if ($azione === 'aggiungi') {
        $username = post('username');
        $password = post('password');
        $nome = post('nome');
        $cognome = post('cognome');
        $email = post('email');
        $classe = post('classe');
        $ruolo = post('ruolo');

        $errors = [];
        if (empty($username)) $errors[] = 'Username obbligatorio';
        if (empty($password)) $errors[] = 'Password obbligatoria';
        if (empty($nome)) $errors[] = 'Nome obbligatorio';
        if (empty($cognome)) $errors[] = 'Cognome obbligatorio';
        if (empty($email) || !isValidEmail($email)) $errors[] = 'Email non valida';

        if (empty($errors)) {
            try {
                $stmt = $db->prepare("INSERT INTO utenti (username, password, nome, cognome, email, classe, ruolo) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$username, hashPassword($password), $nome, $cognome, $email, $classe, $ruolo]);
                setSuccessMessage('Utente creato con successo');
                redirect('utenti.php');
            } catch (PDOException $e) {
                setErrorMessage('Errore durante la creazione: ' . $e->getMessage());
            }
        } else {
            setErrorMessage(implode('<br>', $errors));
        }
    }
    elseif ($azione === 'modifica') {
        $user_id = post('user_id');
        $username = post('username');
        $nome = post('nome');
        $cognome = post('cognome');
        $email = post('email');
        $classe = post('classe');
        $ruolo = post('ruolo');
        $password = post('password');

        $errors = [];
        if (empty($username)) $errors[] = 'Username obbligatorio';
        if (empty($nome)) $errors[] = 'Nome obbligatorio';
        if (empty($cognome)) $errors[] = 'Cognome obbligatorio';
        if (empty($email) || !isValidEmail($email)) $errors[] = 'Email non valida';

        if (empty($errors)) {
            try {
                // Se la password è fornita, aggiornala
                if (!empty($password)) {
                    $stmt = $db->prepare("UPDATE utenti SET username = ?, password = ?, nome = ?, cognome = ?, email = ?, classe = ?, ruolo = ? WHERE id = ?");
                    $stmt->execute([$username, hashPassword($password), $nome, $cognome, $email, $classe, $ruolo, $user_id]);
                } else {
                    $stmt = $db->prepare("UPDATE utenti SET username = ?, nome = ?, cognome = ?, email = ?, classe = ?, ruolo = ? WHERE id = ?");
                    $stmt->execute([$username, $nome, $cognome, $email, $classe, $ruolo, $user_id]);
                }
                setSuccessMessage('Utente modificato con successo');
                redirect('utenti.php');
            } catch (PDOException $e) {
                setErrorMessage('Errore durante la modifica: ' . $e->getMessage());
            }
        } else {
            setErrorMessage(implode('<br>', $errors));
        }
    }
    elseif ($azione === 'toggle_attivo') {
        $user_id = post('user_id');
        if ($user_id != getCurrentUserId()) {
            $stmt = $db->prepare("UPDATE utenti SET attivo = NOT attivo WHERE id = ?");
            $stmt->execute([$user_id]);
            setSuccessMessage('Stato utente modificato');
            redirect('utenti.php');
        } else {
            setErrorMessage('Non puoi disattivare il tuo account');
        }
    }
    elseif ($azione === 'elimina') {
        $user_id = post('user_id');
        if ($user_id != getCurrentUserId()) {
            $stmt = $db->prepare("DELETE FROM utenti WHERE id = ?");
            $stmt->execute([$user_id]);
            setSuccessMessage('Utente eliminato con successo');
            redirect('utenti.php');
        } else {
            setErrorMessage('Non puoi eliminare il tuo account');
        }
    }
}

// Carica utenti
$stmt = $db->query("SELECT * FROM utenti ORDER BY ruolo, cognome, nome");
$utenti = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Utenti - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
    <div class="dashboard">
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            <div class="topbar">
                <h1>Gestione Utenti</h1>
                <div class="topbar-actions">
                    <button onclick="document.getElementById('modalAggiungi').classList.add('active')" class="btn btn-primary">+ Nuovo Utente</button>
                    <div class="user-info">
                        <div class="user-avatar"><?php echo strtoupper(substr(getCurrentUserFullName(), 0, 1)); ?></div>
                    </div>
                    <a href="../public/logout.php" class="btn btn-secondary btn-sm">Logout</a>
                </div>
            </div>

            <?php if ($success = getSuccessMessage()): ?>
                <div class="alert alert-success"><?php echo e($success); ?></div>
            <?php endif; ?>

            <?php if ($error = getErrorMessage()): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3>Elenco Utenti</h3>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Nome Completo</th>
                                    <th>Email</th>
                                    <th>Classe</th>
                                    <th>Ruolo</th>
                                    <th>Stato</th>
                                    <th>Data Creazione</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($utenti as $utente): ?>
                                    <tr>
                                        <td><?php echo $utente['id']; ?></td>
                                        <td><strong><?php echo e($utente['username']); ?></strong></td>
                                        <td><?php echo e($utente['nome'] . ' ' . $utente['cognome']); ?></td>
                                        <td><?php echo e($utente['email']); ?></td>
                                        <td>
                                            <?php if ($utente['classe'] && $utente['ruolo'] === 'studente'): ?>
                                                <span class="badge badge-info"><?php echo e($utente['classe']); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $badge_class = $utente['ruolo'] === 'admin' ? 'danger' : ($utente['ruolo'] === 'docente' ? 'primary' : 'success');
                                            ?>
                                            <span class="badge badge-<?php echo $badge_class; ?>"><?php echo ucfirst($utente['ruolo']); ?></span>
                                        </td>
                                        <td>
                                            <?php if ($utente['attivo']): ?>
                                                <span class="badge badge-success">Attivo</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Inattivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo formatDate($utente['data_creazione']); ?></td>
                                        <td>
                                            <div class="table-actions">
                                                <button onclick="modificaUtente(<?php echo htmlspecialchars(json_encode($utente), ENT_QUOTES, 'UTF-8'); ?>)" class="btn btn-primary btn-sm">✏️ Modifica</button>

                                                <?php if ($utente['id'] != getCurrentUserId()): ?>
                                                    <form method="POST" action="" style="display: inline;">
                                                        <input type="hidden" name="azione" value="toggle_attivo">
                                                        <input type="hidden" name="user_id" value="<?php echo $utente['id']; ?>">
                                                        <button type="submit" class="btn btn-warning btn-sm">
                                                            <?php echo $utente['attivo'] ? '🔒 Disattiva' : '✅ Attiva'; ?>
                                                        </button>
                                                    </form>

                                                    <form method="POST" action="" style="display: inline;">
                                                        <input type="hidden" name="azione" value="elimina">
                                                        <input type="hidden" name="user_id" value="<?php echo $utente['id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Eliminare questo utente?')">🗑️ Elimina</button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Aggiungi Utente -->
    <div id="modalAggiungi" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Aggiungi Nuovo Utente</h3>
                <button class="modal-close" onclick="document.getElementById('modalAggiungi').classList.remove('active')">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="azione" value="aggiungi">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nome">Nome *</label>
                            <input type="text" id="nome" name="nome" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="cognome">Cognome *</label>
                            <input type="text" id="cognome" name="cognome" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="classe">Classe (solo per studenti)</label>
                        <input type="text" id="classe" name="classe" class="form-control" placeholder="es. 5A, 3B, ecc.">
                        <small class="text-muted">Lascia vuoto per docenti e admin</small>
                    </div>
                    <div class="form-group">
                        <label for="ruolo">Ruolo *</label>
                        <select id="ruolo" name="ruolo" class="form-control" required>
                            <option value="studente">Studente</option>
                            <option value="docente">Docente</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalAggiungi').classList.remove('active')">Annulla</button>
                    <button type="submit" class="btn btn-primary">Crea Utente</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Modifica Utente -->
    <div id="modalModifica" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Modifica Utente</h3>
                <button class="modal-close" onclick="document.getElementById('modalModifica').classList.remove('active')">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="azione" value="modifica">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_username">Username *</label>
                        <input type="text" id="edit_username" name="username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_password">Nuova Password (lascia vuoto per non modificare)</label>
                        <input type="password" id="edit_password" name="password" class="form-control">
                        <small class="text-muted">Compila solo se vuoi cambiare la password</small>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_nome">Nome *</label>
                            <input type="text" id="edit_nome" name="nome" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_cognome">Cognome *</label>
                            <input type="text" id="edit_cognome" name="cognome" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="edit_email">Email *</label>
                        <input type="email" id="edit_email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_classe">Classe (solo per studenti)</label>
                        <input type="text" id="edit_classe" name="classe" class="form-control" placeholder="es. 5A, 3B, ecc.">
                        <small class="text-muted">Lascia vuoto per docenti e admin</small>
                    </div>
                    <div class="form-group">
                        <label for="edit_ruolo">Ruolo *</label>
                        <select id="edit_ruolo" name="ruolo" class="form-control" required>
                            <option value="studente">Studente</option>
                            <option value="docente">Docente</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalModifica').classList.remove('active')">Annulla</button>
                    <button type="submit" class="btn btn-primary">Salva Modifiche</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function modificaUtente(utente) {
            // Popola i campi del modal con i dati dell'utente
            document.getElementById('edit_user_id').value = utente.id;
            document.getElementById('edit_username').value = utente.username;
            document.getElementById('edit_nome').value = utente.nome;
            document.getElementById('edit_cognome').value = utente.cognome;
            document.getElementById('edit_email').value = utente.email;
            document.getElementById('edit_classe').value = utente.classe || '';
            document.getElementById('edit_ruolo').value = utente.ruolo;
            document.getElementById('edit_password').value = '';

            // Apri il modal
            document.getElementById('modalModifica').classList.add('active');
        }

        // Chiudi modal quando si clicca sull'overlay
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('active');
            }
        });
    </script>

    <?php include __DIR__ . '/../includes/footer-scripts.php'; ?>
</body>
</html>
