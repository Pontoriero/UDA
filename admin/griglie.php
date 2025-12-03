<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

$db = getDB();

// Ottieni tutte le griglie
$stmt = $db->prepare("
    SELECT g.*, u.nome, u.cognome,
    (SELECT COUNT(*) FROM criteri WHERE griglia_id = g.id) as num_criteri
    FROM griglie g
    JOIN utenti u ON g.creato_da = u.id
    ORDER BY g.data_creazione DESC
");
$stmt->execute();
$griglie = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Griglie di Valutazione - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
    <div class="dashboard">
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            <div class="topbar">
                <h1>Griglie di Valutazione</h1>
                <div class="topbar-actions">
                    <a href="griglia-edit.php" class="btn btn-primary">+ Nuova Griglia</a>
                    <div class="user-info">
                        <div class="user-avatar"><?php echo strtoupper(substr(getCurrentUserFullName(), 0, 1)); ?></div>
                        <div>
                            <strong><?php echo e(getCurrentUserFullName()); ?></strong>
                        </div>
                    </div>
                    <a href="../public/logout.php" class="btn btn-secondary btn-sm">Logout</a>
                </div>
            </div>

            <?php if ($success = getSuccessMessage()): ?>
                <div class="alert alert-success"><?php echo e($success); ?></div>
            <?php endif; ?>

            <?php if ($error = getErrorMessage()): ?>
                <div class="alert alert-error"><?php echo e($error); ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3>Elenco Griglie</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($griglie)): ?>
                        <p class="text-muted">Nessuna griglia creata. <a href="griglia-edit.php">Crea la prima griglia</a></p>
                    <?php else: ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nome</th>
                                        <th>Materia</th>
                                        <th>Anno Scolastico</th>
                                        <th>Criteri</th>
                                        <th>Creato da</th>
                                        <th>Data</th>
                                        <th>Stato</th>
                                        <th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($griglie as $griglia): ?>
                                        <tr>
                                            <td><?php echo $griglia['id']; ?></td>
                                            <td><strong><?php echo e($griglia['nome']); ?></strong></td>
                                            <td><?php echo e($griglia['materia']); ?></td>
                                            <td><?php echo e($griglia['anno_scolastico']); ?></td>
                                            <td><?php echo $griglia['num_criteri']; ?></td>
                                            <td><?php echo e($griglia['nome'] . ' ' . $griglia['cognome']); ?></td>
                                            <td><?php echo formatDate($griglia['data_creazione']); ?></td>
                                            <td>
                                                <?php if ($griglia['attiva']): ?>
                                                    <span class="badge badge-success">Attiva</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">Inattiva</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="table-actions">
                                                    <a href="griglia-view.php?id=<?php echo $griglia['id']; ?>" class="btn btn-primary btn-sm">Visualizza</a>
                                                    <a href="griglia-edit.php?id=<?php echo $griglia['id']; ?>" class="btn btn-warning btn-sm">Modifica</a>
                                                    <a href="griglia-delete.php?id=<?php echo $griglia['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Sei sicuro di voler eliminare questa griglia?')">Elimina</a>
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
</body>
</html>
