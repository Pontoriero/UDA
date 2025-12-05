<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

$db = getDB();

// Ottieni tutte le prove
$stmt = $db->query("
    SELECT p.*, g.nome as griglia_nome, u.nome as docente_nome, u.cognome as docente_cognome,
    (SELECT COUNT(DISTINCT studente_id) FROM valutazioni WHERE prova_id = p.id) as num_studenti
    FROM prove p
    JOIN griglie g ON p.griglia_id = g.id
    JOIN utenti u ON p.docente_id = u.id
    ORDER BY p.data_creazione DESC
");
$prove = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
    <div class="dashboard">
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            <div class="topbar">
                <h1>📊 Report e Statistiche</h1>
                <div class="topbar-actions">
                    <div class="user-info">
                        <div class="user-avatar"><?php echo strtoupper(substr(getCurrentUserFullName(), 0, 1)); ?></div>
                    </div>
                    <a href="../public/logout.php" class="btn btn-secondary btn-sm">Logout</a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Seleziona Prova da Analizzare</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($prove)): ?>
                        <p class="text-muted">Nessuna prova disponibile. Le prove vengono create dai docenti.</p>
                    <?php else: ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nome Prova</th>
                                        <th>Griglia</th>
                                        <th>Docente</th>
                                        <th>Data Prova</th>
                                        <th>Studenti Valutati</th>
                                        <th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($prove as $prova): ?>
                                        <tr>
                                            <td><?php echo $prova['id']; ?></td>
                                            <td><strong><?php echo e($prova['nome']); ?></strong></td>
                                            <td><?php echo e($prova['griglia_nome']); ?></td>
                                            <td><?php echo e($prova['docente_nome'] . ' ' . $prova['docente_cognome']); ?></td>
                                            <td><?php echo formatDate($prova['data_prova']); ?></td>
                                            <td><span class="badge badge-primary"><?php echo $prova['num_studenti']; ?> studenti</span></td>
                                            <td>
                                                <a href="report-dettaglio.php?prova_id=<?php echo $prova['id']; ?>" class="btn btn-primary btn-sm">📊 Vedi Report</a>
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
