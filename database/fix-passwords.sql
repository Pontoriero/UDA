-- Aggiornamento password per gli utenti di default
-- Password: admin123, docente123, studente123

USE uda_portal;

-- Admin: admin123
UPDATE utenti SET password = '$2y$12$H6bQsk2kO5XtsMSHaYJ9uO3n2.xAF3HoXYA9vR9uwnSdWl20ZIyBK' WHERE username = 'admin';

-- Docente: docente123
UPDATE utenti SET password = '$2y$12$fVHq5ju7rN/kvTLtx8cv6u0qBKlirVu.Y6ny8DfjgEYWzuu1qFJAW' WHERE username = 'docente1';

-- Studente: studente123
UPDATE utenti SET password = '$2y$12$Zl0BhihoGFhUHIqcyTuBBunRGIbSLO8m9Fmzo.T3hbDk3tJ7WwdK6' WHERE username = 'studente1';

SELECT 'Password aggiornate con successo!' as Messaggio;
