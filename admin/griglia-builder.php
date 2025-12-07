<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

$db = getDB();
$griglia_id = get('id', 0);

if (!$griglia_id) {
    setErrorMessage('ID griglia non valido');
    redirect('griglie.php');
}

// Carica griglia
$stmt = $db->prepare("SELECT * FROM griglie WHERE id = ?");
$stmt->execute([$griglia_id]);
$griglia = $stmt->fetch();

if (!$griglia) {
    setErrorMessage('Griglia non trovata');
    redirect('griglie.php');
}

// Carica criteri con livelli
$stmt = $db->prepare("SELECT * FROM criteri WHERE griglia_id = ? ORDER BY ordine");
$stmt->execute([$griglia_id]);
$criteri = $stmt->fetchAll();

foreach ($criteri as &$criterio) {
    $stmt = $db->prepare("SELECT * FROM livelli WHERE criterio_id = ? ORDER BY ordine");
    $stmt->execute([$criterio['id']]);
    $criterio['livelli'] = $stmt->fetchAll();
}

// Gestione salvataggio via AJAX
if (isPost() && get('ajax') == '1') {
    header('Content-Type: application/json');

    $azione = post('azione');

    try {
        $db->beginTransaction();

        if ($azione === 'salva_tutto') {
            // Ricevi JSON con tutta la struttura
            $data = json_decode(post('data'), true);

            // Elimina criteri e livelli esistenti
            $stmt = $db->prepare("DELETE FROM criteri WHERE griglia_id = ?");
            $stmt->execute([$griglia_id]);

            // Inserisci nuovi criteri e livelli
            foreach ($data['criteri'] as $ordine => $criterio) {
                $stmt = $db->prepare("INSERT INTO criteri (griglia_id, nome, descrizione, peso, ordine) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $griglia_id,
                    $criterio['nome'],
                    $criterio['descrizione'] ?? '',
                    $criterio['peso'],
                    $ordine
                ]);
                $criterio_id = $db->lastInsertId();

                // Inserisci livelli
                foreach ($criterio['livelli'] as $liv_ordine => $livello) {
                    $stmt = $db->prepare("INSERT INTO livelli (criterio_id, nome, descrizione, punteggio, ordine) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $criterio_id,
                        $livello['nome'],
                        $livello['descrizione'],
                        $livello['punteggio'],
                        $liv_ordine
                    ]);
                }
            }

            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Griglia salvata con successo']);
            exit;
        }

        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Azione non valida']);
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crea Griglia Veloce - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        .griglia-builder {
            max-width: 1400px;
            margin: 0 auto;
        }

        .builder-toolbar {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: center;
            box-shadow: var(--shadow);
        }

        .criterio-card {
            background: white;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            position: relative;
        }

        .criterio-card.dragging {
            opacity: 0.5;
            border-color: var(--primary-color);
        }

        .criterio-header {
            display: grid;
            grid-template-columns: 40px 2fr 1fr 80px;
            gap: 10px;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary-color);
        }

        .drag-handle {
            cursor: move;
            font-size: 24px;
            color: var(--text-muted);
            text-align: center;
        }

        .livelli-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-top: 15px;
        }

        .livello-card {
            background: #f9fafb;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 15px;
        }

        .livello-card input[type="text"],
        .livello-card input[type="number"],
        .livello-card textarea {
            width: 100%;
            margin: 5px 0;
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 13px;
        }

        .livello-card textarea {
            min-height: 80px;
            resize: vertical;
        }

        .livello-label {
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 10px;
            display: block;
        }

        .template-selector {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 2px dashed var(--border-color);
        }

        .template-btn {
            display: inline-block;
            padding: 8px 15px;
            margin: 5px;
            background: var(--light-color);
            border: 1px solid var(--border-color);
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .template-btn:hover {
            background: var(--primary-color);
            color: white;
        }

        .btn-remove-criterio {
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--danger-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            cursor: pointer;
            font-size: 18px;
            line-height: 1;
        }

        .progress-indicator {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--success-color);
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: var(--shadow-lg);
            display: none;
            z-index: 1000;
        }

        .quick-stats {
            background: var(--info-color);
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            font-size: 14px;
        }

        .csv-import-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .csv-import-box input[type="file"] {
            display: none;
        }

        .btn-upload {
            background: white;
            color: #667eea;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            border: none;
            transition: all 0.3s;
        }

        .btn-upload:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .csv-info {
            flex: 1;
        }

        .csv-info h3 {
            margin: 0 0 5px 0;
            font-size: 18px;
        }

        .csv-info p {
            margin: 0;
            opacity: 0.9;
            font-size: 14px;
        }

        .csv-templates {
            display: flex;
            gap: 10px;
        }

        .csv-templates a {
            color: white;
            text-decoration: underline;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            <div class="topbar">
                <h1>🚀 Builder Griglia Veloce: <?php echo e($griglia['nome']); ?></h1>
                <div class="topbar-actions">
                    <button onclick="salvaGriglia()" class="btn btn-success">💾 Salva Tutto</button>
                    <a href="griglia-edit.php?id=<?php echo $griglia_id; ?>" class="btn btn-secondary btn-sm">← Indietro</a>
                    <a href="../public/logout.php" class="btn btn-secondary btn-sm">Logout</a>
                </div>
            </div>

            <div class="griglia-builder">
                <!-- Toolbar -->
                <div class="builder-toolbar">
                    <button onclick="aggiungiCriterio()" class="btn btn-primary">+ Aggiungi Criterio</button>
                    <button onclick="caricaTemplate('standard')" class="btn btn-secondary">📋 Template Standard</button>
                    <button onclick="caricaTemplate('progetto')" class="btn btn-secondary">📊 Template Progetto</button>
                    <button onclick="duplicaUltimo()" class="btn btn-warning">📑 Duplica Ultimo</button>
                    <div class="quick-stats" id="stats">
                        <strong>Criteri: <span id="count-criteri">0</span></strong> |
                        Peso totale: <span id="peso-totale">0</span>
                    </div>
                </div>

                <!-- Import CSV -->
                <div class="csv-import-box">
                    <div class="csv-info">
                        <h3>📥 Importa da CSV</h3>
                        <p>Carica un file CSV per importare velocemente criteri e livelli</p>
                        <div class="csv-templates">
                            <a href="../database/template_griglia_progetto.csv" download>⬇️ Scarica Template Progetto</a>
                            <a href="../database/template_griglia_semplice.csv" download>⬇️ Scarica Template Semplice</a>
                        </div>
                    </div>
                    <label for="csv-file-input" class="btn-upload">
                        📁 Scegli File CSV
                    </label>
                    <input type="file" id="csv-file-input" accept=".csv" onchange="importaCSV(event)">
                </div>

                <!-- Template Livelli Veloci -->
                <div class="template-selector">
                    <strong>🎯 Template Livelli Rapidi:</strong><br>
                    <span class="template-btn" onclick="applicaTemplateBase()">Base (4 livelli numerici)</span>
                    <span class="template-btn" onclick="applicaTemplateTesto()">Testuale (Eccellente, Buono, Sufficiente, Insufficiente)</span>
                    <span class="template-btn" onclick="applicaTemplateProgetto()">Progetto (Base, Inter., Avan., Ecc.)</span>
                </div>

                <!-- Container Criteri -->
                <div id="criteri-container">
                    <!-- I criteri vengono aggiunti dinamicamente qui -->
                </div>

                <button onclick="aggiungiCriterio()" class="btn btn-primary btn-block" style="margin-top: 20px;">+ Aggiungi Altro Criterio</button>
            </div>

            <div class="progress-indicator" id="progress-indicator">
                ✓ Salvato!
            </div>
        </div>
    </div>

    <script>
    let criteri = <?php echo json_encode($criteri); ?>;
    let criterioCounter = criteri.length;
    let templateLivelliCorrente = null;

    // Template livelli predefiniti
    const templateLivelli = {
        base: [
            { nome: 'Livello 4', descrizione: 'Ottimo', punteggio: 10 },
            { nome: 'Livello 3', descrizione: 'Buono', punteggio: 8 },
            { nome: 'Livello 2', descrizione: 'Sufficiente', punteggio: 6 },
            { nome: 'Livello 1', descrizione: 'Insufficiente', punteggio: 4 }
        ],
        testuale: [
            { nome: 'Eccellente', descrizione: 'Lavoro eccellente e professionale', punteggio: 10 },
            { nome: 'Buono', descrizione: 'Lavoro buono con alcune imperfezioni', punteggio: 8 },
            { nome: 'Sufficiente', descrizione: 'Lavoro sufficiente', punteggio: 6 },
            { nome: 'Insufficiente', descrizione: 'Lavoro insufficiente', punteggio: 4 }
        ],
        progetto: [
            { nome: 'Ecc.', descrizione: 'Completa e professionale', punteggio: 10 },
            { nome: 'Avan.', descrizione: 'Completa', punteggio: 7.5 },
            { nome: 'Inter.', descrizione: 'Parzialmente completa', punteggio: 5 },
            { nome: 'Base', descrizione: 'Manca o è incompleta', punteggio: 2.5 }
        ]
    };

    // Carica criteri esistenti
    document.addEventListener('DOMContentLoaded', function() {
        if (criteri.length > 0) {
            criteri.forEach((criterio, index) => {
                aggiungiCriterioDOM(criterio, index);
            });
        } else {
            // Se non ci sono criteri, aggiungi uno vuoto
            aggiungiCriterio();
        }
        aggiornaStats();
    });

    function aggiungiCriterio(dati = null) {
        const criterio = dati || {
            id: 'new_' + criterioCounter,
            nome: '',
            descrizione: '',
            peso: 1.0,
            livelli: templateLivelli.base
        };

        aggiungiCriterioDOM(criterio, criterioCounter);
        criterioCounter++;
        aggiornaStats();
    }

    function aggiungiCriterioDOM(criterio, index) {
        const container = document.getElementById('criteri-container');
        const card = document.createElement('div');
        card.className = 'criterio-card';
        card.dataset.index = index;
        card.draggable = true;

        const livelli = criterio.livelli || templateLivelli.base;

        card.innerHTML = `
            <button class="btn-remove-criterio" onclick="rimuoviCriterio(${index})" title="Rimuovi criterio">×</button>

            <div class="criterio-header">
                <div class="drag-handle" title="Trascina per riordinare">⋮⋮</div>
                <input type="text" class="form-control" placeholder="Nome criterio *"
                       value="${criterio.nome || ''}"
                       onchange="aggiornaCriterio(${index}, 'nome', this.value)">
                <input type="number" class="form-control" placeholder="Peso" step="0.1" min="0"
                       value="${criterio.peso || 1}"
                       onchange="aggiornaCriterio(${index}, 'peso', this.value)">
                <span class="badge badge-primary">Criterio ${index + 1}</span>
            </div>

            <input type="text" class="form-control" placeholder="Descrizione criterio (opzionale)"
                   value="${criterio.descrizione || ''}"
                   onchange="aggiornaCriterio(${index}, 'descrizione', this.value)">

            <div class="livelli-grid">
                ${livelli.map((livello, livIndex) => `
                    <div class="livello-card">
                        <span class="livello-label">Livello ${livIndex + 1}</span>
                        <input type="text" placeholder="Nome livello"
                               value="${livello.nome || ''}"
                               onchange="aggiornaLivello(${index}, ${livIndex}, 'nome', this.value)">
                        <input type="number" step="0.1" placeholder="Punteggio"
                               value="${livello.punteggio || 0}"
                               onchange="aggiornaLivello(${index}, ${livIndex}, 'punteggio', this.value)">
                        <textarea placeholder="Descrizione livello"
                                  onchange="aggiornaLivello(${index}, ${livIndex}, 'descrizione', this.value)">${livello.descrizione || ''}</textarea>
                    </div>
                `).join('')}
            </div>
        `;

        // Drag and drop
        card.addEventListener('dragstart', handleDragStart);
        card.addEventListener('dragover', handleDragOver);
        card.addEventListener('drop', handleDrop);
        card.addEventListener('dragend', handleDragEnd);

        container.appendChild(card);
    }

    function aggiornaCriterio(index, campo, valore) {
        const cards = document.querySelectorAll('.criterio-card');
        const realIndex = Array.from(cards).findIndex(c => c.dataset.index == index);

        if (!criteri[realIndex]) {
            criteri[realIndex] = { livelli: templateLivelli.base };
        }

        criteri[realIndex][campo] = valore;
        aggiornaStats();
    }

    function aggiornaLivello(criterioIndex, livelloIndex, campo, valore) {
        const cards = document.querySelectorAll('.criterio-card');
        const realIndex = Array.from(cards).findIndex(c => c.dataset.index == criterioIndex);

        if (!criteri[realIndex]) {
            criteri[realIndex] = { livelli: [] };
        }
        if (!criteri[realIndex].livelli[livelloIndex]) {
            criteri[realIndex].livelli[livelloIndex] = {};
        }

        criteri[realIndex].livelli[livelloIndex][campo] = valore;
    }

    function rimuoviCriterio(index) {
        if (!confirm('Sei sicuro di voler rimuovere questo criterio?')) return;

        const card = document.querySelector(`.criterio-card[data-index="${index}"]`);
        card.remove();
        aggiornaStats();
    }

    function duplicaUltimo() {
        const cards = document.querySelectorAll('.criterio-card');
        if (cards.length === 0) {
            alert('Nessun criterio da duplicare');
            return;
        }

        const lastCard = cards[cards.length - 1];
        const lastIndex = parseInt(lastCard.dataset.index);
        const lastCriterio = leggiCriterioDaDOM(lastCard);

        aggiungiCriterio({
            ...lastCriterio,
            nome: lastCriterio.nome + ' (copia)'
        });
    }

    function applicaTemplateBase() {
        templateLivelliCorrente = templateLivelli.base;
        alert('Template Base applicato! I prossimi criteri useranno questo template.');
    }

    function applicaTemplateTesto() {
        templateLivelliCorrente = templateLivelli.testuale;
        alert('Template Testuale applicato! I prossimi criteri useranno questo template.');
    }

    function applicaTemplateProgetto() {
        templateLivelliCorrente = templateLivelli.progetto;
        alert('Template Progetto applicato! I prossimi criteri useranno questo template.');
    }

    function caricaTemplate(tipo) {
        if (!confirm('Questo sostituirà tutti i criteri esistenti. Continuare?')) return;

        document.getElementById('criteri-container').innerHTML = '';
        criteri = [];
        criterioCounter = 0;

        if (tipo === 'progetto') {
            // Template per valutazione progetti
            const criteriProgetto = [
                { nome: 'Intestazione del Progetto', peso: 1, livelli: templateLivelli.progetto },
                { nome: 'Breve descrizione del progetto', peso: 5, livelli: templateLivelli.progetto },
                { nome: 'Obiettivi del progetto', peso: 5, livelli: templateLivelli.progetto },
                { nome: 'Stakeholders principali', peso: 1, livelli: templateLivelli.progetto },
                { nome: 'Attività da eseguire', peso: 5, livelli: templateLivelli.progetto },
                { nome: 'Analisi dei rischi', peso: 3, livelli: templateLivelli.progetto },
                { nome: 'Principali Deliverable', peso: 3, livelli: templateLivelli.progetto },
                { nome: 'Milestone', peso: 3, livelli: templateLivelli.progetto },
                { nome: 'Principali risorse', peso: 3, livelli: templateLivelli.progetto },
                { nome: 'Tempistica preliminare (Gantt)', peso: 4, livelli: templateLivelli.progetto },
                { nome: 'Autorizzazioni', peso: 1, livelli: templateLivelli.progetto },
                { nome: 'WBS (Work Breakdown Structure)', peso: 4, livelli: templateLivelli.progetto },
                { nome: 'Consegna files', peso: 5, livelli: templateLivelli.progetto },
                { nome: 'Rispetto dei tempi', peso: 5, livelli: templateLivelli.progetto }
            ];

            criteriProgetto.forEach(c => aggiungiCriterio(c));
        } else if (tipo === 'standard') {
            // Template standard
            const criteriStandard = [
                { nome: 'Conoscenze', peso: 2, livelli: templateLivelli.testuale },
                { nome: 'Competenze', peso: 2, livelli: templateLivelli.testuale },
                { nome: 'Abilità', peso: 1, livelli: templateLivelli.testuale }
            ];

            criteriStandard.forEach(c => aggiungiCriterio(c));
        }

        aggiornaStats();
    }

    function aggiornaStats() {
        const cards = document.querySelectorAll('.criterio-card');
        const numCriteri = cards.length;

        let pesoTotale = 0;
        cards.forEach(card => {
            const pesoInput = card.querySelector('input[type="number"]');
            pesoTotale += parseFloat(pesoInput.value) || 0;
        });

        document.getElementById('count-criteri').textContent = numCriteri;
        document.getElementById('peso-totale').textContent = pesoTotale.toFixed(1);
    }

    function leggiCriterioDaDOM(card) {
        const inputs = card.querySelectorAll('input[type="text"], input[type="number"], textarea');
        const nomeInput = card.querySelector('.criterio-header input[type="text"]');
        const pesoInput = card.querySelector('.criterio-header input[type="number"]');
        const descrizioneInput = card.querySelector('input[type="text"]:not(.criterio-header input)');

        const peso = parseFloat(pesoInput.value) || 1;
        const livelli = [];
        const livelliCards = card.querySelectorAll('.livello-card');

        livelliCards.forEach(livCard => {
            const inputs = livCard.querySelectorAll('input, textarea');
            const punteggioInput = parseFloat(inputs[1].value) || 0;

            // Calcola il punteggio proporzionale al peso del criterio
            // Se il punteggio è > 1, è un valore del template (2.5, 5, 7.5, 10)
            // Lo convertiamo in frazione (/10) e moltiplichiamo per il peso
            const frazione = punteggioInput > 1 ? punteggioInput / 10 : punteggioInput;
            const punteggioFinale = peso * frazione;

            livelli.push({
                nome: inputs[0].value,
                punteggio: punteggioFinale,
                descrizione: inputs[2].value
            });
        });

        return {
            nome: nomeInput.value,
            descrizione: descrizioneInput.value,
            peso: peso,
            livelli: livelli
        };
    }

    function salvaGriglia() {
        const cards = document.querySelectorAll('.criterio-card');
        const criteriData = [];

        let errori = [];

        cards.forEach((card, index) => {
            const criterio = leggiCriterioDaDOM(card);

            if (!criterio.nome.trim()) {
                errori.push(`Criterio ${index + 1}: nome obbligatorio`);
            }

            criterio.livelli.forEach((liv, livIndex) => {
                if (!liv.nome.trim()) {
                    errori.push(`Criterio ${index + 1}, Livello ${livIndex + 1}: nome obbligatorio`);
                }
                if (!liv.descrizione.trim()) {
                    errori.push(`Criterio ${index + 1}, Livello ${livIndex + 1}: descrizione obbligatoria`);
                }
            });

            criteriData.push(criterio);
        });

        if (errori.length > 0) {
            alert('Errori di validazione:\n\n' + errori.join('\n'));
            return;
        }

        if (criteriData.length === 0) {
            alert('Aggiungi almeno un criterio prima di salvare');
            return;
        }

        // Salva via AJAX
        const formData = new FormData();
        formData.append('azione', 'salva_tutto');
        formData.append('data', JSON.stringify({ criteri: criteriData }));

        fetch('griglia-builder.php?id=<?php echo $griglia_id; ?>&ajax=1', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostraProgressoIndicator();
                setTimeout(() => {
                    window.location.href = 'griglia-edit.php?id=<?php echo $griglia_id; ?>';
                }, 1000);
            } else {
                alert('Errore: ' + data.message);
            }
        })
        .catch(error => {
            alert('Errore durante il salvataggio: ' + error);
        });
    }

    function mostraProgressoIndicator() {
        const indicator = document.getElementById('progress-indicator');
        indicator.style.display = 'block';
        setTimeout(() => {
            indicator.style.display = 'none';
        }, 3000);
    }

    // Drag and Drop
    let draggedElement = null;

    function handleDragStart(e) {
        draggedElement = this;
        this.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
    }

    function handleDragOver(e) {
        if (e.preventDefault) {
            e.preventDefault();
        }
        e.dataTransfer.dropEffect = 'move';
        return false;
    }

    function handleDrop(e) {
        if (e.stopPropagation) {
            e.stopPropagation();
        }

        if (draggedElement !== this) {
            const container = document.getElementById('criteri-container');
            const allCards = [...container.querySelectorAll('.criterio-card')];
            const draggedIndex = allCards.indexOf(draggedElement);
            const targetIndex = allCards.indexOf(this);

            if (draggedIndex < targetIndex) {
                this.parentNode.insertBefore(draggedElement, this.nextSibling);
            } else {
                this.parentNode.insertBefore(draggedElement, this);
            }
        }

        return false;
    }

    function handleDragEnd(e) {
        this.classList.remove('dragging');
    }

    // Import CSV
    function importaCSV(event) {
        const file = event.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function(e) {
            const csv = e.target.result;
            parseCSV(csv);
        };
        reader.readAsText(file);
    }

    function parseCSV(csv) {
        const lines = csv.split('\n').filter(line => line.trim());

        // Salta l'header
        const dataLines = lines.slice(1);

        const criteriImportati = [];
        let criterioCorrente = null;
        let livelli = [];

        dataLines.forEach(line => {
            // Parse CSV considerando virgole dentro le virgolette
            const columns = parseCSVLine(line);

            const tipo = columns[0]?.trim();
            const nome = columns[1]?.trim();
            const peso = columns[2]?.trim();
            const punteggio = columns[3]?.trim();
            const descrizione = columns[4]?.trim();

            if (tipo === 'CRITERIO') {
                // Se c'era un criterio precedente, salvalo
                if (criterioCorrente) {
                    criterioCorrente.livelli = livelli;
                    criteriImportati.push(criterioCorrente);
                }

                // Inizia un nuovo criterio
                criterioCorrente = {
                    nome: nome,
                    peso: parseFloat(peso) || 1.0,
                    descrizione: descrizione || '',
                    livelli: []
                };
                livelli = [];
            } else if (tipo === 'LIVELLO' && criterioCorrente) {
                livelli.push({
                    nome: nome,
                    punteggio: parseFloat(punteggio) || 0,
                    descrizione: descrizione || ''
                });
            }
        });

        // Aggiungi l'ultimo criterio
        if (criterioCorrente) {
            criterioCorrente.livelli = livelli;
            criteriImportati.push(criterioCorrente);
        }

        // Conferma import
        if (criteriImportati.length === 0) {
            alert('Nessun criterio trovato nel file CSV. Verifica il formato.');
            return;
        }

        const conferma = confirm(
            `Trovati ${criteriImportati.length} criteri nel file CSV.\n\n` +
            `Questo sostituirà tutti i criteri esistenti. Continuare?`
        );

        if (!conferma) return;

        // Pulisci e importa
        document.getElementById('criteri-container').innerHTML = '';
        criteri = [];
        criterioCounter = 0;

        criteriImportati.forEach(criterio => {
            aggiungiCriterio(criterio);
        });

        aggiornaStats();

        // Mostra messaggio di successo
        alert(`✅ Importati ${criteriImportati.length} criteri con successo!`);

        // Reset input file
        document.getElementById('csv-file-input').value = '';
    }

    function parseCSVLine(line) {
        const result = [];
        let current = '';
        let inQuotes = false;

        for (let i = 0; i < line.length; i++) {
            const char = line[i];
            const nextChar = line[i + 1];

            if (char === '"') {
                if (inQuotes && nextChar === '"') {
                    current += '"';
                    i++; // Skip next quote
                } else {
                    inQuotes = !inQuotes;
                }
            } else if (char === ',' && !inQuotes) {
                result.push(current);
                current = '';
            } else {
                current += char;
            }
        }

        result.push(current);
        return result;
    }
    </script>
    <?php include __DIR__ . '/../includes/footer-scripts.php'; ?>
</body>
</html>
