-- Creazione tabella UDA (Unità Di Apprendimento)
CREATE TABLE IF NOT EXISTS uda (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    descrizione TEXT,
    docente_id INT NOT NULL,
    anno_scolastico VARCHAR(20),
    data_inizio DATE,
    data_fine DATE,
    attivo TINYINT(1) DEFAULT 1,
    data_creazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (docente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    INDEX idx_docente (docente_id),
    INDEX idx_attivo (attivo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Aggiunge campo uda_id alla tabella prove
ALTER TABLE prove
ADD COLUMN uda_id INT NULL AFTER griglia_id,
ADD FOREIGN KEY (uda_id) REFERENCES uda(id) ON DELETE SET NULL;

-- Indice per migliorare le query
ALTER TABLE prove ADD INDEX idx_uda (uda_id);
