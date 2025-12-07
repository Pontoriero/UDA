-- Script per correggere il duplicato "Consegna files" nelle griglie Project Charter
-- Il secondo "Consegna files" dovrebbe essere "Rispetto dei tempi"

-- Trova i criteri duplicati "Consegna files" nella griglia Project Charter
SELECT
    g.id as griglia_id,
    g.nome as griglia_nome,
    c.id as criterio_id,
    c.nome as criterio_nome,
    c.peso,
    c.ordine
FROM criteri c
JOIN griglie g ON c.griglia_id = g.id
WHERE g.nome LIKE '%Project%Charter%'
  AND c.nome = 'Consegna files'
ORDER BY c.ordine;

-- Corregge il secondo "Consegna files" rinominandolo in "Rispetto dei tempi"
-- SOLO se ci sono esattamente 2 criteri "Consegna files"

UPDATE criteri c
JOIN (
    -- Trova il secondo "Consegna files" (quello con ordine maggiore)
    SELECT c2.id
    FROM criteri c2
    JOIN griglie g ON c2.griglia_id = g.id
    WHERE g.nome LIKE '%Project%Charter%'
      AND c2.nome = 'Consegna files'
    ORDER BY c2.ordine DESC
    LIMIT 1
) AS duplicato ON c.id = duplicato.id
SET c.nome = 'Rispetto dei tempi'
WHERE (
    SELECT COUNT(*)
    FROM criteri c3
    JOIN griglie g ON c3.griglia_id = g.id
    WHERE g.nome LIKE '%Project%Charter%'
      AND c3.nome = 'Consegna files'
) = 2;

-- Verifica che la correzione sia andata a buon fine
SELECT
    g.nome as griglia_nome,
    c.nome as criterio_nome,
    c.peso,
    c.ordine
FROM criteri c
JOIN griglie g ON c.griglia_id = g.id
WHERE g.nome LIKE '%Project%Charter%'
ORDER BY c.ordine;
