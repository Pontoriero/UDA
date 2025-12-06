# Correzione Sistema di Calcolo Voti

## 🔍 Problema Identificato

Il sistema utilizzava **punteggi fissi** per i livelli (Base=2.5, Inter=5, Avan=7.5, Ecc=10) indipendentemente dal peso del criterio.

Secondo la griglia cartacea, i punteggi devono essere **proporzionali al peso** del criterio:
- **Base** = Peso × 0.25
- **Intermedio** = Peso × 0.50
- **Avanzato** = Peso × 0.75
- **Eccellente** = Peso × 1.00

### Esempio Pratico

**PRIMA (ERRATO):**
- Criterio "Breve descrizione" (peso 5), livello Avanzato → 7.50 punti (FISSO)
- Criterio "Stakeholders" (peso 1), livello Eccellente → 10.00 punti (FISSO)
- ❌ Un criterio con peso 1 valeva come uno con peso 5!

**DOPO (CORRETTO):**
- Criterio "Breve descrizione" (peso 5), livello Avanzato → 5 × 0.75 = **3.75 punti**
- Criterio "Stakeholders" (peso 1), livello Eccellente → 1 × 1.00 = **1.00 punto**
- ✅ I pesi ora influiscono correttamente sul punteggio!

## 📋 Modifiche Applicate

### 1. Database
- I punteggi nella tabella `livelli` sono stati convertiti in percentuali (0.25, 0.50, 0.75, 1.00)
- Le valutazioni esistenti sono state ricalcolate con la formula corretta

### 2. Codice PHP
- **docente/valuta.php**: Calcolo punteggio = peso × percentuale al salvataggio
- **docente/studenti.php**: Formula voto = (SUM(punteggio) / SUM(peso)) × 10
- **studente/index.php**: Aggiornate query di calcolo media
- **studente/storico.php**: Aggiornate query di calcolo voto
- **docente/studente-dettaglio.php**: Aggiornato calcolo media ponderata

### 3. Formula Finale
```
Voto = (Somma punteggi / Somma pesi) × 10
```

Dove:
- **punteggio** = peso_criterio × percentuale_livello
- **Somma punteggi** = somma di tutti i (peso × percentuale)
- **Somma pesi** = somma di tutti i pesi
- Il risultato è moltiplicato per 10 per avere un voto su 10

## 🚀 Come Applicare

### Opzione 1: Tramite phpMyAdmin (CONSIGLIATO)
1. Accedi a phpMyAdmin
2. Seleziona il database `uda_valutazioni`
3. Vai su "SQL"
4. Copia e incolla il contenuto di `database/fix_punteggio_percentuale.sql`
5. Clicca su "Esegui"

### Opzione 2: Tramite command line
```bash
mysql -u root -p uda_valutazioni < database/fix_punteggio_percentuale.sql
```

## ✅ Verifica

Dopo aver applicato le modifiche, verifica con uno studente già valutato:

**Calcolo Manuale (esempio Bagni Alessandro):**
```
Criterio 1 (peso 1) × Avan (0.75) = 0.75
Criterio 2 (peso 5) × Avan (0.75) = 3.75
Criterio 3 (peso 5) × Ecc (1.00) = 5.00
... (altri criteri)
────────────────────────────────────────
Somma punteggi = 24.25
Somma pesi = 43

Voto = (24.25 / 43) × 10 = 5.64 ✓
```

## 📊 Impatto

- ✅ I voti ora riflettono correttamente l'importanza (peso) dei criteri
- ✅ Conformità con la griglia cartacea fornita
- ✅ Tutti i voti esistenti saranno ricalcolati automaticamente
- ✅ Le future valutazioni useranno la formula corretta

## ⚠️ Note Importanti

1. **Backup**: Assicurati di avere un backup del database prima di procedere
2. **Valutazioni esistenti**: Verranno automaticamente ricalcolate con la nuova formula
3. **Griglie personalizzate**: Se hai creato griglie con punteggi diversi, assicurati di verificarle

## 📞 Supporto

Per qualsiasi problema o domanda:
- Verifica che la migrazione sia stata eseguita correttamente
- Controlla i log per eventuali errori
- Testa con alcune valutazioni di esempio
