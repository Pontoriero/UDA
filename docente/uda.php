<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('docente');

$db = getDB();
$docente_id = getCurrentUserId();

// Gestione CRUD UDA
if (isPost()) {
    $azione = post('azione', 'aggiungi');

    if ($azione === 'aggiungi') {
        $nome = post('nome');
        $descrizione = post('descrizione');
        $anno_scolastico = post('anno_scolastico');
        $data_inizio = post('data_inizio');
        $data_fine = post('data_fine');

        $errors = [];
        if (empty($nome)) $errors[] = 'Il nome è obbligatorio';

        if (empty($errors)) {
            try {
                $stmt = $db->prepare("INSERT INTO uda (nome, descrizione, docente_id, anno_scolastico, data_inizio, data_fine) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$nome, $descrizione, $docente_id, $anno_scolastico, $data_inizio, $data_fine]);
                setSuccessMessage('UDA creata con successo');
                redirect('uda.php');
            } catch (Exception $e) {
                setErrorMessage('Errore: ' . $e->getMessage());
            }
        } else {
            setErrorMessage(implode('<br>', $errors));
        }
    }
    elseif ($azione === 'modifica') {
        $uda_id = post('uda_id');
        $nome = post('nome');
        $descrizione = post('descrizione');
        $anno_scolastico = post('anno_scolastico');
        $data_inizio = post('data_inizio');
        $data_fine = post('data_fine');

        $errors = [];
        if (empty($nome)) $errors[] = 'Il nome è obbligatorio';

        if (empty($errors)) {
            try {
                $stmt = $db->prepare("UPDATE uda SET nome = ?, descrizione = ?, anno_scolastico = ?, data_inizio = ?, data_fine = ? WHERE id = ? AND docente_id = ?");
                $stmt->execute([$nome, $descrizione, $anno_scolastico, $data_inizio, $data_fine, $uda_id, $docente_id]);
                setSuccessMessage('UDA modificata con successo');
                redirect('uda.php');
            } catch (Exception $e) {
                setErrorMessage('Errore: ' . $e->getMessage());
            }
        } else {
            setErrorMessage(implode('<br>', $errors));
        }
    }
    elseif ($azione === 'elimina') {
        $uda_id = post('uda_id');

        try {
            // Verifica se ci sono prove associate
            $stmt = $db->prepare("SELECT COUNT(*) as num_prove FROM prove WHERE uda_id = ?");
            $stmt->execute([$uda_id]);
            $result = $stmt->fetch();

            if ($result['num_prove'] > 0) {
                setErrorMessage('Impossibile eliminare: ci sono ' . $result['num_prove'] . ' prove associate a questa UDA. Rimuovi prima le prove.');
            } else {
                $stmt = $db->prepare("DELETE FROM uda WHERE id = ? AND docente_id = ?");
                $stmt->execute([$uda_id, $docente_id]);
                setSuccessMessage('UDA eliminata con successo');
            }
            redirect('uda.php');
        } catch (Exception $e) {
            setErrorMessage('Errore durante l\'eliminazione: ' . $e->getMessage());
        }
    }
    elseif ($azione === 'toggle_attivo') {
        $uda_id = post('uda_id');
        try {
            $stmt = $db->prepare("UPDATE uda SET attivo = NOT attivo WHERE id = ? AND docente_id = ?");
            $stmt->execute([$uda_id, $docente_id]);
            setSuccessMessage('Stato UDA modificato');
            redirect('uda.php');
        } catch (Exception $e) {
            setErrorMessage('Errore: ' . $e->getMessage());
        }
    }
}

// Carica UDA del docente
$stmt = $db->prepare("
    SELECT u.*,
    (SELECT COUNT(*) FROM prove WHERE uda_id = u.id) as num_prove
    FROM uda u
    WHERE u.docente_id = ?
    ORDER BY u.data_creazione DESC
");
$stmt->execute([$docente_id]);
$uda_list = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Le Mie UDA - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
    <div class="dashboard">
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            <div class="topbar">
                <h1>📚 Le Mie UDA</h1>
                <div class="topbar-actions">
                    <button onclick="document.getElementById('modalNuovaUda').classList.add('active')" class="btn btn-primary">+ Nuova UDA</button>
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
                    <h3>Elenco UDA</h3>
                    <span class="text-muted"><?php echo count($uda_list); ?> UDA</span>
                </div>
                <div class="card-body">
                    <?php if (empty($uda_list)): ?>
                        <p class="text-muted">Nessuna UDA creata. Clicca su "Nuova UDA" per iniziare.</p>
                    <?php else: ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Nome UDA</th>
                                        <th>Anno Scolastico</th>
                                        <th>Periodo</th>
                                        <th>Prove Associate</th>
                                        <th>Stato</th>
                                        <th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($uda_list as $uda): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo e($uda['nome']); ?></strong>
                                                <?php if ($uda['descrizione']): ?>
                                                    <br><small class="text-muted"><?php echo e(substr($uda['descrizione'], 0, 100)); ?><?php echo strlen($uda['descrizione']) > 100 ? '...' : ''; ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $uda['anno_scolastico'] ? e($uda['anno_scolastico']) : '<span class="text-muted">-</span>'; ?></td>
                                            <td>
                                                <?php if ($uda['data_inizio'] || $uda['data_fine']): ?>
                                                    <?php echo $uda['data_inizio'] ? formatDate($uda['data_inizio']) : '...'; ?>
                                                    <br>▼<br>
                                                    <?php echo $uda['data_fine'] ? formatDate($uda['data_fine']) : '...'; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Non definito</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($uda['num_prove'] > 0): ?>
                                                    <span class="badge badge-info"><?php echo $uda['num_prove']; ?> prove</span>
                                                <?php else: ?>
                                                    <span class="text-muted">Nessuna prova</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($uda['attivo']): ?>
                                                    <span class="badge badge-success">Attiva</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">Archiviata</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="table-actions">
                                                    <a href="prove.php?uda_id=<?php echo $uda['id']; ?>" class="btn btn-primary btn-sm">📝 Prove</a>
                                                    <button onclick="modificaUda(<?php echo htmlspecialchars(json_encode($uda), ENT_QUOTES, 'UTF-8'); ?>)" class="btn btn-warning btn-sm">✏️ Modifica</button>
                                                    <form method="POST" action="" style="display: inline;">
                                                        <input type="hidden" name="azione" value="toggle_attivo">
                                                        <input type="hidden" name="uda_id" value="<?php echo $uda['id']; ?>">
                                                        <button type="submit" class="btn btn-secondary btn-sm">
                                                            <?php echo $uda['attivo'] ? '📦 Archivia' : '✅ Attiva'; ?>
                                                        </button>
                                                    </form>
                                                    <form method="POST" action="" style="display: inline;">
                                                        <input type="hidden" name="azione" value="elimina">
                                                        <input type="hidden" name="uda_id" value="<?php echo $uda['id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Eliminare questa UDA? Assicurati che non ci siano prove associate!')">🗑️ Elimina</button>
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

    <!-- Modal Nuova UDA -->
    <div id="modalNuovaUda" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Crea Nuova UDA</h3>
                <button class="modal-close" onclick="document.getElementById('modalNuovaUda').classList.remove('active')">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="azione" value="aggiungi">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="nome">Nome UDA *</label>
                        <input type="text" id="nome" name="nome" class="form-control" required
                               placeholder="es. Cittadinanza Digitale, Il Metodo Scientifico">
                    </div>

                    <div class="form-group">
                        <label for="descrizione">Descrizione</label>
                        <textarea id="descrizione" name="descrizione" class="form-control" rows="4"
                                  placeholder="Descrizione dettagliata dell'Unità Di Apprendimento"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="anno_scolastico">Anno Scolastico</label>
                        <input type="text" id="anno_scolastico" name="anno_scolastico" class="form-control"
                               placeholder="es. 2024/2025" value="<?php echo date('Y') . '/' . (date('Y') + 1); ?>">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="data_inizio">Data Inizio</label>
                            <input type="date" id="data_inizio" name="data_inizio" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="data_fine">Data Fine</label>
                            <input type="date" id="data_fine" name="data_fine" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalNuovaUda').classList.remove('active')">Annulla</button>
                    <button type="submit" class="btn btn-primary">Crea UDA</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Modifica UDA -->
    <div id="modalModificaUda" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Modifica UDA</h3>
                <button class="modal-close" onclick="document.getElementById('modalModificaUda').classList.remove('active')">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="azione" value="modifica">
                <input type="hidden" name="uda_id" id="edit_uda_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_nome">Nome UDA *</label>
                        <input type="text" id="edit_nome" name="nome" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_descrizione">Descrizione</label>
                        <textarea id="edit_descrizione" name="descrizione" class="form-control" rows="4"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="edit_anno_scolastico">Anno Scolastico</label>
                        <input type="text" id="edit_anno_scolastico" name="anno_scolastico" class="form-control">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_data_inizio">Data Inizio</label>
                            <input type="date" id="edit_data_inizio" name="data_inizio" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="edit_data_fine">Data Fine</label>
                            <input type="date" id="edit_data_fine" name="data_fine" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalModificaUda').classList.remove('active')">Annulla</button>
                    <button type="submit" class="btn btn-primary">Salva Modifiche</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function modificaUda(uda) {
            document.getElementById('edit_uda_id').value = uda.id;
            document.getElementById('edit_nome').value = uda.nome;
            document.getElementById('edit_descrizione').value = uda.descrizione || '';
            document.getElementById('edit_anno_scolastico').value = uda.anno_scolastico || '';
            document.getElementById('edit_data_inizio').value = uda.data_inizio || '';
            document.getElementById('edit_data_fine').value = uda.data_fine || '';
            document.getElementById('modalModificaUda').classList.add('active');
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
