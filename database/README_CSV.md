# Formato CSV per Import Griglie

## Struttura del File CSV

Il file CSV deve avere la seguente struttura:

```csv
TIPO,NOME,PESO,PUNTEGGIO,DESCRIZIONE
CRITERIO,Nome del criterio,Peso del criterio,,Descrizione criterio (opzionale)
LIVELLO,Nome livello,,Punteggio,Descrizione del livello
LIVELLO,Nome livello,,Punteggio,Descrizione del livello
LIVELLO,Nome livello,,Punteggio,Descrizione del livello
LIVELLO,Nome livello,,Punteggio,Descrizione del livello
CRITERIO,Altro criterio,Peso,,
LIVELLO,Nome livello,,Punteggio,Descrizione
...
```

## Colonne

1. **TIPO**: `CRITERIO` o `LIVELLO`
2. **NOME**: Nome del criterio o del livello
3. **PESO**: Peso del criterio (solo per CRITERIO, lasciare vuoto per LIVELLO)
4. **PUNTEGGIO**: Punteggio del livello (solo per LIVELLO, lasciare vuoto per CRITERIO)
5. **DESCRIZIONE**: Descrizione del criterio o del livello

## Regole

- **Header obbligatorio**: La prima riga deve essere `TIPO,NOME,PESO,PUNTEGGIO,DESCRIZIONE`
- **Ogni CRITERIO** deve essere seguito dai suoi LIVELLI
- **4 Livelli per criterio**: Ogni criterio deve avere esattamente 4 livelli
- **Virgolette**: Se una descrizione contiene virgole, racchiudila tra virgolette doppie
- **Encoding**: Salva il file come UTF-8

## Esempio Completo

```csv
TIPO,NOME,PESO,PUNTEGGIO,DESCRIZIONE
CRITERIO,Conoscenze,2,,Verifica delle conoscenze teoriche
LIVELLO,Eccellente,,10,"Conoscenze complete, approfondite e ben strutturate"
LIVELLO,Buono,,8,Conoscenze buone con alcune lacune minori
LIVELLO,Sufficiente,,6,Conoscenze essenziali acquisite
LIVELLO,Insufficiente,,4,Conoscenze lacunose e frammentarie
CRITERIO,Competenze,2,,Valutazione delle competenze pratiche
LIVELLO,Eccellente,,10,Competenze eccellenti e autonomia operativa
LIVELLO,Buono,,8,Competenze buone con supervisione minima
LIVELLO,Sufficiente,,6,Competenze di base con supporto
LIVELLO,Insufficiente,,4,Competenze insufficienti
```

## Come Creare il File

### Con Excel/LibreOffice Calc:

1. Apri Excel/Calc
2. Crea le colonne: TIPO, NOME, PESO, PUNTEGGIO, DESCRIZIONE
3. Compila i dati seguendo la struttura
4. Salva come CSV (Delimitato da virgola)
5. Encoding: UTF-8

### Con Google Sheets:

1. Crea un nuovo foglio
2. Compila i dati
3. File → Scarica → Valori separati da virgola (.csv)

## Template Disponibili

Nel portale puoi scaricare 2 template pronti:

1. **Template Progetto**: 13 criteri per valutazione progetti (come nell'esempio fornito)
2. **Template Semplice**: 3 criteri base (Conoscenze, Competenze, Abilità)

## Tips

- ✅ Usa i template come base e modificali
- ✅ Verifica che ogni criterio abbia 4 livelli
- ✅ Controlla che i punteggi siano numerici (es. 10, 7.5, 5)
- ✅ I pesi possono essere decimali (es. 1, 2.5, 3)
- ❌ Non usare ; (punto e virgola) come separatore
- ❌ Non lasciare righe vuote tra i criteri

## Risoluzione Problemi

**"Nessun criterio trovato"**
→ Verifica che il file abbia l'header corretto e che ci siano righe CRITERIO

**"Criteri senza livelli"**
→ Ogni CRITERIO deve essere seguito da almeno 1 LIVELLO (consigliato 4)

**"Caratteri strani"**
→ Salva il CSV con encoding UTF-8

**"Punteggi non importati"**
→ Usa il punto (.) come separatore decimale, non la virgola (,)
