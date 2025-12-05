<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

$db = getDB();

// Ottieni statistiche
$stmt = $db->query("SELECT COUNT(*) FROM griglie WHERE attiva = 1");
$totale_griglie = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM utenti WHERE ruolo = 'docente' AND attivo = 1");
$totale_docenti = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM utenti WHERE ruolo = 'studente' AND attivo = 1");
$totale_studenti = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM prove");
$totale_prove = $stmt->fetchColumn();

// Griglie recenti
$stmt = $db->prepare("
    SELECT g.*, u.nome, u.cognome
    FROM griglie g
    JOIN utenti u ON g.creato_da = u.id
    ORDER BY g.data_creazione DESC
    LIMIT 5
");
$stmt->execute();
$griglie_recenti = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
    <div class="dashboard">
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            <div class="topbar">
                <h1>Dashboard Admin</h1>
                <div class="topbar-actions">
                    <div class="user-info">
                        <div class="user-avatar"><?php echo strtoupper(substr(getCurrentUserFullName(), 0, 1)); ?></div>
                        <div>
                            <strong><?php echo e(getCurrentUserFullName()); ?></strong>
                            <div style="font-size: 12px; color: #6b7280;">Amministratore</div>
                        </div>
                    </div>
                    <a href="../public/logout.php" class="btn btn-secondary btn-sm">Logout</a>
                </div>
            </div>

            <?php if ($success = getSuccessMessage()): ?>
                <div class="alert alert-success"><?php echo e($success); ?></div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon primary">📋</div>
                    <div class="stat-details">
                        <h4><?php echo $totale_griglie; ?></h4>
                        <p>Griglie Attive</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon success">👨‍🏫</div>
                    <div class="stat-details">
                        <h4><?php echo $totale_docenti; ?></h4>
                        <p>Docenti</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon warning">👨‍🎓</div>
                    <div class="stat-details">
                        <h4><?php echo $totale_studenti; ?></h4>
                        <p>Studenti</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon danger">📝</div>
                    <div class="stat-details">
                        <h4><?php echo $totale_prove; ?></h4>
                        <p>Prove Totali</p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Griglie Recenti</h3>
                    <a href="griglie.php" class="btn btn-primary btn-sm">Vedi Tutte</a>
                </div>
                <div class="card-body">
                    <?php if (empty($griglie_recenti)): ?>
                        <p class="text-muted">Nessuna griglia creata.</p>
                    <?php else: ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Materia</th>
                                        <th>Anno Scolastico</th>
                                        <th>Creato da</th>
                                        <th>Data Creazione</th>
                                        <th>Stato</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($griglie_recenti as $griglia): ?>
                                        <tr>
                                            <td><?php echo e($griglia['nome']); ?></td>
                                            <td><?php echo e($griglia['materia']); ?></td>
                                            <td><?php echo e($griglia['anno_scolastico']); ?></td>
                                            <td><?php echo e($griglia['nome'] . ' ' . $griglia['cognome']); ?></td>
                                            <td><?php echo formatDateTime($griglia['data_creazione']); ?></td>
                                            <td>
                                                <?php if ($griglia['attiva']): ?>
                                                    <span class="badge badge-success">Attiva</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">Inattiva</span>
                                                <?php endif; ?>
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
