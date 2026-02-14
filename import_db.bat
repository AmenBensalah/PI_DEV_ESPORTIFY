@echo off
echo Importation de la base de donnees esportify...
"C:\xampp\mysql\bin\mysql.exe" -u root esportify &lt; esportify_import.sql
if %errorlevel% == 0 (
    echo Importation reussie!
) else (
    echo Erreur lors de l'importation
)
pause
