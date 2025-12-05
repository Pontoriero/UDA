<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('docente');

$db = getDB();
$docente_id = getCurrentUserId();
$prova_id = get('prova_id', 0);
$classe = get('classe', '');
$export = get('export', '');

if (!$prova_id || !$classe) {
    setErrorMessage('Parametri non validi');
    redirect('classi.php');
}

// Carica prova
$stmt = $db->prepare("
    SELECT p.*, g.nome as griglia_nome, g.id as griglia_id
    FROM prove p
    JOIN griglie g ON p.griglia_id = g.id
    WHERE p.id = ? AND p.docente_id = ?
");
$stmt->execute([$prova_id, $docente_id]);
$prova = $stmt->fetch();

if (!$prova) {
    setErrorMessage('Prova non trovata');
    redirect('classi.php');
}

// Carica criteri
$stmt = $db->prepare("SELECT * FROM criteri WHERE griglia_id = ? ORDER BY ordine");
$stmt->execute([$prova['griglia_id']]);
$criteri = $stmt->fetchAll();

// Carica studenti della classe valutati per questa prova
$stmt = $db->prepare("
    SELECT DISTINCT u.id, u.username, u.nome, u.cognome, u.classe
    FROM valutazioni v
    JOIN utenti u ON v.studente_id = u.id
    WHERE v.prova_id = ? AND u.classe = ?
    ORDER BY u.cognome, u.nome
");
$stmt->execute([$prova_id, $classe]);
$studenti = $stmt->fetchAll();

// Calcola risultati
$risultati = [];
foreach ($studenti as $studente) {
    $stmt = $db->prepare("
        SELECT v.*, c.nome as criterio_nome, c.peso
        FROM valutazioni v
        JOIN criteri c ON v.criterio_id = c.id
        WHERE v.prova_id = ? AND v.studente_id = ?
        ORDER BY c.ordine
    ");
    $stmt->execute([$prova_id, $studente['id']]);
    $valutazioni = $stmt->fetchAll();

    $somma_punteggi_ponderati = 0;
    $somma_pesi = 0;
    $voti_per_criterio = [];

    foreach ($valutazioni as $val) {
        $somma_punteggi_ponderati += $val['punteggio'] * $val['peso'];
        $somma_pesi += $val['peso'];
        $voti_per_criterio[$val['criterio_id']] = $val['punteggio'];
    }

    $voto_medio = $somma_pesi > 0 ? $somma_punteggi_ponderati / $somma_pesi : 0;

    $risultati[] = [
        'studente' => $studente,
        'voto_medio' => $voto_medio,
        'voti_criteri' => $voti_per_criterio
    ];
}

// Ordina per cognome
usort($risultati, function($a, $b) {
    return strcmp($a['studente']['cognome'], $b['studente']['cognome']);
});

// Export Excel (CSV)
if ($export === 'excel' && !empty($risultati)) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="report_classe_' . $classe . '_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');

    // BOM per UTF-8 (per Excel)
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Header
    $header = ['Cognome', 'Nome', 'Classe'];
    foreach ($criteri as $criterio) {
        $header[] = $criterio['nome'];
    }
    $header[] = 'Media Ponderata';
    fputcsv($output, $header, ';');

    // Dati
    foreach ($risultati as $ris) {
        $row = [
            $ris['studente']['cognome'],
            $ris['studente']['nome'],
            $ris['studente']['classe']
        ];
        foreach ($criteri as $criterio) {
            $row[] = isset($ris['voti_criteri'][$criterio['id']]) ?
                     number_format($ris['voti_criteri'][$criterio['id']], 2, ',', '') : '-';
        }
        $row[] = number_format($ris['voto_medio'], 2, ',', '');
        fputcsv($output, $row, ';');
    }

    // Statistiche
    fputcsv($output, [], ';');
    $voti_medi = array_column($risultati, 'voto_medio');
    $media_classe = array_sum($voti_medi) / count($voti_medi);
    fputcsv($output, ['MEDIA CLASSE', '', '', number_format($media_classe, 2, ',', '')], ';');
    fputcsv($output, ['VOTO MAX', '', '', number_format(max($voti_medi), 2, ',', '')], ';');
    fputcsv($output, ['VOTO MIN', '', '', number_format(min($voti_medi), 2, ',', '')], ';');

    fclose($output);
    exit;
}

// Calcola statistiche
$voti_medi = array_column($risultati, 'voto_medio');
$media_classe = !empty($voti_medi) ? array_sum($voti_medi) / count($voti_medi) : 0;
$voto_max = !empty($voti_medi) ? max($voti_medi) : 0;
$voto_min = !empty($voti_medi) ? min($voti_medi) : 0;
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Classe <?php echo e($classe); ?> - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
    <div class="dashboard">
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            <div class="topbar">
                <h1>📊 Report Classe <?php echo e($classe); ?>: <?php echo e($prova['nome']); ?></h1>
                <div class="topbar-actions">
                    <a href="classi.php?classe=<?php echo urlencode($classe); ?>" class="btn btn-secondary btn-sm">← Torna alla Classe</a>
                    <a href="?prova_id=<?php echo $prova_id; ?>&classe=<?php echo urlencode($classe); ?>&export=excel" class="btn btn-success btn-sm">📥 Esporta Excel</a>
                    <button onclick="window.print()" class="btn btn-primary btn-sm">🖨️ Stampa</button>
                    <a href="../public/logout.php" class="btn btn-secondary btn-sm">Logout</a>
                </div>
            </div>

            <?php if (empty($risultati)): ?>
                <div class="card">
                    <div class="card-body">
                        <p class="text-muted text-center">Nessuno studente della classe <?php echo e($classe); ?> valutato per questa prova.</p>
                    </div>
                </div>
            <?php else: ?>

            <!-- Statistiche -->
            <div class="report-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <div class="stat-box" style="background: white; padding: 20px; border-radius: 8px; box-shadow: var(--shadow); text-align: center;">
                    <div style="color: var(--text-muted); font-size: 14px;">Media Classe</div>
                    <div style="font-size: 36px; font-weight: bold; color: var(--primary-color); margin: 10px 0;">
                        <?php echo formatNumber($media_classe); ?>
                    </div>
                    <div style="color: var(--text-muted); font-size: 14px;">su 10</div>
                </div>

                <div class="stat-box" style="background: white; padding: 20px; border-radius: 8px; box-shadow: var(--shadow); text-align: center;">
                    <div style="color: var(--text-muted); font-size: 14px;">Voto Massimo</div>
                    <div style="font-size: 36px; font-weight: bold; color: var(--success-color); margin: 10px 0;">
                        <?php echo formatNumber($voto_max); ?>
                    </div>
                </div>

                <div class="stat-box" style="background: white; padding: 20px; border-radius: 8px; box-shadow: var(--shadow); text-align: center;">
                    <div style="color: var(--text-muted); font-size: 14px;">Voto Minimo</div>
                    <div style="font-size: 36px; font-weight: bold; color: var(--danger-color); margin: 10px 0;">
                        <?php echo formatNumber($voto_min); ?>
                    </div>
                </div>

                <div class="stat-box" style="background: white; padding: 20px; border-radius: 8px; box-shadow: var(--shadow); text-align: center;">
                    <div style="color: var(--text-muted); font-size: 14px;">Studenti</div>
                    <div style="font-size: 36px; font-weight: bold; color: var(--primary-color); margin: 10px 0;">
                        <?php echo count($risultati); ?>
                    </div>
                </div>
            </div>

            <!-- Tabella Risultati -->
            <div class="card">
                <div class="card-header">
                    <h3>Risultati Classe <?php echo e($classe); ?></h3>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Cognome</th>
                                    <th>Nome</th>
                                    <?php foreach ($criteri as $criterio): ?>
                                        <th><?php echo e($criterio['nome']); ?></th>
                                    <?php endforeach; ?>
                                    <th>Media</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($risultati as $ris): ?>
                                    <?php
                                    $voto_medio = $ris['voto_medio'];
                                    if ($voto_medio >= 9) $voto_class = 'voto-eccellente';
                                    elseif ($voto_medio >= 7) $voto_class = 'voto-buono';
                                    elseif ($voto_medio >= 6) $voto_class = 'voto-sufficiente';
                                    else $voto_class = 'voto-insufficiente';
                                    ?>
                                    <tr>
                                        <td><strong><?php echo e($ris['studente']['cognome']); ?></strong></td>
                                        <td><?php echo e($ris['studente']['nome']); ?></td>
                                        <?php foreach ($criteri as $criterio): ?>
                                            <td class="text-center">
                                                <?php
                                                $punteggio = $ris['voti_criteri'][$criterio['id']] ?? '-';
                                                if ($punteggio !== '-') {
                                                    if ($punteggio >= 9) $class = 'voto-eccellente';
                                                    elseif ($punteggio >= 7) $class = 'voto-buono';
                                                    elseif ($punteggio >= 6) $class = 'voto-sufficiente';
                                                    else $class = 'voto-insufficiente';
                                                    echo '<span class="' . $class . '" style="font-weight: bold;">' . formatNumber($punteggio) . '</span>';
                                                } else {
                                                    echo $punteggio;
                                                }
                                                ?>
                                            </td>
                                        <?php endforeach; ?>
                                        <td>
                                            <span class="<?php echo $voto_class; ?>" style="font-weight: bold; font-size: 18px;">
                                                <?php echo formatNumber($voto_medio); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <?php endif; ?>
        </div>
    </div>

    <style>
        .voto-eccellente { color: #10b981; }
        .voto-buono { color: #3b82f6; }
        .voto-sufficiente { color: #f59e0b; }
        .voto-insufficiente { color: #ef4444; }
    </style>
    <?php include __DIR__ . '/../includes/footer-scripts.php'; ?>
</body>
</html>
