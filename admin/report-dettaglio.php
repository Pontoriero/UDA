<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

$db = getDB();
$prova_id = get('prova_id', 0);

if (!$prova_id) {
    setErrorMessage('ID prova non valido');
    redirect('report.php');
}

// Carica informazioni prova
$stmt = $db->prepare("
    SELECT p.*, g.nome as griglia_nome, g.id as griglia_id,
           u.nome as docente_nome, u.cognome as docente_cognome
    FROM prove p
    JOIN griglie g ON p.griglia_id = g.id
    JOIN utenti u ON p.docente_id = u.id
    WHERE p.id = ?
");
$stmt->execute([$prova_id]);
$prova = $stmt->fetch();

if (!$prova) {
    setErrorMessage('Prova non trovata');
    redirect('report.php');
}

// Carica criteri della griglia
$stmt = $db->prepare("SELECT * FROM criteri WHERE griglia_id = ? ORDER BY ordine");
$stmt->execute([$prova['griglia_id']]);
$criteri = $stmt->fetchAll();

// Carica tutti gli studenti valutati per questa prova con i loro punteggi
$stmt = $db->prepare("
    SELECT DISTINCT u.id, u.username, u.nome, u.cognome
    FROM valutazioni v
    JOIN utenti u ON v.studente_id = u.id
    WHERE v.prova_id = ?
    ORDER BY u.cognome, u.nome
");
$stmt->execute([$prova_id]);
$studenti = $stmt->fetchAll();

// Per ogni studente, calcola il voto medio ponderato e carica le valutazioni per criterio
$risultati = [];
$statistiche_criteri = [];

foreach ($studenti as &$studente) {
    // Carica valutazioni dello studente
    $stmt = $db->prepare("
        SELECT v.*, c.nome as criterio_nome, c.peso, l.nome as livello_nome
        FROM valutazioni v
        JOIN criteri c ON v.criterio_id = c.id
        JOIN livelli l ON v.livello_id = l.id
        WHERE v.prova_id = ? AND v.studente_id = ?
        ORDER BY c.ordine
    ");
    $stmt->execute([$prova_id, $studente['id']]);
    $valutazioni = $stmt->fetchAll();

    // Calcola voto medio ponderato
    $somma_punteggi_ponderati = 0;
    $somma_pesi = 0;
    $voti_per_criterio = [];

    foreach ($valutazioni as $val) {
        $somma_punteggi_ponderati += $val['punteggio'] * $val['peso'];
        $somma_pesi += $val['peso'];
        $voti_per_criterio[$val['criterio_id']] = $val['punteggio'];

        // Statistiche per criterio
        if (!isset($statistiche_criteri[$val['criterio_id']])) {
            $statistiche_criteri[$val['criterio_id']] = [
                'nome' => $val['criterio_nome'],
                'punteggi' => [],
                'peso' => $val['peso']
            ];
        }
        $statistiche_criteri[$val['criterio_id']]['punteggi'][] = $val['punteggio'];
    }

    $voto_medio = $somma_pesi > 0 ? $somma_punteggi_ponderati / $somma_pesi : 0;

    $risultati[] = [
        'studente' => $studente,
        'voto_medio' => $voto_medio,
        'voti_criteri' => $voti_per_criterio
    ];
}

// Ordina per voto medio decrescente
usort($risultati, function($a, $b) {
    return $b['voto_medio'] <=> $a['voto_medio'];
});

// Calcola statistiche generali
$voti_medi = array_column($risultati, 'voto_medio');
$media_classe = !empty($voti_medi) ? array_sum($voti_medi) / count($voti_medi) : 0;
$voto_max = !empty($voti_medi) ? max($voti_medi) : 0;
$voto_min = !empty($voti_medi) ? min($voti_medi) : 0;

// Calcola mediana
$mediana = 0;
if (!empty($voti_medi)) {
    sort($voti_medi);
    $count = count($voti_medi);
    $middle = floor($count / 2);
    if ($count % 2 == 0) {
        $mediana = ($voti_medi[$middle - 1] + $voti_medi[$middle]) / 2;
    } else {
        $mediana = $voti_medi[$middle];
    }
}

// Calcola statistiche per criterio
foreach ($statistiche_criteri as $criterio_id => &$stat) {
    $punteggi = $stat['punteggi'];
    $stat['media'] = array_sum($punteggi) / count($punteggi);
    $stat['max'] = max($punteggi);
    $stat['min'] = min($punteggi);
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Dettaglio - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        .report-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .report-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: var(--shadow);
            text-align: center;
        }

        .stat-box .value {
            font-size: 36px;
            font-weight: bold;
            color: var(--primary-color);
            margin: 10px 0;
        }

        .stat-box .label {
            color: var(--text-muted);
            font-size: 14px;
        }

        .risultati-table {
            background: white;
        }

        .risultati-table th {
            position: sticky;
            top: 0;
            background: var(--dark-color);
            color: white;
            z-index: 10;
        }

        .voto-cell {
            font-weight: bold;
            font-size: 18px;
        }

        .voto-eccellente { color: #10b981; }
        .voto-buono { color: #3b82f6; }
        .voto-sufficiente { color: #f59e0b; }
        .voto-insufficiente { color: #ef4444; }

        .ranking {
            display: inline-block;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            line-height: 30px;
            text-align: center;
            font-weight: bold;
        }

        .ranking.primo { background: #ffd700; color: #333; }
        .ranking.secondo { background: #c0c0c0; color: #333; }
        .ranking.terzo { background: #cd7f32; color: white; }

        .criterio-stats {
            background: #f9fafb;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
        }

        .criterio-stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 10px;
        }

        .mini-stat {
            text-align: center;
            padding: 10px;
            background: white;
            border-radius: 5px;
        }

        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
        }

        .bar-chart {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 15px;
        }

        .bar-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .bar-label {
            width: 150px;
            font-size: 14px;
            font-weight: 500;
        }

        .bar-container {
            flex: 1;
            background: #e5e7eb;
            border-radius: 5px;
            height: 30px;
            position: relative;
        }

        .bar-fill {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            height: 100%;
            border-radius: 5px;
            transition: width 0.5s ease;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 10px;
            color: white;
            font-weight: bold;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            <div class="topbar">
                <h1>📊 Report: <?php echo e($prova['nome']); ?></h1>
                <div class="topbar-actions">
                    <a href="report.php" class="btn btn-secondary btn-sm">← Torna ai Report</a>
                    <button onclick="window.print()" class="btn btn-primary btn-sm">🖨️ Stampa</button>
                    <a href="../public/logout.php" class="btn btn-secondary btn-sm">Logout</a>
                </div>
            </div>

            <div class="report-header">
                <h2 style="margin: 0 0 10px 0;"><?php echo e($prova['nome']); ?></h2>
                <p style="margin: 0; opacity: 0.9;">
                    <strong>Griglia:</strong> <?php echo e($prova['griglia_nome']); ?> |
                    <strong>Docente:</strong> <?php echo e($prova['docente_nome'] . ' ' . $prova['docente_cognome']); ?> |
                    <strong>Data:</strong> <?php echo formatDate($prova['data_prova']); ?> |
                    <strong>Studenti:</strong> <?php echo count($risultati); ?>
                </p>
            </div>

            <!-- Statistiche Generali -->
            <h3 style="margin-bottom: 15px;">📈 Statistiche Generali</h3>
            <div class="report-stats">
                <div class="stat-box">
                    <div class="label">Media Classe</div>
                    <div class="value"><?php echo formatNumber($media_classe); ?></div>
                    <div class="label">su 10</div>
                </div>

                <div class="stat-box">
                    <div class="label">Voto Massimo</div>
                    <div class="value" style="color: var(--success-color);"><?php echo formatNumber($voto_max); ?></div>
                    <div class="label">migliore risultato</div>
                </div>

                <div class="stat-box">
                    <div class="label">Voto Minimo</div>
                    <div class="value" style="color: var(--danger-color);"><?php echo formatNumber($voto_min); ?></div>
                    <div class="label">da recuperare</div>
                </div>

                <div class="stat-box">
                    <div class="label">Mediana</div>
                    <div class="value"><?php echo formatNumber($mediana); ?></div>
                    <div class="label">valore centrale</div>
                </div>

                <div class="stat-box">
                    <div class="label">Studenti Valutati</div>
                    <div class="value"><?php echo count($risultati); ?></div>
                    <div class="label">totale</div>
                </div>

                <div class="stat-box">
                    <div class="label">Criteri Valutati</div>
                    <div class="value"><?php echo count($criteri); ?></div>
                    <div class="label">parametri</div>
                </div>
            </div>

            <!-- Grafico per Criterio -->
            <div class="chart-container">
                <h3>📊 Medie per Criterio</h3>
                <div class="bar-chart">
                    <?php foreach ($statistiche_criteri as $stat): ?>
                        <div class="bar-row">
                            <div class="bar-label"><?php echo e($stat['nome']); ?></div>
                            <div class="bar-container">
                                <div class="bar-fill" style="width: <?php echo ($stat['media'] / 10 * 100); ?>%">
                                    <?php echo formatNumber($stat['media']); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Statistiche per Criterio -->
            <div class="card">
                <div class="card-header">
                    <h3>📋 Dettaglio Statistiche per Criterio</h3>
                </div>
                <div class="card-body">
                    <?php foreach ($statistiche_criteri as $stat): ?>
                        <div class="criterio-stats">
                            <strong><?php echo e($stat['nome']); ?></strong>
                            <span class="badge badge-secondary">Peso: <?php echo formatNumber($stat['peso']); ?></span>
                            <div class="criterio-stats-grid">
                                <div class="mini-stat">
                                    <div style="font-size: 20px; font-weight: bold; color: var(--primary-color);">
                                        <?php echo formatNumber($stat['media']); ?>
                                    </div>
                                    <div style="font-size: 12px; color: var(--text-muted);">Media</div>
                                </div>
                                <div class="mini-stat">
                                    <div style="font-size: 20px; font-weight: bold; color: var(--success-color);">
                                        <?php echo formatNumber($stat['max']); ?>
                                    </div>
                                    <div style="font-size: 12px; color: var(--text-muted);">Massimo</div>
                                </div>
                                <div class="mini-stat">
                                    <div style="font-size: 20px; font-weight: bold; color: var(--danger-color);">
                                        <?php echo formatNumber($stat['min']); ?>
                                    </div>
                                    <div style="font-size: 12px; color: var(--text-muted);">Minimo</div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Tabella Risultati -->
            <div class="card">
                <div class="card-header">
                    <h3>🎯 Risultati Studenti</h3>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table class="risultati-table">
                            <thead>
                                <tr>
                                    <th>Pos.</th>
                                    <th>Studente</th>
                                    <?php foreach ($criteri as $criterio): ?>
                                        <th><?php echo e($criterio['nome']); ?><br><small>(peso <?php echo formatNumber($criterio['peso']); ?>)</small></th>
                                    <?php endforeach; ?>
                                    <th>Media Ponderata</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($risultati as $index => $risultato): ?>
                                    <?php
                                    $studente = $risultato['studente'];
                                    $voto_medio = $risultato['voto_medio'];

                                    // Determina classe CSS per il voto
                                    if ($voto_medio >= 9) $voto_class = 'voto-eccellente';
                                    elseif ($voto_medio >= 7) $voto_class = 'voto-buono';
                                    elseif ($voto_medio >= 6) $voto_class = 'voto-sufficiente';
                                    else $voto_class = 'voto-insufficiente';

                                    // Classe ranking
                                    $ranking_class = '';
                                    if ($index == 0) $ranking_class = 'primo';
                                    elseif ($index == 1) $ranking_class = 'secondo';
                                    elseif ($index == 2) $ranking_class = 'terzo';
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="ranking <?php echo $ranking_class; ?>"><?php echo $index + 1; ?></span>
                                        </td>
                                        <td><strong><?php echo e($studente['cognome'] . ' ' . $studente['nome']); ?></strong></td>

                                        <?php foreach ($criteri as $criterio): ?>
                                            <td class="text-center">
                                                <?php
                                                $punteggio = $risultato['voti_criteri'][$criterio['id']] ?? '-';
                                                if ($punteggio !== '-') {
                                                    if ($punteggio >= 9) $class = 'voto-eccellente';
                                                    elseif ($punteggio >= 7) $class = 'voto-buono';
                                                    elseif ($punteggio >= 6) $class = 'voto-sufficiente';
                                                    else $class = 'voto-insufficiente';
                                                    echo '<span class="' . $class . '">' . formatNumber($punteggio) . '</span>';
                                                } else {
                                                    echo $punteggio;
                                                }
                                                ?>
                                            </td>
                                        <?php endforeach; ?>

                                        <td>
                                            <span class="voto-cell <?php echo $voto_class; ?>">
                                                <?php echo formatNumber($voto_medio); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="../studente/dettaglio.php?prova_id=<?php echo $prova_id; ?>&studente_id=<?php echo $studente['id']; ?>" class="btn btn-primary btn-sm" target="_blank">Dettaglio</a>
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
