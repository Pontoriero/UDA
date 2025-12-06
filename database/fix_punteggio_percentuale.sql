-- Correzione sistema di punteggio: da punteggi fissi a percentuali
-- I livelli ora usano percentuali (0.25, 0.50, 0.75, 1.00)
-- Il punteggio effettivo viene calcolato come: peso_criterio × percentuale_livello

-- STEP 1: Aggiorna i punteggi dei livelli esistenti per usare percentuali
-- Rileva automaticamente il pattern e converte in percentuali

-- Converti tutti i livelli che hanno punteggio 2.5 → 0.25 (Base)
UPDATE livelli SET punteggio = 0.25 WHERE punteggio BETWEEN 2.4 AND 2.6;

-- Converti tutti i livelli che hanno punteggio 5.0 → 0.50 (Intermedio)
UPDATE livelli SET punteggio = 0.50 WHERE punteggio BETWEEN 4.9 AND 5.1;

-- Converti tutti i livelli che hanno punteggio 7.5 → 0.75 (Avanzato)
UPDATE livelli SET punteggio = 0.75 WHERE punteggio BETWEEN 7.4 AND 7.6;

-- Converti tutti i livelli che hanno punteggio 10.0 → 1.00 (Eccellente)
UPDATE livelli SET punteggio = 1.00 WHERE punteggio BETWEEN 9.9 AND 10.1;

-- Converti altri punteggi comuni
UPDATE livelli SET punteggio = 0.40 WHERE punteggio BETWEEN 3.9 AND 4.1;
UPDATE livelli SET punteggio = 0.60 WHERE punteggio BETWEEN 5.9 AND 6.1;
UPDATE livelli SET punteggio = 0.80 WHERE punteggio BETWEEN 7.9 AND 8.1;

-- STEP 2: Ricalcola tutti i punteggi nelle valutazioni esistenti
-- Questo aggiorna le valutazioni già salvate con il nuovo sistema

UPDATE valutazioni v
JOIN livelli l ON v.livello_id = l.id
JOIN criteri c ON v.criterio_id = c.id
SET v.punteggio = c.peso * l.punteggio
WHERE l.punteggio <= 1.0;

-- Verifica: Mostra alcuni esempi di conversione
SELECT
    c.nome as criterio,
    c.peso,
    l.nome as livello,
    l.punteggio as percentuale,
    (c.peso * l.punteggio) as punteggio_effettivo
FROM criteri c
JOIN livelli l ON l.criterio_id = c.id
LIMIT 20;
