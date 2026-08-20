@echo off
title Website Monitoring Cron
cd /d "%~dp0"
echo Starting cron loop (every 10 seconds)...
echo Keep this window open. Close it to stop.
echo.
"C:\xampp\php\php.exe" "%~dp0cron\run-cron.php"
pause
