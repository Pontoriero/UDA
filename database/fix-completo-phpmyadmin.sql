-- ============================================================================
-- Script SQL completo per correggere duplicati e ordine livelli
-- Da eseguire in phpMyAdmin
-- ============================================================================

-- PARTE 1: Rimuovi duplicati "Consegna files"
-- ============================================================================

-- Trova e mostra i duplicati (per verifica)
SELECT
    c.id,
    c.griglia_id,
    c.nome,
    c.ordine
FROM criteri c
WHERE c.nome = 'Consegna files'
ORDER BY c.griglia_id, c.ordine;

-- Elimina i duplicati mantenendo solo quello con ordine minore
DELETE c1 FROM criteri c1
INNER JOIN criteri c2
WHERE
    c1.griglia_id = c2.griglia_id
    AND c1.nome = 'Consegna files'
    AND c2.nome = 'Consegna files'
    AND c1.ordine > c2.ordine;

-- Verifica che "Rispetto dei tempi" esista, altrimenti rinomina l'ultimo "Consegna files"
-- (questa è una verifica manuale - se vedi che manca "Rispetto dei tempi", esegui l'UPDATE sotto)
SELECT
    c.id,
    c.griglia_id,
    c.nome,
    c.ordine
FROM criteri c
WHERE c.nome IN ('Consegna files', 'Rispetto dei tempi')
ORDER BY c.griglia_id, c.ordine;


-- PARTE 2: Inverti ordine dei livelli (0->3, 1->2, 2->1, 3->0)
-- ============================================================================

-- Crea una tabella temporanea per salvare i nuovi ordini
CREATE TEMPORARY TABLE IF NOT EXISTS temp_livelli_updates (
    livello_id INT,
    nuovo_ordine INT
);

-- Calcola i nuovi ordini invertiti
INSERT INTO temp_livelli_updates (livello_id, nuovo_ordine)
SELECT
    l.id,
    CASE l.ordine
        WHEN 0 THEN 3  -- Ecc (ordine 0) -> diventa ordine 3
        WHEN 1 THEN 2  -- Avan (ordine 1) -> diventa ordine 2
        WHEN 2 THEN 1  -- Inter (ordine 2) -> diventa ordine 1
        WHEN 3 THEN 0  -- Base (ordine 3) -> diventa ordine 0
    END as nuovo_ordine
FROM livelli l
WHERE l.criterio_id IN (
    -- Solo criteri con esattamente 4 livelli
    SELECT criterio_id
    FROM livelli
    GROUP BY criterio_id
    HAVING COUNT(*) = 4
);

-- Aggiorna i livelli con i nuovi ordini
UPDATE livelli l
JOIN temp_livelli_updates t ON l.id = t.livello_id
SET l.ordine = t.nuovo_ordine;

-- Pulisci la tabella temporanea
DROP TEMPORARY TABLE IF EXISTS temp_livelli_updates;


-- PARTE 3: Verifica finale
-- ============================================================================

-- Mostra tutti i criteri della griglia 1 (o cambia l'ID con la tua griglia)
SELECT
    c.ordine as ord_criterio,
    c.nome as criterio,
    c.peso,
    l.ordine as ord_livello,
    l.nome as livello,
    l.punteggio
FROM criteri c
JOIN livelli l ON c.id = l.criterio_id
WHERE c.griglia_id = 1
ORDER BY c.ordine, l.ordine;

-- Mostra riepilogo per griglia
SELECT
    c.griglia_id,
    COUNT(DISTINCT c.id) as num_criteri,
    GROUP_CONCAT(DISTINCT c.nome ORDER BY c.ordine SEPARATOR ', ') as criteri
FROM criteri c
WHERE c.griglia_id = 1
GROUP BY c.griglia_id;
