<?php
/**
 * Script per invertire l'ordine dei livelli nelle griglie
 * Da eseguire una sola volta per correggere i livelli invertiti
 */

// Configurazione database (modificare se necessario)
$dbHost = 'localhost';
$dbName = 'uda_portal';
$dbUser = 'root';
$dbPass = '';

try {
    // Connessione al database
    $dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";
    $db = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "Connesso al database $dbName\n\n";

    $db->beginTransaction();

    // Trova tutti i criteri
    $stmt = $db->query("SELECT id, nome, griglia_id FROM criteri ORDER BY griglia_id, ordine");
    $criteri = $stmt->fetchAll();

    $totaleInvertiti = 0;

    foreach ($criteri as $criterio) {
        // Trova i livelli di questo criterio
        $stmt = $db->prepare("SELECT id, nome, ordine FROM livelli WHERE criterio_id = ? ORDER BY ordine");
        $stmt->execute([$criterio['id']]);
        $livelli = $stmt->fetchAll();

        // Inverti solo se ci sono 4 livelli (template progetto)
        if (count($livelli) === 4) {
            echo "Criterio '{$criterio['nome']}' (ID: {$criterio['id']}) - Invertendo 4 livelli\n";

            // Inverti l'ordine: 0->3, 1->2, 2->1, 3->0
            $nuovoOrdine = [3, 2, 1, 0];

            foreach ($livelli as $index => $livello) {
                $stmt = $db->prepare("UPDATE livelli SET ordine = ? WHERE id = ?");
                $stmt->execute([$nuovoOrdine[$index], $livello['id']]);
                echo "  - Livello '{$livello['nome']}' (ordine {$livello['ordine']} -> {$nuovoOrdine[$index]})\n";
            }

            $totaleInvertiti++;
        }
    }

    $db->commit();
    echo "\n✅ Completato! Invertiti {$totaleInvertiti} criteri con 4 livelli.\n";

} catch (PDOException $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo "❌ Errore database: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo "❌ Errore: " . $e->getMessage() . "\n";
    exit(1);
}
