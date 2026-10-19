@echo off
setlocal
cd /d "%~dp0"
for %%I in ("%~dp0.") do set "APP_FOLDER=%%~nxI"
echo Start Apache and MySQL in XAMPP Control Panel first.
echo This folder should be inside C:\xampp\htdocs.
start "" "http://localhost/%APP_FOLDER%/public/setup.php"
