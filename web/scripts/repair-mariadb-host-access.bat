@echo off
setlocal
REM Repair XAMPP MariaDB ERROR 1130 caused by corrupt Aria privilege tables.
REM 1) Stop MySQL in XAMPP Control Panel first (or this script will force-stop it).
REM 2) Run this .bat as Administrator if needed.
REM 3) Start MySQL again from XAMPP when finished.

set MYSQL_DIR=C:\xampp\mysql\bin
set MYSQL_DATA=C:\xampp\mysql\data\mysql

echo.
echo === Stopping mysqld ===
taskkill /F /IM mysqld.exe >nul 2>&1
timeout /t 4 /nobreak >nul

echo.
echo === Repairing Aria tables in mysql system database ===
for %%F in ("%MYSQL_DATA%\*.MAI") do (
  echo aria_chk -r %%~nxF
  "%MYSQL_DIR%\aria_chk.exe" -r "%%F"
)

echo.
echo === Starting mysqld with --skip-grant-tables ===
start "mysqld-repair" /MIN "%MYSQL_DIR%\mysqld.exe" --defaults-file="%MYSQL_DIR%\my.ini" --skip-grant-tables --standalone
timeout /t 8 /nobreak >nul

echo.
echo === Recreating root@localhost and root@127.0.0.1 ===
"%MYSQL_DIR%\mysql.exe" -h 127.0.0.1 -P 3306 -u root --protocol=TCP -e "FLUSH PRIVILEGES; CREATE USER IF NOT EXISTS 'root'@'localhost' IDENTIFIED BY ''; CREATE USER IF NOT EXISTS 'root'@'127.0.0.1' IDENTIFIED BY ''; GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION; GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' WITH GRANT OPTION; FLUSH PRIVILEGES;"
if errorlevel 1 (
  echo Grant step failed. Aria repair may need a second pass.
)

echo.
echo === Restarting MySQL normally ===
taskkill /F /IM mysqld.exe >nul 2>&1
timeout /t 4 /nobreak >nul

echo Start MySQL from the XAMPP Control Panel, then run: php artisan migrate
echo.
pause
