-- Inserimento studenti di esempio e valutazioni per testing report

USE uda_portal;

-- Aggiungi altri 4 studenti (studente1 esiste già)
INSERT INTO utenti (username, password, nome, cognome, email, ruolo) VALUES
('studente2', '$2y$12$Zl0BhihoGFhUHIqcyTuBBunRGIbSLO8m9Fmzo.T3hbDk3tJ7WwdK6', 'Maria', 'Verdi', 'maria.verdi@studenti.it', 'studente'),
('studente3', '$2y$12$Zl0BhihoGFhUHIqcyTuBBunRGIbSLO8m9Fmzo.T3hbDk3tJ7WwdK6', 'Giuseppe', 'Neri', 'giuseppe.neri@studenti.it', 'studente'),
('studente4', '$2y$12$Zl0BhihoGFhUHIqcyTuBBunRGIbSLO8m9Fmzo.T3hbDk3tJ7WwdK6', 'Anna', 'Gialli', 'anna.gialli@studenti.it', 'studente'),
('studente5', '$2y$12$Zl0BhihoGFhUHIqcyTuBBunRGIbSLO8m9Fmzo.T3hbDk3tJ7WwdK6', 'Marco', 'Blu', 'marco.blu@studenti.it', 'studente');

-- Crea una griglia di esempio se non esiste
INSERT INTO griglie (nome, descrizione, anno_scolastico, materia, creato_da, attiva)
SELECT 'Valutazione Progetto UDA', 'Griglia per valutazione progetti didattici', '2024/2025', 'Project Management', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM griglie WHERE nome = 'Valutazione Progetto UDA');

SET @griglia_id = (SELECT id FROM griglie WHERE nome = 'Valutazione Progetto UDA' LIMIT 1);

-- Crea criteri di esempio
INSERT INTO criteri (griglia_id, nome, descrizione, peso, ordine) VALUES
(@griglia_id, 'Descrizione del progetto', 'Chiarezza e completezza della descrizione', 2.0, 1),
(@griglia_id, 'Obiettivi', 'Definizione degli obiettivi SMART', 2.0, 2),
(@griglia_id, 'Pianificazione', 'Qualità della pianificazione temporale', 1.5, 3),
(@griglia_id, 'Documentazione', 'Completezza della documentazione', 1.5, 4);

-- Crea livelli per ogni criterio
-- Criterio 1: Descrizione
SET @criterio1 = (SELECT id FROM criteri WHERE griglia_id = @griglia_id AND nome = 'Descrizione del progetto' LIMIT 1);
INSERT INTO livelli (criterio_id, nome, descrizione, punteggio, ordine) VALUES
(@criterio1, 'Eccellente', 'Descrizione dettagliata, precisa e contestualizzata', 10, 0),
(@criterio1, 'Buono', 'Descrizione chiara con terminologia tecnica', 8, 1),
(@criterio1, 'Sufficiente', 'Descrizione generica e approssimativa', 6, 2),
(@criterio1, 'Insufficiente', 'Descrizione confusa o fuori contesto', 4, 3);

-- Criterio 2: Obiettivi
SET @criterio2 = (SELECT id FROM criteri WHERE griglia_id = @griglia_id AND nome = 'Obiettivi' LIMIT 1);
INSERT INTO livelli (criterio_id, nome, descrizione, punteggio, ordine) VALUES
(@criterio2, 'Eccellente', 'Obiettivi SMART completi e prioritizzati', 10, 0),
(@criterio2, 'Buono', 'Obiettivi SMART ben definiti', 8, 1),
(@criterio2, 'Sufficiente', 'Obiettivi approssimativi poco specifici', 6, 2),
(@criterio2, 'Insufficiente', 'Non identifica obiettivi o sono generici', 4, 3);

-- Criterio 3: Pianificazione
SET @criterio3 = (SELECT id FROM criteri WHERE griglia_id = @griglia_id AND nome = 'Pianificazione' LIMIT 1);
INSERT INTO livelli (criterio_id, nome, descrizione, punteggio, ordine) VALUES
(@criterio3, 'Eccellente', 'Pianificazione professionale con Gantt e dipendenze', 10, 0),
(@criterio3, 'Buono', 'Pianificazione corretta e completa', 8, 1),
(@criterio3, 'Sufficiente', 'Pianificazione incompleta', 6, 2),
(@criterio3, 'Insufficiente', 'Pianificazione assente o scorretta', 4, 3);

-- Criterio 4: Documentazione
SET @criterio4 = (SELECT id FROM criteri WHERE griglia_id = @griglia_id AND nome = 'Documentazione' LIMIT 1);
INSERT INTO livelli (criterio_id, nome, descrizione, punteggio, ordine) VALUES
(@criterio4, 'Eccellente', 'Documentazione completa e organizzazione professionale', 10, 0),
(@criterio4, 'Buono', 'Documentazione completa', 8, 1),
(@criterio4, 'Sufficiente', 'Documentazione parziale', 6, 2),
(@criterio4, 'Insufficiente', 'Documentazione essenziale mancante', 4, 3);

-- Crea una prova di esempio
INSERT INTO prove (nome, descrizione, griglia_id, data_prova, docente_id)
SELECT 'Progetto UDA - Primo Quadrimestre', 'Valutazione progetto didattico primo periodo', @griglia_id, '2024-12-01', 2
WHERE NOT EXISTS (SELECT 1 FROM prove WHERE nome = 'Progetto UDA - Primo Quadrimestre');

SET @prova_id = (SELECT id FROM prove WHERE nome = 'Progetto UDA - Primo Quadrimestre' LIMIT 1);

