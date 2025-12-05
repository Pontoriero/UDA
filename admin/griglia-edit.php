<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

$db = getDB();
$griglia_id = get('id', 0);
$editing = $griglia_id > 0;

$griglia = null;
$criteri = [];

if ($editing) {
    // Carica griglia esistente
    $stmt = $db->prepare("SELECT * FROM griglie WHERE id = ?");
    $stmt->execute([$griglia_id]);
    $griglia = $stmt->fetch();

    if (!$griglia) {
        setErrorMessage('Griglia non trovata');
        redirect('griglie.php');
    }

    // Carica criteri e livelli
    $stmt = $db->prepare("SELECT * FROM criteri WHERE griglia_id = ? ORDER BY ordine");
    $stmt->execute([$griglia_id]);
    $criteri = $stmt->fetchAll();

    foreach ($criteri as &$criterio) {
        $stmt = $db->prepare("SELECT * FROM livelli WHERE criterio_id = ? ORDER BY ordine");
        $stmt->execute([$criterio['id']]);
        $criterio['livelli'] = $stmt->fetchAll();
    }
}

if (isPost()) {
    $nome = post('nome');
    $descrizione = post('descrizione');
    $anno_scolastico = post('anno_scolastico');
    $materia = post('materia');
    $attiva = post('attiva', 0);

    $errors = [];

    if (empty($nome)) $errors[] = 'Il nome è obbligatorio';
    if (empty($materia)) $errors[] = 'La materia è obbligatoria';
    if (empty($anno_scolastico)) $errors[] = 'L\'anno scolastico è obbligatorio';

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            if ($editing) {
                // Aggiorna griglia esistente
                $stmt = $db->prepare("
                    UPDATE griglie SET
                    nome = ?, descrizione = ?, anno_scolastico = ?, materia = ?, attiva = ?
                    WHERE id = ?
                ");
                $stmt->execute([$nome, $descrizione, $anno_scolastico, $materia, $attiva, $griglia_id]);
            } else {
                // Crea nuova griglia
                $stmt = $db->prepare("
                    INSERT INTO griglie (nome, descrizione, anno_scolastico, materia, attiva, creato_da)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$nome, $descrizione, $anno_scolastico, $materia, $attiva, getCurrentUserId()]);
                $griglia_id = $db->lastInsertId();
            }

            $db->commit();
            setSuccessMessage($editing ? 'Griglia aggiornata con successo' : 'Griglia creata con successo');
            redirect('griglia-criteri.php?id=' . $griglia_id);
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'Errore durante il salvataggio: ' . $e->getMessage();
        }
    }

    if (!empty($errors)) {
        setErrorMessage(implode('<br>', $errors));
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $editing ? 'Modifica' : 'Nuova'; ?> Griglia - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
    <div class="dashboard">
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            <div class="topbar">
                <h1><?php echo $editing ? 'Modifica' : 'Nuova'; ?> Griglia di Valutazione</h1>
                <div class="topbar-actions">
                    <a href="griglie.php" class="btn btn-secondary btn-sm">← Torna alle Griglie</a>
                    <div class="user-info">
                        <div class="user-avatar"><?php echo strtoupper(substr(getCurrentUserFullName(), 0, 1)); ?></div>
                    </div>
                    <a href="../public/logout.php" class="btn btn-secondary btn-sm">Logout</a>
                </div>
            </div>

            <?php if ($error = getErrorMessage()): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3>Informazioni Griglia</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nome">Nome Griglia *</label>
                                <input type="text" id="nome" name="nome" class="form-control" required
                                       value="<?php echo e($griglia['nome'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="materia">Materia *</label>
                                <input type="text" id="materia" name="materia" class="form-control" required
                                       value="<?php echo e($griglia['materia'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="anno_scolastico">Anno Scolastico *</label>
                                <input type="text" id="anno_scolastico" name="anno_scolastico" class="form-control"
                                       placeholder="es. 2024/2025" required
                                       value="<?php echo e($griglia['anno_scolastico'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="attiva">Stato</label>
                                <select id="attiva" name="attiva" class="form-control">
                                    <option value="1" <?php echo (!$griglia || $griglia['attiva']) ? 'selected' : ''; ?>>Attiva</option>
                                    <option value="0" <?php echo ($griglia && !$griglia['attiva']) ? 'selected' : ''; ?>>Inattiva</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="descrizione">Descrizione</label>
                            <textarea id="descrizione" name="descrizione" class="form-control" rows="4"><?php echo e($griglia['descrizione'] ?? ''); ?></textarea>
                        </div>

                        <div class="btn-group">
                            <button type="submit" class="btn btn-primary">
                                <?php echo $editing ? 'Salva Modifiche' : 'Crea Griglia e Aggiungi Criteri'; ?>
                            </button>
                            <a href="griglie.php" class="btn btn-secondary">Annulla</a>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($editing): ?>
                <div class="card">
                    <div class="card-header">
                        <h3>Criteri di Valutazione</h3>
                        <div style="display: flex; gap: 10px;">
                            <a href="griglia-builder.php?id=<?php echo $griglia_id; ?>" class="btn btn-success btn-sm">🚀 Builder Veloce</a>
                            <a href="griglia-criteri.php?id=<?php echo $griglia_id; ?>" class="btn btn-primary btn-sm">📝 Gestione Classica</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($criteri)): ?>
                            <p class="text-muted">Nessun criterio definito. <a href="griglia-criteri.php?id=<?php echo $griglia_id; ?>">Aggiungi il primo criterio</a></p>
                        <?php else: ?>
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Criterio</th>
                                            <th>Peso</th>
                                            <th>Livelli</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($criteri as $criterio): ?>
                                            <tr>
                                                <td><strong><?php echo e($criterio['nome']); ?></strong></td>
                                                <td><?php echo formatNumber($criterio['peso']); ?></td>
                                                <td><?php echo count($criterio['livelli']); ?> livelli</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php include __DIR__ . '/../includes/footer-scripts.php'; ?>
</body>
</html>
