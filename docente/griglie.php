<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('docente');

$db = getDB();

// Carica griglie attive
$stmt = $db->prepare("
    SELECT g.*, u.nome, u.cognome,
    (SELECT COUNT(*) FROM criteri WHERE griglia_id = g.id) as num_criteri
    FROM griglie g
    JOIN utenti u ON g.creato_da = u.id
    WHERE g.attiva = 1
    ORDER BY g.nome
");
$stmt->execute();
$griglie = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Griglie Disponibili - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
    <div class="dashboard">
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            <div class="topbar">
                <h1>Griglie Disponibili</h1>
                <div class="topbar-actions">
                    <div class="user-info">
                        <div class="user-avatar"><?php echo strtoupper(substr(getCurrentUserFullName(), 0, 1)); ?></div>
                    </div>
                    <a href="../public/logout.php" class="btn btn-secondary btn-sm">Logout</a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Griglie di Valutazione</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($griglie)): ?>
                        <p class="text-muted">Nessuna griglia disponibile.</p>
                    <?php else: ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Materia</th>
                                        <th>Anno Scolastico</th>
                                        <th>Criteri</th>
                                        <th>Creata da</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($griglie as $griglia): ?>
                                        <tr>
                                            <td><strong><?php echo e($griglia['nome']); ?></strong></td>
                                            <td><?php echo e($griglia['materia']); ?></td>
                                            <td><?php echo e($griglia['anno_scolastico']); ?></td>
                                            <td><?php echo $griglia['num_criteri']; ?></td>
                                            <td><?php echo e($griglia['nome'] . ' ' . $griglia['cognome']); ?></td>
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
</body>
</html>
