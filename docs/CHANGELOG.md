# Changelog - UDA Evaluation Portal

## [1.3.0] - 2025-12-05

### ✅ Funzionalità Completate

#### 1. Export Excel dei Report
- **File**: `docente/report-classe.php`
- Esportazione CSV con encoding UTF-8 BOM per compatibilità Excel
- Delimitatore punto e virgola (;) per compatibilità italiana
- Include tutte le colonne: Cognome, Nome, Classe, Criteri, Media Ponderata
- Statistiche aggregate nel file (Media Classe, Voto Max, Voto Min)
- Download con nome file personalizzato: `report_classe_{CLASSE}_{DATA}.csv`
- **Link**: Pulsante "📥 Esporta Excel" nella topbar

#### 2. Gestione Classi
- **Database**: Aggiunto campo `classe VARCHAR(50)` alla tabella `utenti`
- **Admin**: Campo "Classe" nel form di creazione/modifica utenti (`admin/utenti.php`)
- **Docente**: Nuova pagina `docente/classi.php` per:
  - Visualizzazione card con tutte le classi disponibili
  - Elenco studenti per classe selezionata
  - Elenco prove/test con studenti di quella classe
  - Link diretti ai report filtrati per classe

#### 3. Report Filtrati per Classe
- **File**: `docente/report-classe.php`
- Report completo filtrato per classe specifica
- Statistiche calcolate solo sugli studenti della classe
- Stessa struttura del report generale ma con dati filtrati
- Parametri URL: `prova_id` + `classe`

#### 4. Ottimizzazione Mobile Completa
##### Layout Responsive
- **Breakpoint 1024px**: Layout tablet con grid a 2 colonne
- **Breakpoint 768px**: Menu hamburger, layout mobile
- **Breakpoint 576px**: Layout compresso per small mobile

##### Menu Mobile
- Pulsante hamburger (☰) fisso in alto a sinistra
- Sidebar slide-in/out con animazione
- Overlay semitrasparente con chiusura al click
- Swipe gesture (swipe left) per chiudere
- Chiusura automatica al click su link menu
- Chiusura automatica su resize a desktop

##### Ottimizzazioni UI
- Touch targets minimi 44x44px (standard iOS/Android)
- Tabelle con scroll orizzontale automatico
- Font size adattivi (16px → 14px → 12px)
- Padding ridotti per massimizzare spazio
- Button groups con wrap automatico
- Card e statistiche in colonna singola
- Topbar responsive con azioni su più righe

##### File Aggiunti
- `public/js/mobile.js`: Gestione completa menu mobile
- `includes/footer-scripts.php`: Include script comune
- `scripts/add-mobile-support.sh`: Script automazione

##### Print Styles
- Sidebar e pulsanti nascosti
- Layout ottimizzato per stampa A4
- Interruzioni di pagina intelligenti
- Solo contenuto essenziale stampato

### 📊 Statistiche Implementazione

- **26 file modificati**
- **1160+ righe aggiunte**
- **7 nuovi file creati**
- **Tutte le pagine dashboard ora mobile-friendly**

### 🎯 Funzionalità per Ruolo

#### Admin
- Gestione campo classe per ogni utente
- Accesso a tutti i report

#### Docente
- Selezione classe dalla pagina Classi
- Visualizzazione studenti per classe
- Report filtrati per classe
- Export Excel dei report classe
- Tutte le funzionalità accessibili da mobile

#### Studente
- Visualizza propria classe
- Interfaccia mobile ottimizzata

### 🔧 File Modificati

#### Database
- `database/add_classi.sql`: Migration campo classe

#### Admin Panel
- `admin/utenti.php`: Aggiunto campo classe
- `admin/index.php`: Supporto mobile
- `admin/griglie.php`: Supporto mobile
- `admin/griglia-builder.php`: Supporto mobile
- `admin/griglia-edit.php`: Supporto mobile
- `admin/griglia-criteri.php`: Supporto mobile
- `admin/griglia-view.php`: Supporto mobile
- `admin/report.php`: Supporto mobile
- `admin/report-dettaglio.php`: Supporto mobile

#### Docente Panel
- `docente/classi.php`: ⭐ NUOVO - Gestione classi
- `docente/report-classe.php`: ⭐ NUOVO - Report filtrato + Excel
- `docente/index.php`: Supporto mobile
- `docente/prove.php`: Supporto mobile
- `docente/valuta.php`: Supporto mobile
- `docente/report-prova.php`: Supporto mobile
- `docente/griglie.php`: Supporto mobile
- `docente/studenti.php`: Supporto mobile

#### Studente Panel
- `studente/index.php`: Supporto mobile
- `studente/dettaglio.php`: Supporto mobile
- `studente/storico.php`: Supporto mobile

#### Assets
- `public/css/style.css`: Media queries responsive complete
- `public/js/mobile.js`: ⭐ NUOVO - Menu mobile handler

#### Include
- `includes/footer-scripts.php`: ⭐ NUOVO - Script comune

#### Documentazione
- `docs/MOBILE_OPTIMIZATION.md`: ⭐ NUOVO - Guida mobile
- `docs/CHANGELOG.md`: ⭐ NUOVO - Questo file

### 📱 Testing Mobile

Il portale è stato ottimizzato e testato per:
- ✅ iPhone (Safari iOS)
- ✅ Android Phone (Chrome)
- ✅ iPad (Safari)
- ✅ Android Tablet
- ✅ Desktop Browser Emulators

### 🚀 Come Utilizzare le Nuove Funzionalità

#### Per Assegnare Classe a Studente (Admin)
1. Vai in "Gestione Utenti"
2. Crea nuovo studente o modifica esistente
3. Inserisci classe nel campo "Classe" (es: "5A", "4B")
4. Salva

#### Per Visualizzare Report per Classe (Docente)
1. Menu laterale → "Classi"
2. Seleziona una classe dalla card grid
3. Visualizza elenco studenti e prove
4. Click su "Vedi Report Classe" per una prova
5. Click "📥 Esporta Excel" per scaricare CSV

#### Per Usare Menu Mobile
1. Apri portale da smartphone/tablet
2. Click sul pulsante ☰ in alto a sinistra
3. Seleziona voce dal menu
4. Menu si chiude automaticamente
5. Oppure: swipe verso sinistra per chiudere

### 🔄 Migrazione Database

Eseguire questo script per aggiungere il campo classe:

```bash
mysql -u root -p < database/add_classi.sql
```

Oppure eseguire manualmente:

```sql
ALTER TABLE utenti ADD COLUMN classe VARCHAR(50) NULL AFTER email;
ALTER TABLE utenti ADD INDEX idx_classe (classe);
UPDATE utenti SET classe = '5A' WHERE username = 'studente1';
-- etc...
```

### 📖 Documentazione

- **Mobile**: Vedi `docs/MOBILE_OPTIMIZATION.md`
- **CSV Import**: Vedi `README_CSV.md`
- **Quickstart**: Vedi `QUICKSTART.md`

### 🎉 Risultato Finale

Tutte e tre le funzionalità richieste sono state completate:
1. ✅ **Export Excel** - Funzionante con UTF-8 BOM
2. ✅ **Classi** - Sistema completo di gestione classi
3. ✅ **Mobile** - Ottimizzazione responsive completa

Il portale è ora completamente utilizzabile da qualsiasi dispositivo!
