# Istruzioni per correggere l'ordine dei livelli

## Problema
I livelli nel database sono ancora invertiti. Anche se il template è stato corretto, le griglie già create hanno i livelli salvati con l'ordine sbagliato.

## Soluzione

### Opzione 1: Usa lo script PHP (Raccomandato)

1. Modifica le credenziali del database in `fix-livelli-order.php` (linee 8-11) se necessario:
   ```php
   $dbHost = 'localhost';
   $dbName = 'uda_portal';
   $dbUser = 'root';
   $dbPass = '';
   ```

2. Esegui lo script da terminale:
   ```bash
   php fix-livelli-order.php
   ```

3. Lo script invertirà automaticamente l'ordine dei livelli per tutti i criteri con 4 livelli.

### Opzione 2: Usa lo script SQL

1. Connettiti al database MySQL:
   ```bash
   mysql -u root -p uda_portal
   ```

2. Esegui lo script SQL:
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
