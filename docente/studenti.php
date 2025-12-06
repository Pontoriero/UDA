<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('docente');

$db = getDB();
$docente_id = getCurrentUserId();

// Filtro classe
$classe_selezionata = get('classe', '');

// Carica classi disponibili
$stmt = $db->query("SELECT DISTINCT classe FROM utenti WHERE ruolo = 'studente' AND attivo = 1 AND classe IS NOT NULL AND classe != '' ORDER BY classe");
$classi = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Carica studenti con statistiche (filtrati per classe se selezionata)
$sql = "
    SELECT u.id, u.username, u.nome, u.cognome, u.email, u.classe,
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
    WHERE u.ruolo = 'studente' AND u.attivo = 1";

if (!empty($classe_selezionata)) {
    $sql .= " AND u.classe = ?";
}

$sql .= " ORDER BY u.cognome, u.nome";

$stmt = $db->prepare($sql);
if (!empty($classe_selezionata)) {
    $stmt->execute([$docente_id, $docente_id, $classe_selezionata]);
} else {
    $stmt->execute([$docente_id, $docente_id]);
}
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
                    <h3>Elenco Studenti Valutati</h3>
                    <span class="text-muted"><?php echo count($studenti); ?> studenti</span>
                </div>
                <div class="card-body">
                    <?php if (!empty($classi)): ?>
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label for="filtro_classe">🎓 Filtra per Classe:</label>
                            <select id="filtro_classe" class="form-control" onchange="filtroClasse(this.value)">
                                <option value="">Tutte le classi</option>
                                <?php foreach ($classi as $classe): ?>
                                    <option value="<?php echo e($classe); ?>" <?php echo ($classe_selezionata === $classe) ? 'selected' : ''; ?>>
                                        Classe <?php echo e($classe); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($studenti)): ?>
                        <p class="text-muted text-center">Nessuno studente trovato<?php echo $classe_selezionata ? ' nella classe ' . e($classe_selezionata) : ''; ?>.</p>
                    <?php else: ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Cognome e Nome</th>
                                        <th>Classe</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Prove Valutate</th>
                                        <th>Media Generale</th>
                                        <th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($studenti as $studente): ?>
                                        <tr>
                                            <td><strong><?php echo e($studente['cognome'] . ' ' . $studente['nome']); ?></strong></td>
                                            <td>
                                                <?php if (!empty($studente['classe'])): ?>
                                                    <span class="badge badge-info"><?php echo e($studente['classe']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo e($studente['username']); ?></td>
                                            <td><?php echo e($studente['email']); ?></td>
                                            <td><?php echo $studente['num_prove'] ?? 0; ?></td>
                                            <td>
                                                <?php if ($studente['media']): ?>
                                                    <?php
                                                    $media = $studente['media'];
                                                    $color = 'var(--success-color)';
                                                    if ($media < 6) $color = 'var(--danger-color)';
                                                    elseif ($media < 7) $color = 'var(--warning-color)';
                                                    elseif ($media < 8) $color = 'var(--info-color)';
                                                    ?>
                                                    <strong style="color: <?php echo $color; ?>;">
                                                        <?php echo formatNumber($studente['media']); ?>
                                                    </strong>
                                                <?php else: ?>
                                                    <span class="text-muted">Nessuna valutazione</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($studente['num_prove'] > 0): ?>
                                                    <a href="studente-dettaglio.php?studente_id=<?php echo $studente['id']; ?><?php echo $classe_selezionata ? '&classe=' . urlencode($classe_selezionata) : ''; ?>"
                                                       class="btn btn-primary btn-sm">
                                                        📊 Vedi Voti
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
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

            <script>
                function filtroClasse(classe) {
                    const url = new URL(window.location.href);
                    if (classe) {
                        url.searchParams.set('classe', classe);
                    } else {
                        url.searchParams.delete('classe');
                    }
                    window.location.href = url.toString();
                }
            </script>
        </div>
    </div>
    <?php include __DIR__ . '/../includes/footer-scripts.php'; ?>
</body>
</html>
