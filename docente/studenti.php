<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('docente');

$db = getDB();
$docente_id = getCurrentUserId();

// Carica studenti con statistiche
$stmt = $db->prepare("
    SELECT u.id, u.username, u.nome, u.cognome, u.email,
    (SELECT COUNT(DISTINCT v.prova_id)
     FROM valutazioni v
     JOIN prove p ON v.prova_id = p.id
     WHERE v.studente_id = u.id AND p.docente_id = ?) as num_prove,
    (SELECT AVG(
        (SELECT SUM(v2.punteggio * c.peso) / SUM(c.peso)
         FROM valutazioni v2
         JOIN criteri c ON v2.criterio_id = c.id
         WHERE v2.prova_id = v.prova_id AND v2.studente_id = v.studente_id)
     )
     FROM (SELECT DISTINCT prova_id, studente_id
           FROM valutazioni v
           JOIN prove p ON v.prova_id = p.id
           WHERE v.studente_id = u.id AND p.docente_id = ?) v
    ) as media
    FROM utenti u
    WHERE u.ruolo = 'studente' AND u.attivo = 1
    ORDER BY u.cognome, u.nome
");
$stmt->execute([$docente_id, $docente_id]);
$studenti = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Studenti - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
    <div class="dashboard">
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            <div class="topbar">
                <h1>Studenti</h1>
                <div class="topbar-actions">
                    <div class="user-info">
                        <div class="user-avatar"><?php echo strtoupper(substr(getCurrentUserFullName(), 0, 1)); ?></div>
                    </div>
                    <a href="../public/logout.php" class="btn btn-secondary btn-sm">Logout</a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Elenco Studenti</h3>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Cognome e Nome</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Prove Valutate</th>
                                    <th>Media</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($studenti as $studente): ?>
                                    <tr>
                                        <td><strong><?php echo e($studente['cognome'] . ' ' . $studente['nome']); ?></strong></td>
                                        <td><?php echo e($studente['username']); ?></td>
                                        <td><?php echo e($studente['email']); ?></td>
                                        <td><?php echo $studente['num_prove'] ?? 0; ?></td>
                                        <td>
                                            <?php if ($studente['media']): ?>
                                                <strong style="color: var(--success-color);">
                                                    <?php echo formatNumber($studente['media']); ?>
                                                </strong>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include __DIR__ . '/../includes/footer-scripts.php'; ?>
</body>
</html>
