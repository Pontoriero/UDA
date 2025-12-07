<?php
/**
 * Script per rimuovere il criterio duplicato "Consegna files"
 * e verificare che "Rispetto dei tempi" esista
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

    // Trova tutti i criteri "Consegna files" per ogni griglia
    $stmt = $db->query("
        SELECT c.id, c.griglia_id, c.nome, c.ordine
        FROM criteri c
        WHERE c.nome = 'Consegna files'
        ORDER BY c.griglia_id, c.ordine
    ");
    $consegnaFiles = $stmt->fetchAll();

    echo "Trovati " . count($consegnaFiles) . " criteri 'Consegna files'\n\n";

    // Raggruppa per griglia_id
    $griglieConDuplicati = [];
    foreach ($consegnaFiles as $criterio) {
        $griglieConDuplicati[$criterio['griglia_id']][] = $criterio;
    }

    $totaleRimossi = 0;

    foreach ($griglieConDuplicati as $grigliaId => $criteri) {
        if (count($criteri) > 1) {
            echo "Griglia ID $grigliaId: trovati " . count($criteri) . " duplicati di 'Consegna files'\n";

            // Verifica se esiste "Rispetto dei tempi"
            $stmt = $db->prepare("SELECT id FROM criteri WHERE griglia_id = ? AND nome = 'Rispetto dei tempi'");
            $stmt->execute([$grigliaId]);
            $rispettoTempi = $stmt->fetch();

            if (!$rispettoTempi) {
                echo "  ATTENZIONE: 'Rispetto dei tempi' NON trovato in questa griglia!\n";
                echo "  Rinomino il secondo 'Consegna files' in 'Rispetto dei tempi'\n";

                // Rinomina il secondo duplicato
                $stmt = $db->prepare("UPDATE criteri SET nome = 'Rispetto dei tempi' WHERE id = ?");
                $stmt->execute([$criteri[1]['id']]);
                echo "  ✓ Criterio {$criteri[1]['id']} rinominato\n";
            } else {
                echo "  'Rispetto dei tempi' già esiste (ID: {$rispettoTempi['id']})\n";
                echo "  Rimuovo il duplicato 'Consegna files' (ID: {$criteri[1]['id']})\n";

                // Rimuovi il secondo duplicato (con i suoi livelli)
                $stmt = $db->prepare("DELETE FROM livelli WHERE criterio_id = ?");
                $stmt->execute([$criteri[1]['id']]);

                $stmt = $db->prepare("DELETE FROM criteri WHERE id = ?");
                $stmt->execute([$criteri[1]['id']]);

                echo "  ✓ Duplicato rimosso\n";
                $totaleRimossi++;
            }
        }
    }

    $db->commit();
    echo "\n✅ Completato! Risolti duplicati in " . count($griglieConDuplicati) . " griglie.\n";
    echo "Criteri rimossi: $totaleRimossi\n";

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
