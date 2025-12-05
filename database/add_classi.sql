-- Aggiunta campo classe per gli studenti

USE uda_portal;

-- Aggiungi colonna classe alla tabella utenti
ALTER TABLE utenti ADD COLUMN classe VARCHAR(50) NULL AFTER email;

-- Aggiungi indice per migliorare performance ricerche per classe
ALTER TABLE utenti ADD INDEX idx_classe (classe);

-- Aggiorna studenti di esempio con classi
UPDATE utenti SET classe = '5A' WHERE username = 'studente1';
UPDATE utenti SET classe = '5A' WHERE username = 'studente2';
UPDATE utenti SET classe = '5A' WHERE username = 'studente3';
UPDATE utenti SET classe = '5B' WHERE username = 'studente4';
UPDATE utenti SET classe = '5B' WHERE username = 'studente5';

SELECT 'Campo classe aggiunto con successo!' as Messaggio;
SELECT username, nome, cognome, classe FROM utenti WHERE ruolo = 'studente' ORDER BY classe, cognome;
