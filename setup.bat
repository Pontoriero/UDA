@echo off
echo ======================================
echo    Setup Portale Valutazione UDA
echo ======================================
echo.

REM Verifica PHP
where php >nul 2>nul
if %errorlevel% neq 0 (
    echo [ERRORE] PHP non trovato. Assicurati che XAMPP sia installato.
    pause
    exit /b 1
)

REM Verifica MySQL
where mysql >nul 2>nul
if %errorlevel% neq 0 (
    echo [ERRORE] MySQL non trovato. Assicurati che XAMPP sia installato e avviato.
    pause
    exit /b 1
)

echo [OK] PHP trovato
php -v | findstr /C:"PHP"
echo.

REM Input credenziali
set DB_HOST=localhost
set DB_USER=root
set DB_PASS=
set DB_NAME=uda_portal

set /p DB_USER="Username MySQL (default: root): "
if "%DB_USER%"=="" set DB_USER=root

set /p DB_PASS="Password MySQL (vuota per default XAMPP): "

set /p DB_NAME="Nome database (default: uda_portal): "
if "%DB_NAME%"=="" set DB_NAME=uda_portal

echo.
echo Creazione database %DB_NAME%...

REM Crea database
mysql -h%DB_HOST% -u%DB_USER% -p%DB_PASS% -e "CREATE DATABASE IF NOT EXISTS %DB_NAME% CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if %errorlevel% neq 0 (
    echo [ERRORE] Impossibile creare il database
    pause
    exit /b 1
)

echo [OK] Database creato

REM Importa schema
echo.
echo Importazione schema...
mysql -h%DB_HOST% -u%DB_USER% -p%DB_PASS% %DB_NAME% < database\schema.sql
if %errorlevel% neq 0 (
    echo [ERRORE] Impossibile importare lo schema
    pause
    exit /b 1
)

echo [OK] Schema importato

REM Copia file di configurazione
echo.
echo Configurazione file config.php...
if not exist config\config.php (
    copy config\config.example.php config\config.php
)

echo [OK] Configurazione completata

echo.
echo ======================================
echo    Setup Completato!
echo ======================================
echo.
echo Credenziali di default:
echo   Admin:    username: admin     password: admin123
echo   Docente:  username: docente1  password: docente123
echo   Studente: username: studente1 password: studente123
echo.
echo Per avviare il portale:
echo   1. Avvia XAMPP (Apache e MySQL)
echo   2. Apri il browser su: http://localhost/UDA/public/login.php
echo.
echo OPPURE usa il server PHP built-in:
echo   php -S localhost:8000 -t public
echo   Poi vai su: http://localhost:8000/login.php
echo.
pause