-- Ottieni ID studenti
SET @studente1 = (SELECT id FROM utenti WHERE username = 'studente1' LIMIT 1);
SET @studente2 = (SELECT id FROM utenti WHERE username = 'studente2' LIMIT 1);
SET @studente3 = (SELECT id FROM utenti WHERE username = 'studente3' LIMIT 1);
SET @studente4 = (SELECT id FROM utenti WHERE username = 'studente4' LIMIT 1);
SET @studente5 = (SELECT id FROM utenti WHERE username = 'studente5' LIMIT 1);

-- Valutazioni STUDENTE 1 (Luca Bianchi) - Performance eccellente
INSERT INTO valutazioni (prova_id, studente_id, criterio_id, livello_id, punteggio, note) VALUES
(@prova_id, @studente1, @criterio1, (SELECT id FROM livelli WHERE criterio_id = @criterio1 AND punteggio = 10), 10, 'Ottimo lavoro, descrizione molto dettagliata'),
(@prova_id, @studente1, @criterio2, (SELECT id FROM livelli WHERE criterio_id = @criterio2 AND punteggio = 10), 10, 'Obiettivi perfettamente definiti'),
(@prova_id, @studente1, @criterio3, (SELECT id FROM livelli WHERE criterio_id = @criterio3 AND punteggio = 10), 10, 'Pianificazione impeccabile'),
(@prova_id, @studente1, @criterio4, (SELECT id FROM livelli WHERE criterio_id = @criterio4 AND punteggio = 10), 10, 'Documentazione completa e ben organizzata');

-- Valutazioni STUDENTE 2 (Maria Verdi) - Performance buona
INSERT INTO valutazioni (prova_id, studente_id, criterio_id, livello_id, punteggio, note) VALUES
(@prova_id, @studente2, @criterio1, (SELECT id FROM livelli WHERE criterio_id = @criterio1 AND punteggio = 8), 8, 'Buona descrizione'),
(@prova_id, @studente2, @criterio2, (SELECT id FROM livelli WHERE criterio_id = @criterio2 AND punteggio = 10), 10, 'Obiettivi ben definiti'),
(@prova_id, @studente2, @criterio3, (SELECT id FROM livelli WHERE criterio_id = @criterio3 AND punteggio = 8), 8, 'Buona pianificazione'),
(@prova_id, @studente2, @criterio4, (SELECT id FROM livelli WHERE criterio_id = @criterio4 AND punteggio = 8), 8, 'Documentazione completa');

-- Valutazioni STUDENTE 3 (Giuseppe Neri) - Performance media-alta
INSERT INTO valutazioni (prova_id, studente_id, criterio_id, livello_id, punteggio, note) VALUES
(@prova_id, @studente3, @criterio1, (SELECT id FROM livelli WHERE criterio_id = @criterio1 AND punteggio = 8), 8, 'Descrizione chiara'),
(@prova_id, @studente3, @criterio2, (SELECT id FROM livelli WHERE criterio_id = @criterio2 AND punteggio = 6), 6, 'Obiettivi da migliorare'),
(@prova_id, @studente3, @criterio3, (SELECT id FROM livelli WHERE criterio_id = @criterio3 AND punteggio = 8), 8, 'Pianificazione adeguata'),
(@prova_id, @studente3, @criterio4, (SELECT id FROM livelli WHERE criterio_id = @criterio4 AND punteggio = 6), 6, 'Documentazione sufficiente');

-- Valutazioni STUDENTE 4 (Anna Gialli) - Performance sufficiente
INSERT INTO valutazioni (prova_id, studente_id, criterio_id, livello_id, punteggio, note) VALUES
(@prova_id, @studente4, @criterio1, (SELECT id FROM livelli WHERE criterio_id = @criterio1 AND punteggio = 6), 6, 'Descrizione approssimativa'),
(@prova_id, @studente4, @criterio2, (SELECT id FROM livelli WHERE criterio_id = @criterio2 AND punteggio = 6), 6, 'Obiettivi poco specifici'),
(@prova_id, @studente4, @criterio3, (SELECT id FROM livelli WHERE criterio_id = @criterio3 AND punteggio = 6), 6, 'Pianificazione incompleta'),
(@prova_id, @studente4, @criterio4, (SELECT id FROM livelli WHERE criterio_id = @criterio4 AND punteggio = 8), 8, 'Buona documentazione');

-- Valutazioni STUDENTE 5 (Marco Blu) - Performance media
INSERT INTO valutazioni (prova_id, studente_id, criterio_id, livello_id, punteggio, note) VALUES
(@prova_id, @studente5, @criterio1, (SELECT id FROM livelli WHERE criterio_id = @criterio1 AND punteggio = 8), 8, 'Buona descrizione'),
(@prova_id, @studente5, @criterio2, (SELECT id FROM livelli WHERE criterio_id = @criterio2 AND punteggio = 8), 8, 'Obiettivi ben formulati'),
(@prova_id, @studente5, @criterio3, (SELECT id FROM livelli WHERE criterio_id = @criterio3 AND punteggio = 6), 6, 'Pianificazione da migliorare'),
(@prova_id, @studente5, @criterio4, (SELECT id FROM livelli WHERE criterio_id = @criterio4 AND punteggio = 6), 6, 'Documentazione sufficiente');

SELECT 'Dati di esempio inseriti con successo!' as Messaggio;
SELECT CONCAT('Griglia ID: ', @griglia_id) as Info;
SELECT CONCAT('Prova ID: ', @prova_id) as Info;
SELECT CONCAT('Studenti valutati: 5') as Info;
