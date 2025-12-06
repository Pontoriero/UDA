<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('studente');
checkCambioPasswordObbligatorio();

$db = getDB();
$studente_id = getCurrentUserId();

// Statistiche
$stmt = $db->prepare("SELECT COUNT(DISTINCT prova_id) FROM valutazioni WHERE studente_id = ?");
$stmt->execute([$studente_id]);
$totale_prove = $stmt->fetchColumn();

$stmt = $db->prepare("
    SELECT AVG(
        (SELECT (SUM(v2.punteggio) / SUM(c.peso)) * 10
         FROM valutazioni v2
         JOIN criteri c ON v2.criterio_id = c.id
         WHERE v2.prova_id = v.prova_id AND v2.studente_id = v.studente_id)
    ) as media
    FROM (SELECT DISTINCT prova_id, studente_id FROM valutazioni WHERE studente_id = ?) v
");
$stmt->execute([$studente_id]);
$media_generale = $stmt->fetchColumn() ?? 0;

// Valutazioni recenti
$stmt = $db->prepare("
    SELECT
        p.id as prova_id,
        p.nome as prova_nome,
        p.data_prova,
        g.nome as griglia_nome,
        u.nome as docente_nome,
        u.cognome as docente_cognome,
        (SELECT (SUM(v2.punteggio) / SUM(c.peso)) * 10
         FROM valutazioni v2
         JOIN criteri c ON v2.criterio_id = c.id
         WHERE v2.prova_id = p.id AND v2.studente_id = ?) as voto_medio
    FROM valutazioni v
    JOIN prove p ON v.prova_id = p.id
    JOIN griglie g ON p.griglia_id = g.id
    JOIN utenti u ON p.docente_id = u.id
    WHERE v.studente_id = ?
    GROUP BY p.id
    ORDER BY v.data_valutazione DESC
    LIMIT 10
");
$stmt->execute([$studente_id, $studente_id]);
$valutazioni_recenti = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Studente - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
    <div class="dashboard">
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            <div class="topbar">
                <h1>Le Mie Valutazioni</h1>
                <div class="topbar-actions">
                    <div class="user-info">
                        <div class="user-avatar"><?php echo strtoupper(substr(getCurrentUserFullName(), 0, 1)); ?></div>
                        <div>
                            <strong><?php echo e(getCurrentUserFullName()); ?></strong>
                            <div style="font-size: 12px; color: #6b7280;">Studente</div>
                        </div>
                    </div>
                    <a href="../public/logout.php" class="btn btn-secondary btn-sm">Logout</a>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon primary">📝</div>
                    <div class="stat-details">
                        <h4><?php echo $totale_prove; ?></h4>
                        <p>Prove Valutate</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon success">⭐</div>
                    <div class="stat-details">
                        <h4><?php echo formatNumber($media_generale); ?></h4>
                        <p>Media Generale</p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Valutazioni Recenti</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($valutazioni_recenti)): ?>
                        <p class="text-muted">Nessuna valutazione disponibile al momento.</p>
                    <?php else: ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Prova</th>
                                        <th>Griglia</th>
                                        <th>Docente</th>
                                        <th>Data Prova</th>
                                        <th>Voto Medio</th>
                                        <th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($valutazioni_recenti as $val): ?>
                                        <tr>
                                            <td><strong><?php echo e($val['prova_nome']); ?></strong></td>
                                            <td><?php echo e($val['griglia_nome']); ?></td>
                                            <td><?php echo e($val['docente_nome'] . ' ' . $val['docente_cognome']); ?></td>
                                            <td><?php echo formatDate($val['data_prova']); ?></td>
                                            <td>
                                                <strong style="font-size: 18px; color: var(--success-color);">
                                                    <?php echo formatNumber($val['voto_medio']); ?>
                                                </strong>
                                            </td>
                                            <td>
                                                <a href="dettaglio.php?prova_id=<?php echo $val['prova_id']; ?>" class="btn btn-primary btn-sm">Visualizza Dettaglio</a>
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
    <?php include __DIR__ . '/../includes/footer-scripts.php'; ?>
</body>
</html>
