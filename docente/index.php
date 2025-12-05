<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('docente');

$db = getDB();
$docente_id = getCurrentUserId();

// Statistiche
$stmt = $db->prepare("SELECT COUNT(*) FROM prove WHERE docente_id = ?");
$stmt->execute([$docente_id]);
$totale_prove = $stmt->fetchColumn();

$stmt = $db->prepare("
    SELECT COUNT(DISTINCT v.studente_id)
    FROM valutazioni v
    JOIN prove p ON v.prova_id = p.id
    WHERE p.docente_id = ?
");
$stmt->execute([$docente_id]);
$studenti_valutati = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM griglie WHERE attiva = 1");
$griglie_disponibili = $stmt->fetchColumn();

// Prove recenti
$stmt = $db->prepare("
    SELECT p.*, g.nome as griglia_nome,
    (SELECT COUNT(DISTINCT studente_id) FROM valutazioni WHERE prova_id = p.id) as num_studenti
    FROM prove p
    JOIN griglie g ON p.griglia_id = g.id
    WHERE p.docente_id = ?
    ORDER BY p.data_creazione DESC
    LIMIT 5
");
$stmt->execute([$docente_id]);
$prove_recenti = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Docente - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
    <div class="dashboard">
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            <div class="topbar">
                <h1>Dashboard Docente</h1>
                <div class="topbar-actions">
                    <div class="user-info">
                        <div class="user-avatar"><?php echo strtoupper(substr(getCurrentUserFullName(), 0, 1)); ?></div>
                        <div>
                            <strong><?php echo e(getCurrentUserFullName()); ?></strong>
                            <div style="font-size: 12px; color: #6b7280;">Docente</div>
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
                    <div class="stat-icon primary">📝</div>
                    <div class="stat-details">
                        <h4><?php echo $totale_prove; ?></h4>
                        <p>Prove Create</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon success">👨‍🎓</div>
                    <div class="stat-details">
                        <h4><?php echo $studenti_valutati; ?></h4>
                        <p>Studenti Valutati</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon warning">📋</div>
                    <div class="stat-details">
                        <h4><?php echo $griglie_disponibili; ?></h4>
                        <p>Griglie Disponibili</p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Prove Recenti</h3>
                    <a href="prove.php" class="btn btn-primary btn-sm">+ Nuova Prova</a>
                </div>
                <div class="card-body">
                    <?php if (empty($prove_recenti)): ?>
                        <p class="text-muted">Nessuna prova creata. <a href="prove.php">Crea la prima prova</a></p>
                    <?php else: ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Nome Prova</th>
                                        <th>Griglia</th>
                                        <th>Data Prova</th>
                                        <th>Studenti Valutati</th>
                                        <th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($prove_recenti as $prova): ?>
                                        <tr>
                                            <td><strong><?php echo e($prova['nome']); ?></strong></td>
                                            <td><?php echo e($prova['griglia_nome']); ?></td>
                                            <td><?php echo formatDate($prova['data_prova']); ?></td>
                                            <td><?php echo $prova['num_studenti']; ?></td>
                                            <td>
                                                <a href="valuta.php?prova_id=<?php echo $prova['id']; ?>" class="btn btn-primary btn-sm">Valuta</a>
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
