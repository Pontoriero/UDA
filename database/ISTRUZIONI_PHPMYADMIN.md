# Istruzioni per eseguire la correzione in phpMyAdmin

## Passo 1: Apri phpMyAdmin

1. Vai su http://localhost/phpmyadmin
2. Seleziona il database `uda_portal` dal menu a sinistra
3. Clicca sulla tab "SQL" in alto

## Passo 2: Esegui gli script SQL

Copia e incolla i seguenti blocchi SQL uno alla volta, cliccando "Esegui" dopo ogni blocco:

### 🔍 VERIFICA 1: Trova duplicati "Consegna files"

```sql
SELECT c.id, c.griglia_id, c.nome, c.ordine
FROM criteri c
WHERE c.nome = 'Consegna files'
ORDER BY c.griglia_id, c.ordine;
```

Se vedi più righe con lo stesso `griglia_id`, hai dei duplicati.

---

### 🗑️ CORREZIONE 1: Elimina duplicati "Consegna files"

```sql
DELETE c1 FROM criteri c1
INNER JOIN criteri c2
WHERE
    c1.griglia_id = c2.griglia_id
    AND c1.nome = 'Consegna files'
    AND c2.nome = 'Consegna files'
    AND c1.ordine > c2.ordine;
```

Questo elimina i duplicati mantenendo solo quello con ordine minore.

---

### 🔄 CORREZIONE 2: Inverti ordine dei livelli

**Step 1 - Crea tabella temporanea:**
```sql
CREATE TEMPORARY TABLE temp_livelli_updates (
    livello_id INT,
    nuovo_ordine INT
);
```

**Step 2 - Calcola nuovi ordini:**
```sql
INSERT INTO temp_livelli_updates (livello_id, nuovo_ordine)
SELECT
    l.id,
    CASE l.ordine
        WHEN 0 THEN 3
        WHEN 1 THEN 2
        WHEN 2 THEN 1
        WHEN 3 THEN 0
    END as nuovo_ordine
FROM livelli l
WHERE l.criterio_id IN (
    SELECT criterio_id
    FROM livelli
    GROUP BY criterio_id
    HAVING COUNT(*) = 4
);
```

**Step 3 - Applica i nuovi ordini:**
```sql
UPDATE livelli l
JOIN temp_livelli_updates t ON l.id = t.livello_id
SET l.ordine = t.nuovo_ordine;
```

**Step 4 - Pulisci tabella temporanea:**
```sql
DROP TEMPORARY TABLE temp_livelli_updates;
```

---

### ✅ VERIFICA FINALE: Controlla i risultati

Cambia `griglia_id = 1` con l'ID della tua griglia (nell'URL è il parametro `id`):

```sql
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
```

Dovresti vedere:
- Nessun duplicato "Consegna files"
- "Rispetto dei tempi" presente
- Livelli nell'ordine: Base (0), Inter. (1), Avan. (2), Ecc. (3)

---

## 🎯 Risultato atteso

Per ogni criterio dovresti vedere:
- **Livello ordine 0**: Base
- **Livello ordine 1**: Inter.
- **Livello ordine 2**: Avan.
- **Livello ordine 3**: Ecc.

## 🔧 In caso di problemi

Se qualcosa va storto, puoi ricaricare il template:
1. Vai su http://localhost/UDA/admin/griglia-builder.php?id=1
2. Clicca "📊 Template Progetto"
3. Conferma e salva
