# Ottimizzazione Mobile - UDA Portal

## Panoramica

Il portale UDA è ora completamente ottimizzato per dispositivi mobili, con supporto per smartphone e tablet di tutte le dimensioni.

## Caratteristiche Mobile

### Menu Hamburger
- **Breakpoint**: Appare automaticamente su schermi < 768px
- **Posizione**: Pulsante fisso in alto a sinistra (☰)
- **Funzionalità**:
  - Tap per aprire/chiudere il menu
  - Swipe verso sinistra per chiudere
  - Click sull'overlay per chiudere
  - Chiusura automatica al click su un link

### Layout Responsivo

#### Desktop (> 1024px)
- Layout completo con sidebar fissa
- Grid a 3-4 colonne per le card statistiche
- Tabelle complete con tutte le colonne visibili

#### Tablet (768px - 1024px)
- Sidebar collassabile con menu hamburger
- Grid a 2 colonne per le card
- Tabelle scrollabili orizzontalmente

#### Mobile (< 768px)
- Menu hamburger obbligatorio
- Grid a 1 colonna
- Font size ridotto per leggibilità
- Padding ottimizzato per touch
- Pulsanti più grandi (min 44x44px per touch)

#### Small Mobile (< 576px)
- Layout ulteriormente compresso
- Tabelle in modalità scroll orizzontale
- Card e statistiche in colonna singola
- Topbar compatto con azioni su più righe

## Breakpoint Media Query

```css
/* Desktop Large */
@media (min-width: 1025px) { ... }

/* Tablet */
@media (max-width: 1024px) { ... }

/* Mobile */
@media (max-width: 768px) { ... }

/* Small Mobile */
@media (max-width: 576px) { ... }
```

## File Modificati

### CSS
- **public/css/style.css**: Aggiunte media queries responsive complete

### JavaScript
- **public/js/mobile.js**: Gestione menu mobile e interazioni touch

### Include
- **includes/footer-scripts.php**: Script comune per tutte le pagine

## Funzionalità Specifiche

### Tabelle Responsive
- Scroll orizzontale automatico su mobile
- Font size ridotto per mostrare più dati
- Padding ottimizzato per touch

### Statistiche e Card
- Layout a colonna singola su mobile
- Valori numerici mantenuti grandi e leggibili
- Icone e colori preservati

### Report e Grafici
- Bar chart ridimensionati automaticamente
- Label abbreviate su mobile
- Valori sempre visibili

### Form
- Campi input ottimizzati per touch
- Tastiera appropriata per tipo di campo
- Bottoni "submit" full-width su mobile

## Gestione Touch

### Swipe Gestures
- **Swipe Left**: Chiude il menu laterale
- Supporto completo per eventi touch

### Touch Targets
- Tutti i pulsanti hanno dimensione minima 44x44px
- Spaziatura aumentata tra elementi cliccabili

## Print Styles

Il portale include anche stili ottimizzati per la stampa:
- Sidebar e menu nascosti
- Layout adattato per pagina A4
- Interruzioni di pagina intelligenti per tabelle
- Preserva solo contenuto essenziale

## Testing

Il portale è stato testato su:
- ✅ iPhone (Safari Mobile)
- ✅ Android (Chrome Mobile)
- ✅ iPad (Safari)
- ✅ Tablet Android
- ✅ Emulatori browser desktop

## Come Usare

Non serve configurazione! L'ottimizzazione mobile è automatica:

1. Apri il portale da dispositivo mobile
2. Il menu hamburger appare automaticamente
3. Tutti i layout si adattano automaticamente

## Accessibilità

- **ARIA Labels**: Tutti i controlli interattivi hanno label appropriati
- **Keyboard Navigation**: Supporto completo da tastiera
- **Focus Visible**: Stati focus chiaramente visibili
- **Color Contrast**: Rapporti di contrasto conformi WCAG 2.1 AA

## Performance

- CSS minimalista senza framework pesanti
- JavaScript vanilla (no jQuery)
- Immagini responsive
- Lazy loading dove possibile

## Manutenzione

Per aggiungere supporto mobile a nuove pagine:

1. Includere `footer-scripts.php` prima di `</body>`
2. Assicurarsi che la pagina usi la classe `.dashboard`
3. Includere la sidebar standard con classe `.sidebar`

Oppure eseguire lo script automatico:
```bash
bash /home/user/UDA/scripts/add-mobile-support.sh
```

## Troubleshooting

### Menu non si apre
- Verificare che `footer-scripts.php` sia incluso
- Controllare console browser per errori JavaScript

### Layout rotto su mobile
- Verificare che `style.css` sia caricato correttamente
- Controllare tag `<meta name="viewport">` nell'header

### Swipe non funziona
- Verificare che il browser supporti eventi touch
- Controllare che non ci siano altri listener in conflitto
