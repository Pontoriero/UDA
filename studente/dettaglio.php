<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('studente');

$db = getDB();
$studente_id = getCurrentUserId();
$prova_id = get('prova_id', 0);

if (!$prova_id) {
    setErrorMessage('ID prova non valido');
    redirect('index.php');
}

// Carica informazioni prova
$stmt = $db->prepare("
    SELECT p.*, g.nome as griglia_nome, g.descrizione as griglia_descrizione,
           u.nome as docente_nome, u.cognome as docente_cognome
    FROM prove p
    JOIN griglie g ON p.griglia_id = g.id
    JOIN utenti u ON p.docente_id = u.id
    WHERE p.id = ?
");
$stmt->execute([$prova_id]);
$prova = $stmt->fetch();

if (!$prova) {
    setErrorMessage('Prova non trovata');
    redirect('index.php');
}

// Carica valutazioni dello studente per questa prova con dettagli criteri e livelli
$stmt = $db->prepare("
    SELECT v.*, c.nome as criterio_nome, c.descrizione as criterio_descrizione, c.peso,
           l.nome as livello_nome, l.descrizione as livello_descrizione, l.punteggio as livello_punteggio
    FROM valutazioni v
    JOIN criteri c ON v.criterio_id = c.id
    JOIN livelli l ON v.livello_id = l.id
    WHERE v.prova_id = ? AND v.studente_id = ?
    ORDER BY c.ordine
");
$stmt->execute([$prova_id, $studente_id]);
$valutazioni = $stmt->fetchAll();

// Calcola voto medio ponderato
$somma_punteggi_ponderati = 0;
$somma_pesi = 0;
foreach ($valutazioni as $val) {
    $somma_punteggi_ponderati += $val['punteggio'] * $val['peso'];
    $somma_pesi += $val['peso'];
}
$voto_medio = $somma_pesi > 0 ? $somma_punteggi_ponderati / $somma_pesi : 0;
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dettaglio Valutazione - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        .voto-finale {
            text-align: center;
            padding: 30px;
            background: linear-gradient(135deg, var(--success-color) 0%, #059669 100%);
            color: white;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .voto-finale h2 {
            font-size: 48px;
            margin: 10px 0;
        }
        .criterio-dettaglio {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            background: white;
        }
        .criterio-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary-color);
        }
        .livello-raggiunto {
            background: #d1fae5;
            border-left: 4px solid var(--success-color);
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            <div class="topbar">
                <h1>Dettaglio Valutazione</h1>
                <div class="topbar-actions">
                    <a href="index.php" class="btn btn-secondary btn-sm">← Torna alle Valutazioni</a>
                    <div class="user-info">
                        <div class="user-avatar"><?php echo strtoupper(substr(getCurrentUserFullName(), 0, 1)); ?></div>
                    </div>
                    <a href="../public/logout.php" class="btn btn-secondary btn-sm">Logout</a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div>
                        <h3><?php echo e($prova['nome']); ?></h3>
                        <p style="margin: 5px 0 0 0; color: var(--text-muted);">
                            Griglia: <?php echo e($prova['griglia_nome']); ?> |
                            Docente: <?php echo e($prova['docente_nome'] . ' ' . $prova['docente_cognome']); ?>
                        </p>
                    </div>
                    <span class="badge badge-primary">Data: <?php echo formatDate($prova['data_prova']); ?></span>
                </div>
                <?php if ($prova['descrizione']): ?>
                    <div class="card-body">
                        <strong>Descrizione:</strong> <?php echo e($prova['descrizione']); ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (empty($valutazioni)): ?>
                <div class="card">
                    <div class="card-body">
                        <p class="text-muted">Nessuna valutazione disponibile per questa prova.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="voto-finale">
                    <p style="font-size: 18px; margin-bottom: 0;">Voto Medio Ponderato</p>
                    <h2><?php echo formatNumber($voto_medio); ?> / 10</h2>
                    <p style="font-size: 14px; opacity: 0.9;">Calcolato sulla base dei pesi dei criteri</p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Dettaglio per Criterio</h3>
                    </div>
                    <div class="card-body">
                        <?php foreach ($valutazioni as $val): ?>
                            <div class="criterio-dettaglio">
                                <div class="criterio-header">
                                    <div>
                                        <h4><?php echo e($val['criterio_nome']); ?></h4>
                                        <?php if ($val['criterio_descrizione']): ?>
                                            <p style="color: var(--text-muted); margin: 5px 0 0 0;">
                                                <?php echo e($val['criterio_descrizione']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <div style="text-align: right;">
                                        <span class="badge badge-secondary">Peso: <?php echo formatNumber($val['peso']); ?></span>
                                        <div style="font-size: 24px; color: var(--success-color); font-weight: bold; margin-top: 5px;">
                                            <?php echo formatNumber($val['punteggio']); ?> / 10
                                        </div>
                                    </div>
                                </div>

                                <div class="livello-raggiunto">
                                    <strong>Livello Raggiunto: <?php echo e($val['livello_nome']); ?></strong>
                                    <span class="badge badge-success">Punteggio: <?php echo formatNumber($val['livello_punteggio']); ?></span>
                                    <p style="margin: 10px 0 0 0; color: var(--text-color);">
                                        <?php echo e($val['livello_descrizione']); ?>
                                    </p>
                                </div>

                                <?php if ($val['note']): ?>
                                    <div style="margin-top: 15px; padding: 15px; background: var(--light-color); border-radius: 5px;">
                                        <strong>Note del docente:</strong>
                                        <p style="margin: 5px 0 0 0;"><?php echo e($val['note']); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
