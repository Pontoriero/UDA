<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('docente');

$db = getDB();
$docente_id = getCurrentUserId();
$studente_id = get('studente_id', 0);
$classe_filtro = get('classe', '');

if (!$studente_id) {
    setErrorMessage('ID studente non valido');
    redirect('studenti.php');
}

// Carica dati studente
$stmt = $db->prepare("SELECT * FROM utenti WHERE id = ? AND ruolo = 'studente'");
$stmt->execute([$studente_id]);
$studente = $stmt->fetch();

if (!$studente) {
    setErrorMessage('Studente non trovato');
    redirect('studenti.php');
}

// Carica tutte le prove valutate per questo studente (solo del docente corrente)
$stmt = $db->prepare("
    SELECT DISTINCT p.id, p.nome, p.descrizione, p.data_prova,
           g.nome as griglia_nome
    FROM prove p
    JOIN griglie g ON p.griglia_id = g.id
    JOIN valutazioni v ON v.prova_id = p.id
    WHERE v.studente_id = ? AND p.docente_id = ?
    ORDER BY p.data_prova DESC, p.nome
");
$stmt->execute([$studente_id, $docente_id]);
$prove = $stmt->fetchAll();

// Per ogni prova, carica i voti dettagliati
$risultati = [];
foreach ($prove as $prova) {
    // Carica valutazioni per questa prova
    $stmt = $db->prepare("
        SELECT c.nome as criterio_nome, c.peso,
               l.nome as livello_nome, v.punteggio, v.note
        FROM valutazioni v
        JOIN criteri c ON v.criterio_id = c.id
        JOIN livelli l ON v.livello_id = l.id
        WHERE v.prova_id = ? AND v.studente_id = ?
        ORDER BY c.ordine
    ");
    $stmt->execute([$prova['id'], $studente_id]);
    $valutazioni = $stmt->fetchAll();

    // Calcola media ponderata
    $somma_ponderata = 0;
    $somma_pesi = 0;
    foreach ($valutazioni as $val) {
        $somma_ponderata += $val['punteggio'] * $val['peso'];
        $somma_pesi += $val['peso'];
    }
    $media = $somma_pesi > 0 ? $somma_ponderata / $somma_pesi : 0;

    $risultati[] = [
        'prova' => $prova,
        'valutazioni' => $valutazioni,
        'media' => $media
    ];
}

// Calcola media generale
$medie = array_column($risultati, 'media');
$media_generale = !empty($medie) ? array_sum($medie) / count($medie) : 0;
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dettaglio Voti - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        .student-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .student-header h2 {
            margin: 0 0 10px 0;
        }
        .student-info {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        .student-info-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .prova-card {
            margin-bottom: 30px;
            border-left: 4px solid var(--primary-color);
        }
        .voti-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .voto-item {
            background: var(--light-color);
            padding: 15px;
            border-radius: 8px;
            border-left: 3px solid var(--info-color);
        }
        .voto-item h5 {
            margin: 0 0 10px 0;
            color: var(--dark-color);
        }
        .voto-item .livello {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 5px 0;
        }
        .media-badge {
            font-size: 24px;
            font-weight: bold;
            padding: 10px 20px;
            border-radius: 8px;
            display: inline-block;
        }
        .media-eccellente { background: #10b981; color: white; }
        .media-buona { background: #3b82f6; color: white; }
        .media-discreta { background: #f59e0b; color: white; }
        .media-sufficiente { background: #ef4444; color: white; }
    </style>
</head>
<body>
    <div class="dashboard">
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            <div class="topbar">
                <h1>Dettaglio Voti Studente</h1>
                <div class="topbar-actions">
                    <a href="studenti.php<?php echo $classe_filtro ? '?classe=' . urlencode($classe_filtro) : ''; ?>" class="btn btn-secondary btn-sm">← Torna all'Elenco</a>
                    <div class="user-info">
                        <div class="user-avatar"><?php echo strtoupper(substr(getCurrentUserFullName(), 0, 1)); ?></div>
                    </div>
                    <a href="../public/logout.php" class="btn btn-secondary btn-sm">Logout</a>
                </div>
            </div>

            <div class="student-header">
                <h2>👤 <?php echo e($studente['cognome'] . ' ' . $studente['nome']); ?></h2>
                <div class="student-info">
                    <div class="student-info-item">
                        <strong>Username:</strong> <?php echo e($studente['username']); ?>
                    </div>
                    <?php if (!empty($studente['classe'])): ?>
                        <div class="student-info-item">
                            <strong>Classe:</strong>
                            <span class="badge badge-info"><?php echo e($studente['classe']); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="student-info-item">
                        <strong>Email:</strong> <?php echo e($studente['email']); ?>
                    </div>
                    <div class="student-info-item">
                        <strong>Prove Valutate:</strong> <?php echo count($prove); ?>
                    </div>
                    <div class="student-info-item">
                        <strong>Media Generale:</strong>
                        <?php
                        $classe_media = 'media-sufficiente';
                        if ($media_generale >= 9) $classe_media = 'media-eccellente';
                        elseif ($media_generale >= 8) $classe_media = 'media-buona';
                        elseif ($media_generale >= 7) $classe_media = 'media-discreta';
                        ?>
                        <span class="media-badge <?php echo $classe_media; ?>">
                            <?php echo formatNumber($media_generale); ?>
                        </span>
                    </div>
                </div>
            </div>

            <?php if (empty($prove)): ?>
                <div class="card">
                    <div class="card-body">
                        <p class="text-center text-muted">Nessuna prova valutata per questo studente.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($risultati as $ris): ?>
                    <?php
                    $prova = $ris['prova'];
                    $valutazioni = $ris['valutazioni'];
                    $media = $ris['media'];
                    ?>
                    <div class="card prova-card">
                        <div class="card-header">
                            <div>
                                <h3><?php echo e($prova['nome']); ?></h3>
                                <p style="margin: 5px 0 0 0; color: var(--text-muted);">
                                    <strong>Griglia:</strong> <?php echo e($prova['griglia_nome']); ?> |
                                    <strong>Data:</strong> <?php echo formatDate($prova['data_prova']); ?>
                                </p>
                            </div>
                            <div>
                                <?php
                                $classe_voto = '';
                                if ($media >= 9) $classe_voto = 'media-eccellente';
                                elseif ($media >= 8) $classe_voto = 'media-buona';
                                elseif ($media >= 7) $classe_voto = 'media-discreta';
                                else $classe_voto = 'media-sufficiente';
                                ?>
                                <span class="media-badge <?php echo $classe_voto; ?>">
                                    Media: <?php echo formatNumber($media); ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($prova['descrizione'])): ?>
                                <p style="color: var(--text-muted); margin-bottom: 20px;">
                                    <strong>Descrizione:</strong> <?php echo e($prova['descrizione']); ?>
                                </p>
                            <?php endif; ?>

                            <h4 style="margin: 0 0 15px 0;">Valutazione per Criterio:</h4>
                            <div class="voti-grid">
                                <?php foreach ($valutazioni as $val): ?>
                                    <div class="voto-item">
                                        <h5>
                                            <?php echo e($val['criterio_nome']); ?>
                                            <small style="color: var(--text-muted);">(Peso: <?php echo formatNumber($val['peso']); ?>)</small>
                                        </h5>
                                        <div class="livello">
                                            <span><strong>Livello:</strong> <?php echo e($val['livello_nome']); ?></span>
                                            <span class="badge badge-primary">
                                                <?php echo formatNumber($val['punteggio']); ?> punti
                                            </span>
                                        </div>
                                        <?php if (!empty($val['note'])): ?>
                                            <p style="margin: 10px 0 0 0; padding: 10px; background: white; border-radius: 5px; font-size: 13px;">
                                                <strong>Note:</strong> <?php echo e($val['note']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php include __DIR__ . '/../includes/footer-scripts.php'; ?>
</body>
</html>
