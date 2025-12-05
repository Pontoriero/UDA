<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('docente');

$db = getDB();
$docente_id = getCurrentUserId();
$prova_id = get('prova_id', 0);
$studente_id = get('studente_id', 0);

if (!$prova_id) {
    setErrorMessage('ID prova non valido');
    redirect('prove.php');
}

// Carica prova
$stmt = $db->prepare("
    SELECT p.*, g.nome as griglia_nome, g.id as griglia_id
    FROM prove p
    JOIN griglie g ON p.griglia_id = g.id
    WHERE p.id = ? AND p.docente_id = ?
");
$stmt->execute([$prova_id, $docente_id]);
$prova = $stmt->fetch();

if (!$prova) {
    setErrorMessage('Prova non trovata o non autorizzato');
    redirect('prove.php');
}

// Carica criteri e livelli
$stmt = $db->prepare("SELECT * FROM criteri WHERE griglia_id = ? ORDER BY ordine");
$stmt->execute([$prova['griglia_id']]);
$criteri = $stmt->fetchAll();

foreach ($criteri as &$criterio) {
    $stmt = $db->prepare("SELECT * FROM livelli WHERE criterio_id = ? ORDER BY punteggio DESC");
    $stmt->execute([$criterio['id']]);
    $criterio['livelli'] = $stmt->fetchAll();
}

// Carica studenti
$stmt = $db->query("SELECT id, username, nome, cognome FROM utenti WHERE ruolo = 'studente' AND attivo = 1 ORDER BY cognome, nome");
$studenti = $stmt->fetchAll();

// Se uno studente è selezionato, carica le sue valutazioni
$valutazioni_esistenti = [];
if ($studente_id) {
    $stmt = $db->prepare("SELECT * FROM valutazioni WHERE prova_id = ? AND studente_id = ?");
    $stmt->execute([$prova_id, $studente_id]);
    $valutazioni = $stmt->fetchAll();
    foreach ($valutazioni as $val) {
        $valutazioni_esistenti[$val['criterio_id']] = $val;
    }
}

// Salvataggio valutazione
if (isPost() && $studente_id) {
    try {
        $db->beginTransaction();

        foreach ($criteri as $criterio) {
            $livello_id = post('livello_' . $criterio['id']);
            $note = post('note_' . $criterio['id'], '');

            if ($livello_id) {
                // Trova il punteggio del livello
                $stmt = $db->prepare("SELECT punteggio FROM livelli WHERE id = ?");
                $stmt->execute([$livello_id]);
                $punteggio = $stmt->fetchColumn();

                // Inserisci o aggiorna valutazione
                $stmt = $db->prepare("
                    INSERT INTO valutazioni (prova_id, studente_id, criterio_id, livello_id, punteggio, note)
                    VALUES (?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE livello_id = ?, punteggio = ?, note = ?
                ");
                $stmt->execute([
                    $prova_id, $studente_id, $criterio['id'], $livello_id, $punteggio, $note,
                    $livello_id, $punteggio, $note
                ]);
            }
        }

        $db->commit();
        setSuccessMessage('Valutazione salvata con successo');
        redirect('valuta.php?prova_id=' . $prova_id . '&studente_id=' . $studente_id);
    } catch (Exception $e) {
        $db->rollBack();
        setErrorMessage('Errore durante il salvataggio: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Valuta Studenti - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        .studente-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }
        .studente-card {
            padding: 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }
        .studente-card:hover {
            border-color: var(--primary-color);
            background: var(--light-color);
        }
        .studente-card.selected {
            border-color: var(--primary-color);
            background: var(--primary-color);
            color: white;
        }
        .criterio-valutazione {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            background: white;
        }
        .livello-option {
            display: flex;
            align-items: start;
            padding: 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            margin: 10px 0;
            cursor: pointer;
            transition: all 0.3s;
        }
        .livello-option:hover {
            border-color: var(--primary-color);
            background: var(--light-color);
        }
        .livello-option input[type="radio"]:checked + .livello-content {
            border-left: 4px solid var(--success-color);
        }
        .livello-option input[type="radio"]:checked {
            accent-color: var(--success-color);
        }
        .livello-content {
            flex: 1;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            <div class="topbar">
                <h1>Valuta: <?php echo e($prova['nome']); ?></h1>
                <div class="topbar-actions">
                    <a href="prove.php" class="btn btn-secondary btn-sm">← Torna alle Prove</a>
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
                    <h3>Seleziona Studente</h3>
                </div>
                <div class="card-body">
                    <div class="studente-list">
                        <?php foreach ($studenti as $studente): ?>
                            <a href="valuta.php?prova_id=<?php echo $prova_id; ?>&studente_id=<?php echo $studente['id']; ?>"
                               class="studente-card <?php echo ($studente_id == $studente['id']) ? 'selected' : ''; ?>"
                               style="text-decoration: none; color: inherit;">
                                <strong><?php echo e($studente['cognome'] . ' ' . $studente['nome']); ?></strong>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <?php if ($studente_id): ?>
                <?php
                $stmt = $db->prepare("SELECT nome, cognome FROM utenti WHERE id = ?");
                $stmt->execute([$studente_id]);
                $studente_corrente = $stmt->fetch();
                ?>
                <form method="POST" action="">
                    <div class="card">
                        <div class="card-header">
                            <h3>Valutazione: <?php echo e($studente_corrente['nome'] . ' ' . $studente_corrente['cognome']); ?></h3>
                            <span class="badge badge-primary">Griglia: <?php echo e($prova['griglia_nome']); ?></span>
                        </div>
                        <div class="card-body">
                            <?php if (empty($criteri)): ?>
                                <p class="text-muted">Nessun criterio definito per questa griglia.</p>
                            <?php else: ?>
                                <?php foreach ($criteri as $criterio): ?>
                                    <div class="criterio-valutazione">
                                        <h4><?php echo e($criterio['nome']); ?>
                                            <span class="badge badge-secondary">Peso: <?php echo formatNumber($criterio['peso']); ?></span>
                                        </h4>
                                        <?php if ($criterio['descrizione']): ?>
                                            <p style="color: var(--text-muted); margin-bottom: 15px;"><?php echo e($criterio['descrizione']); ?></p>
                                        <?php endif; ?>

                                        <?php if (empty($criterio['livelli'])): ?>
                                            <p class="text-muted">Nessun livello definito per questo criterio.</p>
                                        <?php else: ?>
                                            <?php foreach ($criterio['livelli'] as $livello): ?>
                                                <?php
                                                $checked = isset($valutazioni_esistenti[$criterio['id']]) &&
                                                           $valutazioni_esistenti[$criterio['id']]['livello_id'] == $livello['id'];
                                                ?>
                                                <label class="livello-option">
                                                    <input type="radio"
                                                           name="livello_<?php echo $criterio['id']; ?>"
                                                           value="<?php echo $livello['id']; ?>"
                                                           <?php echo $checked ? 'checked' : ''; ?>>
                                                    <div class="livello-content">
                                                        <strong><?php echo e($livello['nome']); ?></strong>
                                                        <span class="badge badge-info">Punti: <?php echo formatNumber($livello['punteggio']); ?></span>
                                                        <p style="margin: 5px 0 0 0; color: var(--text-muted); font-size: 14px;">
                                                            <?php echo e($livello['descrizione']); ?>
                                                        </p>
                                                    </div>
                                                </label>
                                            <?php endforeach; ?>
                                        <?php endif; ?>

                                        <div class="form-group" style="margin-top: 15px;">
                                            <label>Note aggiuntive</label>
                                            <textarea name="note_<?php echo $criterio['id']; ?>" class="form-control" rows="2"
                                                      placeholder="Note facoltative per questo criterio"><?php
                                                echo isset($valutazioni_esistenti[$criterio['id']]) ?
                                                     e($valutazioni_esistenti[$criterio['id']]['note']) : '';
                                            ?></textarea>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <button type="submit" class="btn btn-primary btn-block">Salva Valutazione</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            <?php else: ?>
                <div class="card">
                    <div class="card-body">
                        <p class="text-center text-muted">Seleziona uno studente per iniziare la valutazione</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php include __DIR__ . '/../includes/footer-scripts.php'; ?>
</body>
</html>
