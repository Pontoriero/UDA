<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('docente');

$db = getDB();
$docente_id = getCurrentUserId();
$classe_selezionata = get('classe', '');

// Carica tutte le classi disponibili
$stmt = $db->query("SELECT DISTINCT classe FROM utenti WHERE ruolo = 'studente' AND classe IS NOT NULL AND classe != '' ORDER BY classe");
$classi = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Se una classe è selezionata, carica le prove relative
$prove_classe = [];
$studenti_classe = [];

if ($classe_selezionata) {
    // Carica prove che hanno almeno uno studente della classe valutato
    $stmt = $db->prepare("
        SELECT DISTINCT p.id, p.nome, p.descrizione, p.data_prova, g.nome as griglia_nome,
               (SELECT COUNT(DISTINCT v.studente_id)
                FROM valutazioni v
                JOIN utenti u ON v.studente_id = u.id
                WHERE v.prova_id = p.id AND u.classe = ?) as num_studenti_classe,
               (SELECT COUNT(DISTINCT v.studente_id)
                FROM valutazioni v
                WHERE v.prova_id = p.id) as num_studenti_totali
        FROM prove p
        JOIN griglie g ON p.griglia_id = g.id
        WHERE p.docente_id = ?
        AND EXISTS (
            SELECT 1 FROM valutazioni v
            JOIN utenti u ON v.studente_id = u.id
            WHERE v.prova_id = p.id AND u.classe = ?
        )
        ORDER BY p.data_prova DESC
    ");
    $stmt->execute([$classe_selezionata, $docente_id, $classe_selezionata]);
    $prove_classe = $stmt->fetchAll();

    // Carica studenti della classe
    $stmt = $db->prepare("SELECT id, username, nome, cognome FROM utenti WHERE classe = ? AND ruolo = 'studente' ORDER BY cognome, nome");
    $stmt->execute([$classe_selezionata]);
    $studenti_classe = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classi - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        .classi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .classe-card {
            background: white;
            padding: 30px 20px;
            border-radius: 8px;
            box-shadow: var(--shadow);
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            border: 3px solid transparent;
            text-decoration: none;
            color: inherit;
        }

        .classe-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
        }

        .classe-card.active {
            border-color: var(--primary-color);
            background: var(--primary-color);
            color: white;
        }

        .classe-nome {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .classe-studenti {
            font-size: 14px;
            color: var(--text-muted);
        }

        .classe-card.active .classe-studenti {
            color: white;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            <div class="topbar">
                <h1>👥 Gestione Classi</h1>
                <div class="topbar-actions">
                    <div class="user-info">
                        <div class="user-avatar"><?php echo strtoupper(substr(getCurrentUserFullName(), 0, 1)); ?></div>
                    </div>
                    <a href="../public/logout.php" class="btn btn-secondary btn-sm">Logout</a>
                </div>
            </div>

            <?php if (empty($classi)): ?>
                <div class="card">
                    <div class="card-body">
                        <p class="text-muted text-center">
                            Nessuna classe disponibile. Gli studenti devono essere assegnati a una classe dall'amministratore.
                        </p>
                    </div>
                </div>
            <?php else: ?>
                <h3 style="margin-bottom: 20px;">Seleziona una Classe</h3>
                <div class="classi-grid">
                    <?php foreach ($classi as $classe): ?>
                        <?php
                        // Conta studenti nella classe
                        $stmt = $db->prepare("SELECT COUNT(*) FROM utenti WHERE classe = ? AND ruolo = 'studente'");
                        $stmt->execute([$classe]);
                        $num_studenti = $stmt->fetchColumn();
                        ?>
                        <a href="?classe=<?php echo urlencode($classe); ?>"
                           class="classe-card <?php echo ($classe === $classe_selezionata) ? 'active' : ''; ?>">
                            <div class="classe-nome"><?php echo e($classe); ?></div>
                            <div class="classe-studenti"><?php echo $num_studenti; ?> studenti</div>
                        </a>
                    <?php endforeach; ?>
                </div>

                <?php if ($classe_selezionata): ?>
                    <!-- Studenti della Classe -->
                    <div class="card">
                        <div class="card-header">
                            <h3>👨‍🎓 Studenti Classe <?php echo e($classe_selezionata); ?></h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($studenti_classe)): ?>
                                <p class="text-muted">Nessuno studente in questa classe.</p>
                            <?php else: ?>
                                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px;">
                                    <?php foreach ($studenti_classe as $studente): ?>
                                        <div style="padding: 10px; background: var(--light-color); border-radius: 5px;">
                                            <strong><?php echo e($studente['cognome'] . ' ' . $studente['nome']); ?></strong>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Prove della Classe -->
                    <div class="card">
                        <div class="card-header">
                            <h3>📝 Prove Classe <?php echo e($classe_selezionata); ?></h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($prove_classe)): ?>
                                <p class="text-muted">Nessuna prova con studenti di questa classe valutati.</p>
                            <?php else: ?>
                                <div class="table-container">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Nome Prova</th>
                                                <th>Griglia</th>
                                                <th>Data Prova</th>
                                                <th>Studenti Classe</th>
                                                <th>Totale Studenti</th>
                                                <th>Azioni</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($prove_classe as $prova): ?>
                                                <tr>
                                                    <td><strong><?php echo e($prova['nome']); ?></strong></td>
                                                    <td><?php echo e($prova['griglia_nome']); ?></td>
                                                    <td><?php echo formatDate($prova['data_prova']); ?></td>
                                                    <td>
                                                        <span class="badge badge-primary">
                                                            <?php echo $prova['num_studenti_classe']; ?> studenti
                                                        </span>
                                                    </td>
                                                    <td><?php echo $prova['num_studenti_totali']; ?></td>
                                                    <td>
                                                        <div style="display: flex; gap: 5px;">
                                                            <a href="report-classe.php?prova_id=<?php echo $prova['id']; ?>&classe=<?php echo urlencode($classe_selezionata); ?>"
                                                               class="btn btn-success btn-sm">📊 Report Classe</a>
                                                            <a href="valuta.php?prova_id=<?php echo $prova['id']; ?>"
                                                               class="btn btn-primary btn-sm">Valuta</a>
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
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php include __DIR__ . '/../includes/footer-scripts.php'; ?>
</body>
</html>
