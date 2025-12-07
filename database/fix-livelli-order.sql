-- Script per invertire l'ordine dei livelli nelle griglie
-- Inverte l'ordine solo per criteri con esattamente 4 livelli (template progetto)

-- Crea una tabella temporanea per salvare i cambiamenti
CREATE TEMPORARY TABLE IF NOT EXISTS temp_livelli_updates (
    livello_id INT,
    nuovo_ordine INT
);

-- Per ogni criterio con 4 livelli, inverte l'ordine
-- L'ordine attuale è: 0=Ecc, 1=Avan, 2=Inter, 3=Base
-- L'ordine corretto è: 0=Base, 1=Inter, 2=Avan, 3=Ecc

-- Inserisci i nuovi ordini nella tabella temporanea
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

-- Mostra i risultati
SELECT
    c.nome as criterio,
    l.nome as livello,
    l.ordine,
    l.punteggio
FROM criteri c
JOIN livelli l ON c.id = l.criterio_id
WHERE c.id IN (
    SELECT criterio_id
    FROM livelli
    GROUP BY criterio_id
    HAVING COUNT(*) = 4
)
ORDER BY c.id, l.ordine;

-- Pulisci la tabella temporanea
DROP TEMPORARY TABLE IF EXISTS temp_livelli_updates;
