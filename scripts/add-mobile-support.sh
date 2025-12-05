#!/bin/bash
# Script per aggiungere il supporto mobile a tutte le pagine

# Trova tutti i file PHP che hanno </body></html> ma non hanno footer-scripts.php
find /home/user/UDA/{admin,docente,studente} -name "*.php" -type f | while read file; do
    # Controlla se il file contiene </body></html> e NON contiene footer-scripts.php
    if grep -q "</body>" "$file" && ! grep -q "footer-scripts.php" "$file"; then
        echo "Aggiornamento: $file"

        # Aggiungi footer-scripts.php prima di </body>
        sed -i 's|</body>|    <?php include __DIR__ . '"'"'/../includes/footer-scripts.php'"'"'; ?>\n</body>|' "$file"
    fi
done

echo "Completato! Tutte le pagine ora supportano il menu mobile."
