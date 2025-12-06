<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('studente');
checkCambioPasswordObbligatorio();

$db = getDB();
$studente_id = getCurrentUserId();

// Carica tutte le valutazioni
$stmt = $db->prepare("
    SELECT
        p.id as prova_id,
        p.nome as prova_nome,
        p.data_prova,
        p.descrizione as prova_descrizione,
        g.nome as griglia_nome,
        g.materia,
        u.nome as docente_nome,
        u.cognome as docente_cognome,
        (SELECT (SUM(v2.punteggio) / SUM(c.peso)) * 10
         FROM valutazioni v2
         JOIN criteri c ON v2.criterio_id = c.id
         WHERE v2.prova_id = p.id AND v2.studente_id = ?) as voto_medio,
        MAX(v.data_valutazione) as data_valutazione
    FROM valutazioni v
    JOIN prove p ON v.prova_id = p.id
    JOIN griglie g ON p.griglia_id = g.id
    JOIN utenti u ON p.docente_id = u.id
    WHERE v.studente_id = ?
    GROUP BY p.id
    ORDER BY p.data_prova DESC, v.data_valutazione DESC
");
$stmt->execute([$studente_id, $studente_id]);
$valutazioni = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Storico Completo - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
    <div class="dashboard">
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            <div class="topbar">
                <h1>Storico Completo</h1>
                <div class="topbar-actions">
                    <div class="user-info">
                        <div class="user-avatar"><?php echo strtoupper(substr(getCurrentUserFullName(), 0, 1)); ?></div>
                        <div>
                            <strong><?php echo e(getCurrentUserFullName()); ?></strong>
                        </div>
                    </div>
                    <a href="../public/logout.php" class="btn btn-secondary btn-sm">Logout</a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Tutte le Valutazioni</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($valutazioni)): ?>
                        <p class="text-muted">Nessuna valutazione disponibile.</p>
                    <?php else: ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Data Prova</th>
                                        <th>Nome Prova</th>
                                        <th>Materia</th>
                                        <th>Griglia</th>
                                        <th>Docente</th>
                                        <th>Voto Medio</th>
                                        <th>Data Valutazione</th>
                                        <th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($valutazioni as $val): ?>
                                        <tr>
                                            <td><?php echo formatDate($val['data_prova']); ?></td>
                                            <td><strong><?php echo e($val['prova_nome']); ?></strong></td>
                                            <td><?php echo e($val['materia']); ?></td>
                                            <td><?php echo e($val['griglia_nome']); ?></td>
                                            <td><?php echo e($val['docente_nome'] . ' ' . $val['docente_cognome']); ?></td>
                                            <td>
                                                <strong style="font-size: 18px; color: var(--success-color);">
                                                    <?php echo formatNumber($val['voto_medio']); ?>
                                                </strong>
                                            </td>
                                            <td><?php echo formatDateTime($val['data_valutazione']); ?></td>
                                            <td>
                                                <a href="dettaglio.php?prova_id=<?php echo $val['prova_id']; ?>" class="btn btn-primary btn-sm">Dettaglio</a>
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
