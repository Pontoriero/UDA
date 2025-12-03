-- Database UDA Evaluation Portal
-- Schema SQL per la gestione delle valutazioni UDA

CREATE DATABASE IF NOT EXISTS uda_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE uda_portal;

-- Tabella utenti
CREATE TABLE IF NOT EXISTS utenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    cognome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    ruolo ENUM('admin', 'docente', 'studente') NOT NULL,
    attivo BOOLEAN DEFAULT TRUE,
    data_creazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ruolo (ruolo),
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella griglie di valutazione (create dall'admin)
CREATE TABLE IF NOT EXISTS griglie (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(200) NOT NULL,
    descrizione TEXT,
    anno_scolastico VARCHAR(20),
    materia VARCHAR(100),
    creato_da INT NOT NULL,
    data_creazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_modifica TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    attiva BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (creato_da) REFERENCES utenti(id) ON DELETE CASCADE,
    INDEX idx_anno (anno_scolastico),
    INDEX idx_materia (materia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella criteri di valutazione
CREATE TABLE IF NOT EXISTS criteri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    griglia_id INT NOT NULL,
    nome VARCHAR(200) NOT NULL,
    descrizione TEXT,
    peso DECIMAL(5,2) NOT NULL DEFAULT 1.00,
    ordine INT NOT NULL DEFAULT 0,
    FOREIGN KEY (griglia_id) REFERENCES griglie(id) ON DELETE CASCADE,
    INDEX idx_griglia (griglia_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella livelli di valutazione per ogni criterio
CREATE TABLE IF NOT EXISTS livelli (
    id INT AUTO_INCREMENT PRIMARY KEY,
    criterio_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    descrizione TEXT NOT NULL,
    punteggio DECIMAL(5,2) NOT NULL,
    ordine INT NOT NULL DEFAULT 0,
    FOREIGN KEY (criterio_id) REFERENCES criteri(id) ON DELETE CASCADE,
    INDEX idx_criterio (criterio_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella prove/UDA
CREATE TABLE IF NOT EXISTS prove (
    id INT AUTO_INCREMENT PRIMARY KEY,
    griglia_id INT NOT NULL,
    nome VARCHAR(200) NOT NULL,
    descrizione TEXT,
    data_prova DATE,
    docente_id INT NOT NULL,
    data_creazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (griglia_id) REFERENCES griglie(id) ON DELETE CASCADE,
    FOREIGN KEY (docente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    INDEX idx_griglia (griglia_id),
    INDEX idx_docente (docente_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella valutazioni degli studenti
CREATE TABLE IF NOT EXISTS valutazioni (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prova_id INT NOT NULL,
    studente_id INT NOT NULL,
    criterio_id INT NOT NULL,
    livello_id INT NOT NULL,
    punteggio DECIMAL(5,2) NOT NULL,
    note TEXT,
    data_valutazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_modifica TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (prova_id) REFERENCES prove(id) ON DELETE CASCADE,
    FOREIGN KEY (studente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (criterio_id) REFERENCES criteri(id) ON DELETE CASCADE,
    FOREIGN KEY (livello_id) REFERENCES livelli(id) ON DELETE CASCADE,
    UNIQUE KEY unique_valutazione (prova_id, studente_id, criterio_id),
    INDEX idx_prova (prova_id),
    INDEX idx_studente (studente_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserimento utente admin di default
-- Password: admin123 (da cambiare dopo il primo accesso)
INSERT INTO utenti (username, password, nome, cognome, email, ruolo)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Amministratore', 'Sistema', 'admin@uda-portal.local', 'admin');

-- Inserimento docente di esempio
-- Password: docente123
INSERT INTO utenti (username, password, nome, cognome, email, ruolo)
VALUES ('docente1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mario', 'Rossi', 'mario.rossi@scuola.it', 'docente');

-- Inserimento studente di esempio
-- Password: studente123
INSERT INTO utenti (username, password, nome, cognome, email, ruolo)
VALUES ('studente1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Luca', 'Bianchi', 'luca.bianchi@studenti.it', 'studente');
