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
$stmt = $db->prepare("SELECT g.*, u.nome, u.cognome FROM griglie g JOIN utenti u ON g.creato_da = u.id WHERE g.id = ?");
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
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizza Griglia - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        .criterio-box {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            background: #f9fafb;
        }
        .livello-box {
            background: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 4px solid var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            <div class="topbar">
                <h1><?php echo e($griglia['nome']); ?></h1>
                <div class="topbar-actions">
                    <a href="griglie.php" class="btn btn-secondary btn-sm">← Torna alle Griglie</a>
                    <a href="griglia-edit.php?id=<?php echo $griglia_id; ?>" class="btn btn-warning btn-sm">Modifica</a>
                    <div class="user-info">
                        <div class="user-avatar"><?php echo strtoupper(substr(getCurrentUserFullName(), 0, 1)); ?></div>
                    </div>
                    <a href="../public/logout.php" class="btn btn-secondary btn-sm">Logout</a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Informazioni Griglia</h3>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div><strong>Materia:</strong> <?php echo e($griglia['materia']); ?></div>
                        <div><strong>Anno Scolastico:</strong> <?php echo e($griglia['anno_scolastico']); ?></div>
                        <div><strong>Creata da:</strong> <?php echo e($griglia['nome'] . ' ' . $griglia['cognome']); ?></div>
                        <div><strong>Stato:</strong>
                            <?php if ($griglia['attiva']): ?>
                                <span class="badge badge-success">Attiva</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Inattiva</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($griglia['descrizione']): ?>
                        <div style="margin-top: 15px;">
                            <strong>Descrizione:</strong>
                            <p><?php echo e($griglia['descrizione']); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Criteri e Livelli</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($criteri)): ?>
                        <p class="text-muted">Nessun criterio definito.</p>
                    <?php else: ?>
                        <?php foreach ($criteri as $criterio): ?>
                            <div class="criterio-box">
                                <h4><?php echo e($criterio['nome']); ?>
                                    <span class="badge badge-primary">Peso: <?php echo formatNumber($criterio['peso']); ?></span>
                                </h4>
                                <?php if ($criterio['descrizione']): ?>
                                    <p style="color: var(--text-muted);"><?php echo e($criterio['descrizione']); ?></p>
                                <?php endif; ?>

                                <?php if (empty($criterio['livelli'])): ?>
                                    <p class="text-muted">Nessun livello definito.</p>
                                <?php else: ?>
                                    <div style="margin-top: 15px;">
                                        <strong>Livelli:</strong>
                                        <?php foreach ($criterio['livelli'] as $livello): ?>
                                            <div class="livello-box">
                                                <strong><?php echo e($livello['nome']); ?></strong>
                                                <span class="badge badge-info">Punteggio: <?php echo formatNumber($livello['punteggio']); ?></span>
                                                <p style="margin: 5px 0 0 0; color: var(--text-muted);">
                                                    <?php echo e($livello['descrizione']); ?>
                                                </p>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php include __DIR__ . '/../includes/footer-scripts.php'; ?>
</body>
</html>
