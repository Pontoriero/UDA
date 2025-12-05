-- Aggiunta campo per gestire il primo accesso e cambio password obbligatorio

USE uda_portal;

-- Aggiungi colonna primo_accesso alla tabella utenti
-- Se TRUE (1), l'utente deve cambiare la password al prossimo login
ALTER TABLE utenti ADD COLUMN primo_accesso TINYINT(1) DEFAULT 1 AFTER attivo;

-- Imposta a 0 (false) per gli utenti esistenti che hanno già fatto l'accesso
UPDATE utenti SET primo_accesso = 0 WHERE id IN (1, 2, 3);

-- Gli studenti di esempio devono cambiare password al primo accesso
UPDATE utenti SET primo_accesso = 1 WHERE ruolo = 'studente';

SELECT 'Campo primo_accesso aggiunto con successo!' as Messaggio;
SELECT id, username, nome, cognome, ruolo, primo_accesso FROM utenti ORDER BY ruolo, cognome;
