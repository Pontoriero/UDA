<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('docente');

$db = getDB();
$docente_id = getCurrentUserId();

// Gestione creazione prova
if (isPost()) {
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
                                                <div style="display: flex; gap: 5px;">
                                                    <a href="valuta.php?prova_id=<?php echo $prova['id']; ?>" class="btn btn-primary btn-sm">Valuta</a>
                                                    <?php if ($prova['num_studenti'] > 0): ?>
                                                        <a href="report-prova.php?prova_id=<?php echo $prova['id']; ?>" class="btn btn-success btn-sm">📊 Report</a>
                                                    <?php endif; ?>
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
</body>
</html>
