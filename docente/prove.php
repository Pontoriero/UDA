<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('docente');

$db = getDB();
$docente_id = getCurrentUserId();

// Gestione creazione/modifica/eliminazione prova
if (isPost()) {
    $azione = post('azione', 'aggiungi');

    if ($azione === 'aggiungi') {
        $nome = post('nome');
        $descrizione = post('descrizione');
        $griglia_id = post('griglia_id');
        $data_prova = post('data_prova');

        $errors = [];
        if (empty($nome)) $errors[] = 'Il nome è obbligatorio';
        if (empty($griglia_id)) $errors[] = 'Seleziona una griglia';

        if (empty($errors)) {
            try {
                $stmt = $db->prepare("INSERT INTO prove (nome, descrizione, griglia_id, data_prova, docente_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$nome, $descrizione, $griglia_id, $data_prova, $docente_id]);
                setSuccessMessage('Prova creata con successo');
                redirect('prove.php');
            } catch (Exception $e) {
                setErrorMessage('Errore: ' . $e->getMessage());
            }
        } else {
            setErrorMessage(implode('<br>', $errors));
        }
    }
    elseif ($azione === 'modifica') {
        $prova_id = post('prova_id');
        $nome = post('nome');
        $descrizione = post('descrizione');
        $griglia_id = post('griglia_id');
        $data_prova = post('data_prova');

        $errors = [];
        if (empty($nome)) $errors[] = 'Il nome è obbligatorio';
        if (empty($griglia_id)) $errors[] = 'Seleziona una griglia';

        if (empty($errors)) {
            try {
                // Verifica che la prova appartenga al docente
                $stmt = $db->prepare("UPDATE prove SET nome = ?, descrizione = ?, griglia_id = ?, data_prova = ? WHERE id = ? AND docente_id = ?");
                $stmt->execute([$nome, $descrizione, $griglia_id, $data_prova, $prova_id, $docente_id]);
                setSuccessMessage('Prova modificata con successo');
                redirect('prove.php');
            } catch (Exception $e) {
                setErrorMessage('Errore: ' . $e->getMessage());
            }
        } else {
            setErrorMessage(implode('<br>', $errors));
        }
    }
    elseif ($azione === 'elimina') {
        $prova_id = post('prova_id');

        try {
            // Elimina prima le valutazioni associate
            $stmt = $db->prepare("DELETE FROM valutazioni WHERE prova_id = ?");
            $stmt->execute([$prova_id]);

            // Poi elimina la prova (solo se appartiene al docente)
            $stmt = $db->prepare("DELETE FROM prove WHERE id = ? AND docente_id = ?");
            $stmt->execute([$prova_id, $docente_id]);

            setSuccessMessage('Prova eliminata con successo');
            redirect('prove.php');
        } catch (Exception $e) {
            setErrorMessage('Errore durante l\'eliminazione: ' . $e->getMessage());
        }
    }
}

// Carica griglie attive
$stmt = $db->query("SELECT * FROM griglie WHERE attiva = 1 ORDER BY nome");
$griglie = $stmt->fetchAll();

// Carica prove del docente
$stmt = $db->prepare("
    SELECT p.*, g.nome as griglia_nome,
    (SELECT COUNT(DISTINCT studente_id) FROM valutazioni WHERE prova_id = p.id) as num_studenti
    FROM prove p
    JOIN griglie g ON p.griglia_id = g.id
    WHERE p.docente_id = ?
    ORDER BY p.data_creazione DESC
");
$stmt->execute([$docente_id]);
$prove = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Le Mie Prove - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
    <div class="dashboard">
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            <div class="topbar">
                <h1>Le Mie Prove</h1>
                <div class="topbar-actions">
                    <button onclick="document.getElementById('modalNuovaProva').classList.add('active')" class="btn btn-primary">+ Nuova Prova</button>
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
                    <h3>Elenco Prove</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($prove)): ?>
                        <p class="text-muted">Nessuna prova creata. Clicca su "Nuova Prova" per iniziare.</p>
                    <?php else: ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nome Prova</th>
                                        <th>Griglia Utilizzata</th>
                                        <th>Data Prova</th>
                                        <th>Studenti Valutati</th>
                                        <th>Data Creazione</th>
                                        <th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($prove as $prova): ?>
                                        <tr>
                                            <td><?php echo $prova['id']; ?></td>
                                            <td><strong><?php echo e($prova['nome']); ?></strong></td>
                                            <td><?php echo e($prova['griglia_nome']); ?></td>
                                            <td><?php echo formatDate($prova['data_prova']); ?></td>
                                            <td><?php echo $prova['num_studenti']; ?></td>
                                            <td><?php echo formatDate($prova['data_creazione']); ?></td>
                                            <td>
                                                <div class="table-actions">
                                                    <a href="valuta.php?prova_id=<?php echo $prova['id']; ?>" class="btn btn-primary btn-sm">📝 Valuta</a>
                                                    <?php if ($prova['num_studenti'] > 0): ?>
                                                        <a href="report-prova.php?prova_id=<?php echo $prova['id']; ?>" class="btn btn-success btn-sm">📊 Report</a>
                                                    <?php endif; ?>
                                                    <button onclick="modificaProva(<?php echo htmlspecialchars(json_encode($prova), ENT_QUOTES, 'UTF-8'); ?>)" class="btn btn-warning btn-sm">✏️ Modifica</button>
                                                    <form method="POST" action="" style="display: inline;">
                                                        <input type="hidden" name="azione" value="elimina">
                                                        <input type="hidden" name="prova_id" value="<?php echo $prova['id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Eliminare questa prova? Tutte le valutazioni associate verranno perse!')">🗑️ Elimina</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nuova Prova -->
    <div id="modalNuovaProva" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Crea Nuova Prova</h3>
                <button class="modal-close" onclick="document.getElementById('modalNuovaProva').classList.remove('active')">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="azione" value="aggiungi">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="nome">Nome Prova *</label>
                        <input type="text" id="nome" name="nome" class="form-control" required
                               placeholder="es. Verifica Unità 1">
                    </div>

                    <div class="form-group">
                        <label for="griglia_id">Griglia di Valutazione *</label>
                        <select id="griglia_id" name="griglia_id" class="form-control" required>
                            <option value="">Seleziona una griglia...</option>
                            <?php foreach ($griglie as $griglia): ?>
                                <option value="<?php echo $griglia['id']; ?>">
                                    <?php echo e($griglia['nome'] . ' - ' . $griglia['materia']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="data_prova">Data Prova</label>
                        <input type="date" id="data_prova" name="data_prova" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="descrizione">Descrizione</label>
                        <textarea id="descrizione" name="descrizione" class="form-control" rows="3"
                                  placeholder="Descrizione della prova o dell'UDA"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalNuovaProva').classList.remove('active')">Annulla</button>
                    <button type="submit" class="btn btn-primary">Crea Prova</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Modifica Prova -->
    <div id="modalModificaProva" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Modifica Prova</h3>
                <button class="modal-close" onclick="document.getElementById('modalModificaProva').classList.remove('active')">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="azione" value="modifica">
                <input type="hidden" name="prova_id" id="edit_prova_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_nome">Nome Prova *</label>
                        <input type="text" id="edit_nome" name="nome" class="form-control" required
                               placeholder="es. Verifica Unità 1">
                    </div>

                    <div class="form-group">
                        <label for="edit_griglia_id">Griglia di Valutazione *</label>
                        <select id="edit_griglia_id" name="griglia_id" class="form-control" required>
                            <option value="">Seleziona una griglia...</option>
                            <?php foreach ($griglie as $griglia): ?>
                                <option value="<?php echo $griglia['id']; ?>">
                                    <?php echo e($griglia['nome'] . ' - ' . $griglia['materia']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="edit_data_prova">Data Prova</label>
                        <input type="date" id="edit_data_prova" name="data_prova" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="edit_descrizione">Descrizione</label>
                        <textarea id="edit_descrizione" name="descrizione" class="form-control" rows="3"
                                  placeholder="Descrizione della prova o dell'UDA"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalModificaProva').classList.remove('active')">Annulla</button>
                    <button type="submit" class="btn btn-primary">Salva Modifiche</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function modificaProva(prova) {
            document.getElementById('edit_prova_id').value = prova.id;
            document.getElementById('edit_nome').value = prova.nome;
            document.getElementById('edit_griglia_id').value = prova.griglia_id;
            document.getElementById('edit_data_prova').value = prova.data_prova || '';
            document.getElementById('edit_descrizione').value = prova.descrizione || '';
            document.getElementById('modalModificaProva').classList.add('active');
        }

        // Chiudi modal su click overlay
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('active');
            }
        });
    </script>

    <?php include __DIR__ . '/../includes/footer-scripts.php'; ?>
</body>
</html>
