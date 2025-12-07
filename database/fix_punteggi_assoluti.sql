-- Aggiorna i punteggi dei livelli per essere specifici al peso del criterio
-- Invece di avere punteggi fissi (2.5, 5, 7.5, 10) o percentuali (0.25, 0.5, 0.75, 1.0),
-- ogni livello avrà il suo punteggio calcolato come: peso_criterio × frazione

-- STEP 1: Identifica la frazione di ogni livello basandosi sul punteggio attuale
-- Se il punteggio è tra 0 e 1, è già una frazione
-- Se il punteggio è tra 2 e 10, convertiamo in frazione (punteggio / 10)

-- STEP 2: Aggiorna i punteggi moltiplicando la frazione per il peso del criterio

UPDATE livelli l
JOIN criteri c ON l.criterio_id = c.id
SET l.punteggio = CASE
    -- Se il punteggio attuale è già una frazione (<=1), usa quella
    WHEN l.punteggio <= 1.0 THEN c.peso * l.punteggio

    -- Altrimenti converti da punteggio fisso a frazione
    -- 2.5 → 0.25, 5 → 0.50, 7.5 → 0.75, 10 → 1.00
    WHEN l.punteggio BETWEEN 2.4 AND 2.6 THEN c.peso * 0.25
    WHEN l.punteggio BETWEEN 4.9 AND 5.1 THEN c.peso * 0.50
    WHEN l.punteggio BETWEEN 7.4 AND 7.6 THEN c.peso * 0.75
    WHEN l.punteggio BETWEEN 9.9 AND 10.1 THEN c.peso * 1.00

    -- Altri valori comuni
    WHEN l.punteggio BETWEEN 3.9 AND 4.1 THEN c.peso * 0.40
    WHEN l.punteggio BETWEEN 5.9 AND 6.1 THEN c.peso * 0.60
    WHEN l.punteggio BETWEEN 7.9 AND 8.1 THEN c.peso * 0.80

    -- Default: mantieni il punteggio attuale
    ELSE l.punteggio
END;

-- STEP 3: Ricalcola tutti i punteggi nelle valutazioni esistenti
-- Questo aggiorna le valutazioni con i nuovi punteggi

UPDATE valutazioni v
JOIN livelli l ON v.livello_id = l.id
SET v.punteggio = l.punteggio;

-- STEP 4: Verifica alcuni esempi
SELECT
    g.nome as griglia,
    c.nome as criterio,
    c.peso as peso_criterio,
    l.nome as livello,
    l.punteggio as punteggio_livello,
    ROUND(l.punteggio / c.peso, 2) as frazione
FROM livelli l
JOIN criteri c ON l.criterio_id = c.id
JOIN griglie g ON c.griglia_id = g.id
WHERE g.nome LIKE '%Project%'
ORDER BY c.ordine, l.ordine
LIMIT 20;
