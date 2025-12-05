<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

$db = getDB();
$griglia_id = get('id', 0);

if (!$griglia_id) {
    setErrorMessage('ID griglia non valido');
    redirect('griglie.php');
}

// Carica griglia
$stmt = $db->prepare("SELECT * FROM griglie WHERE id = ?");
$stmt->execute([$griglia_id]);
$griglia = $stmt->fetch();

if (!$griglia) {
    setErrorMessage('Griglia non trovata');
    redirect('griglie.php');
}

// Carica criteri con livelli
$stmt = $db->prepare("SELECT * FROM criteri WHERE griglia_id = ? ORDER BY ordine");
$stmt->execute([$griglia_id]);
$criteri = $stmt->fetchAll();

foreach ($criteri as &$criterio) {
    $stmt = $db->prepare("SELECT * FROM livelli WHERE criterio_id = ? ORDER BY ordine");
    $stmt->execute([$criterio['id']]);
    $criterio['livelli'] = $stmt->fetchAll();
}

// Gestione azioni
if (isPost()) {
    $azione = post('azione');

    try {
        $db->beginTransaction();

        if ($azione === 'aggiungi_criterio') {
            $nome = post('nome');
            $descrizione = post('descrizione');
            $peso = post('peso', 1.0);

            if (empty($nome)) {
                throw new Exception('Il nome del criterio è obbligatorio');
            }

            // Ottieni ordine successivo
            $stmt = $db->prepare("SELECT COALESCE(MAX(ordine), 0) + 1 as next_ordine FROM criteri WHERE griglia_id = ?");
            $stmt->execute([$griglia_id]);
            $ordine = $stmt->fetchColumn();

            $stmt = $db->prepare("INSERT INTO criteri (griglia_id, nome, descrizione, peso, ordine) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$griglia_id, $nome, $descrizione, $peso, $ordine]);

            setSuccessMessage('Criterio aggiunto con successo');
        }
        elseif ($azione === 'aggiungi_livello') {
            $criterio_id = post('criterio_id');
            $nome = post('nome_livello');
            $descrizione = post('descrizione_livello');
            $punteggio = post('punteggio');

            if (empty($nome) || empty($descrizione)) {
                throw new Exception('Nome e descrizione del livello sono obbligatori');
            }

            // Ottieni ordine successivo
            $stmt = $db->prepare("SELECT COALESCE(MAX(ordine), 0) + 1 as next_ordine FROM livelli WHERE criterio_id = ?");
            $stmt->execute([$criterio_id]);
            $ordine = $stmt->fetchColumn();

            $stmt = $db->prepare("INSERT INTO livelli (criterio_id, nome, descrizione, punteggio, ordine) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$criterio_id, $nome, $descrizione, $punteggio, $ordine]);

            setSuccessMessage('Livello aggiunto con successo');
        }
        elseif ($azione === 'elimina_criterio') {
            $criterio_id = post('criterio_id');
            $stmt = $db->prepare("DELETE FROM criteri WHERE id = ? AND griglia_id = ?");
            $stmt->execute([$criterio_id, $griglia_id]);
            setSuccessMessage('Criterio eliminato con successo');
        }
        elseif ($azione === 'elimina_livello') {
            $livello_id = post('livello_id');
            $stmt = $db->prepare("DELETE FROM livelli WHERE id = ?");
            $stmt->execute([$livello_id]);
            setSuccessMessage('Livello eliminato con successo');
        }

        $db->commit();
        redirect('griglia-criteri.php?id=' . $griglia_id);
    } catch (Exception $e) {
        $db->rollBack();
        setErrorMessage('Errore: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Criteri - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        .criterio-section {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            background: #f9fafb;
        }
        .criterio-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary-color);
        }
        .livello-item {
            background: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 4px solid var(--primary-color);
            display: flex;
            justify-content: space-between;
            align-items: start;
        }
        .livello-content {
            flex: 1;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            <div class="topbar">
                <h1>Gestione Criteri: <?php echo e($griglia['nome']); ?></h1>
                <div class="topbar-actions">
                    <a href="griglia-edit.php?id=<?php echo $griglia_id; ?>" class="btn btn-secondary btn-sm">← Torna alla Griglia</a>
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

            <!-- Form Aggiungi Criterio -->
            <div class="card">
                <div class="card-header">
                    <h3>Aggiungi Nuovo Criterio</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="azione" value="aggiungi_criterio">
                        <div class="form-row">
                            <div class="form-group" style="flex: 2;">
                                <label for="nome">Nome Criterio *</label>
                                <input type="text" id="nome" name="nome" class="form-control" required
                                       placeholder="es. Conoscenza dei contenuti">
                            </div>
                            <div class="form-group">
                                <label for="peso">Peso</label>
                                <input type="number" id="peso" name="peso" class="form-control" step="0.01" min="0" value="1.00">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="descrizione">Descrizione</label>
                            <textarea id="descrizione" name="descrizione" class="form-control" rows="2"
                                      placeholder="Descrizione del criterio di valutazione"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Aggiungi Criterio</button>
                    </form>
                </div>
            </div>

            <!-- Elenco Criteri -->
            <?php if (empty($criteri)): ?>
                <div class="card">
                    <div class="card-body">
                        <p class="text-muted">Nessun criterio definito. Aggiungi il primo criterio utilizzando il form sopra.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($criteri as $criterio): ?>
                    <div class="criterio-section">
                        <div class="criterio-header">
                            <div>
                                <h3><?php echo e($criterio['nome']); ?> <span class="badge badge-primary">Peso: <?php echo formatNumber($criterio['peso']); ?></span></h3>
                                <?php if ($criterio['descrizione']): ?>
                                    <p style="color: var(--text-muted); margin-top: 5px;"><?php echo e($criterio['descrizione']); ?></p>
                                <?php endif; ?>
                            </div>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="azione" value="elimina_criterio">
                                <input type="hidden" name="criterio_id" value="<?php echo $criterio['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Eliminare questo criterio e tutti i suoi livelli?')">Elimina Criterio</button>
                            </form>
                        </div>

                        <!-- Form Aggiungi Livello -->
                        <div class="card" style="margin-bottom: 15px;">
                            <div class="card-header">
                                <h4 style="font-size: 16px;">Aggiungi Livello di Valutazione</h4>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <input type="hidden" name="azione" value="aggiungi_livello">
                                    <input type="hidden" name="criterio_id" value="<?php echo $criterio['id']; ?>">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Nome Livello *</label>
                                            <input type="text" name="nome_livello" class="form-control" required
                                                   placeholder="es. Eccellente">
                                        </div>
                                        <div class="form-group">
                                            <label>Punteggio *</label>
                                            <input type="number" name="punteggio" class="form-control" step="0.01" min="0" required
                                                   placeholder="es. 10">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Descrizione Livello *</label>
                                        <textarea name="descrizione_livello" class="form-control" rows="2" required
                                                  placeholder="Descrizione dettagliata del livello di competenza"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-success btn-sm">Aggiungi Livello</button>
                                </form>
                            </div>
                        </div>

                        <!-- Livelli Esistenti -->
                        <?php if (empty($criterio['livelli'])): ?>
                            <p class="text-muted">Nessun livello definito per questo criterio.</p>
                        <?php else: ?>
                            <h4 style="margin-bottom: 10px;">Livelli definiti:</h4>
                            <?php foreach ($criterio['livelli'] as $livello): ?>
                                <div class="livello-item">
                                    <div class="livello-content">
                                        <strong><?php echo e($livello['nome']); ?></strong>
                                        <span class="badge badge-info">Punteggio: <?php echo formatNumber($livello['punteggio']); ?></span>
                                        <p style="margin: 5px 0 0 0; color: var(--text-muted);"><?php echo e($livello['descrizione']); ?></p>
                                    </div>
                                    <form method="POST" action="" style="margin-left: 10px;">
                                        <input type="hidden" name="azione" value="elimina_livello">
                                        <input type="hidden" name="livello_id" value="<?php echo $livello['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Eliminare questo livello?')">×</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php include __DIR__ . '/../includes/footer-scripts.php'; ?>
</body>
</html>
