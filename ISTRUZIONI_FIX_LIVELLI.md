# Istruzioni per correggere l'ordine dei livelli e duplicati

## Problemi rilevati

### 1. Ordine livelli invertito
I livelli nel database sono invertiti. Anche se il template è stato corretto, le griglie già create hanno i livelli salvati con l'ordine sbagliato.

### 2. Criterio "Consegna files" duplicato
In alcune griglie il criterio "Consegna files" è presente due volte, e manca "Rispetto dei tempi".

## Soluzione

### Opzione 1: Usa gli script PHP (Raccomandato)

1. Modifica le credenziali del database (se necessario) in entrambi gli script:
   - `fix-livelli-order.php` (linee 8-11)
   - `fix-duplicato-consegna.php` (linee 8-11)

   ```php
   $dbHost = 'localhost';
   $dbName = 'uda_portal';
   $dbUser = 'root';
   $dbPass = '';
   ```

2. Esegui gli script da terminale:
   ```bash
   # 1. Rimuovi il duplicato "Consegna files"
   php fix-duplicato-consegna.php

   # 2. Inverti l'ordine dei livelli
   php fix-livelli-order.php
   ```

3. Gli script correggeranno automaticamente tutte le griglie.

### Opzione 2: Usa gli script SQL

1. Connettiti al database MySQL:
   ```bash
   mysql -u root -p uda_portal
   ```

2. Esegui prima la correzione del duplicato manualmente:
   ```sql
   -- Trova e rimuovi duplicati "Consegna files"
   SELECT id, griglia_id, nome, ordine FROM criteri WHERE nome = 'Consegna files';

   -- Se trovi duplicati, elimina quello con ordine maggiore
   -- DELETE FROM criteri WHERE id = <ID_DUPLICATO>;
   ```

3. Poi esegui lo script per invertire i livelli:
   ```sql
   source database/fix-livelli-order.sql;
   ```

### Opzione 3: Ricarica il template nella griglia

Se preferisci non toccare il database direttamente:

1. Vai su http://localhost/UDA/admin/griglia-builder.php?id=11
2. Clicca sul pulsante "📊 Template Progetto"
3. Conferma di voler sostituire i criteri esistenti
4. Salva la griglia

Ora il template caricherà i livelli nell'ordine corretto:
- **Livello 1: Base** (punteggio proporzionale 25%)
- **Livello 2: Inter.** (punteggio proporzionale 50%)
- **Livello 3: Avan.** (punteggio proporzionale 75%)
- **Livello 4: Ecc.** (punteggio proporzionale 100%)

## Verifica

Dopo aver eseguito la correzione, ricarica la pagina del builder e verifica che i livelli siano nell'ordine corretto.
